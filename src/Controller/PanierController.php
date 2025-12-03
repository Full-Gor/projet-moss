<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur du panier utilisateur
 *
 * PRINCIPE :
 * - Le panier est stocké dans la session PHP ($_SESSION)
 * - Structure d'un article : { productId, name, price, quantity, image }
 * - On récupère toujours les infos depuis la BDD pour avoir les prix à jour
 */
class PanierController extends AbstractController
{
    // Afficher le panier de l'utilisateur
    #[Route('/panier', name: 'app_panier')]
    public function index(SessionInterface $session, ProduitRepository $produitRepo): Response
    {
        // Récupérer le panier depuis la session
        // Si vide, retourner un tableau vide []
        $panier = $session->get('panier', []);

        // Tableau qui contiendra les détails complets des produits
        $panierComplet = [];
        $total = 0;

        // Pour chaque article du panier
        foreach ($panier as $item) {
            // Récupérer le produit depuis la BDD par son ID
            $produit = $produitRepo->find($item['productId']);

            // Si le produit existe toujours en BDD
            if ($produit) {
                // Calculer le sous-total pour cet article
                $sousTotal = $produit->getPrix() * $item['quantity'];

                // Ajouter les détails complets
                $panierComplet[] = [
                    'productId' => $produit->getId(),
                    'name' => $produit->getNom(),
                    'price' => $produit->getPrix(),
                    'quantity' => $item['quantity'],
                    'image' => $produit->getImage(),
                    'total' => $sousTotal,
                    'stock' => $produit->getStock() // Stock disponible
                ];

                // Ajouter au total général
                $total += $sousTotal;
            }
        }

        // Afficher la page panier avec les données
        return $this->render('panier/index.html.twig', [
            'panier' => $panierComplet,
            'total' => $total
        ]);
    }

    // Ajouter un produit au panier
    // POST = données envoyées via formulaire
    #[Route('/panier/ajouter', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, SessionInterface $session, ProduitRepository $produitRepo): Response
    {
        // Récupérer l'ID du produit depuis le formulaire
        // (int) = conversion en nombre entier
        $productId = (int) $request->request->get('product_id');

        // Récupérer la quantité demandée (par défaut 1)
        $quantite = (int) $request->request->get('quantite', 1);

        // Récupérer le produit depuis la BDD
        $produit = $produitRepo->find($productId);

        // Vérifier que le produit existe
        if (!$produit) {
            $this->addFlash('error', 'Produit introuvable !');
            return $this->redirectToRoute('app_panier');
        }

        // Vérifier que le produit est actif (disponible à la vente)
        if (!$produit->isActif()) {
            $this->addFlash('error', 'Ce produit n\'est plus disponible.');
            return $this->redirectToRoute('app_produit');
        }

        // Récupérer le panier actuel
        $panier = $session->get('panier', []);

        // Chercher si le produit est déjà dans le panier
        $produitExiste = false;
        foreach ($panier as $key => $item) {
            if ($item['productId'] === $productId) {
                // Calculer la nouvelle quantité totale
                $nouvelleQuantite = $item['quantity'] + $quantite;

                // Vérifier qu'on ne dépasse pas le stock disponible
                if ($nouvelleQuantite > $produit->getStock()) {
                    $this->addFlash('error', "Stock insuffisant ! Seulement {$produit->getStock()} disponible(s).");
                    return $this->redirectToRoute('app_panier');
                }

                // Mettre à jour la quantité
                $panier[$key]['quantity'] = $nouvelleQuantite;
                $produitExiste = true;
                break;
            }
        }

        // Si le produit n'est pas dans le panier, l'ajouter
        if (!$produitExiste) {
            // Vérifier le stock avant d'ajouter
            if ($quantite > $produit->getStock()) {
                $this->addFlash('error', "Stock insuffisant ! Seulement {$produit->getStock()} disponible(s).");
                return $this->redirectToRoute('app_produit');
            }

            // Ajouter au panier
            $panier[] = [
                'productId' => $productId,
                'quantity' => $quantite
            ];
        }

        // Sauvegarder le panier en session
        $session->set('panier', $panier);

        // Message de confirmation
        $this->addFlash('success', "{$produit->getNom()} ajouté au panier !");

        return $this->redirectToRoute('app_panier');
    }

    // Supprimer un produit du panier
    #[Route('/panier/supprimer/{productId}', name: 'app_panier_supprimer')]
    public function supprimer(int $productId, SessionInterface $session): Response
    {
        // Récupérer le panier
        $panier = $session->get('panier', []);

        // Parcourir le panier et supprimer le produit correspondant
        foreach ($panier as $key => $item) {
            if ($item['productId'] === $productId) {
                // unset() = supprimer un élément d'un tableau
                unset($panier[$key]);
                break;
            }
        }

        // array_values() = réindexer le tableau (0, 1, 2... au lieu de 0, 2, 4...)
        $session->set('panier', array_values($panier));

        $this->addFlash('success', 'Produit supprimé du panier !');

        return $this->redirectToRoute('app_panier');
    }

    // Vider complètement le panier
    #[Route('/panier/vider', name: 'app_panier_vider')]
    public function vider(SessionInterface $session): Response
    {
        // remove() = supprimer une variable de la session
        $session->remove('panier');

        $this->addFlash('success', 'Panier vidé !');

        return $this->redirectToRoute('app_panier');
    }

    // Valider le paiement et enregistrer la commande
    #[Route('/panier/paiement-effectue', name: 'app_panier_paiement_effectue', methods: ['POST'])]
    public function paiementEffectue(
        SessionInterface $session,
        EntityManagerInterface $em,
        ProduitRepository $produitRepo
    ): Response {
        // Récupérer le panier et l'utilisateur
        $panier = $session->get('panier', []);
        $user = $session->get('user');

        // Vérifier que le panier n'est pas vide
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide !');
            return $this->redirectToRoute('app_panier');
        }

        try {
            // Pour chaque article du panier
            foreach ($panier as $item) {
                // Récupérer le produit depuis la BDD
                $produit = $produitRepo->find($item['productId']);

                if (!$produit) {
                    continue; // Passer au suivant si produit introuvable
                }

                // Vérifier qu'il y a assez de stock
                if ($item['quantity'] > $produit->getStock()) {
                    $this->addFlash('error', "Stock insuffisant pour {$produit->getNom()}");
                    return $this->redirectToRoute('app_panier');
                }

                // Créer une nouvelle commande
                $commande = new Commande();
                $commande->setNomClient($user ? $user['nom'] : 'Client anonyme');
                $commande->setProduit($produit->getNom());
                $commande->setQuantite($item['quantity']);
                $commande->setPrix($produit->getPrix());
                $commande->setDateCommande(new \DateTime());
                $commande->setCreatedAt(new \DateTimeImmutable());

                // Décrémenter le stock du produit
                $produit->decrementStock($item['quantity']);
                $produit->setUpdatedAt(new \DateTimeImmutable());

                // persist() = préparer l'enregistrement en BDD
                $em->persist($commande);
                $em->persist($produit);
            }

            // flush() = exécuter tous les enregistrements en BDD
            $em->flush();

            // Vider le panier après succès
            $session->remove('panier');

            $this->addFlash('success', 'Paiement effectué et commande enregistrée avec succès !');
        } catch (\Exception $e) {
            // En cas d'erreur
            $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }
}

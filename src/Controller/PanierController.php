<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        $total = 0;

        foreach ($panier as $item) {
            $total += $item['prix'] * $item['quantite'];
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total
        ]);
    }

    #[Route('/panier/ajouter', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, SessionInterface $session): Response
    {
        $couleur = $request->request->get('couleur', 'noir');
        $quantite = (int) $request->request->get('quantite', 1);
        $prix = 149;

        $panier = $session->get('panier', []);

        // Vérifier si le produit existe déjà dans le panier
        $produitExiste = false;
        foreach ($panier as $key => $item) {
            if ($item['produit'] === 'Moss Air' && $item['couleur'] === $couleur) {
                $panier[$key]['quantite'] += $quantite;
                $produitExiste = true;
                break;
            }
        }

        // Si le produit n'existe pas, l'ajouter
        if (!$produitExiste) {
            $panier[] = [
                'id' => uniqid(),
                'produit' => 'Moss Air',
                'couleur' => $couleur,
                'prix' => $prix,
                'quantite' => $quantite,
                'date_ajout' => new \DateTime()
            ];
        }

        $session->set('panier', $panier);

        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer')]
    public function supprimer(string $id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);

        foreach ($panier as $key => $item) {
            if ($item['id'] === $id) {
                unset($panier[$key]);
                break;
            }
        }

        $session->set('panier', array_values($panier));

        $this->addFlash('success', 'Produit supprimé du panier !');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/vider', name: 'app_panier_vider')]
    public function vider(SessionInterface $session): Response
    {
        $session->remove('panier');

        $this->addFlash('success', 'Panier vidé !');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/commander', name: 'app_panier_commander', methods: ['POST'])]
    public function commander(Request $request, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide !');
            return $this->redirectToRoute('app_panier');
        }

        $nomClient = $request->request->get('nom_client');

        if (empty($nomClient)) {
            $this->addFlash('error', 'Veuillez saisir votre nom !');
            return $this->redirectToRoute('app_panier');
        }

        // Ici vous pourriez sauvegarder les commandes en base de données
        // Pour l'instant, on vide juste le panier

        $session->remove('panier');

        $this->addFlash('success', 'Commande passée avec succès !');

        return $this->redirectToRoute('app_commande_historique');
    }

    #[Route('/panier/paiement-effectue', name: 'app_panier_paiement_effectue', methods: ['POST'])]
    public function paiementEffectue(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $panier = $session->get('panier', []);
        $user = $session->get('user');

        if (!empty($panier)) {
            try {
                // Sauvegarder chaque article du panier en base de données
                foreach ($panier as $item) {
                    $commande = new Commande();
                    $commande->setNomClient($user ? $user['nom'] : 'Client anonyme');
                    $commande->setProduit($item['produit']);
                    $commande->setQuantite($item['quantite']);
                    $commande->setPrix($item['prix']);
                    $commande->setCouleur($item['couleur']);
                    $commande->setDateCommande(new \DateTime());
                    $commande->setCreatedAt(new \DateTimeImmutable());

                    $em->persist($commande);
                }

                $em->flush();
                $this->addFlash('success', 'Paiement effectué et commande enregistrée avec succès !');
            } catch (\Exception $e) {
                // En cas d'erreur de base de données, on continue quand même
                $this->addFlash('success', 'Paiement effectué ! (Note: La commande n\'a pas pu être enregistrée en base de données)');
            }
        }

        // Vider le panier après le paiement (même en cas d'erreur DB)
        $session->remove('panier');

        return $this->redirectToRoute('app_panier');
    }
}

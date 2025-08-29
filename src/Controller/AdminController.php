<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(ProduitRepository $produitRepository, CommandeRepository $commandeRepository): Response
    {
        try {
            // Récupérer les statistiques
            $totalProduits = count($produitRepository->findAll());
            $produitsActifs = count($produitRepository->findActifs());
            $totalCommandes = count($commandeRepository->findAll());

            // Récupérer les produits pour le tableau
            $produits = $produitRepository->findAll();
        } catch (\Exception $e) {
            // En cas d'erreur de connexion, utiliser des valeurs par défaut
            $totalProduits = 0;
            $produitsActifs = 0;
            $totalCommandes = 0;
            $produits = [];

            $this->addFlash('warning', 'Erreur de connexion à la base de données. Affichage des données en mode hors ligne.');
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalProduits' => $totalProduits,
            'produitsActifs' => $produitsActifs,
            'totalCommandes' => $totalCommandes,
            'produits' => $produits
        ]);
    }

    #[Route('/produit/nouveau', name: 'app_admin_produit_new')]
    public function nouveauProduit(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $produit = new Produit();
            $produit->setNom($request->request->get('nom'));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((float) $request->request->get('prix'));
            $produit->setActif($request->request->get('actif') === 'on');

            // Gérer l'upload d'image
            $image = $request->files->get('image');
            if ($image && $image->isValid()) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

                if (in_array($image->getMimeType(), $allowedTypes)) {
                    $fileName = 'produit_' . time() . '.' . $image->getClientOriginalExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/produits/';

                    // Créer le dossier s'il n'existe pas
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $image->move($uploadDir, $fileName);
                    $produit->setImage($fileName);
                }
            }

            $em->persist($produit);
            $em->flush();

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/produit_form.html.twig', [
            'produit' => null,
            'action' => 'Ajouter'
        ]);
    }

    #[Route('/produit/{id}/modifier', name: 'app_admin_produit_edit')]
    public function modifierProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $produit->setNom($request->request->get('nom'));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((float) $request->request->get('prix'));
            $produit->setActif($request->request->get('actif') === 'on');
            $produit->setUpdatedAt(new \DateTimeImmutable());

            // Gérer l'upload d'image
            $image = $request->files->get('image');
            if ($image && $image->isValid()) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

                if (in_array($image->getMimeType(), $allowedTypes)) {
                    $fileName = 'produit_' . $produit->getId() . '_' . time() . '.' . $image->getClientOriginalExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/produits/';

                    // Supprimer l'ancienne image si elle existe
                    if ($produit->getImage() && file_exists($uploadDir . $produit->getImage())) {
                        unlink($uploadDir . $produit->getImage());
                    }

                    $image->move($uploadDir, $fileName);
                    $produit->setImage($fileName);
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/produit_form.html.twig', [
            'produit' => $produit,
            'action' => 'Modifier'
        ]);
    }

    #[Route('/produit/{id}/supprimer', name: 'app_admin_produit_delete', methods: ['POST'])]
    public function supprimerProduit(Produit $produit, EntityManagerInterface $em): Response
    {
        // Supprimer l'image si elle existe
        if ($produit->getImage()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/produits/' . $produit->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $em->remove($produit);
        $em->flush();

        return $this->redirectToRoute('app_admin_dashboard');
    }
}

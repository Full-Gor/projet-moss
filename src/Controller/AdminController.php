<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // Vérifie si l'utilisateur connecté est admin
    private function checkAdmin(SessionInterface $session): bool
    {
        $user = $session->get('user');
        // Retourne true si l'utilisateur est connecté ET a le rôle admin
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function index(ProduitRepository $produitRepository, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé : vous devez être administrateur');
            return $this->redirectToRoute('app_home');
        }

        $produits = $produitRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/produit/new', name: 'app_admin_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $produit = new Produit();
            $produit->setNom($request->request->get('nom'));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((float) $request->request->get('prix'));
            $produit->setActif($request->request->get('actif') === 'on');
            $produit->setUpdatedAt(new \DateTimeImmutable());

            // Gestion de l'image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('produits_directory'),
                        $newFilename
                    );
                    $produit->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/produit_form.html.twig', [
            'produit' => null,
            'action' => 'Créer'
        ]);
    }

    #[Route('/produit/{id}/edit', name: 'app_admin_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager, SluggerInterface $slugger, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $produit->setNom($request->request->get('nom'));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((float) $request->request->get('prix'));
            $produit->setActif($request->request->get('actif') === 'on');
            $produit->setUpdatedAt(new \DateTimeImmutable());

            // Gestion de l'image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('produits_directory'),
                        $newFilename
                    );
                    $produit->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/produit_form.html.twig', [
            'produit' => $produit,
            'action' => 'Modifier'
        ]);
    }

    #[Route('/produit/{id}/delete', name: 'app_admin_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    // ==================== GESTION DES UTILISATEURS ====================

    // Afficher la liste des utilisateurs
    #[Route('/users', name: 'app_admin_users')]
    public function users(Connection $connection, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer tous les utilisateurs (sans le mot de passe)
        $result = $connection->executeQuery('SELECT id, prenom, nom, email, role, created_at FROM user ORDER BY created_at DESC');
        $users = $result->fetchAllAssociative();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    // Modifier le rôle d'un utilisateur
    #[Route('/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function changeRole(int $id, Request $request, Connection $connection, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer le nouveau rôle depuis le formulaire
        $newRole = $request->request->get('role');

        // Vérifier que le rôle est valide (user ou admin)
        if (!in_array($newRole, ['user', 'admin'])) {
            $this->addFlash('error', 'Rôle invalide');
            return $this->redirectToRoute('app_admin_users');
        }

        // Mettre à jour le rôle dans la base de données
        $connection->executeStatement('UPDATE user SET role = ? WHERE id = ?', [$newRole, $id]);

        $this->addFlash('success', 'Rôle modifié avec succès !');
        return $this->redirectToRoute('app_admin_users');
    }

    // Supprimer un utilisateur
    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, Connection $connection, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est admin
        if (!$this->checkAdmin($session)) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('app_home');
        }

        // Empêcher l'admin de se supprimer lui-même
        $currentUser = $session->get('user');
        if ($currentUser['id'] == $id) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même');
            return $this->redirectToRoute('app_admin_users');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete_user' . $id, $request->request->get('_token'))) {
            // Supprimer l'utilisateur de la base de données
            $connection->executeStatement('DELETE FROM user WHERE id = ?', [$id]);
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}

<?php
/**
 * ============================================
 * CONTROLLER ADMIN - Dashboard administrateur
 * ============================================
 *
 * Ce controller gère toutes les pages d'administration :
 * - Dashboard avec liste des produits
 * - Création/modification/suppression de produits
 *
 * SÉCURITÉ : Toutes les routes sont protégées !
 * Seuls les utilisateurs avec role='admin' peuvent accéder.
 */

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * #[Route('/admin')] = préfixe pour toutes les routes de ce controller
 * Toutes les méthodes auront une URL qui commence par /admin
 */
#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * RequestStack = service Symfony pour accéder à la session
     * On le stocke dans une propriété pour l'utiliser dans toutes les méthodes
     */
    private RequestStack $requestStack;

    /**
     * Constructeur = méthode appelée automatiquement à la création du controller
     *
     * L'injection de dépendance : Symfony fournit automatiquement RequestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Vérifie si l'utilisateur connecté est administrateur
     *
     * @return bool true si admin, false sinon
     *
     * PRINCIPE :
     * 1. On récupère les données de l'utilisateur depuis la session
     * 2. On vérifie si le rôle est 'admin'
     * 3. Si non, on refuse l'accès
     */
    private function isAdmin(): bool
    {
        // getSession() = récupère la session PHP
        $session = $this->requestStack->getSession();

        // Récupère les données de l'utilisateur depuis la session
        // Si pas connecté, $user sera null
        $user = $session->get('user');

        // Vérifie si :
        // 1. L'utilisateur est connecté ($user existe)
        // 2. L'utilisateur a le rôle 'admin'
        // Le ?? 'user' signifie : si 'role' n'existe pas, utiliser 'user' par défaut
        return $user && ($user['role'] ?? 'user') === 'admin';
    }

    /**
     * Redirige vers l'accueil si l'utilisateur n'est pas admin
     *
     * @return Response|null null si admin, Response de redirection sinon
     */
    private function checkAdmin(): ?Response
    {
        // Si pas admin, on redirige avec un message d'erreur
        if (!$this->isAdmin()) {
            // addFlash() = ajoute un message temporaire affiché une seule fois
            $this->addFlash('error', 'Accès refusé. Vous devez être administrateur.');

            // redirectToRoute() = redirige vers une autre page
            return $this->redirectToRoute('app_home');
        }

        // Si admin, on retourne null (pas de redirection)
        return null;
    }

    /**
     * Dashboard admin - Liste tous les produits
     *
     * #[Route('/', name: 'app_admin_dashboard')]
     * - '/' = URL relative (donc /admin/ car préfixe /admin)
     * - name = nom de la route pour les liens Twig
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(ProduitRepository $produitRepository): Response
    {
        // PROTECTION : Vérifie si l'utilisateur est admin
        $redirect = $this->checkAdmin();
        if ($redirect) {
            return $redirect; // Redirige si pas admin
        }

        // Si admin OK, on affiche le dashboard
        $produits = $produitRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'produits' => $produits,
        ]);
    }

    /**
     * Créer un nouveau produit
     *
     * methods: ['GET', 'POST'] = accepte GET (afficher le formulaire) et POST (soumettre)
     */
    #[Route('/produit/new', name: 'app_admin_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // PROTECTION : Vérifie si l'utilisateur est admin
        $redirect = $this->checkAdmin();
        if ($redirect) {
            return $redirect;
        }

        // Si la requête est POST, c'est une soumission de formulaire
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

            // persist() = prépare l'insertion en BDD
            // flush() = exécute réellement la requête SQL
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Si GET, on affiche le formulaire vide
        return $this->render('admin/produit_form.html.twig', [
            'produit' => null,
            'action' => 'Créer'
        ]);
    }

    /**
     * Modifier un produit existant
     *
     * {id} = paramètre dynamique dans l'URL (ex: /admin/produit/5/edit)
     * Symfony récupère automatiquement le Produit avec cet ID
     */
    #[Route('/produit/{id}/edit', name: 'app_admin_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // PROTECTION : Vérifie si l'utilisateur est admin
        $redirect = $this->checkAdmin();
        if ($redirect) {
            return $redirect;
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

            // Pas besoin de persist() pour une modification, flush() suffit
            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/produit_form.html.twig', [
            'produit' => $produit,
            'action' => 'Modifier'
        ]);
    }

    /**
     * Supprimer un produit
     *
     * methods: ['POST'] = uniquement POST (pour éviter la suppression par simple lien)
     * Protection CSRF = vérifie un token pour éviter les attaques
     */
    #[Route('/produit/{id}/delete', name: 'app_admin_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        // PROTECTION : Vérifie si l'utilisateur est admin
        $redirect = $this->checkAdmin();
        if ($redirect) {
            return $redirect;
        }

        /**
         * Protection CSRF (Cross-Site Request Forgery)
         *
         * Vérifie que la requête vient bien de notre site
         * Le token est généré dans le formulaire Twig
         */
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('_token'))) {
            // remove() = prépare la suppression
            // flush() = exécute la requête SQL DELETE
            $entityManager->remove($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}

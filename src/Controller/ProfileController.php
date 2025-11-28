<?php
/**
 * ============================================
 * CONTROLLER PROFILE - Page profil utilisateur
 * ============================================
 *
 * Gère l'affichage et la modification du profil utilisateur.
 *
 * SÉCURITÉ : Toutes les routes sont protégées !
 * Seuls les utilisateurs CONNECTÉS peuvent accéder à leur profil.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CommandeRepository;

class ProfileController extends AbstractController
{
    /**
     * Affiche la page de profil de l'utilisateur connecté
     *
     * PROTECTION : Si l'utilisateur n'est pas connecté,
     * il est redirigé vers la page de connexion
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(CommandeRepository $commandeRepository, SessionInterface $session): Response
    {
        /**
         * Récupère les données de l'utilisateur depuis la session
         * $session->get('user') retourne null si pas connecté
         */
        $user = $session->get('user');

        /**
         * PROTECTION DE LA ROUTE
         *
         * if (!$user) = si $user est null/false/vide
         * On redirige vers la page de connexion
         */
        if (!$user) {
            // Message flash pour informer l'utilisateur
            $this->addFlash('error', 'Vous devez être connecté pour accéder à votre profil.');
            return $this->redirectToRoute('app_connexion');
        }

        // Récupérer les commandes de l'utilisateur connecté
        try {
            $commandes = $commandeRepository->findBy(['nom_client' => $user['nom']], ['date_commande' => 'DESC']);
        } catch (\Exception $e) {
            $commandes = [];
        }

        // Informations utilisateur (avec valeurs par défaut si pas définies)
        $userInfo = [
            'nom' => $user['nom'] ?? 'Non défini',
            'email' => $user['email'] ?? 'Non défini',
            'telephone' => $user['telephone'] ?? 'Non défini',
            'adresse' => $user['adresse'] ?? 'Non définie',
            'photo' => $user['photo'] ?? 'default-avatar.jpg'
        ];

        return $this->render('profile/index.html.twig', [
            'userInfo' => $userInfo,
            'commandes' => $commandes
        ]);
    }

    /**
     * Modifier les informations du profil
     *
     * methods: ['POST'] = uniquement accessible en POST (formulaire)
     * Empêche la modification par simple visite de l'URL
     */
    #[Route('/profile/modifier', name: 'app_profile_modifier', methods: ['POST'])]
    public function modifier(Request $request, SessionInterface $session): Response
    {
        $user = $session->get('user');

        /**
         * PROTECTION DE LA ROUTE
         * Même principe que pour la page profil
         */
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_connexion');
        }

        // Récupérer les données du formulaire
        $nom = $request->request->get('nom');
        $email = $request->request->get('email');
        $telephone = $request->request->get('telephone');
        $adresse = $request->request->get('adresse');

        // Gérer l'upload de photo
        $photo = $request->files->get('photo');
        $photoPath = $user['photo'] ?? 'default-avatar.jpg'; // Garder l'ancienne photo par défaut

        if ($photo && $photo->isValid()) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

            if (in_array($photo->getMimeType(), $allowedTypes)) {
                $fileName = 'profile_' . $user['id'] . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/profiles/';

                // Créer le dossier s'il n'existe pas
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Supprimer l'ancienne photo si elle existe et n'est pas la photo par défaut
                if (isset($user['photo']) && $user['photo'] !== 'default-avatar.jpg' && file_exists($this->getParameter('kernel.project_dir') . '/public/images/profiles/' . $user['photo'])) {
                    unlink($this->getParameter('kernel.project_dir') . '/public/images/profiles/' . $user['photo']);
                }

                // Déplacer la nouvelle photo
                $photo->move($uploadDir, $fileName);
                $photoPath = $fileName;
            }
        }

        // Mettre à jour les informations utilisateur
        $user['nom'] = $nom;
        $user['email'] = $email;
        $user['telephone'] = $telephone;
        $user['adresse'] = $adresse;
        $user['photo'] = $photoPath;

        $session->set('user', $user);

        return $this->redirectToRoute('app_profile');
    }
}

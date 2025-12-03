<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(Request $request, SessionInterface $session, Connection $connection): Response
    {
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'login') {
                // Traitement de la connexion
                $identifier = $request->request->get('prenom'); // Peut être un prénom ou un email
                $password = $request->request->get('password'); // Récupérer le mot de passe

                try {
                    // Chercher l'utilisateur par prénom OU par email
                    // IMPORTANT : permet aux admins de se connecter avec leur email
                    $result = $connection->executeQuery(
                        'SELECT * FROM user WHERE prenom = ? OR email = ?',
                        [$identifier, $identifier]
                    );
                    $user = $result->fetchAssociative();

                    // Vérifier si l'utilisateur existe et si le mot de passe est correct
                    if ($user && password_verify($password, $user['password'])) {
                        // Enregistrer l'utilisateur en session (avec son rôle)
                        $session->set('user', [
                            'id' => $user['id'],
                            'prenom' => $user['prenom'],
                            'nom' => $user['nom'],
                            'email' => $user['email'],
                            'role' => $user['role'] ?? 'user', // Récupérer le rôle (user par défaut)
                            'connecte' => true
                        ]);

                        /**
                         * Redirection intelligente selon le rôle
                         * - Si admin : redirige vers le dashboard admin
                         * - Si user : redirige vers l'accueil
                         */
                        $role = $user['role'] ?? 'user';
                        if ($role === 'admin') {
                            return $this->redirectToRoute('app_admin_dashboard');
                        }

                        return $this->redirectToRoute('app_home');
                    } else {
                        $error = 'Identifiant ou mot de passe incorrect';
                    }
                } catch (\Exception $e) {
                    $error = 'Erreur de connexion à la base de données';
                }
            } elseif ($action === 'register') {
                // Traitement de l'inscription
                $prenom = $request->request->get('prenom'); // Récupérer le prénom
                $nom = $request->request->get('nom'); // Récupérer le nom
                $email = $request->request->get('email'); // Récupérer l'email
                $password = $request->request->get('password'); // Récupérer le mot de passe
                $confirmPassword = $request->request->get('confirm_password'); // Récupérer la confirmation

                // Vérifier que tous les champs sont remplis
                if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
                    $error = 'Tous les champs sont obligatoires';
                }
                // Vérifier que les mots de passe correspondent
                elseif ($password !== $confirmPassword) {
                    $error = 'Les mots de passe ne correspondent pas';
                }
                // Vérifier la longueur du mot de passe
                elseif (strlen($password) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères';
                }
                else {
                    try {
                        // Vérifier si l'email existe déjà
                        $result = $connection->executeQuery('SELECT id FROM user WHERE email = ?', [$email]);
                        if ($result->fetchAssociative()) {
                            $error = 'Cet email est déjà utilisé';
                        } else {
                            // Crypter le mot de passe
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                            // Créer le nouvel utilisateur avec prénom, nom et rôle 'user' par défaut
                            $connection->executeStatement(
                                'INSERT INTO user (prenom, nom, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                                [$prenom, $nom, $email, $hashedPassword, 'user']
                            );

                            // Récupérer l'ID du nouvel utilisateur
                            $userId = $connection->lastInsertId();

                            // Enregistrer l'utilisateur en session (avec rôle user)
                            $session->set('user', [
                                'id' => $userId,
                                'prenom' => $prenom,
                                'nom' => $nom,
                                'email' => $email,
                                'role' => 'user', // Rôle user par défaut
                                'connecte' => true
                            ]);

                            $success = 'Inscription réussie !';
                            return $this->redirectToRoute('app_home');
                        }
                    } catch (\Exception $e) {
                        $error = 'Erreur lors de l\'inscription';
                    }
                }
            }
        }

        return $this->render('auth/connexion.html.twig', [
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(SessionInterface $session): Response
    {
        $session->remove('user');
        return $this->redirectToRoute('app_home');
    }
}

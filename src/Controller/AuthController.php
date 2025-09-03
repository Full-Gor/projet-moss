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
                // Traitement de la connexion avec la base de données
                $email = $request->request->get('email');
                $password = $request->request->get('password');

                try {
                    $result = $connection->executeQuery('SELECT * FROM user WHERE email = ?', [$email]);
                    $user = $result->fetchAssociative();

                    if ($user && password_verify($password, $user['password'])) {
                        $session->set('user', [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'nom' => $user['nom'],
                            'connecte' => true
                        ]);
                        return $this->redirectToRoute('app_home');
                    } else {
                        $error = 'Email ou mot de passe incorrect';
                    }
                } catch (\Exception $e) {
                    $error = 'Erreur de connexion à la base de données';
                }
            } elseif ($action === 'register') {
                // Traitement de l'inscription avec la base de données
                $nom = $request->request->get('nom');
                $email = $request->request->get('email');
                $password = $request->request->get('password');
                $confirmPassword = $request->request->get('confirm_password');

                if (empty($nom) || empty($email) || empty($password)) {
                    $error = 'Tous les champs sont obligatoires';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Les mots de passe ne correspondent pas';
                } elseif (strlen($password) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères';
                } else {
                    try {
                        // Vérifier si l'email existe déjà
                        $result = $connection->executeQuery('SELECT id FROM user WHERE email = ?', [$email]);
                        if ($result->fetchAssociative()) {
                            $error = 'Cet email est déjà utilisé';
                        } else {
                            // Créer le nouvel utilisateur
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $connection->executeStatement('INSERT INTO user (nom, email, password, created_at) VALUES (?, ?, ?, NOW())', [$nom, $email, $hashedPassword]);

                            $userId = $connection->lastInsertId();
                            $session->set('user', [
                                'id' => $userId,
                                'email' => $email,
                                'nom' => $nom,
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

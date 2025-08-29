<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(Request $request, SessionInterface $session): Response
    {
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'login') {
                // Traitement de la connexion
                $email = $request->request->get('email');
                $password = $request->request->get('password');

                // Simulation de vérification (à remplacer par une vraie authentification)
                if ($email === 'test@test.com' && $password === 'password') {
                    $session->set('user', [
                        'id' => 1,
                        'email' => $email,
                        'nom' => 'Jean Dupont',
                        'connecte' => true
                    ]);
                    return $this->redirectToRoute('app_home');
                } else {
                    $error = 'Email ou mot de passe incorrect';
                }
            } elseif ($action === 'register') {
                // Traitement de l'inscription
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
                    // Simulation d'inscription réussie
                    $session->set('user', [
                        'id' => 2,
                        'email' => $email,
                        'nom' => $nom,
                        'connecte' => true
                    ]);
                    return $this->redirectToRoute('app_home');
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

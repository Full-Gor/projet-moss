<?php

namespace App\Controller;

use App\Form\LoginType;
use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(Request $request, Connection $connection): Response
    {
        $error = null;

        // Créer les 2 formulaires Symfony
        $loginForm = $this->createForm(LoginType::class);
        $registerForm = $this->createForm(RegistrationType::class);

        // On détermine quel formulaire a été soumis grâce au bouton submit
        $action = $request->request->all();

        // --- CONNEXION ---
        if (isset($action['login'])) {
            $loginForm->handleRequest($request);

            if ($loginForm->isSubmitted() && $loginForm->isValid()) {
                $data = $loginForm->getData();
                $identifier = $data['prenom'];
                $password = $data['password'];

                // Chercher par prénom OU email
                $result = $connection->executeQuery(
                    'SELECT * FROM user WHERE prenom = ? OR email = ?',
                    [$identifier, $identifier]
                );
                $user = $result->fetchAssociative();

                if ($user && password_verify($password, $user['password'])) {
                    $request->getSession()->set('user', [
                        'id' => $user['id'],
                        'prenom' => $user['prenom'],
                        'nom' => $user['nom'],
                        'email' => $user['email'],
                        'role' => $user['role'] ?? 'user',
                        'connecte' => true
                    ]);

                    if (($user['role'] ?? 'user') === 'admin') {
                        return $this->redirectToRoute('app_admin_dashboard');
                    }
                    return $this->redirectToRoute('app_home');
                }

                $error = 'Identifiant ou mot de passe incorrect';
            }
        }

        // --- INSCRIPTION ---
        if (isset($action['registration'])) {
            $registerForm->handleRequest($request);

            if ($registerForm->isSubmitted() && $registerForm->isValid()) {
                $data = $registerForm->getData();

                // Vérifier si l'email existe déjà
                $result = $connection->executeQuery(
                    'SELECT id FROM user WHERE email = ?',
                    [$data['email']]
                );

                if ($result->fetchAssociative()) {
                    $error = 'Cet email est déjà utilisé';
                } else {
                    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                    $connection->executeStatement(
                        'INSERT INTO user (prenom, nom, email, password, role, actif, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())',
                        [$data['prenom'], $data['nom'], $data['email'], $hash, 'user']
                    );

                    $request->getSession()->set('user', [
                        'id' => $connection->lastInsertId(),
                        'prenom' => $data['prenom'],
                        'nom' => $data['nom'],
                        'email' => $data['email'],
                        'role' => 'user',
                        'connecte' => true
                    ]);

                    return $this->redirectToRoute('app_home');
                }
            }
        }

        return $this->render('auth/connexion.html.twig', [
            'loginForm' => $loginForm->createView(),
            'registerForm' => $registerForm->createView(),
            'error' => $error
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(Request $request): Response
    {
        $request->getSession()->remove('user');
        return $this->redirectToRoute('app_home');
    }
}

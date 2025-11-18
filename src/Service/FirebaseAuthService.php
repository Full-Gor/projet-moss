<?php

namespace App\Service;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Exception\AuthException;

class FirebaseAuthService
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Créer un nouvel utilisateur Firebase
     */
    public function createUser(string $email, string $password, ?string $displayName = null): array
    {
        try {
            $userProperties = [
                'email' => $email,
                'emailVerified' => false,
                'password' => $password,
                'disabled' => false,
            ];

            if ($displayName) {
                $userProperties['displayName'] = $displayName;
            }

            $createdUser = $this->auth->createUser($userProperties);

            return [
                'success' => true,
                'uid' => $createdUser->uid,
                'email' => $createdUser->email,
                'displayName' => $createdUser->displayName,
            ];
        } catch (EmailExists $e) {
            return [
                'success' => false,
                'error' => 'Cette adresse email existe déjà',
                'code' => 'email_exists'
            ];
        } catch (AuthException $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage(),
                'code' => 'auth_error'
            ];
        }
    }

    /**
     * Obtenir un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        try {
            $user = $this->auth->getUserByEmail($email);

            return [
                'uid' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName,
                'photoUrl' => $user->photoUrl,
                'emailVerified' => $user->emailVerified,
                'disabled' => $user->disabled,
                'metadata' => [
                    'createdAt' => $user->metadata->createdAt,
                    'lastLoginAt' => $user->metadata->lastLoginAt,
                ]
            ];
        } catch (UserNotFound $e) {
            return null;
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Obtenir un utilisateur par son UID
     */
    public function getUserByUid(string $uid): ?array
    {
        try {
            $user = $this->auth->getUser($uid);

            return [
                'uid' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName,
                'photoUrl' => $user->photoUrl,
                'emailVerified' => $user->emailVerified,
                'disabled' => $user->disabled,
            ];
        } catch (UserNotFound $e) {
            return null;
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(string $uid, array $properties): bool
    {
        try {
            $this->auth->updateUser($uid, $properties);
            return true;
        } catch (AuthException $e) {
            return false;
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(string $uid): bool
    {
        try {
            $this->auth->deleteUser($uid);
            return true;
        } catch (AuthException $e) {
            return false;
        }
    }

    /**
     * Vérifier un token ID Firebase
     */
    public function verifyIdToken(string $idToken): ?array
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);

            return [
                'uid' => $verifiedIdToken->claims()->get('sub'),
                'email' => $verifiedIdToken->claims()->get('email'),
                'emailVerified' => $verifiedIdToken->claims()->get('email_verified'),
            ];
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Créer un lien de connexion par email (passwordless)
     */
    public function createEmailSignInLink(string $email, string $redirectUrl): ?string
    {
        try {
            return $this->auth->getEmailActionLink('EMAIL_SIGNIN', $email, [
                'url' => $redirectUrl,
            ]);
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Créer un lien de réinitialisation de mot de passe
     */
    public function createPasswordResetLink(string $email): ?string
    {
        try {
            return $this->auth->getPasswordResetLink($email);
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Créer un custom token pour l'authentification
     */
    public function createCustomToken(string $uid, array $claims = []): ?string
    {
        try {
            return $this->auth->createCustomToken($uid, $claims)->toString();
        } catch (AuthException $e) {
            return null;
        }
    }

    /**
     * Définir des custom claims pour un utilisateur
     */
    public function setCustomUserClaims(string $uid, array $claims): bool
    {
        try {
            $this->auth->setCustomUserClaims($uid, $claims);
            return true;
        } catch (AuthException $e) {
            return false;
        }
    }

    /**
     * Désactiver un utilisateur
     */
    public function disableUser(string $uid): bool
    {
        return $this->updateUser($uid, ['disabled' => true]);
    }

    /**
     * Activer un utilisateur
     */
    public function enableUser(string $uid): bool
    {
        return $this->updateUser($uid, ['disabled' => false]);
    }

    /**
     * Lister tous les utilisateurs (avec pagination)
     */
    public function listUsers(int $maxResults = 1000): array
    {
        try {
            $users = [];
            foreach ($this->auth->listUsers($maxResults) as $user) {
                $users[] = [
                    'uid' => $user->uid,
                    'email' => $user->email,
                    'displayName' => $user->displayName,
                    'disabled' => $user->disabled,
                ];
            }
            return $users;
        } catch (AuthException $e) {
            return [];
        }
    }
}

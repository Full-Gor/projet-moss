<?php

namespace App\Controller;

use App\Service\FirebaseAuthService;
use App\Service\FirebaseStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/firebase', name: 'firebase_')]
class FirebaseExampleController extends AbstractController
{
    /**
     * Exemple: Créer un utilisateur Firebase
     */
    #[Route('/auth/create', name: 'auth_create', methods: ['POST'])]
    public function createUser(Request $request, FirebaseAuthService $authService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $displayName = $data['displayName'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email et password requis'], 400);
        }

        $result = $authService->createUser($email, $password, $displayName);

        return $this->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Exemple: Obtenir un utilisateur par email
     */
    #[Route('/auth/user/{email}', name: 'auth_get_user', methods: ['GET'])]
    public function getFirebaseUser(string $email, FirebaseAuthService $authService): JsonResponse
    {
        $user = $authService->getUserByEmail($email);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        return $this->json($user);
    }

    /**
     * Exemple: Vérifier un token ID
     */
    #[Route('/auth/verify', name: 'auth_verify', methods: ['POST'])]
    public function verifyToken(Request $request, FirebaseAuthService $authService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return $this->json(['error' => 'Token requis'], 400);
        }

        $result = $authService->verifyIdToken($idToken);

        if (!$result) {
            return $this->json(['error' => 'Token invalide'], 401);
        }

        return $this->json($result);
    }

    /**
     * Exemple: Upload un fichier
     */
    #[Route('/storage/upload', name: 'storage_upload', methods: ['POST'])]
    public function uploadFile(Request $request, FirebaseStorageService $storageService): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'Fichier requis'], 400);
        }

        $result = $storageService->uploadUploadedFile($file, 'uploads');

        return $this->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Exemple: Lister les fichiers
     */
    #[Route('/storage/list', name: 'storage_list', methods: ['GET'])]
    public function listFiles(Request $request, FirebaseStorageService $storageService): JsonResponse
    {
        $prefix = $request->query->get('prefix', '');
        $files = $storageService->listFiles($prefix);

        return $this->json([
            'files' => $files,
            'count' => count($files)
        ]);
    }

    /**
     * Exemple: Supprimer un fichier
     */
    #[Route('/storage/delete', name: 'storage_delete', methods: ['DELETE'])]
    public function deleteFile(Request $request, FirebaseStorageService $storageService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remotePath = $data['path'] ?? null;

        if (!$remotePath) {
            return $this->json(['error' => 'Chemin du fichier requis'], 400);
        }

        $success = $storageService->deleteFile($remotePath);

        return $this->json([
            'success' => $success,
            'message' => $success ? 'Fichier supprimé' : 'Erreur lors de la suppression'
        ], $success ? 200 : 400);
    }

    /**
     * Exemple: Obtenir une URL signée
     */
    #[Route('/storage/signed-url', name: 'storage_signed_url', methods: ['POST'])]
    public function getSignedUrl(Request $request, FirebaseStorageService $storageService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remotePath = $data['path'] ?? null;
        $expirationMinutes = $data['expiration'] ?? 60;

        if (!$remotePath) {
            return $this->json(['error' => 'Chemin du fichier requis'], 400);
        }

        $url = $storageService->getSignedUrl($remotePath, $expirationMinutes);

        if (!$url) {
            return $this->json(['error' => 'Impossible de générer l\'URL'], 400);
        }

        return $this->json([
            'url' => $url,
            'expiresIn' => $expirationMinutes . ' minutes'
        ]);
    }

    /**
     * Exemple: Créer un custom token pour l'authentification
     */
    #[Route('/auth/custom-token/{uid}', name: 'auth_custom_token', methods: ['POST'])]
    public function createCustomToken(string $uid, FirebaseAuthService $authService): JsonResponse
    {
        $token = $authService->createCustomToken($uid, [
            'role' => 'admin', // Exemple de claims personnalisés
            'premium' => true,
        ]);

        if (!$token) {
            return $this->json(['error' => 'Impossible de créer le token'], 400);
        }

        return $this->json([
            'customToken' => $token
        ]);
    }

    /**
     * Exemple: Lister tous les utilisateurs
     */
    #[Route('/auth/users', name: 'auth_list_users', methods: ['GET'])]
    public function listUsers(FirebaseAuthService $authService): JsonResponse
    {
        $users = $authService->listUsers();

        return $this->json([
            'users' => $users,
            'count' => count($users)
        ]);
    }
}

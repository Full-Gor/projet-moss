<?php

namespace App\Service;

use Kreait\Firebase\Contract\Storage;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;

class FirebaseStorageService
{
    private Storage $storage;
    private Bucket $bucket;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
        $this->bucket = $storage->getBucket();
    }

    /**
     * Upload un fichier vers Firebase Storage
     *
     * @param string $localFilePath Chemin du fichier local
     * @param string $destinationPath Chemin de destination dans Firebase Storage
     * @param array $options Options supplémentaires (metadata, contentType, etc.)
     * @return array
     */
    public function uploadFile(string $localFilePath, string $destinationPath, array $options = []): array
    {
        try {
            if (!file_exists($localFilePath)) {
                return [
                    'success' => false,
                    'error' => 'Le fichier local n\'existe pas'
                ];
            }

            $fileContent = file_get_contents($localFilePath);

            $uploadOptions = array_merge([
                'name' => $destinationPath,
            ], $options);

            // Détection automatique du type MIME si non spécifié
            if (!isset($uploadOptions['metadata']['contentType'])) {
                $uploadOptions['metadata']['contentType'] = mime_content_type($localFilePath);
            }

            $object = $this->bucket->upload($fileContent, $uploadOptions);

            return [
                'success' => true,
                'name' => $object->name(),
                'url' => $this->getPublicUrl($destinationPath),
                'size' => $object->info()['size'] ?? null,
                'contentType' => $object->info()['contentType'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload depuis un contenu brut (base64, stream, etc.)
     */
    public function uploadFromString(string $content, string $destinationPath, string $contentType = 'application/octet-stream'): array
    {
        try {
            $object = $this->bucket->upload($content, [
                'name' => $destinationPath,
                'metadata' => [
                    'contentType' => $contentType
                ]
            ]);

            return [
                'success' => true,
                'name' => $object->name(),
                'url' => $this->getPublicUrl($destinationPath),
                'size' => $object->info()['size'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload une image depuis un formulaire Symfony
     */
    public function uploadUploadedFile($uploadedFile, string $directory = 'uploads'): array
    {
        if (!$uploadedFile) {
            return [
                'success' => false,
                'error' => 'Aucun fichier uploadé'
            ];
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            $originalFilename
        );
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        $destinationPath = $directory . '/' . $newFilename;

        return $this->uploadFile(
            $uploadedFile->getPathname(),
            $destinationPath,
            [
                'metadata' => [
                    'contentType' => $uploadedFile->getMimeType(),
                    'originalName' => $uploadedFile->getClientOriginalName(),
                ]
            ]
        );
    }

    /**
     * Télécharger un fichier depuis Firebase Storage
     */
    public function downloadFile(string $remotePath, string $localDestination): bool
    {
        try {
            $object = $this->bucket->object($remotePath);
            $object->downloadToFile($localDestination);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir le contenu d'un fichier sous forme de string
     */
    public function getFileContent(string $remotePath): ?string
    {
        try {
            $object = $this->bucket->object($remotePath);
            return $object->downloadAsString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Supprimer un fichier
     */
    public function deleteFile(string $remotePath): bool
    {
        try {
            $object = $this->bucket->object($remotePath);
            $object->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifier si un fichier existe
     */
    public function fileExists(string $remotePath): bool
    {
        try {
            $object = $this->bucket->object($remotePath);
            return $object->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir les métadonnées d'un fichier
     */
    public function getFileMetadata(string $remotePath): ?array
    {
        try {
            $object = $this->bucket->object($remotePath);
            return $object->info();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Lister les fichiers dans un dossier
     */
    public function listFiles(string $prefix = '', int $maxResults = 1000): array
    {
        try {
            $options = [
                'maxResults' => $maxResults,
            ];

            if ($prefix) {
                $options['prefix'] = $prefix;
            }

            $objects = $this->bucket->objects($options);
            $files = [];

            foreach ($objects as $object) {
                $files[] = [
                    'name' => $object->name(),
                    'size' => $object->info()['size'] ?? null,
                    'contentType' => $object->info()['contentType'] ?? null,
                    'updated' => $object->info()['updated'] ?? null,
                    'url' => $this->getPublicUrl($object->name()),
                ];
            }

            return $files;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir une URL publique pour un fichier
     */
    public function getPublicUrl(string $remotePath): string
    {
        $bucketName = $this->bucket->name();
        return sprintf(
            'https://storage.googleapis.com/%s/%s',
            $bucketName,
            urlencode($remotePath)
        );
    }

    /**
     * Générer une URL signée (avec expiration) pour un fichier privé
     */
    public function getSignedUrl(string $remotePath, int $expirationMinutes = 60): ?string
    {
        try {
            $object = $this->bucket->object($remotePath);
            $expiration = new \DateTime("+{$expirationMinutes} minutes");

            return $object->signedUrl($expiration);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Copier un fichier
     */
    public function copyFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            $sourceObject = $this->bucket->object($sourcePath);
            $sourceObject->copy($this->bucket, ['name' => $destinationPath]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Déplacer/renommer un fichier
     */
    public function moveFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            if ($this->copyFile($sourcePath, $destinationPath)) {
                return $this->deleteFile($sourcePath);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rendre un fichier public
     */
    public function makePublic(string $remotePath): bool
    {
        try {
            $object = $this->bucket->object($remotePath);
            $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rendre un fichier privé
     */
    public function makePrivate(string $remotePath): bool
    {
        try {
            $object = $this->bucket->object($remotePath);
            $object->update(['acl' => []], ['predefinedAcl' => 'private']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

# Configuration Firebase pour le projet

## 📋 Table des matières
1. [Introduction](#introduction)
2. [Prérequis](#prérequis)
3. [Configuration Firebase Console](#configuration-firebase-console)
4. [Configuration du projet](#configuration-du-projet)
5. [Utilisation](#utilisation)
6. [Exemples pratiques](#exemples-pratiques)
7. [Architecture](#architecture)
8. [FAQ](#faq)

---

## 🎯 Introduction

Ce projet utilise **Firebase** en complément de **PostgreSQL**:
- **PostgreSQL**: Base de données principale (User, Produit, Commande)
- **Firebase Auth**: Authentification des utilisateurs
- **Firebase Storage**: Stockage des images/fichiers
- **Firebase Messaging**: Notifications push (optionnel)

## ✅ Prérequis

- Compte Google
- Projet Firebase créé sur [Firebase Console](https://console.firebase.google.com)
- PHP 8.1+
- Composer (déjà installé)

---

## 🔧 Configuration Firebase Console

### Étape 1: Créer un projet Firebase

1. Allez sur [Firebase Console](https://console.firebase.google.com)
2. Cliquez sur "Ajouter un projet"
3. Donnez un nom à votre projet (ex: "projet-moss")
4. Activez/désactivez Google Analytics (optionnel)
5. Cliquez sur "Créer le projet"

### Étape 2: Générer les credentials

1. Dans Firebase Console, allez dans **Paramètres du projet** (⚙️ en haut à gauche)
2. Allez dans l'onglet **Comptes de service**
3. Cliquez sur **Générer une nouvelle clé privée**
4. Un fichier JSON sera téléchargé (ex: `projet-moss-firebase-adminsdk.json`)

### Étape 3: Configurer Firebase Authentication

1. Dans Firebase Console, allez dans **Authentication**
2. Cliquez sur **Commencer**
3. Activez les méthodes de connexion souhaitées:
   - ✅ **E-mail/Mot de passe** (recommandé)
   - Google, Facebook, etc. (optionnel)

### Étape 4: Configurer Firebase Storage

1. Dans Firebase Console, allez dans **Storage**
2. Cliquez sur **Commencer**
3. Choisissez **Mode test** pour commencer (vous pourrez sécuriser plus tard)
4. Choisissez une région (ex: `europe-west1`)

### Étape 5: Règles de sécurité Storage (optionnel mais recommandé)

Dans Storage > Rules, ajoutez:

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /uploads/{allPaths=**} {
      // Permettre lecture publique, écriture authentifiée
      allow read: if true;
      allow write: if request.auth != null;
    }

    match /private/{userId}/{allPaths=**} {
      // Fichiers privés (uniquement le propriétaire)
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
  }
}
```

---

## 🛠️ Configuration du projet

### Étape 1: Placer le fichier credentials

1. Prenez le fichier JSON téléchargé (ex: `projet-moss-firebase-adminsdk.json`)
2. Renommez-le en `firebase-credentials.json`
3. Placez-le dans `/config/firebase-credentials.json`

**⚠️ IMPORTANT**: Ce fichier contient des secrets! Il ne doit JAMAIS être commité dans Git.

### Étape 2: Vérifier le .gitignore

Vérifiez que `.gitignore` contient:

```
/config/firebase-credentials.json
```

### Étape 3: Configurer les variables d'environnement

Modifiez le fichier `.env`:

```bash
###> Firebase Configuration ###
FIREBASE_CREDENTIALS=%kernel.project_dir%/config/firebase-credentials.json
FIREBASE_PROJECT_ID=votre-project-id         # Ex: projet-moss
FIREBASE_STORAGE_BUCKET=votre-project-id.appspot.com
FIREBASE_DATABASE_URL=https://votre-project-id.firebaseio.com
###< Firebase Configuration ###
```

**Pour trouver votre Project ID:**
- Firebase Console > ⚙️ Paramètres du projet > Identifiant du projet

### Étape 4: Vérifier l'installation

```bash
composer install
php bin/console debug:container FirebaseAuthService
php bin/console debug:container FirebaseStorageService
```

Si tout est OK, vous verrez les services listés.

---

## 🚀 Utilisation

### 1. Firebase Authentication

#### Dans un contrôleur:

```php
use App\Service\FirebaseAuthService;

class MonController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function register(Request $request, FirebaseAuthService $authService)
    {
        // Créer un utilisateur Firebase
        $result = $authService->createUser(
            email: 'user@example.com',
            password: 'motdepasse123',
            displayName: 'John Doe'
        );

        if ($result['success']) {
            // Utilisateur créé avec succès
            $firebaseUid = $result['uid'];

            // IMPORTANT: Créer aussi l'utilisateur en base PostgreSQL
            $user = new User();
            $user->setEmail($result['email']);
            $user->setFirebaseUid($firebaseUid); // Ajouter ce champ à l'entité User
            $user->setNom('Doe');
            $user->setPrenom('John');
            // ... sauvegarder en BDD
        }
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, FirebaseAuthService $authService)
    {
        // Côté frontend, l'utilisateur se connecte avec Firebase JS SDK
        // Il envoie ensuite son ID Token au backend

        $idToken = $request->request->get('idToken');

        $result = $authService->verifyIdToken($idToken);

        if ($result) {
            // Token valide, utilisateur authentifié
            $firebaseUid = $result['uid'];

            // Récupérer l'utilisateur depuis PostgreSQL
            $user = $userRepository->findOneBy(['firebaseUid' => $firebaseUid]);

            // Connecter l'utilisateur dans Symfony
            // ...
        }
    }
}
```

### 2. Firebase Storage

#### Upload d'une image de profil:

```php
use App\Service\FirebaseStorageService;

class ProfileController extends AbstractController
{
    #[Route('/profile/upload-photo', name: 'profile_upload_photo')]
    public function uploadPhoto(Request $request, FirebaseStorageService $storageService)
    {
        $file = $request->files->get('photo');

        if ($file) {
            // Upload vers Firebase Storage
            $result = $storageService->uploadUploadedFile($file, 'profile_photos');

            if ($result['success']) {
                // URL publique de l'image
                $photoUrl = $result['url'];

                // Sauvegarder l'URL en BDD
                $user->setPhoto($photoUrl);
                $entityManager->flush();

                return $this->json(['url' => $photoUrl]);
            }
        }
    }
}
```

#### Upload d'une image produit:

```php
#[Route('/admin/produit/upload', name: 'admin_produit_upload')]
public function uploadProduitImage(Request $request, FirebaseStorageService $storageService)
{
    $file = $request->files->get('image');

    $result = $storageService->uploadUploadedFile($file, 'produits');

    if ($result['success']) {
        $produit->setImage($result['url']);
        $entityManager->flush();
    }
}
```

---

## 💡 Exemples pratiques

### Exemple 1: Inscription complète

```php
public function register(Request $request, FirebaseAuthService $authService, EntityManagerInterface $em)
{
    $email = $request->request->get('email');
    $password = $request->request->get('password');
    $nom = $request->request->get('nom');
    $prenom = $request->request->get('prenom');

    // 1. Créer l'utilisateur dans Firebase
    $firebaseResult = $authService->createUser(
        $email,
        $password,
        "$prenom $nom"
    );

    if (!$firebaseResult['success']) {
        return $this->json(['error' => $firebaseResult['error']], 400);
    }

    // 2. Créer l'utilisateur en PostgreSQL
    $user = new User();
    $user->setEmail($email);
    $user->setFirebaseUid($firebaseResult['uid']);
    $user->setNom($nom);
    $user->setPrenom($prenom);
    $user->setActif(true);

    $em->persist($user);
    $em->flush();

    return $this->json([
        'success' => true,
        'userId' => $user->getId(),
        'firebaseUid' => $firebaseResult['uid']
    ]);
}
```

### Exemple 2: Connexion avec vérification de token

```php
public function login(Request $request, FirebaseAuthService $authService, UserRepository $userRepo)
{
    // Le frontend envoie le token Firebase
    $idToken = $request->request->get('idToken');

    // Vérifier le token
    $tokenData = $authService->verifyIdToken($idToken);

    if (!$tokenData) {
        return $this->json(['error' => 'Token invalide'], 401);
    }

    // Récupérer l'utilisateur depuis PostgreSQL
    $user = $userRepo->findOneBy(['firebaseUid' => $tokenData['uid']]);

    if (!$user) {
        return $this->json(['error' => 'Utilisateur non trouvé'], 404);
    }

    if (!$user->isActif()) {
        return $this->json(['error' => 'Compte désactivé'], 403);
    }

    // Connecter l'utilisateur dans votre session Symfony
    // ...

    return $this->json([
        'success' => true,
        'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
        ]
    ]);
}
```

### Exemple 3: Upload multiple d'images

```php
public function uploadMultipleImages(Request $request, FirebaseStorageService $storageService)
{
    $files = $request->files->get('images');
    $uploadedUrls = [];

    foreach ($files as $file) {
        $result = $storageService->uploadUploadedFile($file, 'gallery');

        if ($result['success']) {
            $uploadedUrls[] = $result['url'];
        }
    }

    return $this->json([
        'uploaded' => count($uploadedUrls),
        'urls' => $uploadedUrls
    ]);
}
```

---

## 🏗️ Architecture du système

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONTEND                             │
│              (Twig + JavaScript + Firebase JS SDK)          │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ HTTP Requests
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                    SYMFONY BACKEND                          │
│                                                             │
│  ┌──────────────────┐         ┌─────────────────────┐     │
│  │   Controllers    │────────>│  Firebase Services  │     │
│  │                  │         │  - AuthService      │     │
│  │  - AuthController│         │  - StorageService   │     │
│  │  - ProduitCtrl   │         └─────────┬───────────┘     │
│  │  - UserController│                   │                 │
│  └────────┬─────────┘                   │                 │
│           │                             │                 │
│           │ Doctrine ORM                │ Firebase SDK    │
│           ▼                             ▼                 │
│  ┌──────────────────┐         ┌─────────────────────┐    │
│  │   PostgreSQL     │         │    Firebase API     │    │
│  │                  │         │                     │    │
│  │  - users         │         │  - Authentication   │    │
│  │  - produits      │         │  - Storage          │    │
│  │  - commandes     │         │  - Messaging        │    │
│  └──────────────────┘         └─────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Flux de données:

1. **Inscription:**
   - Frontend → Firebase (création compte)
   - Frontend → Backend (sauvegarde en PostgreSQL avec UID Firebase)

2. **Connexion:**
   - Frontend → Firebase (authentification)
   - Frontend reçoit ID Token
   - Frontend → Backend (vérification token + récupération données PostgreSQL)

3. **Upload fichier:**
   - Frontend → Backend (fichier)
   - Backend → Firebase Storage (upload)
   - Backend → PostgreSQL (sauvegarde URL)

---

## 📝 Modification de l'entité User

Ajoutez un champ `firebaseUid` à votre entité User:

```php
// src/Entity/User.php

#[ORM\Column(length: 255, unique: true, nullable: true)]
private ?string $firebaseUid = null;

public function getFirebaseUid(): ?string
{
    return $this->firebaseUid;
}

public function setFirebaseUid(?string $firebaseUid): static
{
    $this->firebaseUid = $firebaseUid;
    return $this;
}
```

Puis créez la migration:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 🔍 Tests des endpoints

Utilisez les endpoints de test dans `FirebaseExampleController`:

```bash
# Créer un utilisateur
curl -X POST http://localhost:8000/api/firebase/auth/create \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","displayName":"Test User"}'

# Lister les utilisateurs
curl http://localhost:8000/api/firebase/auth/users

# Upload un fichier
curl -X POST http://localhost:8000/api/firebase/storage/upload \
  -F "file=@/path/to/image.jpg"

# Lister les fichiers
curl http://localhost:8000/api/firebase/storage/list
```

---

## ❓ FAQ

### Q: Firebase est-il vraiment gratuit?
**R:** Oui, le plan Spark (gratuit) offre:
- 10k vérifications auth/mois
- 5 GB storage
- 50k lectures Firestore/jour
Largement suffisant pour un MVP ou petit projet.

### Q: Dois-je stocker les mots de passe en base PostgreSQL?
**R:** NON! Firebase gère l'authentification. Vous stockez uniquement le `firebaseUid` en base PostgreSQL pour lier les comptes.

### Q: Comment gérer les images existantes?
**R:** Vous pouvez:
1. Migrer les images existantes vers Firebase Storage
2. Garder les anciennes images en local et n'utiliser Firebase que pour les nouvelles

### Q: Puis-je utiliser PostgreSQL sans Firebase?
**R:** Oui, Firebase est optionnel. Vous pouvez continuer à utiliser PostgreSQL seul.

### Q: Comment sécuriser Firebase Storage?
**R:** Configurez les règles de sécurité dans Firebase Console (voir Étape 5 ci-dessus).

### Q: Comment tester en local?
**R:** Firebase fonctionne directement en local via l'API. Assurez-vous que:
- Le fichier `firebase-credentials.json` est présent
- Les variables d'environnement sont correctes

---

## 🚨 Sécurité

### À FAIRE:
✅ Ajouter `firebase-credentials.json` au `.gitignore`
✅ Ne jamais commiter les secrets
✅ Configurer les règles de sécurité Firebase
✅ Valider les inputs côté backend
✅ Utiliser HTTPS en production

### À NE PAS FAIRE:
❌ Exposer `firebase-credentials.json` publiquement
❌ Stocker les mots de passe en PostgreSQL (Firebase les gère)
❌ Laisser Firebase Storage en mode public non sécurisé
❌ Faire confiance aux données du frontend sans validation

---

## 📚 Ressources

- [Firebase PHP Documentation](https://firebase-php.readthedocs.io/)
- [Firebase Console](https://console.firebase.google.com)
- [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- [Firebase Storage Docs](https://firebase.google.com/docs/storage)

---

## 🆘 Support

En cas de problème:
1. Vérifiez que `firebase-credentials.json` est correct
2. Vérifiez les variables d'environnement dans `.env`
3. Consultez les logs Symfony: `tail -f var/log/dev.log`
4. Vérifiez la console Firebase pour les erreurs

---

**Bonne chance avec votre intégration Firebase! 🚀**

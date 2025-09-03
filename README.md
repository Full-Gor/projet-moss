# MossAir - Purificateur d'air naturel

## 🚀 Développement local

### Prérequis
- PHP 8.0+
- Composer
- Base de données MySQL locale (WAMP/XAMPP)
- Symfony CLI

### Installation

1. **Cloner le projet**
   ```bash
   git clone [URL_DU_REPO]
   cd projet-symf-1
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configuration de la base de données**
   - Créez une base de données `mossair_local` dans phpMyAdmin
   - Copiez le fichier `.env.local.example` vers `.env.local`
   - Modifiez la `DATABASE_URL` selon votre configuration

4. **Créer les tables**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le serveur de développement**
   ```bash
   symfony serve
   ```

### Configuration de la base de données

Créez un fichier `.env.local` avec :
```bash
DATABASE_URL="mysql://root:@127.0.0.1:3306/mossair_local?serverVersion=8.0&charset=utf8mb4"
APP_ENV=dev
APP_SECRET=votre_secret_ici
```

### URL d'accès
- **Site principal** : `http://localhost:8000`
- **Page de test** : `http://localhost:8000/test`
- **Connexion** : `http://localhost:8000/connexion`

### Fonctionnalités
- ✅ Panier d'achat
- ✅ Système de connexion
- ✅ Profil utilisateur
- ✅ Dashboard admin (Ctrl+A)
- ✅ Base de données MySQL locale
- ✅ SEO optimisé
- ✅ Sitemap.xml

### Support
Pour toute question, consultez la documentation Symfony

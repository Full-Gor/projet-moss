# Guide de Déploiement - MossAir

## 📦 Configuration des Assets pour le déploiement

### ✅ Ce qui a été configuré:

1. **framework.yaml** - Configuration des assets avec `base_path`
2. **Templates** - Tous les assets utilisent déjà `{{ asset() }}`
3. **.env** - Variable `ASSET_BASE_PATH` ajoutée

---

## 🚀 Déploiement en production

### Scénario 1: Déploiement à la racine du domaine

**Exemple:** `https://www.mossair.com`

**Configuration:**
```bash
# Dans .env.prod ou variables d'environnement du serveur
APP_ENV=prod
APP_DEBUG=0
ASSET_BASE_PATH=
```

Les assets seront accessible à:
- `https://www.mossair.com/css/style.css`
- `https://www.mossair.com/js/main.js`

---

### Scénario 2: Déploiement dans un sous-dossier

**Exemple:** `https://votre-domaine.com/projects/mossair`

**Configuration:**
```bash
# Dans .env.prod ou variables d'environnement du serveur
APP_ENV=prod
APP_DEBUG=0
ASSET_BASE_PATH=/projects/mossair
```

Les assets seront accessible à:
- `https://votre-domaine.com/projects/mossair/css/style.css`
- `https://votre-domaine.com/projects/mossair/js/main.js`

---

## 🔧 Étapes de déploiement

### 1. Préparer les fichiers

```bash
# Installer les dépendances (sans dev)
composer install --no-dev --optimize-autoloader

# Vider et optimiser le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Compiler les assets (si vous utilisez Asset Mapper)
php bin/console asset-map:compile
```

### 2. Configurer les variables d'environnement

**Option A: Fichier .env.prod.local** (créer sur le serveur)
```bash
# .env.prod.local (NE PAS COMMITER)
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=VOTRE_SECRET_SECURISE_DIFFERENT

# Si déployé dans un sous-dossier
ASSET_BASE_PATH=/projects/mossair

# Base de données production
DATABASE_URL="postgresql://user:password@host:5432/database"

# Firebase (si utilisé)
FIREBASE_PROJECT_ID=votre-project-id-prod
FIREBASE_STORAGE_BUCKET=votre-project-id-prod.appspot.com
```

**Option B: Variables d'environnement du serveur** (RECOMMANDÉ)
Configurez directement dans:
- **Railway/Render**: Variables d'environnement dans le dashboard
- **VPS/Serveur dédié**: Variables dans Apache/Nginx config ou .bashrc

### 3. Configuration du serveur web

#### **Apache (.htaccess ou VirtualHost)**

```apache
<VirtualHost *:80>
    ServerName www.mossair.com
    DocumentRoot /var/www/mossair/public

    <Directory /var/www/mossair/public>
        AllowOverride All
        Require all granted

        # Réécriture d'URL pour Symfony
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/mossair-error.log
    CustomLog ${APACHE_LOG_DIR}/mossair-access.log combined
</VirtualHost>
```

#### **Nginx**

```nginx
server {
    listen 80;
    server_name www.mossair.com;
    root /var/www/mossair/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/mossair_error.log;
    access_log /var/log/nginx/mossair_access.log;
}
```

### 4. Permissions des fichiers

```bash
# Sur le serveur
cd /var/www/mossair

# Donner les bonnes permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .

# var/ doit être writable
sudo chmod -R 775 var/
sudo chmod -R 775 public/uploads/ # si vous avez des uploads
```

---

## 🌐 Plateformes d'hébergement

### Railway.app

```yaml
# railway.toml (optionnel)
[build]
  builder = "NIXPACKS"

[deploy]
  startCommand = "php -S 0.0.0.0:$PORT -t public"
```

**Variables d'environnement Railway:**
```
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=votre-secret-securise
ASSET_BASE_PATH=
DATABASE_URL=postgresql://...
```

### Render.com

**Build Command:**
```bash
composer install --no-dev --optimize-autoloader && php bin/console cache:clear --env=prod
```

**Start Command:**
```bash
php -S 0.0.0.0:10000 -t public
```

### VPS (Ubuntu/Debian)

```bash
# Installation PHP 8.1+
sudo apt update
sudo apt install php8.1-fpm php8.1-mysql php8.1-pgsql php8.1-xml php8.1-mbstring

# Installation Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Cloner le projet
cd /var/www
git clone https://github.com/votre-repo/mossair.git

# Installer les dépendances
cd mossair
composer install --no-dev --optimize-autoloader

# Configuration
cp .env .env.prod.local
nano .env.prod.local  # Éditer avec vos vraies valeurs

# Permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 var/

# Cache
php bin/console cache:clear --env=prod
```

---

## 🐛 Dépannage

### Problème: CSS/JS ne se chargent pas

**Solution 1: Vérifier les chemins**
```bash
# Dans le navigateur, ouvrir DevTools (F12)
# Onglet Network → Vérifier les erreurs 404

# Vérifier que les fichiers existent
ls -la public/css/
ls -la public/js/
```

**Solution 2: Vérifier ASSET_BASE_PATH**
```bash
# Si déployé dans /projects/mossair
ASSET_BASE_PATH=/projects/mossair

# Si à la racine
ASSET_BASE_PATH=
```

**Solution 3: Vérifier les permissions**
```bash
sudo chmod -R 755 public/
```

**Solution 4: Vider le cache**
```bash
php bin/console cache:clear --env=prod
```

### Problème: Page blanche / Erreur 500

**Vérifier les logs:**
```bash
tail -f var/log/prod.log
```

**Vérifier PHP:**
```bash
php -v  # Version PHP
php bin/console about  # Info Symfony
```

**Augmenter les limites PHP:**
```ini
; php.ini
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
```

---

## ✅ Checklist de déploiement

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] Variables d'environnement configurées (APP_ENV=prod, APP_DEBUG=0)
- [ ] `ASSET_BASE_PATH` défini correctement
- [ ] Secret `APP_SECRET` changé et sécurisé
- [ ] Base de données configurée
- [ ] `php bin/console cache:clear --env=prod`
- [ ] Permissions fichiers (755 pour code, 775 pour var/)
- [ ] Serveur web configuré (Apache/Nginx)
- [ ] SSL/HTTPS configuré (Certbot/Let's Encrypt)
- [ ] Firewall configuré
- [ ] Backups automatiques activés
- [ ] Monitoring activé (erreurs, performances)

---

## 🔒 Sécurité

### Fichiers à NE PAS commiter:
```
.env.local
.env.prod.local
config/firebase-credentials.json
var/
vendor/
```

### Générer un nouveau APP_SECRET:
```bash
php -r "echo bin2hex(random_bytes(16));"
```

### Activer HTTPS:
```bash
# Avec Let's Encrypt (gratuit)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d www.mossair.com
```

---

## 📊 Performance

### Optimisations recommandées:

1. **OPcache** (php.ini)
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

2. **Cache HTTP**
```php
// Dans le contrôleur
$response->setPublic();
$response->setMaxAge(3600);
```

3. **Compression Gzip** (Nginx)
```nginx
gzip on;
gzip_types text/css application/javascript image/svg+xml;
```

---

## 🆘 Support

Si vous rencontrez des problèmes:
1. Vérifier les logs: `var/log/prod.log`
2. Vérifier les logs serveur: `/var/log/nginx/` ou `/var/log/apache2/`
3. Activer temporairement le debug: `APP_DEBUG=1`
4. Consulter la documentation Symfony: https://symfony.com/doc/current/deployment.html

---

**Dernière mise à jour:** 2025-11-13
**Version:** 1.0

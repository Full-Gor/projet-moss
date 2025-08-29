# MossAir - Purificateur d'air naturel

## 🚀 Déploiement sur Render.com

### Prérequis
- Compte GitHub avec le code source
- Compte Supabase avec base de données PostgreSQL
- Compte Render.com

### Étapes de déploiement

1. **Pousser le code sur GitHub**
   ```bash
   git add .
   git commit -m "Préparation pour déploiement Render"
   git push origin main
   ```

2. **Créer un compte Render.com**
   - Allez sur https://render.com
   - Créez un compte gratuit

3. **Connecter GitHub**
   - Dans Render, cliquez sur "New +"
   - Sélectionnez "Web Service"
   - Connectez votre repository GitHub

4. **Configurer le service**
   - **Name**: `mossair-symfony`
   - **Environment**: `PHP`
   - **Build Command**: `chmod +x render-build.sh && ./render-build.sh`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`

5. **Variables d'environnement**
   - `APP_ENV`: `prod`
   - `APP_SECRET`: Généré automatiquement
   - `DATABASE_URL`: `postgresql://postgres:[YOUR-PASSWORD]@db.[YOUR-PROJECT-REF].supabase.co:5432/postgres`

6. **Déployer**
   - Cliquez sur "Create Web Service"
   - Le déploiement se fait automatiquement

### URL finale
Votre site sera accessible sur : `https://mossair-symfony.onrender.com`

### Fonctionnalités
- ✅ Panier d'achat
- ✅ Système de connexion
- ✅ Profil utilisateur
- ✅ Dashboard admin (Ctrl+A)
- ✅ Base de données Supabase
- ✅ SEO optimisé
- ✅ Sitemap.xml

### Support
Pour toute question, consultez la documentation Render.com

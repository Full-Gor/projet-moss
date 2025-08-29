# MossAir - Purificateur d'air naturel

## 🚀 Déploiement sur Railway.app

### Prérequis
- Compte GitHub avec le code source
- Compte Supabase avec base de données PostgreSQL
- Compte Railway.app

### Étapes de déploiement

1. **Pousser le code sur GitHub**
   ```bash
   git add .
   git commit -m "Préparation pour déploiement Railway"
   git push origin main
   ```

2. **Créer un compte Railway.app**
   - Allez sur https://railway.app
   - Créez un compte gratuit

3. **Connecter GitHub**
   - Dans Railway, cliquez sur "New Project"
   - Sélectionnez "Deploy from GitHub repo"
   - Connectez votre repository GitHub

4. **Configuration automatique**
   - Railway détecte automatiquement que c'est un projet PHP/Symfony
   - Aucune configuration supplémentaire nécessaire

5. **Variables d'environnement**
   - `APP_ENV`: `prod`
   - `APP_SECRET`: `votre_secret_ici`
   - `DATABASE_URL`: `postgresql://postgres:[YOUR-PASSWORD]@db.[YOUR-PROJECT-REF].supabase.co:5432/postgres`

6. **Déployer**
   - Le déploiement se fait automatiquement
   - Votre site est accessible immédiatement

### URL finale
Votre site sera accessible sur : `https://mossair-production.up.railway.app`

### Fonctionnalités
- ✅ Panier d'achat
- ✅ Système de connexion
- ✅ Profil utilisateur
- ✅ Dashboard admin (Ctrl+A)
- ✅ Base de données Supabase
- ✅ SEO optimisé
- ✅ Sitemap.xml

### Support
Pour toute question, consultez la documentation Railway.app

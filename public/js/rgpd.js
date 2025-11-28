/* ============================================
   RGPD / COOKIES - JAVASCRIPT
   ============================================

   Ce fichier gère la logique du consentement cookies.

   PRINCIPE JAVASCRIPT :
   - Les variables stockent des données (let, const)
   - Les fonctions regroupent des actions réutilisables
   - Les événements (click, load) déclenchent des actions
   - Le DOM (Document Object Model) permet de manipuler le HTML

   PRINCIPE RGPD :
   - L'utilisateur DOIT donner son consentement AVANT tout tracking
   - Les cookies "nécessaires" sont exemptés (fonctionnement du site)
   - L'utilisateur peut refuser ou personnaliser ses choix
   - Le choix doit être sauvegardé et respecté
============================================ */

// ============================================
// CONFIGURATION DES COOKIES
// ============================================

/*
   const = déclare une constante (valeur qui ne change pas)
   Objet JavaScript avec les paramètres de chaque type de cookie
*/
const COOKIE_CONFIG = {
    // Nom du cookie qui stocke les préférences de l'utilisateur
    consentCookieName: 'mossair_cookie_consent',

    // Durée de validité du consentement en jours (RGPD recommande max 13 mois)
    consentDuration: 365,

    // Types de cookies disponibles
    types: {
        necessary: {
            name: 'Nécessaires',
            description: 'Cookies essentiels au fonctionnement du site',
            required: true // Obligatoire, ne peut pas être désactivé
        },
        analytics: {
            name: 'Analytiques',
            description: 'Cookies pour mesurer l\'audience (Google Analytics)',
            required: false
        },
        marketing: {
            name: 'Marketing',
            description: 'Cookies pour la publicité ciblée',
            required: false
        }
    }
};

// ============================================
// FONCTIONS UTILITAIRES POUR LES COOKIES
// ============================================

/*
   Fonction pour créer/modifier un cookie

   SYNTAXE : function nomFonction(parametre1, parametre2) { ... }

   Paramètres :
   - name : nom du cookie (string)
   - value : valeur à stocker (string)
   - days : durée de vie en jours (number)
*/
function setCookie(name, value, days) {
    /*
       new Date() = crée un objet Date avec la date/heure actuelle
       getTime() = récupère le timestamp (millisecondes depuis 1970)
       On ajoute le nombre de jours en millisecondes
    */
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

    /*
       expires = date d'expiration du cookie
       toUTCString() = convertit la date en format texte standard
       path=/ = le cookie est accessible sur tout le site
       SameSite=Lax = protection contre les attaques CSRF
    */
    const expires = 'expires=' + date.toUTCString();
    document.cookie = name + '=' + value + ';' + expires + ';path=/;SameSite=Lax';
}

/*
   Fonction pour lire un cookie

   Paramètres :
   - name : nom du cookie à lire

   Retourne : la valeur du cookie ou une chaîne vide si non trouvé
*/
function getCookie(name) {
    /*
       document.cookie = chaîne contenant tous les cookies
       Format : "nom1=valeur1; nom2=valeur2; ..."

       split(';') = découpe la chaîne en tableau à chaque ";"
    */
    const cookies = document.cookie.split(';');

    /*
       for...of = boucle sur chaque élément du tableau
       Syntaxe : for (const element of tableau) { ... }
    */
    for (const cookie of cookies) {
        /*
           trim() = supprime les espaces au début et à la fin
           startsWith() = vérifie si la chaîne commence par...
        */
        const trimmed = cookie.trim();
        if (trimmed.startsWith(name + '=')) {
            /*
               substring() = extrait une partie de la chaîne
               On récupère tout après "nom="
            */
            return trimmed.substring(name.length + 1);
        }
    }

    // Si le cookie n'existe pas, retourne une chaîne vide
    return '';
}

/*
   Fonction pour supprimer un cookie
   On le recrée avec une date d'expiration dans le passé
*/
function deleteCookie(name) {
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
}

// ============================================
// GESTION DU CONSENTEMENT
// ============================================

/*
   Fonction pour vérifier si l'utilisateur a déjà donné son consentement
   Retourne : true si consentement donné, false sinon
*/
function hasConsent() {
    /*
       !== = différent de (comparaison stricte, vérifie aussi le type)
       '' = chaîne vide
    */
    return getCookie(COOKIE_CONFIG.consentCookieName) !== '';
}

/*
   Fonction pour récupérer les préférences de l'utilisateur
   Retourne : objet avec les préférences ou null si pas de consentement
*/
function getConsentPreferences() {
    const consent = getCookie(COOKIE_CONFIG.consentCookieName);

    // Si pas de cookie, retourne null
    if (!consent) {
        return null;
    }

    /*
       try...catch = gestion des erreurs
       Si JSON.parse échoue, on retourne null au lieu de planter
    */
    try {
        /*
           JSON.parse() = convertit une chaîne JSON en objet JavaScript
           Exemple : '{"analytics":true}' devient {analytics: true}
        */
        return JSON.parse(consent);
    } catch (e) {
        // En cas d'erreur, on supprime le cookie corrompu
        deleteCookie(COOKIE_CONFIG.consentCookieName);
        return null;
    }
}

/*
   Fonction pour sauvegarder les préférences

   Paramètres :
   - preferences : objet avec les choix (ex: {analytics: true, marketing: false})
*/
function saveConsentPreferences(preferences) {
    /*
       JSON.stringify() = convertit un objet JavaScript en chaîne JSON
       Inverse de JSON.parse()
    */
    const value = JSON.stringify(preferences);
    setCookie(COOKIE_CONFIG.consentCookieName, value, COOKIE_CONFIG.consentDuration);
}

// ============================================
// GESTION DE LA BANNIÈRE
// ============================================

/*
   Fonction pour afficher la bannière
*/
function showCookieBanner() {
    /*
       document.getElementById() = récupère un élément HTML par son id
       Retourne l'élément ou null s'il n'existe pas
    */
    const banner = document.getElementById('cookie-banner');

    // Si la bannière existe, on la rend visible
    if (banner) {
        /*
           classList.remove() = supprime une classe CSS de l'élément
           Ici on retire "hidden" pour afficher la bannière
        */
        banner.classList.remove('hidden');
    }
}

/*
   Fonction pour cacher la bannière
*/
function hideCookieBanner() {
    const banner = document.getElementById('cookie-banner');

    if (banner) {
        /*
           classList.add() = ajoute une classe CSS à l'élément
        */
        banner.classList.add('hidden');
    }
}

/*
   Fonction pour afficher/cacher le panneau de personnalisation
*/
function toggleCustomizePanel() {
    const panel = document.getElementById('cookie-customize-panel');

    if (panel) {
        /*
           classList.toggle() = ajoute la classe si absente, la retire si présente
           Pratique pour basculer un état visible/invisible
        */
        panel.classList.toggle('visible');
    }
}

// ============================================
// ACTIONS UTILISATEUR
// ============================================

/*
   Fonction appelée quand l'utilisateur clique sur "Tout accepter"
*/
function acceptAllCookies() {
    // On crée un objet avec tous les types acceptés
    const preferences = {
        necessary: true,  // Toujours true (obligatoire)
        analytics: true,
        marketing: true,
        timestamp: new Date().toISOString() // Date du consentement (RGPD exige une preuve)
    };

    // Sauvegarde les préférences
    saveConsentPreferences(preferences);

    // Cache la bannière
    hideCookieBanner();

    // Active les scripts de tracking
    loadAnalytics();

    // Message dans la console pour debug
    console.log('Cookies acceptés:', preferences);
}

/*
   Fonction appelée quand l'utilisateur clique sur "Tout refuser"
*/
function refuseAllCookies() {
    // Seuls les cookies nécessaires sont acceptés
    const preferences = {
        necessary: true,
        analytics: false,
        marketing: false,
        timestamp: new Date().toISOString()
    };

    saveConsentPreferences(preferences);
    hideCookieBanner();

    console.log('Cookies refusés (sauf nécessaires):', preferences);
}

/*
   Fonction appelée quand l'utilisateur sauvegarde ses préférences personnalisées
*/
function saveCustomPreferences() {
    /*
       document.getElementById('xxx').checked = récupère l'état d'une checkbox
       Retourne true si cochée, false sinon
    */
    const preferences = {
        necessary: true, // Toujours true
        analytics: document.getElementById('cookie-analytics').checked,
        marketing: document.getElementById('cookie-marketing').checked,
        timestamp: new Date().toISOString()
    };

    saveConsentPreferences(preferences);
    hideCookieBanner();

    // Charge analytics si accepté
    if (preferences.analytics) {
        loadAnalytics();
    }

    console.log('Préférences personnalisées:', preferences);
}

// ============================================
// CHARGEMENT DES SCRIPTS DE TRACKING
// ============================================

/*
   Fonction pour charger Google Analytics

   IMPORTANT RGPD : Cette fonction ne doit être appelée
   QU'APRÈS le consentement de l'utilisateur !
*/
function loadAnalytics() {
    /*
       Vérifie si GA est déjà chargé pour éviter de le charger 2 fois
       window.gaLoaded = variable globale qu'on crée nous-mêmes
    */
    if (window.gaLoaded) {
        return;
    }

    // Marque GA comme chargé
    window.gaLoaded = true;

    /*
       Création dynamique d'une balise <script>
       document.createElement('script') = crée un nouvel élément script
    */
    const script = document.createElement('script');
    script.async = true; // Chargement asynchrone (ne bloque pas la page)
    script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX';

    /*
       document.head.appendChild() = ajoute l'élément à la fin du <head>
       Le script sera automatiquement exécuté par le navigateur
    */
    document.head.appendChild(script);

    /*
       Configuration de Google Analytics
       gtag() est défini par le script Google chargé ci-dessus
    */
    window.dataLayer = window.dataLayer || [];
    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX', {
        'anonymize_ip': true // Anonymise l'IP (recommandé RGPD)
    });

    console.log('Google Analytics chargé');
}

// ============================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// ============================================

/*
   DOMContentLoaded = événement déclenché quand le HTML est chargé
   C'est le bon moment pour initialiser nos scripts
*/
document.addEventListener('DOMContentLoaded', function() {
    // Vérifie si l'utilisateur a déjà fait son choix
    const preferences = getConsentPreferences();

    if (preferences) {
        // L'utilisateur a déjà consenti, on respecte ses choix
        console.log('Préférences existantes:', preferences);

        // Cache la bannière
        hideCookieBanner();

        // Charge les scripts selon les préférences
        if (preferences.analytics) {
            loadAnalytics();
        }
    } else {
        // Pas de consentement, on affiche la bannière
        console.log('Aucun consentement, affichage de la bannière');
        showCookieBanner();
    }
});

// ============================================
// FONCTION POUR RÉINITIALISER LE CONSENTEMENT
// ============================================

/*
   Fonction utile pour permettre à l'utilisateur de modifier ses choix
   Peut être appelée depuis un lien "Gérer mes cookies" dans le footer
*/
function resetCookieConsent() {
    deleteCookie(COOKIE_CONFIG.consentCookieName);
    showCookieBanner();
    console.log('Consentement réinitialisé');
}

/*
   Expose la fonction resetCookieConsent globalement
   Pour pouvoir l'appeler depuis un onclick dans le HTML
*/
window.resetCookieConsent = resetCookieConsent;

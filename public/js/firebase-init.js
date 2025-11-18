// Firebase Frontend Configuration Example
// Documentation: https://firebase.google.com/docs/web/setup

// Configuration Firebase (à obtenir depuis Firebase Console)
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "your-project-id.firebaseapp.com",
    projectId: "your-project-id",
    storageBucket: "your-project-id.appspot.com",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// Initialiser Firebase (décommenter quand vous aurez installé Firebase JS SDK)
/*
import { initializeApp } from "firebase/app";
import { getAuth, signInWithEmailAndPassword, createUserWithEmailAndPassword } from "firebase/auth";
import { getStorage, ref, uploadBytes, getDownloadURL } from "firebase/storage";

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const storage = getStorage(app);
*/

// Exemple 1: Inscription
async function registerUser(email, password, nom, prenom) {
    try {
        // Créer l'utilisateur dans Firebase
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;

        // Envoyer les données au backend pour créer l'utilisateur en PostgreSQL
        const response = await fetch('/api/user/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                firebaseUid: user.uid,
                email: email,
                nom: nom,
                prenom: prenom
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('Utilisateur créé avec succès:', data);
            return { success: true, user: data };
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Erreur inscription:', error);
        return { success: false, error: error.message };
    }
}

// Exemple 2: Connexion
async function loginUser(email, password) {
    try {
        // Authentifier avec Firebase
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;

        // Obtenir le token ID
        const idToken = await user.getIdToken();

        // Envoyer le token au backend pour vérification
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idToken: idToken
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('Connexion réussie:', data);
            // Stocker le token pour les requêtes futures
            localStorage.setItem('firebaseToken', idToken);
            return { success: true, user: data.user };
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Erreur connexion:', error);
        return { success: false, error: error.message };
    }
}

// Exemple 3: Upload d'image
async function uploadImage(file) {
    try {
        // Créer une référence dans Firebase Storage
        const storageRef = ref(storage, `uploads/${Date.now()}_${file.name}`);

        // Upload le fichier
        const snapshot = await uploadBytes(storageRef, file);

        // Obtenir l'URL publique
        const downloadURL = await getDownloadURL(snapshot.ref);

        console.log('Fichier uploadé:', downloadURL);
        return { success: true, url: downloadURL };
    } catch (error) {
        console.error('Erreur upload:', error);
        return { success: false, error: error.message };
    }
}

// Exemple 4: Upload d'image avec backend Symfony
async function uploadImageViaBackend(file) {
    try {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/api/firebase/storage/upload', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            console.log('Image uploadée:', data.url);
            return { success: true, url: data.url };
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Erreur upload:', error);
        return { success: false, error: error.message };
    }
}

// Exemple 5: Déconnexion
async function logoutUser() {
    try {
        await auth.signOut();
        localStorage.removeItem('firebaseToken');
        console.log('Déconnexion réussie');
        return { success: true };
    } catch (error) {
        console.error('Erreur déconnexion:', error);
        return { success: false, error: error.message };
    }
}

// Exemple 6: Vérifier si l'utilisateur est connecté
auth.onAuthStateChanged((user) => {
    if (user) {
        console.log('Utilisateur connecté:', user.email);
        // L'utilisateur est connecté
        // Vous pouvez charger ses données depuis le backend
    } else {
        console.log('Utilisateur non connecté');
        // L'utilisateur n'est pas connecté
        // Rediriger vers la page de connexion si nécessaire
    }
});

// Exemple 7: Réinitialisation du mot de passe
async function resetPassword(email) {
    try {
        await auth.sendPasswordResetEmail(email);
        console.log('Email de réinitialisation envoyé');
        return { success: true };
    } catch (error) {
        console.error('Erreur réinitialisation:', error);
        return { success: false, error: error.message };
    }
}

// Utilitaire: Obtenir le token ID actuel
async function getCurrentToken() {
    const user = auth.currentUser;
    if (user) {
        return await user.getIdToken();
    }
    return null;
}

// Utilitaire: Faire une requête authentifiée au backend
async function authenticatedFetch(url, options = {}) {
    const token = await getCurrentToken();

    if (!token) {
        throw new Error('Utilisateur non authentifié');
    }

    const headers = {
        ...options.headers,
        'Authorization': `Bearer ${token}`
    };

    return fetch(url, {
        ...options,
        headers
    });
}

// Export pour utilisation dans d'autres fichiers
export {
    registerUser,
    loginUser,
    logoutUser,
    uploadImage,
    uploadImageViaBackend,
    resetPassword,
    getCurrentToken,
    authenticatedFetch
};

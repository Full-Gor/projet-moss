// Animation fade-in au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Animation des images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 1s ease-in-out';
        
        if (img.complete) {
            img.style.opacity = '1';
        } else {
            img.addEventListener('load', function() {
                img.style.opacity = '1';
            });
        }
    });
    
    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Message flash auto-hide
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 3000);
    });
});

// Gestion du panier simple
let cart = [];

function addToCart(product, color) {
    cart.push({
        product: product,
        color: color,
        price: 149
    });
    
    updateCartCount();
    showNotification('Produit ajoutÃ© au panier');
}

function updateCartCount() {
    const cartIcon = document.querySelector('.cart');
    if (cartIcon && cart.length > 0) {
        cartIcon.textContent = `í»’ Panier (${cart.length})`;
    }
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background-color: #4a7c59;
        color: white;
        padding: 1rem 2rem;
        border-radius: 5px;
        animation: slideIn 0.3s ease-out;
        z-index: 1001;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Animation CSS pour les notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        badgeId: Number,
        displayed: Boolean
    }

    async toggle() {
        const button = this.element.querySelector('button');
        const originalText = button.textContent;
        
        // Désactiver le bouton pendant la requête
        button.disabled = true;
        button.textContent = 'Chargement...';

        try {
            const response = await fetch(`/badges/toggle/${this.badgeIdValue}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.displayedValue = data.displayed;
                this.updateUI();
                
                // Mettre à jour le header du profil en temps réel
                this.updateProfileHeader(data.displayed);
                
                // Afficher une notification de succès
                this.showSuccessNotification(data.displayed);
            } else if (data.error) {
                alert(data.error);
                button.textContent = originalText;
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
            button.textContent = originalText;
        } finally {
            button.disabled = false;
        }
    }

    updateUI() {
        const button = this.element.querySelector('button');
        const badgeIcon = this.element.querySelector('.badge-icon');

        if (this.displayedValue) {
            button.textContent = '✓ Affiché sur le profil';
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            button.classList.add('bg-green-600', 'hover:bg-green-700');
            if (badgeIcon) {
                badgeIcon.classList.remove('grayscale');
            }
        } else {
            button.textContent = 'Afficher sur le profil';
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
    }

    updateProfileHeader(isDisplayed) {
        // Récupérer le badge (icon + name) depuis l'élément parent
        const badgeCard = this.element;
        const badgeIcon = badgeCard.querySelector('.text-4xl.badge-icon');
        const badgeName = badgeCard.querySelector('.font-bold.text-gray-900');
        
        if (!badgeIcon || !badgeName) return;

        const icon = badgeIcon.textContent.trim();
        const name = badgeName.textContent.trim();

        // Trouver le header du profil (là où sont affichés les badges)
        const profileHeader = document.querySelector('.bg-gradient-to-r.from-blue-500.to-purple-600');
        if (!profileHeader) return;

        const badgeContainer = profileHeader.querySelector('.flex.items-center.space-x-3.flex-wrap');
        if (!badgeContainer) return;

        // Chercher si le badge existe déjà dans le header
        const existingBadge = Array.from(badgeContainer.querySelectorAll('span[title]')).find(
            span => span.getAttribute('title') === name
        );

        if (isDisplayed) {
            // Ajouter le badge s'il n'existe pas
            if (!existingBadge) {
                const newBadge = document.createElement('span');
                newBadge.className = 'text-3xl animate-bounce-in';
                newBadge.title = name;
                newBadge.textContent = icon;
                badgeContainer.appendChild(newBadge);

                // Retirer l'animation après qu'elle soit terminée
                setTimeout(() => {
                    newBadge.classList.remove('animate-bounce-in');
                }, 500);
            }
        } else {
            // Retirer le badge
            if (existingBadge) {
                existingBadge.classList.add('animate-fade-out');
                setTimeout(() => {
                    existingBadge.remove();
                }, 300);
            }
        }
    }

    showSuccessNotification(isDisplayed) {
        // Supprimer les anciennes notifications
        document.querySelectorAll('.badge-success-notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = 'badge-success-notification';
        notification.style.cssText = `
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            z-index: 9999;
            background: ${isDisplayed ? 'linear-gradient(to right, #10B981, #059669)' : 'linear-gradient(to right, #6B7280, #4B5563)'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        `;

        const icon = document.createElement('span');
        icon.style.cssText = 'font-size: 1.5rem;';
        icon.textContent = isDisplayed ? '✓' : '✕';

        const text = document.createElement('span');
        text.style.cssText = 'font-weight: 500;';
        text.textContent = isDisplayed 
            ? 'Badge ajouté à votre profil' 
            : 'Badge retiré de votre profil';

        notification.appendChild(icon);
        notification.appendChild(text);
        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Auto-fermeture après 3 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}
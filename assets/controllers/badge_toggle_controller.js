import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        badgeId: Number,
        displayed: Boolean
    }

    async toggle() {
        const button = this.element.querySelector('button');
        
        // Empêcher les clics multiples sans désactiver le bouton
        if (this.isProcessing) {
            return;
        }
        this.isProcessing = true;
        
        // Changer le curseur pendant le traitement
        button.style.cursor = 'wait';

        try {
            const response = await fetch(`/badges/toggle/${this.badgeIdValue}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                // Mettre à jour l'état
                this.displayedValue = data.displayed;
                
                // Mettre à jour le bouton
                this.updateButton(button, data.displayed);
                
                // Mettre à jour le header du profil en temps réel
                this.updateProfileHeader(data.displayed);
                
                // Afficher un toast
                this.showToast(data.displayed);
            } else if (data.error) {
                this.showErrorToast(data.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showErrorToast('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            this.isProcessing = false;
            button.style.cursor = 'pointer';
        }
    }

    updateButton(button, isDisplayed) {
        // Vider le contenu du bouton
        button.innerHTML = '';
        
        // S'assurer que le curseur reste pointer
        button.style.cursor = 'pointer';
        
        // Créer les spans pour l'icône et le texte
        const iconSpan = document.createElement('span');
        const textSpan = document.createElement('span');
        
        if (isDisplayed) {
            iconSpan.textContent = '✓';
            textSpan.textContent = 'Affiché';
            button.className = 'w-full font-semibold text-xs py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-1 cursor-pointer bg-green-500 text-white hover:bg-green-600';
        } else {
            iconSpan.textContent = '+';
            textSpan.textContent = 'Afficher';
            button.className = 'w-full font-semibold text-xs py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-1 cursor-pointer bg-blue-500 text-white hover:bg-blue-600';
        }
        
        button.appendChild(iconSpan);
        button.appendChild(textSpan);
    }

    updateProfileHeader(isDisplayed) {
        // Récupérer l'icône et le nom du badge
        const badgeCard = this.element;
        const badgeIcon = badgeCard.querySelector('.text-4xl');
        const badgeName = badgeCard.querySelector('.font-bold.text-gray-900');
        
        if (!badgeIcon || !badgeName) {
            console.error('Badge icon ou name non trouvé');
            return;
        }

        const icon = badgeIcon.textContent.trim();
        const name = badgeName.textContent.trim();

        // Trouver le container des badges dans la sidebar
        // Chercher dans la carte profil de la sidebar
        const sidebarCard = document.querySelector('.lg\\:sticky .bg-gradient-to-br');
        let badgeContainer = sidebarCard?.querySelector('.flex.justify-center.gap-1');
        
        // Si le container n'existe pas encore, le créer
        if (!badgeContainer && sidebarCard) {
            const usernameSection = sidebarCard.querySelector('.text-center');
            if (usernameSection) {
                badgeContainer = document.createElement('div');
                badgeContainer.className = 'flex justify-center gap-1 mt-3';
                
                // Insérer après les rangs/badges créateur
                const ranksContainer = usernameSection.querySelector('.flex.justify-center.gap-2');
                if (ranksContainer) {
                    ranksContainer.after(badgeContainer);
                } else {
                    usernameSection.appendChild(badgeContainer);
                }
            }
        }
        
        if (!badgeContainer) {
            console.error('Badge container non trouvé dans la sidebar');
            return;
        }

        // Chercher si le badge existe déjà
        const existingBadge = Array.from(badgeContainer.querySelectorAll('span[title]')).find(
            span => span.getAttribute('title') === name
        );

        if (isDisplayed) {
            // Ajouter le badge s'il n'existe pas
            if (!existingBadge) {
                const newBadge = document.createElement('span');
                newBadge.className = 'text-2xl hover:scale-125 transition-transform cursor-help';
                newBadge.title = name;
                newBadge.textContent = icon;
                
                // Animation d'apparition
                newBadge.style.opacity = '0';
                newBadge.style.transform = 'scale(0)';
                badgeContainer.appendChild(newBadge);
                
                // Trigger animation
                setTimeout(() => {
                    newBadge.style.transition = 'all 0.3s ease-out';
                    newBadge.style.opacity = '1';
                    newBadge.style.transform = 'scale(1)';
                }, 10);
            }
        } else {
            // Retirer le badge avec animation
            if (existingBadge) {
                existingBadge.style.transition = 'all 0.3s ease-out';
                existingBadge.style.opacity = '0';
                existingBadge.style.transform = 'scale(0)';
                
                setTimeout(() => {
                    existingBadge.remove();
                    // Si plus de badges, supprimer le container
                    if (badgeContainer.children.length === 0) {
                        badgeContainer.remove();
                    }
                }, 300);
            }
        }
    }

    showToast(isDisplayed) {
        // Créer un toast comme dans _toast.html.twig
        const toastContainer = document.createElement('div');
        toastContainer.style.cssText = 'position: fixed; top: 80px; right: 16px; z-index: 999999; pointer-events: none;';
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            max-width: 384px;
            width: 384px;
            margin-bottom: 16px;
            pointer-events: auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
        `;
        
        const bgColor = isDisplayed 
            ? 'background: #f0fdf4; border-left: 4px solid #22c55e;'
            : 'background: #eff6ff; border-left: 4px solid #3b82f6;';
        
        toast.innerHTML = `
            <div style="padding: 16px; ${bgColor}">
                <div style="display: flex; align-items: center;">
                    <div style="flex-shrink: 0; line-height: 1;">
                        <span style="font-size: 24px; display: block;">${isDisplayed ? '✅' : 'ℹ️'}</span>
                    </div>
                    <div style="margin-left: 12px; flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 14px; font-weight: 500; line-height: 1.5; color: ${isDisplayed ? '#166534' : '#1e40af'};">
                            ${isDisplayed ? 'Badge ajouté à votre profil' : 'Badge retiré de votre profil'}
                        </p>
                    </div>
                    <button onclick="this.closest('[style*=fixed]').remove()" style="margin-left: 16px; flex-shrink: 0; color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0;">
                        <svg style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toastContainer.remove(), 300);
        }, 5000);
    }

    showErrorToast(message) {
        const toastContainer = document.createElement('div');
        toastContainer.style.cssText = 'position: fixed; top: 80px; right: 16px; z-index: 999999; pointer-events: none;';
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            max-width: 384px;
            width: 384px;
            margin-bottom: 16px;
            pointer-events: auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
        `;
        
        toast.innerHTML = `
            <div style="padding: 16px; background: #fef2f2; border-left: 4px solid #ef4444;">
                <div style="display: flex; align-items: center;">
                    <div style="flex-shrink: 0; line-height: 1;">
                        <span style="font-size: 24px; display: block;">❌</span>
                    </div>
                    <div style="margin-left: 12px; flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 14px; font-weight: 500; line-height: 1.5; color: #991b1b;">
                            ${message}
                        </p>
                    </div>
                    <button onclick="this.closest('[style*=fixed]').remove()" style="margin-left: 16px; flex-shrink: 0; color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0;">
                        <svg style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toastContainer.remove(), 300);
        }, 5000);
    }
}
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['passedCheckbox', 'stoppedCheckbox'];

    connect() {
        this.stationId = this.element.dataset.stationId;
        this.lineId = this.element.dataset.stationLineId;
        console.log('Station controller connected - Station:', this.stationId, 'Line:', this.lineId);
    }

    async togglePassed(event) {
        const checkbox = event.currentTarget;
        const isChecked = checkbox.checked;

        console.log('Toggle passed - Station:', this.stationId, 'Checked:', isChecked);
        await this.updateStation('passed', isChecked, checkbox);
    }

    async toggleStopped(event) {
        const checkbox = event.currentTarget;
        const isChecked = checkbox.checked;

        console.log('Toggle stopped - Station:', this.stationId, 'Checked:', isChecked);
        await this.updateStation('stopped', isChecked, checkbox);
    }

    async updateStation(type, checked, checkbox) {
        const url = `/lines/${this.lineId}/station/${this.stationId}/toggle`;
        console.log('Fetching URL:', url);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    type: type,
                    checked: checked 
                })
            });

            console.log('Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server error:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Success response:', data);
            
            // Animation de confirmation
            this.animateSuccess(checkbox);

            // Afficher notification de nouveaux badges SI il y en a
            if (data.newBadges && data.newBadges.length > 0) {
                console.log('New badges unlocked:', data.newBadges);
                this.showBadgeNotification(data.newBadges);
                
                // Recharger après 2 secondes pour laisser voir la notification
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Pas de nouveau badge, recharger après 300ms
                setTimeout(() => {
                    window.location.reload();
                }, 300);
            }

        } catch (error) {
            console.error('Error updating station:', error);
            // Remettre la checkbox dans son état précédent
            checkbox.checked = !checked;
            alert('Une erreur est survenue. Veuillez réessayer.');
        }
    }

    animateSuccess(element) {
        const parent = element.parentElement;
        parent.classList.add('scale-110');
        setTimeout(() => {
            parent.classList.remove('scale-110');
        }, 200);
    }

    showBadgeNotification(badges) {
        // Supprimer les anciennes notifications
        document.querySelectorAll('.badge-notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = 'badge-notification';
        notification.style.cssText = `
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            z-index: 9999;
            max-width: 20rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
        `;
        
        const badgesList = badges.map(b => 
            `<div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                <span style="font-size: 2rem;">${b.icon}</span>
                <span style="font-weight: 600; font-size: 1.125rem;">${b.name}</span>
            </div>`
        ).join('');
        
        notification.innerHTML = `
            <div style="background: linear-gradient(to right, #FBBF24, #F59E0B); border-radius: 0.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); padding: 1.25rem; color: white; position: relative;">
                <p style="font-size: 1rem; font-weight: 500; margin: 0;">Vous avez débloqué un nouveau badge !</p>
                ${badgesList}
                <button onclick="this.closest('.badge-notification').remove()" 
                        style="position: absolute; top: 0.5rem; right: 0.5rem; color: white; font-size: 1.5rem; background: none; border: none; cursor: pointer; padding: 0.25rem; line-height: 1;">×</button>
            </div>
        `;

        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['passedCheckbox', 'stoppedCheckbox'];

    connect() {
        this.stationId = this.element.dataset.stationId;
        this.lineId = this.element.dataset.stationLineId;
        this.isProcessing = false;
        console.log('Station controller connected - Station:', this.stationId, 'Line:', this.lineId);
    }

    async togglePassed(event) {
        const checkbox = event.currentTarget;

        // Empêcher si déjà en cours (n'importe quelle requête)
        if (this.isProcessing) {
            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            return;
        }

        const isChecked = checkbox.checked;

        // Bloquer les deux checkboxes
        this.disableCheckboxes();

        try {
            // RÈGLE : Si on décoche Passé, décocher automatiquement Visité
            if (!isChecked && this.hasStoppedCheckboxTarget && this.stoppedCheckboxTarget.checked) {
                this.stoppedCheckboxTarget.checked = false;
                // Mettre à jour Visité d'abord
                await this.sendUpdate('stopped', false);
            }

            // Puis mettre à jour Passé
            await this.sendUpdate('passed', isChecked);

            // Animation
            this.animateSuccess(checkbox);

            // Recharger après un court délai
            setTimeout(() => {
                window.location.reload();
            }, 200);

        } catch (error) {
            console.error('Error:', error);
            checkbox.checked = !isChecked;
            alert('Une erreur est survenue. Veuillez réessayer.');
            this.enableCheckboxes();
        }
    }

    async toggleStopped(event) {
        const checkbox = event.currentTarget;

        // Empêcher si déjà en cours
        if (this.isProcessing) {
            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            return;
        }

        const isChecked = checkbox.checked;

        // Bloquer les deux checkboxes
        this.disableCheckboxes();

        try {
            // RÈGLE : Si on coche Visité, cocher automatiquement Passé
            if (isChecked && this.hasPassedCheckboxTarget && !this.passedCheckboxTarget.checked) {
                this.passedCheckboxTarget.checked = true;
                // Mettre à jour Passé d'abord
                await this.sendUpdate('passed', true);
            }

            // Puis mettre à jour Visité
            await this.sendUpdate('stopped', isChecked);

            // Animation
            this.animateSuccess(checkbox);

            // Recharger après un court délai
            setTimeout(() => {
                window.location.reload();
            }, 200);

        } catch (error) {
            console.error('Error:', error);
            checkbox.checked = !isChecked;
            alert('Une erreur est survenue. Veuillez réessayer.');
            this.enableCheckboxes();
        }
    }

    async sendUpdate(type, checked) {
        const url = `/lines/${this.lineId}/station/${this.stationId}/toggle`;

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

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    disableCheckboxes() {
        this.isProcessing = true;
        if (this.hasPassedCheckboxTarget) {
            this.passedCheckboxTarget.disabled = true;
        }
        if (this.hasStoppedCheckboxTarget) {
            this.stoppedCheckboxTarget.disabled = true;
        }
    }

    enableCheckboxes() {
        this.isProcessing = false;
        if (this.hasPassedCheckboxTarget) {
            this.passedCheckboxTarget.disabled = false;
        }
        if (this.hasStoppedCheckboxTarget) {
            this.stoppedCheckboxTarget.disabled = false;
        }
    }

    animateSuccess(element) {
        const parent = element.parentElement;
        parent.classList.add('scale-110');
        setTimeout(() => {
            parent.classList.remove('scale-110');
        }, 200);
    }
}
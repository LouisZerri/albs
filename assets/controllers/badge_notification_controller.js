import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Auto-disparition après 5 secondes
        setTimeout(() => {
            this.element.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => {
                this.element.remove();
            }, 300);
        }, 5000);
    }

    close() {
        this.element.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => {
            this.element.remove();
        }, 300);
    }
}
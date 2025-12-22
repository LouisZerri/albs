import { Controller } from '@hotwired/stimulus';

let globalProcessing = false;

export default class extends Controller {
    static targets = ['circle', 'statusText', 'mobileIcon'];

    connect() {
        this.stationId = this.element.dataset.stationId;
        this.lineId = this.element.dataset.stationLineId;
        this.passed = this.element.dataset.stationPassed === 'true';
        this.stopped = this.element.dataset.stationStopped === 'true';
        this.isTerminus = this.element.dataset.stationTerminus === 'true';
    }

    getCurrentState() {
        if (this.stopped) return 2;
        if (this.passed) return 1;
        return 0;
    }

    async cycle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (globalProcessing) return;

        globalProcessing = true;

        const circle = event.currentTarget;
        circle.style.opacity = '0.5';
        circle.style.pointerEvents = 'none';

        const currentState = this.getCurrentState();
        const nextState = (currentState + 1) % 3;

        // Sauvegarder l'ancien état AVANT de changer quoi que ce soit
        const oldPassed = this.passed;
        const oldStopped = this.stopped;

        // Feedback visuel immédiat
        this.setVisualState(circle, nextState);

        try {
            let newPassed, newStopped;

            switch (nextState) {
                case 0: newPassed = false; newStopped = false; break;
                case 1: newPassed = true; newStopped = false; break;
                case 2: newPassed = true; newStopped = true; break;
            }

            const response = await fetch(`/lines/${this.lineId}/station/${this.stationId}/toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ passed: newPassed, stopped: newStopped })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (data.success) {
                // Mettre à jour l'état local
                this.passed = data.passed;
                this.stopped = data.stopped;

                // Mettre à jour les compteurs avec les anciennes et nouvelles valeurs
                this.updateStats(oldPassed, oldStopped, data.passed, data.stopped);

                // Animation succès
                circle.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    circle.style.transform = 'scale(1)';
                }, 200);

                // Afficher badges
                if (data.newBadges && data.newBadges.length > 0) {
                    this.showBadges(data.newBadges);
                }
            } else {
                throw new Error('Server error');
            }

        } catch (error) {
            console.error('Error:', error);
            // Revenir à l'ancien état visuel
            this.setVisualState(circle, currentState);
            // Restaurer les valeurs
            this.passed = oldPassed;
            this.stopped = oldStopped;
        } finally {
            setTimeout(() => {
                globalProcessing = false;
                circle.style.opacity = '1';
                circle.style.pointerEvents = 'auto';
            }, 300);
        }
    }

    setVisualState(circle, state) {
        circle.classList.remove(
            'bg-white', 'bg-blue-400', 'bg-blue-500', 'bg-green-500', 'bg-green-600', 'bg-gray-900',
            'ring-blue-200', 'ring-blue-300', 'ring-green-200', 'ring-green-300', 'ring-transparent'
        );

        const isTerminus = this.isTerminus;

        if (isTerminus) {
            switch (state) {
                case 0:
                    circle.classList.add('bg-gray-900', 'ring-transparent');
                    break;
                case 1:
                    circle.classList.add('bg-blue-500', 'ring-blue-300');
                    break;
                case 2:
                    circle.classList.add('bg-green-600', 'ring-green-300');
                    break;
            }
        } else {
            switch (state) {
                case 0:
                    circle.classList.add('bg-white', 'ring-transparent');
                    break;
                case 1:
                    circle.classList.add('bg-blue-400', 'ring-blue-200');
                    break;
                case 2:
                    circle.classList.add('bg-green-500', 'ring-green-200');
                    break;
            }
        }

        if (this.hasStatusTextTarget) {
            if (this.isTerminus) {
                // Pour les terminus, on affiche avec le point devant
                const texts = ['', '· 🚶 Passé', '· ✅ Visité'];
                const colors = ['hidden', 'text-blue-600 font-medium', 'text-green-600 font-medium'];
                this.statusTextTarget.textContent = texts[state];
                this.statusTextTarget.className = `text-xs sm:text-sm ${colors[state]}`;
            } else {
                const texts = ['Touchez le cercle', '🚶 Passé', '✅ Visité'];
                const colors = ['text-gray-400', 'text-blue-600 font-medium', 'text-green-600 font-medium'];
                this.statusTextTarget.textContent = texts[state];
                this.statusTextTarget.className = `text-xs sm:text-sm ${colors[state]}`;
            }
        }

        if (this.hasMobileIconTarget) {
            const icons = ['○', '🚶', '✅'];
            this.mobileIconTarget.textContent = icons[state];
        }
    }

    updateStats(oldPassed, oldStopped, newPassed, newStopped) {
        const statsEl = document.getElementById('line-stats');
        if (!statsEl) return;

        let passed = parseInt(statsEl.dataset.passed);
        let stopped = parseInt(statsEl.dataset.stopped);
        const total = parseInt(statsEl.dataset.total);

        // Décrémenter si on avait et on n'a plus
        if (oldPassed && !newPassed) passed--;
        if (oldStopped && !newStopped) stopped--;

        // Incrémenter si on n'avait pas et on a maintenant
        if (!oldPassed && newPassed) passed++;
        if (!oldStopped && newStopped) stopped++;

        // S'assurer qu'on ne descend pas en dessous de 0
        passed = Math.max(0, passed);
        stopped = Math.max(0, stopped);

        // Mettre à jour les data attributes pour les prochains updates
        statsEl.dataset.passed = passed;
        statsEl.dataset.stopped = stopped;

        // Mettre à jour l'affichage
        const passedCount = document.getElementById('passed-count');
        const stoppedCount = document.getElementById('stopped-count');
        const passedPercentage = document.getElementById('passed-percentage');
        const stoppedPercentage = document.getElementById('stopped-percentage');
        const passedBar = document.getElementById('passed-bar');
        const stoppedBar = document.getElementById('stopped-bar');

        if (passedCount) passedCount.textContent = passed;
        if (stoppedCount) stoppedCount.textContent = stopped;

        const passedPct = total > 0 ? Math.round((passed / total) * 100) : 0;
        const stoppedPct = total > 0 ? Math.round((stopped / total) * 100) : 0;

        if (passedPercentage) passedPercentage.textContent = passedPct;
        if (stoppedPercentage) stoppedPercentage.textContent = stoppedPct;

        // Limiter entre 4% et 96% pour l'emoji
        if (passedBar) passedBar.style.width = `${Math.max(Math.min(passedPct, 96), 4)}%`;
        if (stoppedBar) stoppedBar.style.width = `${Math.max(Math.min(stoppedPct, 96), 4)}%`;
    }

    showBadges(badges) {
        badges.forEach((badge, index) => {
            setTimeout(() => {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 flex items-center gap-3';
                toast.innerHTML = `<span class="text-4xl">${badge.icon}</span><div><p class="font-bold text-lg">Nouveau badge !</p><p class="text-sm">${badge.name}</p></div>`;
                document.body.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.3s';
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            }, index * 600);
        });
    }
}
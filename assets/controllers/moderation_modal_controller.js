// assets/controllers/moderation_modal_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = [
        'warnModal', 'warnUserId', 'warnPostId', 'warnDiscussionId', 'warnUsername',
        'banModal', 'banUserId', 'banUsername'
    ]

    // ========== AVERTISSEMENT ==========
    
    openWarn(event) {
        const { userId, postId, discussionId, username } = event.params
        
        this.warnUserIdTarget.value = userId
        this.warnPostIdTarget.value = postId || ''
        this.warnUsernameTarget.textContent = username
        
        // Nouveau : discussion ID (optionnel)
        if (this.hasWarnDiscussionIdTarget) {
            this.warnDiscussionIdTarget.value = discussionId || ''
        }
        
        this.warnModalTarget.classList.remove('hidden')
    }

    closeWarn() {
        this.warnModalTarget.classList.add('hidden')
    }

    // ========== BANNISSEMENT ==========
    
    openBan(event) {
        const { userId, username } = event.params
        
        this.banUserIdTarget.value = userId
        this.banUsernameTarget.textContent = username
        this.banModalTarget.classList.remove('hidden')
    }

    closeBan() {
        this.banModalTarget.classList.add('hidden')
    }

    // ========== FERMETURE PAR CLIC EXTÉRIEUR ==========
    
    clickOutside(event) {
        if (event.target === this.warnModalTarget) {
            this.closeWarn()
        }
        if (event.target === this.banModalTarget) {
            this.closeBan()
        }
    }
}
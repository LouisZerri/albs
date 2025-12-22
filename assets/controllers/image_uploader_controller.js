import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input', 'previews', 'dropzone']
    static values = {
        maxFiles: { type: Number, default: 3 },
        maxSize: { type: Number, default: 5242880 } // 5 Mo
    }

    connect() {
        this.files = []
    }

    handleFiles(event) {
        this.addFiles(event.target.files)
    }

    handleDrop(event) {
        event.preventDefault()
        this.dropzoneTarget.classList.remove('border-blue-500', 'bg-blue-50')
        this.addFiles(event.dataTransfer.files)
    }

    dragOver(event) {
        event.preventDefault()
        this.dropzoneTarget.classList.add('border-blue-500', 'bg-blue-50')
    }

    dragLeave(event) {
        event.preventDefault()
        this.dropzoneTarget.classList.remove('border-blue-500', 'bg-blue-50')
    }

    addFiles(fileList) {
        for (const file of fileList) {
            if (this.files.length >= this.maxFilesValue) {
                alert(`Maximum ${this.maxFilesValue} images autorisées`)
                break
            }
            if (!file.type.startsWith('image/')) {
                alert('Seules les images sont autorisées')
                continue
            }
            if (file.size > this.maxSizeValue) {
                alert('Image trop volumineuse (max 5 Mo)')
                continue
            }

            this.files.push(file)
            this.createPreview(file, this.files.length - 1)
        }
        this.updateInputFiles()
    }

    createPreview(file, index) {
        const reader = new FileReader()
        reader.onload = (e) => {
            const wrapper = document.createElement('div')
            wrapper.className = 'relative inline-block'
            wrapper.dataset.index = index
            wrapper.innerHTML = `
                <img src="${e.target.result}" class="w-20 h-20 object-cover rounded border">
                <button type="button" 
                        data-action="image-uploader#removeImage"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs hover:bg-red-600 flex items-center justify-center">
                    ×
                </button>
            `
            this.previewsTarget.appendChild(wrapper)
        }
        reader.readAsDataURL(file)
    }

    removeImage(event) {
        const wrapper = event.currentTarget.closest('[data-index]')
        const index = parseInt(wrapper.dataset.index)
        
        // Supprimer le fichier
        this.files.splice(index, 1)
        
        // Supprimer la prévisualisation
        wrapper.remove()
        
        // Réindexer les previews restantes
        this.previewsTarget.querySelectorAll('[data-index]').forEach((el, i) => {
            el.dataset.index = i
        })
        
        this.updateInputFiles()
    }

    updateInputFiles() {
        const dataTransfer = new DataTransfer()
        this.files.forEach(f => dataTransfer.items.add(f))
        this.inputTarget.files = dataTransfer.files
    }
}
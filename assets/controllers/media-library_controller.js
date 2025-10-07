import { Controller } from "@hotwired/stimulus";
import { MediaService } from "../services/media-service";

export default class extends Controller {
  static targets = [
    "imageGrid",
    "emptyState",
    "dropzone",
    "fileInput",
    "uploadProgress",
    "progressBar",
    "progressText",
    "deleteModal",
  ];

  connect() {
    this.imageToDelete = null;
    this.loadImages();
    this.dropzoneTarget.addEventListener("click", (e) => {
      if (e.target === this.fileInputTarget) return;
      this.fileInputTarget.click();
    });
  }

  async loadImages() {
    try {
      const images = await MediaService.loadImages();

      this.imageGridTarget.innerHTML = "";

      if (images.length === 0) {
        this.imageGridTarget.appendChild(this.emptyStateTarget);
        this.emptyStateTarget.classList.remove("hidden");
        return;
      }

      this.emptyStateTarget.classList.add("hidden");

      images.forEach((image) => {
        const card = this.createImageCard(image);
        this.imageGridTarget.appendChild(card);
      });
    } catch (error) {
      if (process.env.NODE_ENV === "development") {
        console.error("Erreur lors du chargement des images:", error);
      }
      this.showFlash(error.message || "Erreur lors du chargement des images", "error");
    }
  }

  createImageCard(image) {
    const card = document.createElement("div");
    card.className = "card bg-base-100 shadow-sm border border-base-300 hover:shadow-md transition-shadow";
    card.dataset.filename = image.filename;

    const sizeInMB = MediaService.formatFileSize(image.size);
    const date = MediaService.formatDate(image.modified);

    card.innerHTML = `
            <figure class="aspect-video bg-base-200">
                <img src="${image.url}" alt="${image.filename}" class="w-full h-full object-cover">
            </figure>
            <div class="card-body p-4">
                <h3 class="text-sm font-medium truncate" title="${image.filename}">${image.filename}</h3>
                <div class="text-xs text-base-content/60 space-y-1">
                    <p>${sizeInMB} MB</p>
                    <p>${date}</p>
                </div>
                <div class="card-actions justify-end mt-2">
                    <button
                        class="btn btn-sm btn-error btn-outline"
                        data-action="click->media-library#openDeleteModal"
                        data-filename="${image.filename}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Supprimer
                    </button>
                </div>
            </div>
        `;

    return card;
  }

  triggerFileInput(event) {
    event.preventDefault();
    this.fileInputTarget.click();
  }

  handleDragOver(event) {
    event.preventDefault();
    this.dropzoneTarget.classList.add("border-primary", "bg-primary/5");
  }

  handleDragLeave(event) {
    event.preventDefault();
    this.dropzoneTarget.classList.remove("border-primary", "bg-primary/5");
  }

  handleDrop(event) {
    event.preventDefault();
    this.dropzoneTarget.classList.remove("border-primary", "bg-primary/5");

    const files = event.dataTransfer.files;
    if (files.length > 0) {
      this.uploadFiles(Array.from(files));
    }
  }

  handleFileSelect(event) {
    const files = event.target.files;
    if (files.length > 0) {
      this.uploadFiles(Array.from(files));
    }
    event.target.value = "";
  }

  async uploadFiles(files) {
    const validFiles = files.filter((file) => {
      const validation = MediaService.validateFile(file);
      if (!validation.valid) {
        this.showFlash(`${file.name}: ${validation.error}`, "error");
        return false;
      }
      return true;
    });

    if (validFiles.length === 0) return;

    this.uploadProgressTarget.style.display = "block";
    let completed = 0;

    for (const file of validFiles) {
      try {
        await MediaService.uploadImage(file);
        completed++;

        this.progressBarTarget.value = (completed / validFiles.length) * 100;
        this.progressTextTarget.textContent = `${completed} / ${validFiles.length} fichier(s) uploadé(s)`;
      } catch (error) {
        if (process.env.NODE_ENV === "development") {
          console.error(`Erreur upload ${file.name}:`, error);
        }
        this.showFlash(`Erreur lors de l'upload de ${file.name}`, "error");
      }
    }

    this.uploadProgressTarget.style.display = "none";
    this.progressBarTarget.value = 0;
    this.showFlash(`${completed} image(s) ajoutée(s) avec succès`, "success");

    await this.loadImages();
  }

  openDeleteModal(event) {
    this.imageToDelete = event.currentTarget.dataset.filename;
    this.deleteModalTarget.showModal();
  }

  closeDeleteModal() {
    this.deleteModalTarget.close();
    this.imageToDelete = null;
  }

  async confirmDelete() {
    if (!this.imageToDelete) return;

    try {
      await MediaService.deleteImage(this.imageToDelete);

      this.showFlash("Image supprimée avec succès", "success");
      const card = this.imageGridTarget.querySelector(`[data-filename="${this.imageToDelete}"]`);
      if (card) {
        card.remove();
      }

      if (this.imageGridTarget.children.length === 0) {
        this.imageGridTarget.appendChild(this.emptyStateTarget);
        this.emptyStateTarget.classList.remove("hidden");
      }
    } catch (error) {
      if (process.env.NODE_ENV === "development") {
        console.error("Erreur suppression:", error);
      }
      this.showFlash(error.message || "Erreur lors de la suppression", "error");
    } finally {
      this.closeDeleteModal();
    }
  }

  showFlash(message, type = "info") {
    const container = document.getElementById("flash-container");
    if (!container) return;

    const alertClass = type === "success" ? "alert-success" : type === "error" ? "alert-error" : "alert-info";

    const icon =
      type === "success"
        ? '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';

    const alert = document.createElement("div");
    alert.className = `alert ${alertClass} mb-4`;
    alert.innerHTML = `
            ${icon}
            <span>${message}</span>
        `;

    container.appendChild(alert);

    setTimeout(() => {
      alert.remove();
    }, 5000);
  }
}

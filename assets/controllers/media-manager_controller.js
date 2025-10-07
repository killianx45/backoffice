import { Controller } from "@hotwired/stimulus";
import { MediaService } from "../services/media-service";

export default class extends Controller {
  static targets = ["preview"];
  static values = {
    type: String,
  };

  connect() {
    this.selectedImage = null;
    this.editorCallback = null;
    this.modalElement = null;

    window.addEventListener("ckeditor:open-media-manager", (e) => {
      this.editorCallback = e.detail.callback;
      this.openModal();
    });
  }

  disconnect() {
    this.destroyModal();
    window.removeEventListener("ckeditor:open-media-manager", this.openModal);
  }

  openModal() {
    if (!this.modalElement) {
      this.createModal();
    }

    this.modalElement.showModal();
    this.loadImages();
  }

  closeModal() {
    if (this.modalElement) {
      this.modalElement.close();
      this.selectedImage = null;
      this.editorCallback = null;

      const gallery = this.modalElement.querySelector("[data-gallery]");
      if (gallery) {
        gallery.querySelectorAll("[data-image-url]").forEach((card) => {
          card.classList.remove("border-primary", "ring-2", "ring-primary");
        });
      }
    }
  }

  createModal() {
    const modalHTML = `
            <dialog data-media-modal class="modal">
                <div class="modal-box w-11/12 max-w-5xl bg-base-100">
                    <h3 class="font-bold text-lg mb-4">Gestionnaire de médias</h3>

                    <div role="tablist" class="tabs tabs-bordered mb-4">
                        <a role="tab" class="tab tab-active" data-tab="library">Bibliothèque</a>
                        <a role="tab" class="tab" data-tab="upload">Téléverser</a>
                    </div>

                    <div data-tab-content="library">
                        <div data-gallery class="grid grid-cols-4 gap-4 max-h-96 overflow-y-auto p-1">
                            <div class="col-span-4 flex justify-center items-center h-40">
                                <span class="loading loading-spinner loading-lg"></span>
                            </div>
                        </div>
                    </div>

                    <div data-tab-content="upload" class="hidden">
                        <div data-upload-zone
                             class="border-2 border-dashed border-base-300 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 text-base-content/50">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <p class="text-base-content/70">Cliquez ou glissez une image ici</p>
                            <p class="text-sm text-base-content/50 mt-2">PNG, JPG, GIF, WebP (max 5MB)</p>
                            <input type="file"
                                   data-file-input
                                   accept="image/*"
                                   class="hidden">
                        </div>
                    </div>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" data-action-close>Annuler</button>
                        <button type="button" class="btn btn-primary" data-action-select>Insérer</button>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    this.modalElement = document.body.querySelector("[data-media-modal]");

    this.attachEventListeners();
  }

  attachEventListeners() {
    if (!this.modalElement) return;

    const tabButtons = this.modalElement.querySelectorAll("[data-tab]");
    tabButtons.forEach((btn) => {
      btn.addEventListener("click", (e) => this.switchTab(e));
    });

    const closeBtn = this.modalElement.querySelector("[data-action-close]");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => this.closeModal());
    }

    const selectBtn = this.modalElement.querySelector("[data-action-select]");
    if (selectBtn) {
      selectBtn.addEventListener("click", () => this.selectImage());
    }

    const uploadZone = this.modalElement.querySelector("[data-upload-zone]");
    const fileInput = this.modalElement.querySelector("[data-file-input]");

    if (uploadZone && fileInput) {
      uploadZone.addEventListener("click", () => fileInput.click());
      uploadZone.addEventListener("dragover", (e) => this.handleDragOver(e, uploadZone));
      uploadZone.addEventListener("dragleave", (e) => this.handleDragLeave(e, uploadZone));
      uploadZone.addEventListener("drop", (e) => this.handleDrop(e, uploadZone));
      fileInput.addEventListener("change", (e) => this.handleFileSelect(e));
    }
  }

  switchTab(event) {
    const tab = event.currentTarget.dataset.tab;

    this.modalElement.querySelectorAll('[role="tab"]').forEach((t) => t.classList.remove("tab-active"));
    event.currentTarget.classList.add("tab-active");

    this.modalElement.querySelectorAll("[data-tab-content]").forEach((content) => {
      if (content.dataset.tabContent === tab) {
        content.classList.remove("hidden");
      } else {
        content.classList.add("hidden");
      }
    });
  }

  async loadImages() {
    const gallery = this.modalElement?.querySelector("[data-gallery]");
    if (!gallery) return;

    try {
      const images = await MediaService.loadImages();

      gallery.innerHTML = "";

      if (images.length === 0) {
        gallery.innerHTML = `
                    <div class="col-span-4 text-center py-8">
                        <p class="text-base-content/50">Aucune image disponible</p>
                    </div>
                `;
        return;
      }

      images.forEach((image) => {
        const imageCard = document.createElement("div");
        imageCard.className =
          "aspect-square bg-base-200 rounded-box overflow-hidden cursor-pointer border-2 border-transparent hover:border-primary transition-all";
        imageCard.dataset.imageUrl = image.url;
        imageCard.innerHTML = `
                    <img src="${image.url}" alt="${image.filename}" class="w-full h-full object-cover">
                `;
        imageCard.addEventListener("click", (e) => this.selectImageFromGallery(e));
        gallery.appendChild(imageCard);
      });
    } catch (error) {
      if (gallery) {
        gallery.innerHTML = `
                    <div class="col-span-4 text-center py-8">
                        <p class="text-error">${error.message || "Erreur lors du chargement des images"}</p>
                    </div>
                `;
      }
    }
  }

  selectImageFromGallery(event) {
    const gallery = this.modalElement?.querySelector("[data-gallery]");
    if (gallery) {
      gallery.querySelectorAll("[data-image-url]").forEach((card) => {
        card.classList.remove("border-primary", "ring-2", "ring-primary");
      });
    }

    event.currentTarget.classList.add("border-primary", "ring-2", "ring-primary");
    this.selectedImage = event.currentTarget.dataset.imageUrl;
  }

  selectImage() {
    if (!this.selectedImage) {
      alert("Veuillez sélectionner une image");
      return;
    }

    if (this.editorCallback) {
      this.editorCallback(this.selectedImage);
    } else if (this.hasPreviewTarget) {
      const filename = this.selectedImage.split("/").pop();

      this.previewTarget.innerHTML = `
                <img src="${this.selectedImage}" alt="Image sélectionnée" class="h-20 rounded-box object-cover">
            `;

      const hiddenInput = this.element.querySelector('input[name*="[featuredImage]"]');
      if (hiddenInput) {
        hiddenInput.value = filename;
      }
    }

    this.closeModal();
  }

  handleDragOver(event, uploadZone) {
    event.preventDefault();
    uploadZone.classList.add("border-primary", "bg-primary/5");
  }

  handleDragLeave(event, uploadZone) {
    event.preventDefault();
    uploadZone.classList.remove("border-primary", "bg-primary/5");
  }

  handleDrop(event, uploadZone) {
    event.preventDefault();
    uploadZone.classList.remove("border-primary", "bg-primary/5");

    const files = event.dataTransfer.files;
    if (files.length > 0) {
      this.uploadFile(files[0]);
    }
  }

  handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
      this.uploadFile(file);
    }
  }

  async uploadFile(file) {
    const uploadZone = this.modalElement?.querySelector("[data-upload-zone]");
    if (!uploadZone) return;

    const validation = MediaService.validateFile(file);
    if (!validation.valid) {
      alert(validation.error);
      return;
    }

    try {
      uploadZone.innerHTML = `
                <span class="loading loading-spinner loading-lg"></span>
                <p class="mt-4">Téléversement en cours...</p>
            `;

      const data = await MediaService.uploadImage(file);

      this.selectedImage = data.url;
      await this.loadImages();

      const libraryTab = this.modalElement.querySelector('[data-tab="library"]');
      if (libraryTab) {
        libraryTab.click();
      }

      const gallery = this.modalElement.querySelector("[data-gallery]");
      const uploadedCard = gallery?.querySelector(`[data-image-url="${data.url}"]`);
      if (uploadedCard) {
        uploadedCard.classList.add("border-primary", "ring-2", "ring-primary");
      }
    } catch (error) {
      alert(error.message || "Erreur lors du téléversement du fichier");
    } finally {
      if (uploadZone) {
        uploadZone.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 text-base-content/50">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-base-content/70">Cliquez ou glissez une image ici</p>
                    <p class="text-sm text-base-content/50 mt-2">PNG, JPG, GIF, WebP (max 5MB)</p>
                `;
      }
    }
  }

  destroyModal() {
    if (this.modalElement) {
      this.modalElement.remove();
      this.modalElement = null;
    }
  }
}

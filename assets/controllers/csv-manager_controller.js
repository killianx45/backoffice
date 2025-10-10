import { Controller } from "@hotwired/stimulus";
import { CSVService } from "../services/csv-service";

export default class extends Controller {
  static targets = ["preview"];

  connect() {
    this.selectedCSV = null;
    this.selectedChartType = "bar";
    this.modal = null;
  }

  openModal() {
    if (!this.modal) {
      this.createModal();
    }
    this.modal.showModal();
    this.loadCSVFiles();
  }

  createModal() {
    const modal = document.createElement("dialog");
    modal.className = "modal";

    modal.innerHTML = `
            <div class="modal-box max-w-4xl">
                <h3 class="font-bold text-lg mb-4">Sélectionner un fichier CSV</h3>

                <div role="tablist" class="tabs tabs-bordered">
                    <input type="radio" name="csv_tabs" role="tab" class="tab" aria-label="Bibliothèque" checked />
                    <div role="tabpanel" class="tab-content pt-4">
                        <div id="csv-library-list" class="grid grid-cols-2 sm:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                            <div class="flex items-center justify-center col-span-full h-40">
                                <div class="loading loading-spinner loading-lg"></div>
                            </div>
                        </div>
                    </div>

                    <input type="radio" name="csv_tabs" role="tab" class="tab" aria-label="Upload" />
                    <div role="tabpanel" class="tab-content pt-4">
                        <div class="border-2 border-dashed border-base-300 rounded-lg p-8 text-center cursor-pointer" id="csv-dropzone">
                            <input
                                type="file"
                                accept=".csv,text/csv"
                                class="hidden"
                                id="csv-file-input"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-lg font-medium mb-2">Glissez-déposez un fichier CSV ici</p>
                            <p class="text-sm text-base-content/60">ou cliquez pour sélectionner</p>
                        </div>
                        <div id="upload-progress" class="hidden mt-4">
                            <progress class="progress progress-primary w-full"></progress>
                        </div>
                    </div>
                </div>

                <div class="form-control mt-4">
                    <label class="label">
                        <span class="label-text">Type de graphique</span>
                    </label>
                    <select class="select select-bordered" id="csv-chart-type-select">
                        <option value="bar">Barres</option>
                        <option value="line">Ligne</option>
                    </select>
                </div>

                <div class="modal-action">
                    <button class="btn" id="csv-cancel-btn">Annuler</button>
                    <button class="btn btn-primary" id="csv-select-btn">Sélectionner</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button type="button">close</button>
            </form>
        `;

    document.body.appendChild(modal);
    this.modal = modal;

    const dropzone = modal.querySelector("#csv-dropzone");
    const fileInput = modal.querySelector("#csv-file-input");
    const chartTypeSelect = modal.querySelector("#csv-chart-type-select");
    const cancelBtn = modal.querySelector("#csv-cancel-btn");
    const selectBtn = modal.querySelector("#csv-select-btn");
    const backdrop = modal.querySelector(".modal-backdrop button");

    dropzone.addEventListener("click", () => fileInput.click());
    fileInput.addEventListener("change", (e) => this.handleFileUpload(e));
    chartTypeSelect.addEventListener("change", (e) => {
      this.selectedChartType = e.target.value;
    });
    cancelBtn.addEventListener("click", () => this.closeModal());
    selectBtn.addEventListener("click", (e) => this.selectCSV(e));
    backdrop.addEventListener("click", () => this.closeModal());
  }

  async loadCSVFiles() {
    const listContainer = this.modal.querySelector("#csv-library-list");

    try {
      const csvFiles = await CSVService.loadCSVFiles();

      if (csvFiles.length === 0) {
        listContainer.innerHTML = `
                    <div class="col-span-full text-center text-base-content/60 py-8">
                        <p>Aucun fichier CSV disponible</p>
                    </div>
                `;
        return;
      }

      listContainer.innerHTML = "";

      csvFiles.forEach((csv) => {
        const card = document.createElement("div");
        card.className =
          "card bg-base-100 border-2 border-base-300 cursor-pointer hover:border-primary transition-colors";
        card.dataset.filename = csv.filename;
        card.dataset.url = csv.url;

        card.innerHTML = `
                    <div class="card-body p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-success mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h4 class="text-xs font-medium text-center truncate" title="${csv.filename}">${
          csv.filename
        }</h4>
                        <p class="text-xs text-center text-base-content/60">${CSVService.formatFileSize(csv.size)}</p>
                    </div>
                `;

        card.addEventListener("click", () => {
          listContainer.querySelectorAll(".card").forEach((c) => {
            c.classList.remove("border-primary", "bg-primary/10");
          });
          card.classList.add("border-primary", "bg-primary/10");
          this.selectedCSV = csv;
        });

        listContainer.appendChild(card);
      });
    } catch (error) {
      console.error("Erreur chargement CSV:", error);
      listContainer.innerHTML = `
                <div class="col-span-full text-center text-error py-8">
                    <p>Erreur lors du chargement des fichiers</p>
                </div>
            `;
    }
  }

  async handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const progressContainer = this.modal.querySelector("#upload-progress");
    progressContainer.classList.remove("hidden");

    try {
      const result = await CSVService.uploadCSV(file);
      progressContainer.classList.add("hidden");

      this.selectedCSV = {
        filename: result.filename,
        url: result.url,
      };

      this.modal.querySelector('input[name="csv_tabs"]').checked = true;
      await this.loadCSVFiles();

      alert("Fichier uploadé avec succès !");
    } catch (error) {
      console.error("Erreur upload:", error);
      alert(error.message || "Erreur lors de l'upload");
      progressContainer.classList.add("hidden");
    }
  }

  selectCSV(event) {
    event.preventDefault();

    if (!this.selectedCSV) {
      alert("Veuillez sélectionner un fichier CSV");
      return;
    }

    if (this.hasPreviewTarget) {
      const filename = this.selectedCSV.filename;

      this.previewTarget.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm">${filename}</span>
                    <span class="badge badge-sm">${this.selectedChartType}</span>
                </div>
            `;

      const form = this.element.closest("form") || document.querySelector('form[name^="article"]');
      const chartContainer = this.element;
      const csvFileInput = chartContainer.querySelector('input[id$="_csvFile"]');
      if (csvFileInput) {
        csvFileInput.value = filename;
      }

      const chartTypeInput = chartContainer.querySelector('select[id$="_chartType"]');
      if (chartTypeInput) {
        chartTypeInput.value = this.selectedChartType;
      }
    }

    this.closeModal();
  }

  closeModal() {
    if (this.modal) {
      this.modal.close();
    }
  }

  disconnect() {
    if (this.modal) {
      this.modal.remove();
      this.modal = null;
    }
  }
}

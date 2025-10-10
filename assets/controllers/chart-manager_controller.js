import { Controller } from "@hotwired/stimulus";
import { CSVService } from "../services/csv-service";
import { ChartValidator } from "../utils/chart-validator";

export default class extends Controller {
  connect() {
    this.selectedDataset = null;
    this.datasetVariables = [];
    this.selectedVariables = [];
    this.chartConfig = {
      datasetId: null,
      chartType: "bar",
      title: "",
      selectedVariables: [],
      xAxisLabel: "",
      yAxisLabel: "",
    };
    this.editorCallback = null;
    this.modalElement = null;
    this.isEditMode = false;
    this.editingElement = null;

    this.handleChartManagerEvent = this.handleChartManagerEvent.bind(this);
    window.addEventListener("ckeditor:open-chart-manager", this.handleChartManagerEvent);
  }

  disconnect() {
    this.destroyModal();
    window.removeEventListener("ckeditor:open-chart-manager", this.handleChartManagerEvent);
  }

  handleChartManagerEvent(e) {
    this.editorCallback = e.detail.callback;
    this.isEditMode = e.detail.isEditMode || false;
    this.editingElement = e.detail.element || null;

    if (this.isEditMode && this.editingElement) {
      this.loadExistingChartData(this.editingElement);
    }

    this.openModal();
  }

  async loadExistingChartData(element) {
    const datasetId = element.getAttribute("data-dataset-id");
    const chartType = element.getAttribute("data-chart-type");
    const title = element.getAttribute("data-title");
    const selectedVariablesStr = element.getAttribute("data-selected-variables");
    const xAxisLabel = element.getAttribute("data-x-axis-label");
    const yAxisLabel = element.getAttribute("data-y-axis-label");

    this.chartConfig = {
      datasetId: datasetId ? parseInt(datasetId) : null,
      chartType: chartType || "bar",
      title: title || "",
      selectedVariables: selectedVariablesStr ? JSON.parse(selectedVariablesStr) : [],
      xAxisLabel: xAxisLabel || "",
      yAxisLabel: yAxisLabel || "",
    };

    if (this.chartConfig.datasetId) {
      try {
        const response = await fetch(`/api/datasets/${this.chartConfig.datasetId}`);
        const data = await response.json();
        this.selectedDataset = data.dataset;
        this.datasetVariables = data.dataset.variables;
        this.selectedVariables = this.chartConfig.selectedVariables;
      } catch (error) {
        console.error("Error loading dataset:", error);
      }
    }
  }

  openModal() {
    if (!this.modalElement) {
      this.createModal();
    }

    if (this.isEditMode) {
      this.populateFormWithExistingData();
    } else {
      this.resetForm();
    }

    this.modalElement.showModal();
    this.loadDatasets();
  }

  populateFormWithExistingData() {
    if (!this.modalElement) return;

    const chartTypeSelect = this.modalElement.querySelector("[data-chart-type]");
    const titleInput = this.modalElement.querySelector("[data-chart-title]");
    const xAxisInput = this.modalElement.querySelector("[data-x-axis]");
    const yAxisInput = this.modalElement.querySelector("[data-y-axis]");

    if (chartTypeSelect) chartTypeSelect.value = this.chartConfig.chartType;
    if (titleInput) titleInput.value = this.chartConfig.title;
    if (xAxisInput) xAxisInput.value = this.chartConfig.xAxisLabel;
    if (yAxisInput) yAxisInput.value = this.chartConfig.yAxisLabel;

    this.updateChartTypeInfo();
    this.updateVariableSelectors();
  }

  closeModal() {
    if (this.modalElement) {
      this.modalElement.close();
    }
  }

  resetForm() {
    this.selectedDataset = null;
    this.datasetVariables = [];
    this.selectedVariables = [];
    this.chartConfig = {
      datasetId: null,
      chartType: "bar",
      title: "",
      selectedVariables: [],
      xAxisLabel: "",
      yAxisLabel: "",
    };

    if (this.modalElement) {
      const titleInput = this.modalElement.querySelector("[data-chart-title]");
      const xAxisInput = this.modalElement.querySelector("[data-x-axis]");
      const yAxisInput = this.modalElement.querySelector("[data-y-axis]");
      const chartTypeSelect = this.modalElement.querySelector("[data-chart-type]");

      if (titleInput) titleInput.value = "";
      if (xAxisInput) xAxisInput.value = "";
      if (yAxisInput) yAxisInput.value = "";
      if (chartTypeSelect) chartTypeSelect.value = "bar";

      this.updateChartTypeInfo();
      this.updateVariableSelectors();
    }
  }

  createModal() {
    const modalHTML = `
            <dialog data-chart-modal class="modal">
                <div class="modal-box w-11/12 max-w-6xl bg-base-100">
                    <h3 class="font-bold text-lg mb-4">📊 Gestionnaire de graphiques</h3>

                    <div role="tablist" class="tabs tabs-bordered mb-4">
                        <a role="tab" class="tab tab-active" data-tab="library">Sélection dataset</a>
                        <a role="tab" class="tab" data-tab="upload">Téléverser CSV</a>
                        <a role="tab" class="tab" data-tab="variables">Variables</a>
                        <a role="tab" class="tab" data-tab="configure">Configuration</a>
                    </div>

                    <div data-tab-content="library">
                        <div data-dataset-gallery class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto p-1">
                            <div class="col-span-full flex justify-center items-center h-40">
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
                            <p class="text-base-content/70">Cliquez ou glissez un fichier CSV ici</p>
                            <p class="text-sm text-base-content/50 mt-2">CSV (max 2MB) - Vous serez redirigé vers la configuration des variables</p>
                            <input type="file"
                                   data-file-input
                                   accept=".csv,text/csv"
                                   class="hidden">
                        </div>
                    </div>

                    <div data-tab-content="variables" class="hidden">
                        <div class="alert alert-info mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <h3 class="font-bold">Sélectionnez d'abord un dataset</h3>
                                <div class="text-xs">Retournez à l'onglet "Sélection dataset" pour choisir un jeu de données</div>
                            </div>
                        </div>
                        <div data-chart-type-info class="mb-4"></div>
                        <div data-variable-selectors class="space-y-4"></div>
                    </div>

                    <div data-tab-content="configure" class="hidden">
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Type de graphique</span>
                                </label>
                                <select data-chart-type class="select select-bordered w-full">
                                    <option value="bar">Barres</option>
                                    <option value="line">Ligne</option>
                                    <option value="pie">Camembert</option>
                                    <option value="scatter">Nuage de points</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Titre du graphique</span>
                                </label>
                                <input data-chart-title type="text" placeholder="Ex: Ventes mensuelles 2024" class="input input-bordered w-full" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Label axe X</span>
                                    </label>
                                    <input data-x-axis type="text" placeholder="Ex: Mois" class="input input-bordered w-full" />
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Label axe Y</span>
                                    </label>
                                    <input data-y-axis type="text" placeholder="Ex: Revenus (€)" class="input input-bordered w-full" />
                                </div>
                            </div>

                            <div data-validation-errors class="hidden"></div>
                        </div>
                    </div>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" data-action-close>Annuler</button>
                        <button type="button" class="btn btn-primary" data-action-insert>
                            ${this.isEditMode ? "Mettre à jour" : "Insérer"}
                        </button>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    this.modalElement = document.body.querySelector("[data-chart-modal]");

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

    const insertBtn = this.modalElement.querySelector("[data-action-insert]");
    if (insertBtn) {
      insertBtn.addEventListener("click", () => this.insertChart());
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

    const chartTypeSelect = this.modalElement.querySelector("[data-chart-type]");
    const titleInput = this.modalElement.querySelector("[data-chart-title]");
    const xAxisInput = this.modalElement.querySelector("[data-x-axis]");
    const yAxisInput = this.modalElement.querySelector("[data-y-axis]");

    if (chartTypeSelect) {
      chartTypeSelect.addEventListener("change", (e) => {
        this.chartConfig.chartType = e.target.value;
        this.updateChartTypeInfo();
        this.updateVariableSelectors();
      });
    }

    if (titleInput) {
      titleInput.addEventListener("input", (e) => {
        this.chartConfig.title = e.target.value;
      });
    }

    if (xAxisInput) {
      xAxisInput.addEventListener("input", (e) => {
        this.chartConfig.xAxisLabel = e.target.value;
      });
    }

    if (yAxisInput) {
      yAxisInput.addEventListener("input", (e) => {
        this.chartConfig.yAxisLabel = e.target.value;
      });
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

  async loadDatasets() {
    const gallery = this.modalElement?.querySelector("[data-dataset-gallery]");
    if (!gallery) return;

    try {
      const response = await fetch("/api/datasets");
      const data = await response.json();
      const datasets = data.datasets;

      gallery.innerHTML = "";

      if (datasets.length === 0) {
        gallery.innerHTML = `
                    <div class="col-span-full text-center py-8">
                        <p class="text-base-content/50">Aucun dataset configuré disponible</p>
                        <p class="text-sm text-base-content/40 mt-2">Les fournisseurs doivent uploader et configurer des fichiers CSV</p>
                    </div>
                `;
        return;
      }

      datasets.forEach((dataset) => {
        const datasetCard = document.createElement("div");
        datasetCard.className =
          "card bg-base-100 border-2 border-base-300 cursor-pointer hover:border-primary transition-all";
        datasetCard.dataset.datasetId = dataset.id;
        datasetCard.innerHTML = `
                    <div class="card-body p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-success mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h4 class="text-xs font-medium text-center truncate" title="${dataset.originalName}">${dataset.originalName}</h4>
                        <div class="text-xs text-center space-y-1">
                            <p class="text-base-content/60">${dataset.rowCount} lignes</p>
                            <p class="text-base-content/60">${dataset.variableCount} variables</p>
                            <span class="badge badge-success badge-sm">Configuré</span>
                        </div>
                    </div>
                `;
        datasetCard.addEventListener("click", (e) => this.selectDataset(dataset.id));
        gallery.appendChild(datasetCard);

        if (this.isEditMode && dataset.id === this.chartConfig.datasetId) {
          datasetCard.classList.add("border-primary", "ring-2", "ring-primary");
        }
      });
    } catch (error) {
      if (gallery) {
        gallery.innerHTML = `
                    <div class="col-span-full text-center py-8">
                        <p class="text-error">${error.message || "Erreur lors du chargement des datasets"}</p>
                    </div>
                `;
      }
    }
  }

  async selectDataset(datasetId) {
    try {
      const response = await fetch(`/api/datasets/${datasetId}`);
      const data = await response.json();
      this.selectedDataset = data.dataset;
      this.datasetVariables = data.dataset.variables;
      this.chartConfig.datasetId = datasetId;

      const gallery = this.modalElement?.querySelector("[data-dataset-gallery]");
      if (gallery) {
        gallery.querySelectorAll("[data-dataset-id]").forEach((card) => {
          card.classList.remove("border-primary", "ring-2", "ring-primary");
        });
        const selectedCard = gallery.querySelector(`[data-dataset-id="${datasetId}"]`);
        if (selectedCard) {
          selectedCard.classList.add("border-primary", "ring-2", "ring-primary");
        }
      }

      const variablesTab = this.modalElement.querySelector('[data-tab="variables"]');
      if (variablesTab) {
        variablesTab.click();
      }

      this.updateChartTypeInfo();
      this.updateVariableSelectors();
    } catch (error) {
      console.error("Error selecting dataset:", error);
      alert("Erreur lors de la sélection du dataset");
    }
  }

  updateChartTypeInfo() {
    const infoContainer = this.modalElement?.querySelector("[data-chart-type-info]");
    if (!infoContainer || !this.selectedDataset) return;

    const requirements = ChartValidator.getRequiredVariablesForChartType(this.chartConfig.chartType);
    const chartLabel = ChartValidator.getChartTypeLabel(this.chartConfig.chartType);

    infoContainer.innerHTML = `
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h3 class="font-bold">${chartLabel}</h3>
                    <div class="text-xs space-y-1">
                        ${requirements.map(req => `<div>• ${req.description}</div>`).join('')}
                    </div>
                </div>
            </div>
        `;
  }

  updateVariableSelectors() {
    const selectorsContainer = this.modalElement?.querySelector("[data-variable-selectors]");
    if (!selectorsContainer || !this.selectedDataset) return;

    const requirements = ChartValidator.getRequiredVariablesForChartType(this.chartConfig.chartType);

    selectorsContainer.innerHTML = "";

    requirements.forEach((req) => {
      const selectorDiv = document.createElement("div");
      selectorDiv.className = "form-control";

      const allowMultiple = typeof req.count === 'object' && req.count.min;
      const filteredVars = this.datasetVariables.filter(v => v.type === req.type);

      selectorDiv.innerHTML = `
                <label class="label">
                    <span class="label-text font-semibold">${req.description}</span>
                    <span class="label-text-alt badge ${req.type === 'numeric' ? 'badge-primary' : 'badge-secondary'}">
                        ${req.type === 'numeric' ? 'Numérique' : 'Catégorielle'}
                    </span>
                </label>
                <select class="select select-bordered" data-role="${req.role}" ${allowMultiple ? 'multiple size="3"' : ''}>
                    ${!allowMultiple ? '<option value="">Sélectionner une variable</option>' : ''}
                    ${filteredVars.map(v => `<option value="${v.id}">${v.name}</option>`).join('')}
                </select>
            `;

      const select = selectorDiv.querySelector("select");
      select.addEventListener("change", () => this.updateSelectedVariables());

      selectorsContainer.appendChild(selectorDiv);
    });

    if (this.isEditMode && this.selectedVariables.length > 0) {
      this.selectedVariables.forEach(sv => {
        const select = selectorsContainer.querySelector(`[data-role="${sv.role}"]`);
        if (select) {
          if (select.multiple) {
            Array.from(select.options).forEach(opt => {
              if (parseInt(opt.value) === sv.variableId) {
                opt.selected = true;
              }
            });
          } else {
            select.value = sv.variableId;
          }
        }
      });
    }

    this.updateSelectedVariables();
  }

  updateSelectedVariables() {
    const selectorsContainer = this.modalElement?.querySelector("[data-variable-selectors]");
    if (!selectorsContainer) return;

    this.selectedVariables = [];

    const selects = selectorsContainer.querySelectorAll("select");
    selects.forEach((select) => {
      const role = select.dataset.role;
      if (select.multiple) {
        Array.from(select.selectedOptions).forEach(option => {
          if (option.value) {
            const variable = this.datasetVariables.find(v => v.id === parseInt(option.value));
            this.selectedVariables.push({
              variableId: parseInt(option.value),
              name: variable.name,
              role: role
            });
          }
        });
      } else {
        if (select.value) {
          const variable = this.datasetVariables.find(v => v.id === parseInt(select.value));
          this.selectedVariables.push({
            variableId: parseInt(select.value),
            name: variable.name,
            role: role
          });
        }
      }
    });

    this.chartConfig.selectedVariables = this.selectedVariables;
  }

  insertChart() {
    const validation = ChartValidator.validate(
      this.chartConfig.chartType,
      this.selectedVariables,
      this.datasetVariables
    );

    if (!validation.valid) {
      this.showValidationErrors(validation.errors);
      return;
    }

    if (!this.chartConfig.title.trim()) {
      alert("Veuillez entrer un titre pour le graphique");
      return;
    }

    if (this.editorCallback) {
      this.editorCallback(this.chartConfig, this.isEditMode, this.editingElement);
    }

    this.closeModal();
  }

  showValidationErrors(errors) {
    const errorsContainer = this.modalElement?.querySelector("[data-validation-errors]");
    if (!errorsContainer) return;

    errorsContainer.innerHTML = `
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <h3 class="font-bold">Erreurs de configuration</h3>
                    <ul class="list-disc list-inside text-sm">
                        ${errors.map(err => `<li>${err}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
    errorsContainer.classList.remove("hidden");

    setTimeout(() => {
      errorsContainer.classList.add("hidden");
    }, 5000);
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

    if (!file.name.endsWith(".csv")) {
      alert("Veuillez sélectionner un fichier CSV");
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      alert("Le fichier ne doit pas dépasser 2MB");
      return;
    }

    try {
      uploadZone.innerHTML = `
                <span class="loading loading-spinner loading-lg"></span>
                <p class="mt-4">Téléversement en cours...</p>
            `;

      const data = await CSVService.uploadCSV(file);

      if (data.datasetId) {
        this.closeModal();
        window.location.href = `/api/csv/dataset/${data.datasetId}/configure`;
        return;
      }
    } catch (error) {
      alert(error.message || "Erreur lors du téléversement du fichier");
    } finally {
      if (uploadZone) {
        uploadZone.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 text-base-content/50">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-base-content/70">Cliquez ou glissez un fichier CSV ici</p>
                    <p class="text-sm text-base-content/50 mt-2">CSV (max 2MB) - Vous serez redirigé vers la configuration des variables</p>
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

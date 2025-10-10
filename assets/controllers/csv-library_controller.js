import { Controller } from '@hotwired/stimulus';
import { CSVService } from '../services/csv-service';

export default class extends Controller {
    static targets = [
        'csvGrid',
        'dropzone',
        'fileInput',
        'uploadProgress',
        'progressBar',
        'progressText',
        'progressPercent',
        'deleteModal',
        'previewModal',
        'previewContent'
    ];

    connect() {
        this.currentFileToDelete = null;
        this.loadCSVFiles();
    }

    async loadCSVFiles() {
        try {
            const response = await fetch('/api/datasets/all');
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des datasets');
            }
            const data = await response.json();
            const datasets = data.datasets;

            this.csvGridTarget.innerHTML = '';

            if (datasets.length === 0) {
                this.showEmptyState();
            } else {
                datasets.forEach(dataset => this.createDatasetCard(dataset));
            }
        } catch (error) {
            console.error('Erreur lors du chargement des datasets:', error);
            this.showFlash('Erreur lors du chargement des datasets', 'error');
        }
    }

    showEmptyState() {
        const emptyState = document.createElement('div');
        emptyState.className = 'flex items-center justify-center h-64 text-base-content/50';
        emptyState.innerHTML = `
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>Aucun fichier CSV</p>
            </div>
        `;
        this.csvGridTarget.appendChild(emptyState);
    }

    createDatasetCard(dataset) {
        const card = document.createElement('div');
        card.className = 'card bg-base-100 shadow-sm border border-base-300 hover:shadow-md transition-shadow';

        const statusBadge = dataset.isConfigured
            ? '<span class="badge badge-success badge-sm">Configuré</span>'
            : '<span class="badge badge-warning badge-sm">Non configuré</span>';

        card.innerHTML = `
            <div class="card-body p-4">
                <div class="flex items-start gap-3 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-sm truncate" title="${dataset.originalName}">${dataset.originalName}</h3>
                        <p class="text-xs text-base-content/60 mt-1">${this.formatFileSize(dataset.fileSize)}</p>
                        <p class="text-xs text-base-content/60">${dataset.rowCount} lignes · ${dataset.variableCount} variables</p>
                        <div class="mt-2">${statusBadge}</div>
                    </div>
                </div>

                <div class="card-actions justify-end gap-2">
                    ${!dataset.isConfigured ? `
                        <a
                            href="/api/csv/dataset/${dataset.id}/configure"
                            class="btn btn-xs btn-primary"
                        >
                            Configurer
                        </a>
                    ` : ''}
                    <button
                        class="btn btn-xs btn-ghost"
                        data-action="click->csv-library#openPreview"
                        data-filename="${dataset.filename}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Aperçu
                    </button>
                    <button
                        class="btn btn-xs btn-error"
                        data-action="click->csv-library#openDeleteModal"
                        data-filename="${dataset.filename}"
                        data-dataset-id="${dataset.id}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        `;

        this.csvGridTarget.appendChild(card);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
    }

    handleDragOver(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.add('border-primary', 'bg-base-200');
    }

    handleDragLeave(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('border-primary', 'bg-base-200');
    }

    handleDrop(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('border-primary', 'bg-base-200');

        const files = Array.from(event.dataTransfer.files);
        this.uploadFiles(files);
    }

    openFileDialog() {
        this.fileInputTarget.click();
    }

    handleFileSelect(event) {
        const files = Array.from(event.target.files);
        this.uploadFiles(files);
        event.target.value = '';
    }

    async uploadFiles(files) {
        if (files.length === 0) return;

        if (files.length > 1) {
            this.showFlash('Veuillez uploader un seul fichier à la fois pour la configuration', 'warning');
            return;
        }

        const file = files[0];

        this.uploadProgressTarget.classList.remove('hidden');
        this.progressTextTarget.textContent = `Upload de ${file.name}...`;

        try {
            const data = await CSVService.uploadCSV(file);

            if (data.datasetId) {
                this.showFlash('Fichier uploadé avec succès. Redirection vers la configuration...', 'success');
                setTimeout(() => {
                    window.location.href = `/api/csv/dataset/${data.datasetId}/configure`;
                }, 1000);
            } else {
                this.uploadProgressTarget.classList.add('hidden');
                this.showFlash('Fichier uploadé avec succès', 'success');
                await this.loadCSVFiles();
            }
        } catch (error) {
            console.error('Erreur upload:', error);
            this.uploadProgressTarget.classList.add('hidden');
            this.showFlash(error.message || 'Erreur lors de l\'upload', 'error');
        }
    }

    openDeleteModal(event) {
        const datasetId = event.currentTarget.dataset.datasetId;
        const filename = event.currentTarget.dataset.filename;
        this.currentFileToDelete = filename;
        this.currentDatasetToDelete = datasetId;
        this.deleteModalTarget.showModal();
    }

    closeDeleteModal() {
        this.deleteModalTarget.close();
        this.currentFileToDelete = null;
        this.currentDatasetToDelete = null;
    }

    async confirmDelete() {
        if (!this.currentFileToDelete || !this.currentDatasetToDelete) return;

        try {
            await CSVService.deleteCSV(this.currentFileToDelete);

            const deleteResponse = await fetch(`/api/datasets/${this.currentDatasetToDelete}`, {
                method: 'DELETE'
            });

            if (!deleteResponse.ok) {
                throw new Error('Erreur lors de la suppression du dataset');
            }

            this.showFlash('Dataset supprimé avec succès', 'success');
            this.closeDeleteModal();
            await this.loadCSVFiles();
        } catch (error) {
            console.error('Erreur suppression:', error);
            this.showFlash(error.message || 'Erreur lors de la suppression', 'error');
        }
    }

    async openPreview(event) {
        const filename = event.currentTarget.dataset.filename;

        try {
            const preview = await CSVService.previewCSV(filename);
            this.previewContentTarget.textContent = preview.preview;
            this.previewModalTarget.showModal();
        } catch (error) {
            console.error('Erreur aperçu:', error);
            this.showFlash('Erreur lors du chargement de l\'aperçu', 'error');
        }
    }

    closePreviewModal() {
        this.previewModalTarget.close();
    }

    showFlash(message, type = 'info') {
        const alertClass = {
            success: 'alert-success',
            error: 'alert-error',
            warning: 'alert-warning',
            info: 'alert-info'
        }[type] || 'alert-info';

        const flash = document.createElement('div');
        flash.className = `alert ${alertClass} mb-4`;
        flash.innerHTML = `
            <span>${message}</span>
        `;

        const container = document.getElementById('flash-container');
        if (container) {
            container.appendChild(flash);
            setTimeout(() => flash.remove(), 5000);
        }
    }
}

import { Controller } from '@hotwired/stimulus';
import { apiUrl } from "../services/locale-helper";

export default class extends Controller {
    static targets = ['variableRow', 'saveButton', 'cancelButton'];
    static values = {
        datasetId: Number
    };

    connect() {
        console.log('Dataset config controller connected', this.datasetIdValue);
    }

    async toggleVariableType(event) {
        const row = event.currentTarget.closest('tr');
        const variableId = parseInt(row.dataset.variableId);
        const select = row.querySelector('select');
        const newType = select.value;

        try {
            const response = await fetch(apiUrl(`api/datasets/${this.datasetIdValue}/variables/${variableId}`), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: newType })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Type de variable mis à jour');
                row.querySelector('.badge').textContent = newType === 'numeric' ? 'Numérique' : 'Catégorielle';
                row.querySelector('.badge').classList.toggle('badge-primary', newType === 'numeric');
                row.querySelector('.badge').classList.toggle('badge-secondary', newType === 'categorical');
            } else {
                this.showError(data.error || 'Erreur lors de la mise à jour');
                select.value = row.dataset.originalType;
            }
        } catch (error) {
            console.error('Error updating variable type:', error);
            this.showError('Erreur de connexion au serveur');
            select.value = row.dataset.originalType;
        }
    }

    async confirmConfiguration() {
        try {
            const response = await fetch(apiUrl(`api/datasets/${this.datasetIdValue}/configure`), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Configuration enregistrée avec succès');
                setTimeout(() => {
                    window.location.href = apiUrl('cms/csv');
                }, 1500);
            } else {
                this.showError(data.error || 'Erreur lors de la configuration');
            }
        } catch (error) {
            console.error('Error configuring dataset:', error);
            this.showError('Erreur de connexion au serveur');
        }
    }

    cancel() {
        if (confirm('Êtes-vous sûr de vouloir annuler la configuration ?')) {
            window.location.href = apiUrl('cms/csv');
        }
    }

    showSuccess(message) {
        this.showAlert(message, 'alert-success');
    }

    showError(message) {
        this.showAlert(message, 'alert-error');
    }

    showAlert(message, type) {
        const existingAlert = document.querySelector('.fixed.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = `alert ${type} fixed top-4 right-4 w-96 shadow-lg z-50`;
        alert.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'alert-success' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'}" />
            </svg>
            <span>${message}</span>
        `;
        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}

export class CSVService {
    static async uploadCSV(file) {
        if (!this.validateFile(file)) {
            throw new Error("Fichier invalide");
        }

        const formData = new FormData();
        formData.append("file", file);

        const response = await fetch("/api/csv/upload", {
            method: "POST",
            body: formData,
        });

        if (response.status === 403) {
            throw new Error("Vous n'avez pas la permission d'uploader des fichiers CSV");
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Réponse serveur invalide");
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Erreur lors de l'upload");
        }

        return data;
    }

    static async deleteCSV(filename) {
        const response = await fetch(`/api/csv/delete/${filename}`, {
            method: "DELETE",
        });

        if (response.status === 403) {
            throw new Error("Vous n'avez pas la permission de supprimer ce fichier");
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Erreur lors de la suppression");
        }

        return data;
    }

    static async previewCSV(filename) {
        const response = await fetch(`/api/csv/preview/${filename}`);

        if (response.status === 403) {
            throw new Error("Vous n'avez pas la permission de voir cet aperçu");
        }

        if (response.status === 404) {
            throw new Error("Fichier CSV introuvable");
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Réponse serveur invalide");
        }

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || "Erreur lors du chargement de l'aperçu");
        }

        const data = await response.json();
        return data;
    }

    static async fetchCSVContent(url) {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error("Erreur lors du chargement du fichier CSV");
        }
        return await response.text();
    }

    static validateFile(file) {
        const maxSize = 2 * 1024 * 1024;
        const allowedTypes = ["text/csv", "text/plain", "application/csv"];

        if (file.size > maxSize) {
            throw new Error(
                `Le fichier est trop volumineux (${this.formatFileSize(file.size)}). Maximum: 2MB`
            );
        }

        if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
            throw new Error(
                "Type de fichier non autorisé. Seuls les fichiers CSV sont acceptés."
            );
        }

        return true;
    }

    static formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
    }

}

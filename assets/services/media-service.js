export class MediaService {
  static MAX_FILE_SIZE = 5 * 1024 * 1024;
  static ALLOWED_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

  static async loadImages() {
    const response = await fetch("/api/media/images");
    const data = await response.json();

    if (!data.images || !Array.isArray(data.images)) {
      throw new Error("Format de données invalide");
    }

    return data.images;
  }

  static async uploadImage(file) {
    const formData = new FormData();
    formData.append("file", file);

    const response = await fetch("/api/media/upload/image", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || "Erreur lors de l'upload");
    }

    return data;
  }

  static async deleteImage(filename) {
    const response = await fetch(`/api/media/delete/image/${filename}`, {
      method: "DELETE",
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || "Erreur lors de la suppression");
    }

    return data;
  }

  static validateFile(file) {
    if (!this.ALLOWED_TYPES.includes(file.type)) {
      return { valid: false, error: "Type de fichier non autorisé" };
    }

    if (file.size > this.MAX_FILE_SIZE) {
      return { valid: false, error: "Fichier trop volumineux (max 5MB)" };
    }

    return { valid: true };
  }

  static formatFileSize(bytes) {
    return (bytes / (1024 * 1024)).toFixed(2);
  }

  static formatDate(timestamp) {
    return new Date(timestamp * 1000).toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }
}

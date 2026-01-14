import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["modal", "iframe"];
  static values = {
    url: String,
  };

  connect() {
    window.addEventListener("message", this.handleMessage.bind(this));
  }

  disconnect() {
    window.removeEventListener("message", this.handleMessage.bind(this));
  }

  handleMessage(event) {
    if (event.data === "close-preview") {
      this.close();
    }
  }

  open(event) {
    event.preventDefault();
    const url = event.currentTarget.dataset.previewUrl;

    if (this.hasIframeTarget) {
      this.iframeTarget.src = url;
    }

    if (this.hasModalTarget) {
      this.modalTarget.showModal();
      document.body.style.overflow = "hidden";
    }
  }

  close() {
    if (this.hasModalTarget) {
      this.modalTarget.close();
      document.body.style.overflow = "";
    }

    if (this.hasIframeTarget) {
      this.iframeTarget.src = "about:blank";
    }
  }

  backdropClick(event) {
    if (event.target === this.modalTarget) {
      this.close();
    }
  }
}

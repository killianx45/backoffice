import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["chatLog", "input", "sendButton", "loader", "widget", "toggleButton"];
  static values = {
    apiUrl: String
  };

  connect() {
    this.isOpen = false;
    this.isLoading = false;
    this.initializeWidget();
  }

  initializeWidget() {
    if (this.hasWidgetTarget) {
      this.widgetTarget.classList.add("hidden");
    }
  }

  toggleWidget() {
    if (!this.hasWidgetTarget) return;

    this.isOpen = !this.isOpen;

    if (this.isOpen) {
      this.widgetTarget.classList.remove("hidden");
      this.widgetTarget.classList.add("animate-fade-in");
      if (this.hasInputTarget) {
        setTimeout(() => this.inputTarget.focus(), 100);
      }
    } else {
      this.widgetTarget.classList.add("hidden");
      this.widgetTarget.classList.remove("animate-fade-in");
    }
  }

  async sendMessage(event) {
    event?.preventDefault();

    if (!this.hasInputTarget || this.isLoading) return;

    const message = this.inputTarget.value.trim();

    if (!message) return;

    this.inputTarget.value = "";
    this.displayMessage("user", message);
    this.setLoading(true);

    try {
      const response = await fetch(this.apiUrlValue, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify({ message }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));

        if (response.status === 403) {
          throw new Error("Access denied. Please log in with sufficient permissions.");
        } else if (response.status === 503) {
          throw new Error("AI service temporarily unavailable. Please try again later.");
        } else if (response.status === 401) {
          throw new Error("Session expired. Please log in again.");
        } else {
          throw new Error(errorData.error || `Server error (${response.status})`);
        }
      }

      const data = await response.json();

      if (!data || !data.message) {
        throw new Error("Invalid response from server");
      }

      this.displayMessage("bot", data.message);
    } catch (error) {
      console.error("Chat error:", error);

      let errorMessage = "Sorry, I encountered an error. Please try again later.";

      if (error.message) {
        errorMessage = error.message;
      } else if (error.name === "TypeError" && error.message.includes("Failed to fetch")) {
        errorMessage = "Network error. Please check your connection.";
      }

      this.displayMessage("bot", errorMessage, true);
    } finally {
      this.setLoading(false);
    }
  }

  displayMessage(role, content, isError = false) {
    if (!this.hasChatLogTarget) return;

    const messageDiv = document.createElement("div");
    messageDiv.className = `chat ${role === "user" ? "chat-end" : "chat-start"}`;

    const bubbleDiv = document.createElement("div");
    bubbleDiv.className = `chat-bubble ${
      role === "user"
        ? "chat-bubble-primary"
        : isError
        ? "chat-bubble-error"
        : "chat-bubble-neutral"
    }`;
    bubbleDiv.textContent = content;

    messageDiv.appendChild(bubbleDiv);
    this.chatLogTarget.appendChild(messageDiv);

    this.scrollToBottom();
  }

  scrollToBottom() {
    if (!this.hasChatLogTarget) return;

    requestAnimationFrame(() => {
      this.chatLogTarget.scrollTop = this.chatLogTarget.scrollHeight;
    });
  }

  setLoading(loading) {
    this.isLoading = loading;

    if (this.hasSendButtonTarget) {
      this.sendButtonTarget.disabled = loading;
    }

    if (this.hasInputTarget) {
      this.inputTarget.disabled = loading;
    }

    if (this.hasLoaderTarget) {
      this.loaderTarget.classList.toggle("hidden", !loading);
    }
  }

  handleKeyPress(event) {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      this.sendMessage();
    }
  }
}

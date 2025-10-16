import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["icon", "text"];
  static values = {
    lightText: String,
    darkText: String,
  };

  connect() {
    this.initializeTheme();
    this.updateToggleState();
  }

  initializeTheme() {
    const savedTheme = localStorage.getItem("theme");
    const systemPrefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

    let currentTheme;
    if (savedTheme) {
      currentTheme = savedTheme;
    } else {
      currentTheme = systemPrefersDark ? "dark" : "light";
    }

    this.setTheme(currentTheme);
    window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
      if (!localStorage.getItem("theme")) {
        this.setTheme(e.matches ? "dark" : "light");
        this.updateToggleState();
      }
    });
  }

  toggle() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute("data-theme");
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    this.element.classList.add("theme-switching");
    setTimeout(() => {
      this.element.classList.remove("theme-switching");
    }, 600);

    this.setTheme(newTheme);
    this.updateToggleState();
    localStorage.setItem("theme", newTheme);
    this.dispatch("changed", { detail: { theme: newTheme } });
  }

  setTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.setAttribute("content", theme === "dark" ? "#1d232a" : "#ffffff");
    }
  }

  updateToggleState() {
    const currentTheme = document.documentElement.getAttribute("data-theme");
    const isDark = currentTheme === "dark";

    if (this.hasIconTarget) {
      this.iconTarget.innerHTML = isDark ? this.getSunIcon() : this.getMoonIcon();
    }

    const lightModeText = this.lightTextValue || "Mode clair";
    const darkModeText = this.darkTextValue || "Mode sombre";

    if (this.hasTextTarget) {
      this.textTarget.textContent = isDark ? lightModeText : darkModeText;
    }

    this.element.setAttribute("title", isDark ? lightModeText : darkModeText);

    this.element.classList.toggle("theme-dark", isDark);
    this.element.classList.toggle("theme-light", !isDark);
  }
  getMoonIcon() {
    return `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        `;
  }

  getSunIcon() {
    return `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        `;
  }
}

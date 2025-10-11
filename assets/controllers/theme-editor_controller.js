import { Controller } from "@hotwired/stimulus";
import Chart from "chart.js/auto";

export default class extends Controller {
  static targets = ["preview", "chart"];

  connect() {
    this.debouncedUpdate = this.debounce(this.updatePreview.bind(this), 250);
    this.initializeChart();
    this.updatePreview();
  }

  disconnect() {
    if (this.chart) {
      this.chart.destroy();
    }
  }

  updatePreview() {
    const formData = this.extractFormData();
    this.applyLayoutStyles(formData.layout);
    this.applyTypographyStyles(formData.typography);
    this.applyColorStyles(formData.colors);
    this.applyComponentStyles(formData.components);
    this.updateChart(formData.charts, formData.colors);
  }

  extractFormData() {
    const form = this.element;

    return {
      layout: {
        articleWidth: this.getSelectValue("theme_configuration_layout_articleWidth"),
        featuredImageSize: this.getSelectValue("theme_configuration_layout_featuredImageSize"),
        contentAlignment: this.getSelectValue("theme_configuration_layout_contentAlignment"),
        spacing: this.getSelectValue("theme_configuration_layout_spacing"),
        titlePosition: this.getSelectValue("theme_configuration_layout_titlePosition"),
      },
      typography: {
        headingFont: this.getSelectValue("theme_configuration_typography_headingFont"),
        bodyFont: this.getSelectValue("theme_configuration_typography_bodyFont"),
        headingSize: this.getSelectValue("theme_configuration_typography_headingSize"),
        bodySize: this.getSelectValue("theme_configuration_typography_bodySize"),
        lineHeight: this.getSelectValue("theme_configuration_typography_lineHeight"),
      },
      colors: {
        primary: this.getColorValue("theme_configuration_colors_primary"),
        secondary: this.getColorValue("theme_configuration_colors_secondary"),
        accent: this.getColorValue("theme_configuration_colors_accent"),
        base100: this.getColorValue("theme_configuration_colors_base100"),
        base200: this.getColorValue("theme_configuration_colors_base200"),
        base300: this.getColorValue("theme_configuration_colors_base300"),
        titleOverlay: this.getColorValue("theme_configuration_colors_titleOverlay"),
        chartPalette: this.getChartPalette(),
      },
      charts: {
        legendPosition: this.getSelectValue("theme_configuration_charts_legendPosition"),
      },
      components: {
        cardStyle: this.getSelectValue("theme_configuration_components_cardStyle"),
        buttonStyle: this.getSelectValue("theme_configuration_components_buttonStyle"),
        borderRadius: this.getSelectValue("theme_configuration_components_borderRadius"),
      },
    };
  }

  getSelectValue(id) {
    const element = document.getElementById(id);
    return element ? element.value : "";
  }

  getColorValue(id) {
    const element = document.getElementById(id);
    return element ? element.value : "#000000";
  }

  getCheckboxValue(id) {
    const element = document.getElementById(id);
    return element ? element.checked : false;
  }

  getChartPalette() {
    const palette = [];
    for (let i = 0; i < 5; i++) {
      const input = document.getElementById(`theme_configuration_colors_chartPalette_${i}`);
      if (input) {
        palette.push(input.value);
      }
    }
    return palette.length > 0 ? palette : ["#a855f7", "#9333ea", "#7e22ce", "#6b21a8", "#581c87"];
  }

  applyLayoutStyles(layout) {
    const preview = this.previewTarget;
    const article = preview.querySelector(".preview-article");
    const content = preview.querySelector(".preview-content");
    const imageContainer = preview.querySelector(".preview-image-container");
    const title = preview.querySelector(".preview-title");
    const gradient = preview.querySelector(".preview-gradient");

    if (article) {
      const widths = { narrow: "600px", medium: "800px", wide: "1200px" };
      article.style.maxWidth = widths[layout.articleWidth] || "800px";
    }

    if (content) {
      content.style.textAlign = layout.contentAlignment || "left";
      const spacings = { compact: "1.5rem", comfortable: "2rem", spacious: "2.5rem" };
      content.style.padding = spacings[layout.spacing] || "2rem";
    }

    if (imageContainer && title && gradient) {
      const isOverlay = layout.titlePosition?.includes("overlay");

      const img = imageContainer.querySelector("img");
      if (img) {
        const sizes = { small: "12rem", medium: "16rem", large: "24rem", full: "600px" };
        img.style.height = sizes[layout.featuredImageSize] || "600px";
        img.style.objectFit = "cover";
      }

      gradient.style.display = isOverlay ? "block" : "none";
      title.style.color = isOverlay ? "#ffffff" : "";

      if (isOverlay) {
        title.style.position = "absolute";
        title.style.left = "2rem";
        title.style.right = "2rem";
        title.style.transform = "none";

        if (layout.titlePosition === "overlay-top") {
          title.style.top = "2rem";
          title.style.bottom = "auto";
        } else if (layout.titlePosition === "overlay-center") {
          title.style.top = "50%";
          title.style.bottom = "auto";
          title.style.transform = "translateY(-50%)";
        } else {
          title.style.top = "auto";
          title.style.bottom = "2rem";
        }
      } else {
        title.style.position = "relative";
        title.style.top = "auto";
        title.style.bottom = "auto";
        title.style.left = "auto";
        title.style.right = "auto";
        title.style.transform = "none";
        title.style.marginTop = "1rem";
      }
    }
  }

  applyTypographyStyles(typography) {
    const preview = this.previewTarget;
    const headings = preview.querySelectorAll("h1, h2, h3, h4, h5, h6");
    const body = preview.querySelector(".preview-content");

    const fonts = {
      montserrat: "Montserrat, sans-serif",
      "darker-grotesque": "Darker Grotesque, sans-serif",
    };

    const headingSizes = { small: "0.9em", medium: "1em", large: "1.3em" };
    const bodySizes = { small: "0.9rem", medium: "1rem", large: "1.1rem" };
    const lineHeights = { tight: "1.4", normal: "1.6", relaxed: "1.8" };

    headings.forEach((heading) => {
      heading.style.fontFamily = fonts[typography.headingFont] || fonts["montserrat"];
      heading.style.fontSize = headingSizes[typography.headingSize] || "1em";
    });

    if (body) {
      body.style.fontFamily = fonts[typography.bodyFont] || fonts["darker-grotesque"];
      body.style.fontSize = bodySizes[typography.bodySize] || "1rem";
      body.style.lineHeight = lineHeights[typography.lineHeight] || "1.6";
    }
  }

  applyColorStyles(colors) {
    const preview = this.previewTarget;
    const headings = preview.querySelectorAll(".preview-heading");
    const links = preview.querySelectorAll(".preview-link");
    const title = preview.querySelector(".preview-title");
    const content = preview.querySelector(".preview-content");
    const gradient = preview.querySelector(".preview-gradient");

    headings.forEach((heading) => {
      heading.style.color = colors.primary || "#171717";
    });

    links.forEach((link) => {
      link.style.color = colors.accent || "#262626";
    });

    if (title && title.style.position === "absolute") {
      title.style.color = "#ffffff";
      if (gradient) {
        const overlay = colors.titleOverlay || "#000000";
        const rgb = this.hexToRgb(overlay);
        gradient.style.background = `linear-gradient(to top, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.7), rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.3), transparent)`;
      }
    } else if (title) {
      title.style.color = colors.primary || "#171717";
    }

    if (content) {
      content.style.backgroundColor = colors.base100 || "#ffffff";
      content.style.borderColor = colors.base300 || "#e8e8e8";
    }
  }

  applyComponentStyles(components) {
    const preview = this.previewTarget;
    const card = preview.querySelector(".preview-card");
    const button = preview.querySelector(".preview-button");

    const borderRadii = { none: "0", small: "0.25rem", medium: "0.5rem", large: "1rem" };

    if (card) {
      card.style.borderRadius = borderRadii[components.borderRadius] || "0.5rem";

      if (components.cardStyle === "bordered") {
        card.style.border = "1px solid";
        card.style.borderColor = "inherit";
        card.style.boxShadow = "none";
      } else if (components.cardStyle === "elevated") {
        card.style.border = "none";
        card.style.boxShadow = "0 4px 6px rgba(0, 0, 0, 0.1)";
      } else {
        card.style.border = "none";
        card.style.boxShadow = "none";
      }
    }

    if (button) {
      button.style.borderRadius = borderRadii[components.borderRadius] || "0.5rem";

      if (components.buttonStyle === "solid") {
        button.style.backgroundColor = "#171717";
        button.style.color = "#ffffff";
        button.style.borderColor = "#171717";
      } else if (components.buttonStyle === "outline") {
        button.style.backgroundColor = "transparent";
        button.style.color = "#171717";
        button.style.borderColor = "#171717";
        button.style.border = "1px solid";
      } else if (components.buttonStyle === "ghost") {
        button.style.backgroundColor = "transparent";
        button.style.color = "#171717";
        button.style.border = "none";
      }
    }
  }

  initializeChart() {
    if (!this.hasChartTarget) return;

    const ctx = this.chartTarget.getContext("2d");

    this.chart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin"],
        datasets: [
          {
            label: "Exemple de données",
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: "#a855f7",
            borderColor: "#a855f7",
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: true,
            position: "top",
          },
        },
        scales: {
          x: {
            grid: {
              display: true,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              display: true,
            },
          },
        },
      },
    });
  }

  updateChart(chartConfig, colors) {
    if (!this.chart) return;

    this.chart.data.datasets[0].backgroundColor = colors.chartPalette[0] || "#a855f7";
    this.chart.data.datasets[0].borderColor = colors.chartPalette[0] || "#a855f7";

    if (chartConfig.legendPosition === "none") {
      this.chart.options.plugins.legend.display = false;
    } else {
      this.chart.options.plugins.legend.display = true;
      this.chart.options.plugins.legend.position = chartConfig.legendPosition || "top";
    }

    this.chart.update("reset");
  }

  hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result
      ? {
          r: parseInt(result[1], 16),
          g: parseInt(result[2], 16),
          b: parseInt(result[3], 16),
        }
      : { r: 0, g: 0, b: 0 };
  }

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}

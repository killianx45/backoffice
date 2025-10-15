import { Controller } from "@hotwired/stimulus";
import Chart from "chart.js/auto";
import { CSVParser } from "../utils/csv-parser";
import { apiUrl } from "../services/locale-helper";

export default class extends Controller {
  static values = { themeColors: Object };

  async connect() {
    await this.renderAllCharts();
  }

  async renderAllCharts() {
    const chartElements = this.element.querySelectorAll(".ck-chart");

    for (const chartElement of chartElements) {
      await this.renderChart(chartElement);
    }
  }

  async renderChart(chartElement) {
    const datasetId = chartElement.getAttribute("data-dataset-id");
    const chartType = chartElement.getAttribute("data-chart-type");
    const title = chartElement.getAttribute("data-title");
    const selectedVariablesStr = chartElement.getAttribute("data-selected-variables");
    const xAxisLabel = chartElement.getAttribute("data-x-axis-label");
    const yAxisLabel = chartElement.getAttribute("data-y-axis-label");

    if (!datasetId || !chartType || !selectedVariablesStr) {
      console.error("Chart configuration incomplete", chartElement);
      return;
    }

    const canvas = document.createElement("canvas");
    canvas.style.maxWidth = "100%";
    canvas.style.height = "400px";

    chartElement.innerHTML = "";
    chartElement.appendChild(canvas);

    try {
      const selectedVariables = JSON.parse(selectedVariablesStr);

      const datasetResponse = await fetch(apiUrl(`api/datasets/${datasetId}`));
      if (!datasetResponse.ok) {
        throw new Error(`Failed to load dataset: ${datasetResponse.statusText}`);
      }
      const datasetData = await datasetResponse.json();
      const dataset = datasetData.dataset;

      const csvUrl = dataset.url;
      const csvResponse = await fetch(csvUrl);
      if (!csvResponse.ok) {
        throw new Error(`Failed to load CSV: ${csvResponse.statusText}`);
      }

      const csvText = await csvResponse.text();
      const parsedCSV = CSVParser.parse(csvText);

      // Utilise les couleurs du thème passées depuis le template ou récupère le thème via API
      let themeColors = null;
      let theme = null;

      if (this.hasThemeColorsValue && this.themeColorsValue) {
        themeColors = this.generateThemeColorPaletteFromColors(this.themeColorsValue);
        // On simule un objet theme pour la cohérence
        theme = { configuration: { colors: this.themeColorsValue, charts: { legendPosition: "top" } } };
      } else {
        theme = await this.fetchActiveTheme();
        themeColors = theme ? this.generateThemeColorPalette(theme) : null;
      }

      const chartData = CSVParser.toChartDataAdvanced(parsedCSV, chartType, selectedVariables, themeColors);

      // Récupère les couleurs pour le style du graphique
      const chartColors = this.getChartColors(theme);

      const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display:
              theme &&
              theme.configuration &&
              theme.configuration.charts &&
              theme.configuration.charts.legendPosition !== "none",
            position:
              theme &&
              theme.configuration &&
              theme.configuration.charts &&
              theme.configuration.charts.legendPosition !== "none"
                ? theme.configuration.charts.legendPosition || "top"
                : "top",
            labels: {
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
          },
          title: {
            display: !!title,
            text: title || "",
            font: {
              size: 16,
              weight: "bold",
            },
            color: theme && theme.configuration ? theme.configuration.colors.primary : "#333",
          },
        },
      };

      if (chartType !== "pie" && chartType !== "scatter") {
        chartOptions.scales = {
          x: {
            title: {
              display: !!xAxisLabel,
              text: xAxisLabel || "",
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            ticks: {
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            grid: {
              color:
                theme && theme.configuration
                  ? this.hexToRgba(theme.configuration.colors.base300, 0.3)
                  : "rgba(232, 232, 232, 0.3)",
            },
          },
          y: {
            title: {
              display: !!yAxisLabel,
              text: yAxisLabel || "",
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            beginAtZero: true,
            ticks: {
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            grid: {
              color:
                theme && theme.configuration
                  ? this.hexToRgba(theme.configuration.colors.base300, 0.3)
                  : "rgba(232, 232, 232, 0.3)",
            },
          },
        };
      }

      if (chartType === "scatter") {
        chartOptions.scales = {
          x: {
            type: "linear",
            title: {
              display: !!xAxisLabel,
              text: xAxisLabel || "",
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            ticks: {
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            grid: {
              color:
                theme && theme.configuration
                  ? this.hexToRgba(theme.configuration.colors.base300, 0.3)
                  : "rgba(232, 232, 232, 0.3)",
            },
          },
          y: {
            type: "linear",
            title: {
              display: !!yAxisLabel,
              text: yAxisLabel || "",
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            beginAtZero: true,
            ticks: {
              color: theme && theme.configuration ? theme.configuration.colors.secondary : "#666",
            },
            grid: {
              color:
                theme && theme.configuration
                  ? this.hexToRgba(theme.configuration.colors.base300, 0.3)
                  : "rgba(232, 232, 232, 0.3)",
            },
          },
        };
      }

      const config = {
        type: chartType,
        data: chartData,
        options: chartOptions,
      };

      new Chart(canvas, config);
    } catch (error) {
      console.error("Error rendering chart:", error);
      chartElement.innerHTML = `
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Erreur lors du chargement du graphique: ${error.message}</span>
                </div>
            `;
    }
  }

  async fetchActiveTheme() {
    try {
      const response = await fetch(apiUrl("api/theme/active"));
      if (!response.ok) {
        return null;
      }
      return await response.json();
    } catch (error) {
      console.warn("Could not fetch active theme:", error);
      return null;
    }
  }

  generateThemeColorPalette(theme) {
    if (!theme || !theme.configuration) {
      return null;
    }

    // Utilise d'abord la palette prédéfinie du thème si disponible
    if (theme.configuration.colors.chartPalette && Array.isArray(theme.configuration.colors.chartPalette)) {
      return theme.configuration.colors.chartPalette;
    }

    const primary = theme.configuration.colors.primary;
    const accent = theme.configuration.colors.accent;
    const baseColor = accent || primary;

    return this.generateColorPaletteFromBaseColor(baseColor);
  }

  generateThemeColorPaletteFromColors(colors) {
    if (!colors) {
      return null;
    }

    // Utilise d'abord la palette prédéfinie du thème si disponible
    if (colors.chartPalette && Array.isArray(colors.chartPalette)) {
      return colors.chartPalette;
    }

    const primary = colors.primary;
    const accent = colors.accent;
    const baseColor = accent || primary;

    return this.generateColorPaletteFromBaseColor(baseColor);
  }

  generateColorPaletteFromBaseColor(baseColor) {
    const parseColor = (color) => {
      if (color.startsWith("hsl")) {
        const match = color.match(/hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)/);
        if (match) {
          return {
            h: parseInt(match[1]),
            s: parseInt(match[2]),
            l: parseInt(match[3]),
          };
        }
      }

      if (color.startsWith("#")) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(color);
        if (!result) return { h: 276, s: 82, l: 63 };

        let r = parseInt(result[1], 16) / 255;
        let g = parseInt(result[2], 16) / 255;
        let b = parseInt(result[3], 16) / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h,
          s,
          l = (max + min) / 2;

        if (max === min) {
          h = s = 0;
        } else {
          const d = max - min;
          s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
          switch (max) {
            case r:
              h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
              break;
            case g:
              h = ((b - r) / d + 2) / 6;
              break;
            case b:
              h = ((r - g) / d + 4) / 6;
              break;
          }
        }

        return {
          h: Math.round(h * 360),
          s: Math.round(s * 100),
          l: Math.round(l * 100),
        };
      }

      return { h: 276, s: 82, l: 63 };
    };

    const hsl = parseColor(baseColor);

    const baseSaturation = hsl.s < 40 ? 70 : hsl.s;
    const baseHue = hsl.s < 10 ? 276 : hsl.h;
    const baseLightness = hsl.l < 30 ? 55 : hsl.l > 70 ? 55 : hsl.l;

    return [
      `hsl(${baseHue}, ${baseSaturation}%, ${baseLightness}%)`,
      `hsl(${baseHue}, ${baseSaturation}%, ${Math.max(baseLightness - 10, 35)}%)`,
      `hsl(${baseHue}, ${baseSaturation}%, ${Math.min(baseLightness + 10, 70)}%)`,
      `hsl(${baseHue}, ${Math.max(baseSaturation - 15, 50)}%, ${baseLightness}%)`,
      `hsl(${baseHue}, ${baseSaturation}%, ${Math.min(baseLightness + 15, 75)}%)`,
      `hsl(${baseHue}, ${Math.max(baseSaturation - 25, 45)}%, ${baseLightness}%)`,
      `hsl(${baseHue}, ${baseSaturation}%, ${Math.max(baseLightness - 15, 40)}%)`,
    ];
  }

  getChartColors(theme) {
    const defaultColors = {
      background: "rgba(59, 130, 246, 0.8)",
      backgroundTransparent: "rgba(59, 130, 246, 0.2)",
      border: "rgba(59, 130, 246, 1)",
    };

    if (!theme || !theme.configuration || !theme.configuration.colors) {
      return defaultColors;
    }

    const themeColors = theme.configuration.colors;
    const primary = themeColors.primary;
    const accent = themeColors.accent;

    const hexToRgb = (hex) => {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result
        ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
          }
        : { r: 59, g: 130, b: 246 };
    };

    const rgb = hexToRgb(accent || primary);

    return {
      background: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.8)`,
      backgroundTransparent: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.2)`,
      border: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 1)`,
    };
  }

  hexToRgba(color, alpha = 1) {
    // Si c'est déjà une couleur HSL
    if (color.startsWith("hsl")) {
      const match = color.match(/hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)/);
      if (match) {
        const h = parseInt(match[1]) / 360;
        const s = parseInt(match[2]) / 100;
        const l = parseInt(match[3]) / 100;

        const hue2rgb = (p, q, t) => {
          if (t < 0) t += 1;
          if (t > 1) t -= 1;
          if (t < 1 / 6) return p + (q - p) * 6 * t;
          if (t < 1 / 2) return q;
          if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
          return p;
        };

        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        const r = Math.round(hue2rgb(p, q, h + 1 / 3) * 255);
        const g = Math.round(hue2rgb(p, q, h) * 255);
        const b = Math.round(hue2rgb(p, q, h - 1 / 3) * 255);

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
      }
    }

    // Si c'est une couleur hexadécimale
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(color);
    if (result) {
      const r = parseInt(result[1], 16);
      const g = parseInt(result[2], 16);
      const b = parseInt(result[3], 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    // Fallback
    return `rgba(232, 232, 232, ${alpha})`;
  }
}

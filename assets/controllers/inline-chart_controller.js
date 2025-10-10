import { Controller } from "@hotwired/stimulus";
import Chart from "chart.js/auto";
import { CSVParser } from "../utils/csv-parser";

export default class extends Controller {
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
    const csvFile = chartElement.getAttribute("data-csv-file");
    const chartType = chartElement.getAttribute("data-chart-type");
    const title = chartElement.getAttribute("data-title");
    const xAxisLabel = chartElement.getAttribute("data-x-axis-label");
    const yAxisLabel = chartElement.getAttribute("data-y-axis-label");

    if (!csvFile || !chartType) {
      console.error("Chart configuration incomplete", chartElement);
      return;
    }

    const canvas = document.createElement("canvas");
    canvas.style.maxWidth = "100%";
    canvas.style.height = "400px";

    chartElement.innerHTML = "";
    chartElement.appendChild(canvas);

    try {
      const theme = await this.fetchActiveTheme();
      const csvUrl = `/media/csv/${csvFile}`;
      const response = await fetch(csvUrl);

      if (!response.ok) {
        throw new Error(`Failed to load CSV: ${response.statusText}`);
      }

      const csvText = await response.text();
      const parsedCSV = CSVParser.parse(csvText);

      const themeColors = theme ? this.generateThemeColorPalette(theme) : null;

      const chartData = CSVParser.toChartData(parsedCSV, chartType, themeColors);

      const config = {
        type: chartType,
        data: {
          labels: chartData.labels,
          datasets: chartData.datasets,
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: "top",
            },
            title: {
              display: !!title,
              text: title || "",
              font: {
                size: 16,
                weight: "bold",
              },
            },
          },
          scales: {
            x: {
              title: {
                display: !!xAxisLabel,
                text: xAxisLabel || "",
              },
            },
            y: {
              title: {
                display: !!yAxisLabel,
                text: yAxisLabel || "",
              },
              beginAtZero: true,
            },
          },
        },
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
      const response = await fetch("/api/theme/active");
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

    const primary = theme.configuration.colors.primary;
    const accent = theme.configuration.colors.accent;
    const baseColor = accent || primary;

    const parseColor = (color) => {
      if (color.startsWith('hsl')) {
        const match = color.match(/hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)/);
        if (match) {
          return {
            h: parseInt(match[1]),
            s: parseInt(match[2]),
            l: parseInt(match[3])
          };
        }
      }

      if (color.startsWith('#')) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(color);
        if (!result) return { h: 276, s: 82, l: 63 };

        let r = parseInt(result[1], 16) / 255;
        let g = parseInt(result[2], 16) / 255;
        let b = parseInt(result[3], 16) / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;

        if (max === min) {
          h = s = 0;
        } else {
          const d = max - min;
          s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
          switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
            case g: h = ((b - r) / d + 2) / 6; break;
            case b: h = ((r - g) / d + 4) / 6; break;
          }
        }

        return {
          h: Math.round(h * 360),
          s: Math.round(s * 100),
          l: Math.round(l * 100)
        };
      }

      return { h: 276, s: 82, l: 63 };
    };

    const hsl = parseColor(baseColor);

    const baseSaturation = hsl.s < 40 ? 70 : hsl.s;
    const baseHue = hsl.s < 10 ? 276 : hsl.h;
    const baseLightness = hsl.l < 30 ? 55 : (hsl.l > 70 ? 55 : hsl.l);

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

    if (!theme || !theme.configuration) {
      return defaultColors;
    }

    const primary = theme.configuration.colors.primary;
    const accent = theme.configuration.colors.accent;

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
}

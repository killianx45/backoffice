import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';
import { CSVParser } from '../utils/csv-parser';
import { CSVService } from '../services/csv-service';

Chart.register(...registerables);

export default class extends Controller {
    static targets = ['canvas'];
    static values = {
        csvUrl: String,
        chartType: String,
        title: String,
        xAxisLabel: String,
        yAxisLabel: String
    };

    async connect() {
        if (!this.hasCsvUrlValue) {
            console.error('CSV URL manquante');
            return;
        }

        try {
            const theme = await this.fetchActiveTheme();
            const csvContent = await CSVService.fetchCSVContent(this.csvUrlValue);
            const csvData = CSVParser.parse(csvContent);
            const chartData = CSVParser.toChartData(csvData, this.chartTypeValue || 'bar', theme?.configuration?.colors?.chartPalette);

            this.renderChart(chartData, theme);
        } catch (error) {
            console.error('Erreur lors du chargement du graphique:', error);
            this.showError(error.message);
        }
    }

    async fetchActiveTheme() {
        try {
            const response = await fetch('/api/theme/active');
            if (!response.ok) {
                console.warn('Impossible de récupérer le thème actif, utilisation des couleurs par défaut');
                return null;
            }
            return await response.json();
        } catch (error) {
            console.warn('Erreur lors de la récupération du thème:', error);
            return null;
        }
    }

    renderChart(chartData, theme) {
        const chartConfig = theme?.configuration?.charts || {};
        const gridStyle = chartConfig.gridStyle || 'solid';

        const config = {
            type: this.chartTypeValue || 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: chartConfig.animation !== false,
                plugins: {
                    legend: {
                        display: chartConfig.legendPosition !== 'none',
                        position: chartConfig.legendPosition || 'top',
                    },
                    title: {
                        display: this.hasTitleValue,
                        text: this.titleValue || ''
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: gridStyle !== 'none',
                            color: 'rgba(0, 0, 0, 0.1)',
                            borderDash: gridStyle === 'dashed' ? [5, 5] : gridStyle === 'dotted' ? [2, 2] : []
                        },
                        title: {
                            display: this.hasYAxisLabelValue,
                            text: this.yAxisLabelValue || ''
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: this.hasXAxisLabelValue,
                            text: this.xAxisLabelValue || ''
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        };

        if (this.chartTypeValue === 'line') {
            config.options.elements = {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 4,
                    hoverRadius: 6
                }
            };
        }

        this.chart = new Chart(this.canvasTarget, config);
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Erreur lors du chargement du graphique: ${message}</span>
        `;

        this.element.appendChild(errorDiv);
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
        }
    }
}

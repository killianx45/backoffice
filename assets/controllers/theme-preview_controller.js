import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static values = {
        config: Object
    }

    connect() {
        this.renderPreviewChart();
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
        }
    }

    renderPreviewChart() {
        const canvas = document.getElementById('preview-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const config = this.configValue;

        const sampleData = {
            labels: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
            datasets: [
                {
                    label: 'Série 1',
                    data: [12, 19, 3, 5, 2, 3],
                    backgroundColor: config.colors.chartPalette[0],
                    borderColor: config.colors.chartPalette[0],
                    borderWidth: 2,
                },
                {
                    label: 'Série 2',
                    data: [2, 3, 20, 5, 1, 4],
                    backgroundColor: config.colors.chartPalette[1],
                    borderColor: config.colors.chartPalette[1],
                    borderWidth: 2,
                }
            ]
        };

        const chartType = config.charts.defaultType || 'bar';

        this.chart = new Chart(ctx, {
            type: chartType,
            data: sampleData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: config.charts.animation,
                plugins: {
                    legend: {
                        display: config.charts.legendPosition !== 'none',
                        position: config.charts.legendPosition || 'top',
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
                            display: config.charts.gridStyle !== 'none',
                            borderDash: config.charts.gridStyle === 'dashed' ? [5, 5] : [],
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                        }
                    }
                },
                ...(chartType === 'line' && {
                    elements: {
                        line: {
                            tension: 0.4
                        }
                    }
                })
            }
        });
    }
}

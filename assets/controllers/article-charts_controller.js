import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["chartsContainer", "chartItem", "chartNumber"];
  static values = {
    index: Number,
    prototype: String,
  };

  connect() {
    if (this.indexValue === 0) {
      this.indexValue = 0;
    }
    this.updateChartNumbers();
  }

  addChart(event) {
    event.preventDefault();

    const prototype = this.prototypeValue;
    const newForm = prototype.replace(/__name__/g, this.indexValue);

    const wrapper = document.createElement("div");
    wrapper.innerHTML = newForm;
    const chartCard = wrapper.firstElementChild;

    chartCard.classList.add("card", "bg-base-200", "mb-4");
    chartCard.dataset.articleChartsTarget = "chartItem";

    const cardBody = document.createElement("div");
    cardBody.className = "card-body p-4";

    const header = document.createElement("div");
    header.className = "flex justify-between items-center mb-3";
    header.innerHTML = `
            <h4 class="font-medium">Graphique #<span data-article-charts-target="chartNumber">${
              this.chartItemTargets.length + 1
            }</span></h4>
            <button type="button" class="btn btn-sm btn-ghost btn-circle" data-action="click->article-charts#removeChart">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

    const formContainer = document.createElement("div");
    formContainer.className = "grid grid-cols-1 gap-4";
    formContainer.dataset.controller = "csv-manager";

    const csvFileDiv = document.createElement("div");
    csvFileDiv.className = "form-control";
    csvFileDiv.innerHTML = `
            <label class="label">
                <span class="label-text">Fichier CSV</span>
            </label>
            <button type="button"
                    data-action="csv-manager#openModal"
                    class="btn btn-outline btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Sélectionner un CSV
            </button>
            <div data-csv-manager-target="preview" class="mt-2"></div>
        `;

    const csvFileInput = chartCard.querySelector('input[id$="_csvFile"]');
    if (csvFileInput) {
      csvFileDiv.appendChild(csvFileInput);
    }

    formContainer.appendChild(csvFileDiv);

    const chartTypeSelect = chartCard.querySelector('select[id$="_chartType"]');
    const titleInput = chartCard.querySelector('input[id$="_title"]');
    const orderInput = chartCard.querySelector('input[id$="_displayOrder"]');
    const xAxisInput = chartCard.querySelector('input[id$="_xAxisLabel"]');
    const yAxisInput = chartCard.querySelector('input[id$="_yAxisLabel"]');

    const fieldsGrid = document.createElement("div");
    fieldsGrid.className = "grid grid-cols-1 md:grid-cols-3 gap-4";

    if (chartTypeSelect) {
      const chartTypeDiv = this.createFormControl("Type de graphique", chartTypeSelect);
      fieldsGrid.appendChild(chartTypeDiv);
    }

    if (titleInput) {
      const titleDiv = this.createFormControl("Titre du graphique (optionnel)", titleInput);
      fieldsGrid.appendChild(titleDiv);
    }

    if (orderInput) {
      const orderDiv = this.createFormControl("Ordre d'affichage", orderInput);
      fieldsGrid.appendChild(orderDiv);
    }

    formContainer.appendChild(fieldsGrid);

    const axisGrid = document.createElement("div");
    axisGrid.className = "grid grid-cols-1 md:grid-cols-2 gap-4 mt-4";

    if (xAxisInput) {
      const xAxisDiv = this.createFormControl("Libellé de l'axe X (optionnel)", xAxisInput);
      axisGrid.appendChild(xAxisDiv);
    }

    if (yAxisInput) {
      const yAxisDiv = this.createFormControl("Libellé de l'axe Y (optionnel)", yAxisInput);
      axisGrid.appendChild(yAxisDiv);
    }

    formContainer.appendChild(axisGrid);
    cardBody.appendChild(header);
    cardBody.appendChild(formContainer);
    chartCard.innerHTML = "";
    chartCard.appendChild(cardBody);

    this.chartsContainerTarget.appendChild(chartCard);
    this.indexValue++;
    this.updateChartNumbers();
  }

  createFormControl(labelText, input) {
    const div = document.createElement("div");
    div.className = "form-control";

    const label = document.createElement("label");
    label.className = "label";
    label.innerHTML = `<span class="label-text">${labelText}</span>`;

    div.appendChild(label);
    div.appendChild(input);

    return div;
  }

  removeChart(event) {
    event.preventDefault();
    const chartItem = event.target.closest('[data-article-charts-target="chartItem"]');
    if (chartItem) {
      chartItem.remove();
      this.updateChartNumbers();
    }
  }

  updateChartNumbers() {
    this.chartNumberTargets.forEach((numberSpan, index) => {
      numberSpan.textContent = index + 1;
    });
  }
}

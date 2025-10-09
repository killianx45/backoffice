export class CSVParser {
  static parse(csvText) {
    const lines = csvText.trim().split("\n");
    if (lines.length < 2) {
      throw new Error("CSV doit contenir au moins une ligne d'en-tête et une ligne de données");
    }

    const delimiter = this.detectDelimiter(lines[0]);
    const headers = this.parseCSVLine(lines[0], delimiter);
    const data = [];

    for (let i = 1; i < lines.length; i++) {
      if (lines[i].trim() === "") continue;

      const values = this.parseCSVLine(lines[i], delimiter);
      if (values.length === headers.length) {
        const row = {};
        headers.forEach((header, index) => {
          row[header] = values[index];
        });
        data.push(row);
      }
    }

    return { headers, data };
  }

  static detectDelimiter(line) {
    const delimiters = [",", ";", "\t", "|"];
    const counts = delimiters.map((delimiter) => ({
      delimiter,
      count: line.split(delimiter).length - 1,
    }));
    const mostCommon = counts.reduce((max, current) => (current.count > max.count ? current : max));

    return mostCommon.delimiter;
  }

  static parseCSVLine(line, delimiter = ";") {
    const result = [];
    let current = "";
    let inQuotes = false;

    for (let i = 0; i < line.length; i++) {
      const char = line[i];
      const nextChar = line[i + 1];

      if (char === '"') {
        if (inQuotes && nextChar === '"') {
          current += '"';
          i++;
        } else {
          inQuotes = !inQuotes;
        }
      } else if (char === delimiter && !inQuotes) {
        result.push(current.trim());
        current = "";
      } else {
        current += char;
      }
    }
    result.push(current.trim());

    return result;
  }

  static toChartData(csvData, chartType = "bar", themeColors = null) {
    const { headers, data } = csvData;

    if (headers.length === 0 || data.length === 0) {
      throw new Error("Données CSV vides");
    }

    const labelColumn = headers[0];
    const labels = data.map((row) => row[labelColumn]);

    const datasets = [];
    const colors = themeColors || [
      "hsl(276, 82%, 63%)",
      "hsl(276, 82%, 53%)",
      "hsl(276, 82%, 73%)",
      "hsl(276, 65%, 63%)",
      "hsl(276, 82%, 83%)",
      "hsl(276, 45%, 63%)",
      "hsl(276, 82%, 43%)",
    ];

    let colorIndex = 0;

    for (let i = 1; i < headers.length; i++) {
      const columnName = headers[i];
      const isNumericColumn = data.some((row) => {
        const value = parseFloat(row[columnName]);
        return !isNaN(value);
      });
      if (!isNumericColumn) {
        continue;
      }

      const columnData = data.map((row) => {
        const value = row[columnName];
        const numValue = parseFloat(value);
        return isNaN(numValue) ? 0 : numValue;
      });

      const color = colors[colorIndex % colors.length];
      colorIndex++;

      datasets.push({
        label: columnName,
        data: columnData,
        backgroundColor: chartType === "line" ? "transparent" : color,
        borderColor: color,
        borderWidth: 2,
        tension: 0.4,
        fill: false,
      });
    }

    return {
      labels,
      datasets,
    };
  }

  static getNumericColumns(csvData) {
    const { headers, data } = csvData;
    const numericColumns = [];

    for (let i = 1; i < headers.length; i++) {
      const columnName = headers[i];
      const isNumeric = data.every((row) => {
        const value = parseFloat(row[columnName]);
        return !isNaN(value);
      });

      if (isNumeric) {
        numericColumns.push(columnName);
      }
    }

    return numericColumns;
  }
}

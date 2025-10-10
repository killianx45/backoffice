export class ChartValidator {
    static CHART_REQUIREMENTS = {
        pie: {
            label: 'Camembert',
            requiredVariables: [
                { role: 'label', type: 'categorical', count: 1, description: 'Variable catégorielle (labels)' },
                { role: 'value', type: 'numeric', count: 1, description: 'Variable numérique (valeurs)' }
            ]
        },
        scatter: {
            label: 'Nuage de points',
            requiredVariables: [
                { role: 'x', type: 'numeric', count: 1, description: 'Variable numérique (axe X)' },
                { role: 'y', type: 'numeric', count: 1, description: 'Variable numérique (axe Y)' }
            ]
        },
        bar: {
            label: 'Barres',
            requiredVariables: [
                { role: 'x', type: 'categorical', count: 1, description: 'Variable catégorielle (axe X)' },
                { role: 'y', type: 'numeric', count: { min: 1 }, description: 'Variable(s) numérique(s) (axe Y)' }
            ]
        },
        line: {
            label: 'Ligne',
            requiredVariables: [
                { role: 'x', type: 'categorical', count: 1, description: 'Variable catégorielle (axe X)' },
                { role: 'y', type: 'numeric', count: { min: 1 }, description: 'Variable(s) numérique(s) (axe Y)' }
            ]
        }
    };

    static validate(chartType, selectedVariables, datasetVariables) {
        const errors = [];

        if (!this.CHART_REQUIREMENTS[chartType]) {
            return {
                valid: false,
                errors: [`Type de graphique inconnu: ${chartType}`]
            };
        }

        const requirements = this.CHART_REQUIREMENTS[chartType].requiredVariables;

        for (const req of requirements) {
            const varsWithRole = selectedVariables.filter(v => v.role === req.role);
            const expectedCount = typeof req.count === 'number' ? req.count : req.count.min;

            if (varsWithRole.length === 0) {
                errors.push(`Variable manquante pour le rôle "${req.role}": ${req.description}`);
                continue;
            }

            if (typeof req.count === 'number' && varsWithRole.length !== req.count) {
                errors.push(`Le rôle "${req.role}" nécessite exactement ${req.count} variable(s), ${varsWithRole.length} fournie(s)`);
            }

            if (req.count.min && varsWithRole.length < req.count.min) {
                errors.push(`Le rôle "${req.role}" nécessite au moins ${req.count.min} variable(s), ${varsWithRole.length} fournie(s)`);
            }

            for (const selectedVar of varsWithRole) {
                const datasetVar = datasetVariables.find(dv => dv.id === selectedVar.variableId);

                if (!datasetVar) {
                    errors.push(`Variable introuvable: ${selectedVar.name}`);
                    continue;
                }

                if (datasetVar.type !== req.type) {
                    errors.push(`Variable "${datasetVar.name}" doit être de type ${req.type === 'numeric' ? 'numérique' : 'catégorielle'} pour le rôle "${req.role}"`);
                }
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    static getRequiredVariablesForChartType(chartType) {
        return this.CHART_REQUIREMENTS[chartType]?.requiredVariables || [];
    }

    static getChartTypeLabel(chartType) {
        return this.CHART_REQUIREMENTS[chartType]?.label || chartType;
    }

    static getCompatibleChartTypes(datasetVariables) {
        const compatibleTypes = [];
        const categoricalVars = datasetVariables.filter(v => v.type === 'categorical');
        const numericVars = datasetVariables.filter(v => v.type === 'numeric');

        if (categoricalVars.length >= 1 && numericVars.length >= 1) {
            compatibleTypes.push('pie', 'bar', 'line');
        }

        if (numericVars.length >= 2) {
            compatibleTypes.push('scatter');
        }

        return compatibleTypes;
    }
}

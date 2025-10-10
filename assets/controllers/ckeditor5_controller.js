import { Controller } from '@hotwired/stimulus';
import EnhancedEditor from '../ckeditor5.js';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        content: String
    };

    async connect() {
        const editorElement = this.element.querySelector('textarea');
        if (!editorElement) {
            if (process.env.NODE_ENV === 'development') {
                console.error('Textarea element not found for CKEditor');
            }
            return;
        }

        const theme = await this.fetchActiveTheme();

        const config = {
            initialData: this.contentValue || '',
        };

        EnhancedEditor.create(editorElement, config)
            .then(editor => {
                this.editor = editor;

                if (theme) {
                    this.applyThemeStyles(editor, theme);
                }

                editor.model.document.on('change:data', () => {
                    this.updateHiddenInput(editor);
                });

                this.addCustomImageButton(editor);
                this.addCustomChartButton(editor);

                editor.editing.view.document.on('click', (evt, data) => {
                    const viewElement = data.target;
                    if (viewElement && viewElement.name === 'img') {
                        setTimeout(() => this.addDeleteButtonToImage(viewElement), 100);
                    }
                    if (viewElement && viewElement.hasClass && viewElement.hasClass('ck-chart-widget')) {
                        setTimeout(() => this.addActionsToChart(viewElement), 100);
                    }
                });
            })
            .catch(error => {
                if (process.env.NODE_ENV === 'development') {
                    console.error('CKEditor initialization error:', error);
                }
            });
    }

    async fetchActiveTheme() {
        try {
            const response = await fetch('/api/theme/active');
            if (!response.ok) {
                return null;
            }
            return await response.json();
        } catch (error) {
            console.warn('Could not fetch active theme:', error);
            return null;
        }
    }

    applyThemeStyles(editor, theme) {
        const config = theme.configuration;
        const editorElement = editor.ui.view.editable.element;

        if (!editorElement) return;

        const styleElement = document.createElement('style');
        styleElement.id = 'ckeditor-theme-styles';
        styleElement.textContent = `
            .ck-content {
                font-family: ${config.typography.bodyFont === 'montserrat' ? 'Montserrat' : 'Darker Grotesque'} !important;
                font-size: ${config.typography.bodySize === 'small' ? '0.9rem' : config.typography.bodySize === 'large' ? '1.1rem' : '1rem'} !important;
                line-height: ${config.typography.lineHeight === 'tight' ? '1.4' : config.typography.lineHeight === 'relaxed' ? '1.8' : '1.6'} !important;
            }

            .ck-content h1,
            .ck-content h2,
            .ck-content h3,
            .ck-content h4,
            .ck-content h5,
            .ck-content h6 {
                font-family: ${config.typography.headingFont === 'darker-grotesque' ? 'Darker Grotesque' : 'Montserrat'} !important;
                color: ${config.colors.primary} !important;
            }

            .ck-content blockquote {
                border-left: 4px solid ${config.colors.accent} !important;
                padding-left: 1rem !important;
                font-style: italic !important;
                opacity: 0.8 !important;
            }

            .ck-content a {
                color: ${config.colors.accent} !important;
            }

            .ck-content code {
                background-color: ${config.colors.base200} !important;
                padding: 0.25rem 0.5rem !important;
                border-radius: 0.25rem !important;
            }

            .ck-content pre {
                background-color: ${config.colors.base200} !important;
                padding: 1rem !important;
                border-radius: 0.5rem !important;
            }
        `;

        const existingStyle = document.getElementById('ckeditor-theme-styles');
        if (existingStyle) {
            existingStyle.remove();
        }

        document.head.appendChild(styleElement);
    }

    disconnect() {
        if (this.editor) {
            this.editor.destroy()
                .catch(error => {
                    if (process.env.NODE_ENV === 'development') {
                        console.error('CKEditor destruction error:', error);
                    }
                });
        }
    }

    addCustomImageButton(editor) {
        const toolbarView = editor.ui.view.toolbar;
        const imageButton = document.createElement('button');
        imageButton.className = 'ck ck-button ck-off';
        imageButton.type = 'button';

        imageButton.style.display = 'inline-flex';
        imageButton.style.alignItems = 'center';
        imageButton.style.justifyContent = 'center';
        imageButton.style.padding = '0 var(--ck-spacing-small)';
        imageButton.style.minHeight = 'var(--ck-ui-component-min-height)';
        imageButton.style.cursor = 'pointer';
        imageButton.style.border = '1px solid transparent';
        imageButton.style.borderRadius = 'var(--ck-border-radius)';
        imageButton.style.backgroundColor = 'transparent';

        imageButton.innerHTML = `
            <span class="ck ck-button__label" style="display: inline-block;">🖼️ Image</span>
        `;
        imageButton.title = 'Insérer une image';

        imageButton.addEventListener('mouseover', () => {
            imageButton.style.backgroundColor = 'var(--ck-color-button-default-hover-background)';
        });

        imageButton.addEventListener('mouseout', () => {
            imageButton.style.backgroundColor = 'transparent';
        });

        imageButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.openMediaManager();
        });

        const toolbarElement = toolbarView.element;
        const items = toolbarElement.querySelectorAll('.ck-toolbar__items');
        if (items.length > 0) {
            items[0].appendChild(imageButton);
        } else {
            toolbarElement.appendChild(imageButton);
        }
    }

    updateHiddenInput(editor) {
        const html = editor.getData();
        const textarea = this.element.querySelector('textarea');
        if (textarea) {
            textarea.value = html;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    openMediaManager() {
        const event = new CustomEvent('ckeditor:open-media-manager', {
            detail: {
                callback: (imageUrl) => this.insertImage(imageUrl)
            }
        });
        window.dispatchEvent(event);
    }

    insertImage(url) {
        if (url && this.editor) {
            this.editor.model.change(writer => {
                const imageElement = writer.createElement('imageBlock', { src: url });
                this.editor.model.insertContent(imageElement, this.editor.model.document.selection);
            });
        }
    }

    addDeleteButtonToImage(viewElement) {
        const figureElement = viewElement.parentElement;
        if (!figureElement || figureElement.tagName.toLowerCase() !== 'figure') return;

        const existingButton = figureElement.querySelector('.ck-image-delete-btn');
        if (existingButton) return;

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'ck-image-delete-btn btn btn-circle btn-xs btn-error';
        deleteBtn.style.position = 'absolute';
        deleteBtn.style.top = '8px';
        deleteBtn.style.right = '8px';
        deleteBtn.style.zIndex = '10';
        deleteBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        `;
        deleteBtn.type = 'button';

        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.deleteImage(viewElement);
        });

        figureElement.style.position = 'relative';
        figureElement.appendChild(deleteBtn);
    }

    deleteImage(viewElement) {
        if (!this.editor) return;

        const modelElement = this.editor.editing.mapper.toModelElement(viewElement);
        if (modelElement) {
            this.editor.model.change(writer => {
                let elementToRemove = modelElement;

                if (modelElement.parent && modelElement.parent.name === 'imageBlock') {
                    elementToRemove = modelElement.parent;
                }

                writer.remove(elementToRemove);
            });
        }
    }

    addCustomChartButton(editor) {
        const toolbarView = editor.ui.view.toolbar;
        const chartButton = document.createElement('button');
        chartButton.className = 'ck ck-button ck-off';
        chartButton.type = 'button';

        chartButton.style.display = 'inline-flex';
        chartButton.style.alignItems = 'center';
        chartButton.style.justifyContent = 'center';
        chartButton.style.padding = '0 var(--ck-spacing-small)';
        chartButton.style.minHeight = 'var(--ck-ui-component-min-height)';
        chartButton.style.cursor = 'pointer';
        chartButton.style.border = '1px solid transparent';
        chartButton.style.borderRadius = 'var(--ck-border-radius)';
        chartButton.style.backgroundColor = 'transparent';

        chartButton.innerHTML = `
            <span class="ck ck-button__label" style="display: inline-block;">📊 Graphique</span>
        `;
        chartButton.title = 'Insérer un graphique';

        chartButton.addEventListener('mouseover', () => {
            chartButton.style.backgroundColor = 'var(--ck-color-button-default-hover-background)';
        });

        chartButton.addEventListener('mouseout', () => {
            chartButton.style.backgroundColor = 'transparent';
        });

        chartButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openChartManager();
        });

        const toolbarElement = toolbarView.element;
        const items = toolbarElement.querySelectorAll('.ck-toolbar__items');
        if (items.length > 0) {
            items[0].appendChild(chartButton);
        } else {
            toolbarElement.appendChild(chartButton);
        }
    }

    openChartManager(isEditMode = false, existingElement = null) {
        const event = new CustomEvent('ckeditor:open-chart-manager', {
            detail: {
                callback: (chartConfig, isEdit, elementToEdit) => this.insertChart(chartConfig, isEdit, elementToEdit),
                isEditMode: isEditMode,
                element: existingElement
            }
        });
        window.dispatchEvent(event);
    }

    insertChart(chartConfig, isEditMode = false, elementToEdit = null) {
        if (!chartConfig || !this.editor) {
            return;
        }

        this.editor.model.change(writer => {
            if (isEditMode && elementToEdit) {
                const modelElement = this.editor.editing.mapper.toModelElement(elementToEdit);
                if (modelElement) {
                    writer.setAttribute('csvFile', chartConfig.csvFile, modelElement);
                    writer.setAttribute('chartType', chartConfig.chartType, modelElement);
                    writer.setAttribute('title', chartConfig.title, modelElement);
                    writer.setAttribute('xAxisLabel', chartConfig.xAxisLabel, modelElement);
                    writer.setAttribute('yAxisLabel', chartConfig.yAxisLabel, modelElement);
                }
            } else {
                const chartElement = writer.createElement('chartWidget', {
                    csvFile: chartConfig.csvFile,
                    chartType: chartConfig.chartType,
                    title: chartConfig.title,
                    xAxisLabel: chartConfig.xAxisLabel,
                    yAxisLabel: chartConfig.yAxisLabel
                });
                this.editor.model.insertContent(chartElement, this.editor.model.document.selection);
            }
        });
    }

    addActionsToChart(viewElement) {
        const chartWidget = viewElement.parentElement || viewElement;
        if (!chartWidget) return;

        const existingActions = chartWidget.querySelector('.ck-chart-actions');
        if (existingActions) return;

        const actionsContainer = document.createElement('div');
        actionsContainer.className = 'ck-chart-actions';
        actionsContainer.style.position = 'absolute';
        actionsContainer.style.top = '8px';
        actionsContainer.style.right = '8px';
        actionsContainer.style.zIndex = '10';
        actionsContainer.style.display = 'flex';
        actionsContainer.style.gap = '4px';

        const editBtn = document.createElement('button');
        editBtn.className = 'btn btn-circle btn-xs btn-info';
        editBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
        `;
        editBtn.type = 'button';
        editBtn.title = 'Modifier le graphique';

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-circle btn-xs btn-error';
        deleteBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        `;
        deleteBtn.type = 'button';
        deleteBtn.title = 'Supprimer le graphique';

        editBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.editChart(viewElement);
        });

        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.deleteChart(viewElement);
        });

        actionsContainer.appendChild(editBtn);
        actionsContainer.appendChild(deleteBtn);

        if (chartWidget.style) {
            chartWidget.style.position = 'relative';
        }
        chartWidget.appendChild(actionsContainer);
    }

    editChart(viewElement) {
        if (!this.editor) return;

        const modelElement = this.editor.editing.mapper.toModelElement(viewElement);
        if (modelElement) {
            const chartData = {
                csvFile: modelElement.getAttribute('csvFile'),
                chartType: modelElement.getAttribute('chartType'),
                title: modelElement.getAttribute('title'),
                xAxisLabel: modelElement.getAttribute('xAxisLabel'),
                yAxisLabel: modelElement.getAttribute('yAxisLabel')
            };

            const elementWithData = {
                getAttribute: (attr) => chartData[attr]
            };

            this.openChartManager(true, elementWithData);
        }
    }

    deleteChart(viewElement) {
        if (!this.editor) return;

        const modelElement = this.editor.editing.mapper.toModelElement(viewElement);
        if (modelElement) {
            this.editor.model.change(writer => {
                writer.remove(modelElement);
            });
        }
    }
}

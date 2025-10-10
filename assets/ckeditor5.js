import {
    Autoformat,
    AutoLink,
    Bold,
    ClassicEditor,
    Essentials,
    Heading,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    Italic,
    Link,
    List,
    Paragraph,
    Strikethrough,
    Underline,
    Undo,
    BlockQuote,
    Code,
    CodeBlock,
    Plugin,
    Widget,
    toWidget
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

class ChartWidget extends Plugin {
    static get requires() {
        return [Widget];
    }

    init() {
        this._defineSchema();
        this._defineConverters();
    }

    _defineSchema() {
        const schema = this.editor.model.schema;

        schema.register('chartWidget', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['csvFile', 'chartType', 'title', 'xAxisLabel', 'yAxisLabel']
        });
    }

    _defineConverters() {
        const editor = this.editor;
        const conversion = editor.conversion;

        conversion.for('upcast').elementToElement({
            view: {
                name: 'div',
                classes: 'ck-chart'
            },
            model: (viewElement, { writer }) => {
                return writer.createElement('chartWidget', {
                    csvFile: viewElement.getAttribute('data-csv-file'),
                    chartType: viewElement.getAttribute('data-chart-type'),
                    title: viewElement.getAttribute('data-title'),
                    xAxisLabel: viewElement.getAttribute('data-x-axis-label'),
                    yAxisLabel: viewElement.getAttribute('data-y-axis-label')
                });
            }
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'chartWidget',
            view: (modelElement, { writer }) => {
                const csvFile = modelElement.getAttribute('csvFile');
                const chartType = modelElement.getAttribute('chartType');
                const title = modelElement.getAttribute('title');
                const xAxisLabel = modelElement.getAttribute('xAxisLabel');
                const yAxisLabel = modelElement.getAttribute('yAxisLabel');

                return writer.createContainerElement('div', {
                    class: 'ck-chart',
                    'data-csv-file': csvFile,
                    'data-chart-type': chartType,
                    'data-title': title,
                    'data-x-axis-label': xAxisLabel,
                    'data-y-axis-label': yAxisLabel
                });
            }
        });

        conversion.for('editingDowncast').elementToElement({
            model: 'chartWidget',
            view: (modelElement, { writer }) => {
                const csvFile = modelElement.getAttribute('csvFile');
                const chartType = modelElement.getAttribute('chartType');
                const title = modelElement.getAttribute('title');

                const chartDiv = writer.createContainerElement('div', {
                    class: 'ck-chart-widget',
                    'data-csv-file': csvFile,
                    'data-chart-type': chartType,
                    'data-title': title,
                    'data-x-axis-label': modelElement.getAttribute('xAxisLabel'),
                    'data-y-axis-label': modelElement.getAttribute('yAxisLabel')
                });

                const chartIcon = writer.createRawElement('div', {
                    class: 'ck-chart-preview'
                }, function(domElement) {
                    domElement.innerHTML = `
                        <div style="padding: 20px; background: #f0f0f0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin: 0 auto; color: #666;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p style="margin-top: 10px; font-weight: bold; color: #333;">📊 ${title || 'Graphique'}</p>
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Type: ${chartType === 'bar' ? 'Barres' : 'Ligne'}</p>
                            <p style="font-size: 12px; color: #666;">CSV: ${csvFile}</p>
                        </div>
                    `;
                });

                writer.insert(writer.createPositionAt(chartDiv, 0), chartIcon);

                return toWidget(chartDiv, writer, { label: 'chart widget' });
            }
        });
    }
}

export default class EnhancedEditor extends ClassicEditor {}

EnhancedEditor.builtinPlugins = [
    Autoformat,
    AutoLink,
    Bold,
    BlockQuote,
    Code,
    CodeBlock,
    ChartWidget,
    Essentials,
    Heading,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    Italic,
    Link,
    List,
    Paragraph,
    Strikethrough,
    Underline,
    Undo
];

EnhancedEditor.defaultConfig = {
    licenseKey: 'GPL',
    toolbar: {
        items: [
            'undo', 'redo',
            '|',
            'heading',
            '|',
            'bold', 'italic', 'underline', 'strikethrough',
            '|',
            'link',
            '|',
            'bulletedList', 'numberedList',
            '|',
            'code', 'codeBlock',
            '|',
            'blockQuote'
        ],
        shouldNotGroupWhenFull: true
    },
    heading: {
        options: [
            {
                model: 'paragraph',
                title: 'Paragraph',
                class: 'ck-heading_paragraph'
            },
            {
                model: 'heading1',
                view: 'h1',
                title: 'Heading 1',
                class: 'ck-heading_heading1'
            },
            {
                model: 'heading2',
                view: 'h2',
                title: 'Heading 2',
                class: 'ck-heading_heading2'
            },
            {
                model: 'heading3',
                view: 'h3',
                title: 'Heading 3',
                class: 'ck-heading_heading3'
            }
        ]
    },
    image: {
        toolbar: [
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side',
            '|',
            'toggleImageCaption',
            'imageTextAlternative',
            '|',
            'resizeImage'
        ],
        styles: ['block', 'inline', 'side']
    },
    link: {
        defaultProtocol: 'https://',
        decorators: {
            openInNewTab: {
                mode: 'manual',
                label: 'Open in new tab',
                attributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer'
                }
            }
        },
        addTargetToExternalLinks: true
    },
    list: {
        properties: {
            styles: true,
            startIndex: true,
            reversed: true
        }
    }
};

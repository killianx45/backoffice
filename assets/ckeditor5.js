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
    CodeBlock
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

export default class EnhancedEditor extends ClassicEditor {}

EnhancedEditor.builtinPlugins = [
    Autoformat,
    AutoLink,
    Bold,
    BlockQuote,
    Code,
    CodeBlock,
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

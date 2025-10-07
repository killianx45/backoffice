import { Controller } from '@hotwired/stimulus';
import EnhancedEditor from '../ckeditor5.js';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        content: String
    };

    connect() {
        const editorElement = this.element.querySelector('textarea');
        if (!editorElement) {
            if (process.env.NODE_ENV === 'development') {
                console.error('Textarea element not found for CKEditor');
            }
            return;
        }

        const config = {
            initialData: this.contentValue || '',
        };

        EnhancedEditor.create(editorElement, config)
            .then(editor => {
                this.editor = editor;

                editor.model.document.on('change:data', () => {
                    this.updateHiddenInput(editor);
                });

                this.addCustomImageButton(editor);

                editor.editing.view.document.on('click', (evt, data) => {
                    const viewElement = data.target;
                    if (viewElement && viewElement.name === 'img') {
                        setTimeout(() => this.addDeleteButtonToImage(viewElement), 100);
                    }
                });
            })
            .catch(error => {
                if (process.env.NODE_ENV === 'development') {
                    console.error('CKEditor initialization error:', error);
                }
            });
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
}

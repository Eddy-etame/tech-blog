import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submitBtn', 'btnText'];

    async handleSubmit(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const form = this.element;
        const formData = new FormData(form);
        const action = form.action;
        const submitBtn = this.hasSubmitBtnTarget ? this.submitBtnTarget : form.querySelector('button[type="submit"]');

        if (submitBtn) {
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        }

        this.clearError();

        try {
            const response = await fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (data.formHtml) {
                    const wrapper = document.getElementById('comment-form-wrapper');
                    if (wrapper) {
                        wrapper.outerHTML = data.formHtml;
                        const newWrapper = document.getElementById('comment-form-wrapper');
                        if (newWrapper) {
                            newWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            const firstInput = newWrapper.querySelector('input, textarea');
                            if (firstInput) firstInput.focus();
                        }
                    }
                }
                this.showError(data.error || 'Une erreur s\'est produite. Veuillez réessayer.');
                return;
            }

            const commentsList = document.getElementById('comments-list');
            const commentsEmpty = document.getElementById('comments-empty');

            if (commentsEmpty) commentsEmpty.remove();
            let newCommentEl = null;
            if (commentsList && data.commentHtml) {
                commentsList.insertAdjacentHTML('beforeend', data.commentHtml);
                newCommentEl = commentsList.lastElementChild;
                if (newCommentEl) {
                    newCommentEl.classList.add('comment-added');
                    setTimeout(() => newCommentEl.classList.remove('comment-added'), 2000);
                    newCommentEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            const formWrapper = document.getElementById('comment-form-wrapper');
            if (formWrapper && data.formHtml) {
                formWrapper.outerHTML = data.formHtml;
                const newWrapper = document.getElementById('comment-form-wrapper');
                if (newWrapper) {
                    const firstInput = newWrapper.querySelector('input, textarea');
                    if (firstInput) firstInput.focus();
                }
            }
        } catch (err) {
            this.showError('Une erreur s\'est produite. Veuillez réessayer.');
        } finally {
            if (submitBtn) {
                submitBtn.classList.remove('btn-loading');
                submitBtn.disabled = false;
            }
        }
    }

    showError(message) {
        this.clearError();
        const section = document.getElementById('comments-section');
        if (section) {
            const div = document.createElement('div');
            div.className = 'alert-error';
            div.textContent = message;
            section.insertBefore(div, section.firstChild);
        }
    }

    clearError() {
        const section = document.getElementById('comments-section');
        if (section) {
            const existing = section.querySelector('.alert-error');
            if (existing) existing.remove();
        }
    }
}

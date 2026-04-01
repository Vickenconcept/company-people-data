import { createApp, h, nextTick, ref, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

function escapeHtml(unsafe) {
    return unsafe
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function plainTextToHtml(text) {
    const trimmed = String(text ?? '').trim();
    if (trimmed === '') return '<p></p>';

    const paragraphs = trimmed.split(/\r?\n\r?\n/);
    if (paragraphs.length > 1) {
        return paragraphs
            .map((p) => {
                const escaped = escapeHtml(p.trim());
                return escaped === '' ? '<p></p>' : `<p>${escaped.replaceAll(/\r?\n/g, '<br>')}</p>`;
            })
            .join('');
    }

    const escaped = escapeHtml(trimmed);
    return `<p>${escaped.replaceAll(/\r?\n/g, '<br>')}</p>`;
}

function normalizeBodyToHtml(body) {
    const raw = String(body ?? '');
    const trimmed = raw.trim();

    if (trimmed === '') {
        return '<p></p>';
    }

    // Handle older payloads that accidentally stored {"body":"..."} JSON text.
    if (trimmed.startsWith('{') && trimmed.includes('"body"')) {
        try {
            const parsed = JSON.parse(trimmed);
            return normalizeBodyToHtml(parsed?.body ?? '');
        } catch {
            // fall through
        }
    }

    // If it's already HTML-ish, normalize full documents to body inner HTML.
    if (trimmed.includes('<') && trimmed.includes('>')) {
        if (/<html[\s>]/i.test(trimmed) || /<body[\s>]/i.test(trimmed)) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(trimmed, 'text/html');
            const bodyHtml = doc?.body?.innerHTML?.trim() ?? '';
            if (bodyHtml !== '') {
                return bodyHtml;
            }
        }

        return trimmed;
    }

    return plainTextToHtml(raw);
}

function getLeadEmailQuillStateBody() {
    const stateEl = document.getElementById('lead-email-quill-state');
    if (!stateEl) return '';

    try {
        const parsed = JSON.parse(stateEl.textContent || '{}');
        return parsed.body ?? '';
    } catch {
        return stateEl.textContent ?? '';
    }
}

function findLivewireComponentFrom(el) {
    if (!window.Livewire?.find) return null;

    let cur = el;
    while (cur) {
        if (cur.hasAttribute && cur.hasAttribute('wire:id')) {
            const id = cur.getAttribute('wire:id');
            if (id) return window.Livewire.find(id);
        }

        cur = cur.parentElement;
    }

    return null;
}

let quillApp = null;
let syncFromServer = null;
let lastStateBodyHtml = null;
let contentAutosaveTimer = null;
let isSyncingFromServer = false;
let quillObserver = null;
let dropifyAssetsPromise = null;

function mountOrSyncLeadEmailQuill() {
    const editorEl = document.getElementById('lead-email-quill-editor');
    const stateBody = getLeadEmailQuillStateBody();

    if (!editorEl || editorEl.dataset.mounted === 'true') {
        if (syncFromServer) {
            const nextHtml = normalizeBodyToHtml(stateBody);
            if (nextHtml !== lastStateBodyHtml) {
                lastStateBodyHtml = nextHtml;
                isSyncingFromServer = true;
                syncFromServer(nextHtml);
                nextTick(() => {
                    isSyncingFromServer = false;
                });
            }
        }
        return;
    }

    const livewire = findLivewireComponentFrom(editorEl);

    editorEl.dataset.mounted = 'true';

    quillApp = createApp({
        components: { QuillEditor },
        setup() {
            const content = ref(normalizeBodyToHtml(stateBody));
            const setContent = (val) => {
                content.value = val;
            };

            lastStateBodyHtml = content.value;

            // Vue editor -> Livewire
            watch(
                content,
                (val) => {
                    if (!livewire || isSyncingFromServer) return;

                    if (contentAutosaveTimer) clearTimeout(contentAutosaveTimer);
                    contentAutosaveTimer = setTimeout(async () => {
                        // Persist editor HTML to DB.
                        await livewire.call('updateEmailBody', val);
                    }, 600);
                },
                { flush: 'post' }
            );

            // Livewire -> Vue editor
            syncFromServer = (html) => {
                content.value = normalizeBodyToHtml(html);
            };

            return { content, setContent };
        },
        render() {
            const toolbar = [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean'],
            ];

            // Avoid `template:` so we don't rely on Vue runtime compilation.
            return h(QuillEditor, {
                contentType: 'html',
                theme: 'snow',
                style: { height: '320px' },
                toolbar,
                content: this.content,
                'onUpdate:content': this.setContent,
            });
        },
    });

    quillApp.mount(editorEl);
}

function mountOrSyncLeadEmailQuillDebounced() {
    // Lightweight debounce for rapid Livewire updates.
    clearTimeout(window.__leadEmailQuillDebounce);
    window.__leadEmailQuillDebounce = setTimeout(mountOrSyncLeadEmailQuill, 50);
}

function setupLeadEmailQuillObserver() {
    if (quillObserver) {
        quillObserver.disconnect();
    }

    quillObserver = new MutationObserver(mountOrSyncLeadEmailQuillDebounced);
    quillObserver.observe(document.body, { childList: true, subtree: true });

    // Initial attempt (in case modal is already open on first load).
    mountOrSyncLeadEmailQuill();
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }

            existing.addEventListener('load', () => {
                existing.dataset.loaded = 'true';
                resolve();
            }, { once: true });

            existing.addEventListener('error', () => reject(new Error(`Failed to load script: ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });
        script.addEventListener('error', () => reject(new Error(`Failed to load script: ${src}`)), { once: true });
        document.head.appendChild(script);
    });
}

function ensureStylesheet(href) {
    if (document.querySelector(`link[href="${href}"]`)) {
        return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

function ensureDropifyAssets() {
    if (dropifyAssetsPromise) {
        return dropifyAssetsPromise;
    }

    ensureStylesheet('https://jeremyfagis.github.io/dropify/dist/css/dropify.min.css');

    dropifyAssetsPromise = loadScript('https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js')
        .then(() => loadScript('https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js'));

    return dropifyAssetsPromise;
}

function initDropify() {
    const fileInputs = document.querySelectorAll('.dropify');
    if (!fileInputs.length) {
        return;
    }

    ensureDropifyAssets()
        .then(() => {
            const $ = window.jQuery;
            if (!$ || typeof $.fn.dropify !== 'function') {
                return;
            }

            $('.dropify').each(function initOne() {
                const existing = $(this).data('dropify');
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                }
            });

            $('.dropify').dropify();
        })
        .catch(() => {
            // Keep page functional even if Dropify CDN fails.
        });
}

function bootstrapPageIntegrations() {
    setupLeadEmailQuillObserver();
    initDropify();
}

document.addEventListener('DOMContentLoaded', bootstrapPageIntegrations);
document.addEventListener('livewire:initialized', bootstrapPageIntegrations);
document.addEventListener('livewire:navigated', bootstrapPageIntegrations);


// Visual editor for admin pages. The page is still stored as Markdown in the <textarea>,
// so the server-side safe renderer (raw HTML stripped, unsafe links dropped) is unchanged.
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Markdown } from 'tiptap-markdown';

const ICONS = {
    bold: 'B',
    italic: 'I',
    h2: 'H2',
    h3: 'H3',
    bullet: '• List',
    ordered: '1. List',
    quote: '❝',
    link: 'Link',
    image: 'Image',
    hr: '―',
    undo: '↶',
    redo: '↷',
};

export function initPageEditor(textarea) {
    const uploadUrl = textarea.dataset.uploadUrl;
    const csrf = textarea.form?.querySelector('input[name="_token"]')?.value;

    const wrapper = document.createElement('div');
    wrapper.className = 'editor rounded-xl border border-slate-300 bg-white';
    const toolbar = document.createElement('div');
    toolbar.className = 'editor-toolbar flex flex-wrap gap-1 border-b border-slate-200 p-2';
    const mount = document.createElement('div');
    wrapper.append(toolbar, mount);
    textarea.insertAdjacentElement('beforebegin', wrapper);
    textarea.classList.add('hidden');
    textarea.removeAttribute('required'); // hidden fields can't show native validation bubbles

    const editor = new Editor({
        element: mount,
        injectCSS: false, // CSP: no inline <style>; styles live in app.css
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                underline: false, // no Markdown equivalent
                code: false,
                codeBlock: false,
                link: { openOnClick: false, autolink: true, protocols: ['https', 'mailto'] },
            }),
            Image.configure({ inline: false }),
            Markdown.configure({ html: false, linkify: true, breaks: false }),
        ],
        content: textarea.value,
        editorProps: { attributes: { class: 'page-content editor-area min-h-[420px] px-4 py-3 focus:outline-none' } },
        onUpdate: ({ editor: e }) => {
            textarea.value = e.storage.markdown.getMarkdown();
        },
    });

    const chain = () => editor.chain().focus();
    const buttons = [
        ['bold', () => chain().toggleBold().run(), () => editor.isActive('bold')],
        ['italic', () => chain().toggleItalic().run(), () => editor.isActive('italic')],
        ['h2', () => chain().toggleHeading({ level: 2 }).run(), () => editor.isActive('heading', { level: 2 })],
        ['h3', () => chain().toggleHeading({ level: 3 }).run(), () => editor.isActive('heading', { level: 3 })],
        ['bullet', () => chain().toggleBulletList().run(), () => editor.isActive('bulletList')],
        ['ordered', () => chain().toggleOrderedList().run(), () => editor.isActive('orderedList')],
        ['quote', () => chain().toggleBlockquote().run(), () => editor.isActive('blockquote')],
        ['link', () => setLink(), () => editor.isActive('link')],
        ['image', () => pickImage(), () => false],
        ['hr', () => chain().setHorizontalRule().run(), () => false],
        ['undo', () => chain().undo().run(), () => false],
        ['redo', () => chain().redo().run(), () => false],
    ];

    const els = buttons.map(([name, run]) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = ICONS[name];
        b.title = name;
        b.className = 'editor-btn';
        if (name === 'bold') b.classList.add('font-bold');
        if (name === 'italic') b.classList.add('italic');
        b.addEventListener('click', run);
        toolbar.appendChild(b);
        return b;
    });
    const refresh = () => buttons.forEach(([, , active], i) => els[i].classList.toggle('is-active', active()));
    editor.on('selectionUpdate', refresh);
    editor.on('transaction', refresh);

    function setLink() {
        const prev = editor.getAttributes('link').href ?? '';
        const url = window.prompt('Link address (https://… or mailto:…). Leave empty to remove.', prev);
        if (url === null) return;
        if (url.trim() === '') {
            chain().extendMarkRange('link').unsetLink().run();
            return;
        }
        if (!/^(https:\/\/|mailto:|\/)/i.test(url.trim())) {
            window.alert('Only https://, mailto: or site-relative (/…) links are allowed.');
            return;
        }
        chain().extendMarkRange('link').setLink({ href: url.trim() }).run();
    }

    function pickImage() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp';
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                window.alert('The image must be 5 MB or smaller.');
                return;
            }
            const body = new FormData();
            body.append('image', file);
            const res = await fetch(uploadUrl, {
                method: 'POST',
                body,
                headers: { 'X-CSRF-TOKEN': csrf ?? '', Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.url) {
                window.alert(data.message ?? 'Upload failed.');
                return;
            }
            const alt = window.prompt('Short description of the image (for screen readers):', '') ?? '';
            chain().setImage({ src: data.url, alt }).run();
        });
        input.click();
    }

    // Make sure the latest Markdown is submitted.
    textarea.form?.addEventListener('submit', () => {
        textarea.value = editor.storage.markdown.getMarkdown();
    });
}

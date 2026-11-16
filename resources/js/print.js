// "Print" prints only the results (data-print="<selector>"), not the whole page.
// The target is cloned into #print-root; CSS hides everything else while printing.

export function initPrint(button) {
    button.addEventListener('click', () => {
        const targets = button.dataset.print ? [...document.querySelectorAll(button.dataset.print)] : [];
        if (targets.length === 0) {
            window.print();
            return;
        }

        document.getElementById('print-root')?.remove();
        const root = document.createElement('div');
        root.id = 'print-root';

        const head = document.createElement('div');
        head.className = 'print-head';
        const brand = document.createElement('p');
        brand.className = 'print-brand';
        brand.textContent = 'SCIMSCORE';
        head.appendChild(brand);
        if (button.dataset.printTitle) {
            const h = document.createElement('h1');
            h.textContent = button.dataset.printTitle;
            head.appendChild(h);
        }
        const date = document.createElement('p');
        date.className = 'print-date';
        date.textContent = `Printed ${new Date().toLocaleString()}`;
        head.appendChild(date);
        root.appendChild(head);

        for (const t of targets) {
            const clone = t.cloneNode(true);
            clone.removeAttribute('id');
            clone.querySelectorAll('[id]').forEach((el) => el.removeAttribute('id'));
            clone.querySelectorAll('button, form, [data-print-exclude]').forEach((el) => el.remove());
            root.appendChild(clone);
        }

        document.body.appendChild(root);
        document.body.classList.add('is-printing');

        const cleanup = () => {
            document.body.classList.remove('is-printing');
            root.remove();
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        window.print();
    });
}

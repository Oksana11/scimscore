// Institution autocomplete for the profile form.
// Results come from our own endpoint (local DB + ROR), never from third parties directly.
// All DOM is built with textContent (no innerHTML) to avoid XSS from registry data.

const DEBOUNCE_MS = 300;

export function initInstitutionPicker(root) {
    const search = root.querySelector('[data-institution-search]');
    const list = root.querySelector('[data-institution-list]');
    const ref = root.querySelector('[data-institution-ref]');
    const display = root.querySelector('[data-institution-display]');
    const custom = root.querySelector('[data-institution-custom]');
    const customName = custom?.querySelector('input');
    const url = root.dataset.searchUrl;

    let timer = null;
    let controller = null;

    const close = () => {
        list.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
    };

    const choose = (value, name) => {
        ref.value = value;
        display.value = name;
        search.value = name;
        custom.classList.toggle('hidden', value !== 'custom');
        if (value === 'custom') {
            customName.value = customName.value || search.dataset.lastQuery || '';
            customName.focus();
        }
        close();
    };

    const option = (label, sub, value, name) => {
        const li = document.createElement('li');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'block w-full px-3 py-2 text-left hover:bg-slate-100 focus:bg-slate-100 focus:outline-none';
        btn.setAttribute('role', 'option');
        const title = document.createElement('span');
        title.textContent = label;
        btn.appendChild(title);
        if (sub) {
            const small = document.createElement('span');
            small.className = 'ml-2 text-xs text-slate-500';
            small.textContent = sub;
            btn.appendChild(small);
        }
        btn.addEventListener('click', () => choose(value, name));
        li.appendChild(btn);
        return li;
    };

    const render = (data, query) => {
        list.replaceChildren();
        for (const i of data.local ?? []) {
            list.appendChild(option(i.name, [i.country_code, i.custom ? 'custom' : null].filter(Boolean).join(' · '), i.ref, i.name));
        }
        for (const i of data.ror ?? []) {
            list.appendChild(option(i.name, [i.city, i.country_code, 'ROR'].filter(Boolean).join(' · '), i.ref, i.name));
        }
        list.appendChild(option(`+ Add new institution${query ? `: “${query}”` : ''}`, '', 'custom', query));
        list.classList.remove('hidden');
        search.setAttribute('aria-expanded', 'true');
    };

    search.addEventListener('input', () => {
        const q = search.value.trim();
        ref.value = '';
        search.dataset.lastQuery = q;
        clearTimeout(timer);
        if (q.length < 2) {
            close();
            return;
        }
        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            try {
                const res = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                render(res.ok ? await res.json() : {}, q);
            } catch (e) {
                if (e.name !== 'AbortError') render({}, q);
            }
        }, DEBOUNCE_MS);
    });

    search.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) close();
    });
}

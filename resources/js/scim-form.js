// Live progress, section sums and running total while filling in the SCIM questionnaire.
// Display only: the server validates and recalculates everything.
export function initScimForm(root) {
    const form = root.closest('form') ?? document;
    const answered = form.querySelector('[data-scim-answered]');
    const progress = form.querySelector('[data-scim-progress]');
    const totalEls = form.querySelectorAll('[data-scim-total]');

    const update = () => {
        let total = 0;
        let count = 0;
        root.querySelectorAll('[data-scim-section]').forEach((section) => {
            let sum = 0;
            section.querySelectorAll('input[type="radio"]:checked').forEach((r) => {
                sum += parseInt(r.value, 10) || 0;
                count += 1;
            });
            const out = section.querySelector('[data-section-sum]');
            if (out) out.textContent = String(sum);
            total += sum;
        });
        totalEls.forEach((el) => (el.textContent = String(total)));
        if (answered) answered.textContent = String(count);
        if (progress) progress.value = count;
    };

    root.addEventListener('change', update);

    // Local development only (button rendered when APP_ENV=local): random answers.
    form.querySelector('[data-scim-demo]')?.addEventListener('click', () => {
        const names = new Set([...root.querySelectorAll('input[type="radio"]')].map((r) => r.name));
        names.forEach((name) => {
            const options = root.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`);
            options[Math.floor(Math.random() * options.length)].checked = true;
        });
        update();
    });

    update();
}

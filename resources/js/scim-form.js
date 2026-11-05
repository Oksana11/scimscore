// Live section sums and total while filling in the SCIM questionnaire (display only;
// the server recalculates everything).
export function initScimForm(root) {
    const update = () => {
        let total = 0;
        root.querySelectorAll('[data-scim-section]').forEach((section) => {
            let sum = 0;
            section.querySelectorAll('input[type="radio"]:checked').forEach((r) => {
                sum += parseInt(r.value, 10) || 0;
            });
            const out = section.querySelector('[data-section-sum]');
            if (out) out.textContent = String(sum);
            total += sum;
        });
        const totalEl = root.querySelector('[data-scim-total]');
        if (totalEl) totalEl.textContent = String(total);
    };
    root.addEventListener('change', update);
    update();
}

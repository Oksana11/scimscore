// Enables the "Resend code" button after the server-defined waiting time.
export function initResendTimer(button) {
    let left = parseInt(button.dataset.wait ?? '0', 10);
    const label = button.querySelector('[data-resend-label]');
    if (left <= 0) return;

    const tick = setInterval(() => {
        left -= 1;
        if (left <= 0) {
            clearInterval(tick);
            button.disabled = false;
            if (label) label.textContent = '';
        } else if (label) {
            label.textContent = ` in ${left} s`;
        }
    }, 1000);
}

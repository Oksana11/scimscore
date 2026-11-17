import { initInstitutionPicker } from './institution-picker';
import { initResendTimer } from './resend-timer';
import { initScimForm } from './scim-form';
import { initFormValidation } from './form-validation';
import { initPrint } from './print';
import { initTotpQr } from './totp-qr';

document.querySelectorAll('[data-institution-picker]').forEach(initInstitutionPicker);
document.querySelectorAll('[data-resend]').forEach(initResendTimer);
document.querySelectorAll('[data-scim-form]').forEach(initScimForm);
document.querySelectorAll('[data-print]').forEach(initPrint);
document.querySelectorAll('form').forEach(initFormValidation);
document.querySelectorAll('form[data-confirm]').forEach((f) =>
    f.addEventListener('submit', (e) => {
        if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    }),
);
document.querySelectorAll('canvas[data-totp-qr]').forEach(initTotpQr);

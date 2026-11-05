import { initInstitutionPicker } from './institution-picker';
import { initResendTimer } from './resend-timer';
import { initScimForm } from './scim-form';

document.querySelectorAll('[data-institution-picker]').forEach(initInstitutionPicker);
document.querySelectorAll('[data-resend]').forEach(initResendTimer);
document.querySelectorAll('[data-scim-form]').forEach(initScimForm);
document.querySelectorAll('[data-print]').forEach((b) => b.addEventListener('click', () => window.print()));

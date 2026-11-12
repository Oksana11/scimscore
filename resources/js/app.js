import { initInstitutionPicker } from './institution-picker';
import { initResendTimer } from './resend-timer';
import { initScimForm } from './scim-form';
import { initFormValidation } from './form-validation';
import { initPrint } from './print';

document.querySelectorAll('[data-institution-picker]').forEach(initInstitutionPicker);
document.querySelectorAll('[data-resend]').forEach(initResendTimer);
document.querySelectorAll('[data-scim-form]').forEach(initScimForm);
document.querySelectorAll('[data-print]').forEach(initPrint);
document.querySelectorAll('form').forEach(initFormValidation);

import { initInstitutionPicker } from './institution-picker';
import { initResendTimer } from './resend-timer';

document.querySelectorAll('[data-institution-picker]').forEach(initInstitutionPicker);
document.querySelectorAll('[data-resend]').forEach(initResendTimer);

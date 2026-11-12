// Inline form feedback. Display only: the server validates everything again.
//  - a server error under a field disappears as soon as the field is changed;
//  - out-of-range / malformed values (min, max, type, maxlength) are reported while typing;
//  - such values block submit, so the user sees the message instead of a round trip.
// Messages are built with textContent (no innerHTML).

const FIELDS = 'input[name], select[name], textarea[name]';

const wrapperOf = (field) => field.closest('[data-field]') ?? field.closest('div');

// Error paragraphs that belong to this wrapper itself (not to a nested field).
const ownErrors = (wrapper, selector) =>
    [...wrapper.querySelectorAll(selector)].filter((el) => el.parentElement === wrapper || el.closest('div') === wrapper);

function constraintMessage(field) {
    const v = field.validity;
    if (v.valid || v.valueMissing) return null;

    if (v.rangeOverflow || v.rangeUnderflow) {
        const { min, max } = field;
        if (min !== '' && max !== '') return `Must be between ${min} and ${max}.`;
        return max !== '' ? `Must be ${max} or less.` : `Must be ${min} or more.`;
    }
    if (field.type === 'number' && (v.badInput || v.stepMismatch)) return 'Enter a whole number.';
    if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address.';
    if (v.tooLong) return `Maximum ${field.maxLength} characters.`;
    if (v.tooShort) return `At least ${field.minLength} characters.`;

    return field.validationMessage || 'Invalid value.';
}

function setLiveError(field, message) {
    const wrapper = wrapperOf(field);
    if (!wrapper) return;
    let el = ownErrors(wrapper, '[data-live-error]')[0];

    if (!message) {
        el?.remove();
        field.removeAttribute('aria-invalid');
        return;
    }
    if (!el) {
        el = document.createElement('p');
        el.className = 'field-error';
        el.dataset.liveError = '';
        el.setAttribute('role', 'alert');
        field.insertAdjacentElement('afterend', el);
    }
    el.textContent = message;
    field.setAttribute('aria-invalid', 'true');
}

function clearServerErrors(field) {
    const wrapper = wrapperOf(field);
    if (!wrapper) return;
    ownErrors(wrapper, '.field-error:not([data-live-error])').forEach((el) => el.remove());
}

export function initFormValidation(form) {
    const onChange = (e) => {
        const field = e.target;
        if (!(field instanceof HTMLElement) || !field.matches(FIELDS) || field.type === 'hidden' && e.type === 'input') return;
        clearServerErrors(field);
        if (field.type !== 'hidden') setLiveError(field, constraintMessage(field));
    };

    form.addEventListener('input', onChange);
    form.addEventListener('change', onChange);

    form.addEventListener('submit', (e) => {
        const invalid = [...form.querySelectorAll(FIELDS)].filter((f) => f.type !== 'hidden' && constraintMessage(f));
        if (invalid.length === 0) return;
        e.preventDefault();
        invalid.forEach((f) => setLiveError(f, constraintMessage(f)));
        invalid[0].focus();
    });
}

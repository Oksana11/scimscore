// Visual check of the main pages (run by .github/workflows/screenshots.yml).
import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';

const BASE = 'http://127.0.0.1:8000';
const out = (n) => `screenshots/${n}.png`;
const lastCode = () => {
    const log = readFileSync('storage/logs/laravel.log', 'utf8');
    const m = [...log.matchAll(/letter-spacing:8px[^>]*>(\d{6})</g)];
    return m.at(-1)[1];
};

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));

const shot = async (name) => { await page.waitForLoadState('networkidle'); await page.screenshot({ path: out(name), fullPage: true }); };

await page.goto(BASE); await shot('01-landing');

// Quick assessment
await page.goto(`${BASE}/quick`); await shot('02-quick-caregiver');
await page.selectOption('#profession_id', { index: 1 });
await page.selectOption('#country_code', 'IL');
await page.fill('#institution_search', 'Loewenstein');
await page.waitForTimeout(800);
await page.locator('[data-institution-list] button').first().click();
await page.click('button[type=submit]');
await page.waitForURL('**/quick/assessment');
await shot('03-quick-form-empty');
await page.fill('#patient_code', '15355');
await page.fill('#age', '40');
await page.check('input[name=gender][value="1"]');
await page.selectOption('#nli', 'T4');
await page.selectOption('#ais', 'B');
await page.fill('#ams', '50');
for (const name of await page.$$eval('input[type=radio][name^="answers"]', (els) => [...new Set(els.map((e) => e.name))])) {
    await page.locator(`input[name="${name}"]`).nth(1).check();
}
await page.check('input[name=accept_terms]');
await shot('04-quick-form-filled');
await page.click('form button[type=submit]');
await page.waitForURL(/\/quick\/[0-9A-Z]{26}$/);
await shot('05-quick-result');

// Sign in (code from the log mailer)
await page.goto(`${BASE}/login`); await shot('06-login');
await page.fill('#email', 'doctor@clinic.test');
await page.check('input[name=accept_terms]');
await page.click('button[type=submit]');
await page.waitForURL('**/login/code'); await shot('07-code');
await page.fill('#code', lastCode());
await page.click('button[type=submit]');
await page.waitForURL('**/profile/complete'); await shot('08-profile-setup');
await page.selectOption('#profession_id', { index: 1 });
await page.selectOption('#country_code', 'IL');
await page.fill('#institution_search', 'Sheba');
await page.waitForTimeout(800);
await page.locator('[data-institution-list] button').first().click();
await page.click('form button[type=submit]');
await page.waitForURL('**/dashboard'); await shot('09-dashboard-empty');

// Patient + two assessments
await page.goto(`${BASE}/assessments/new`); await shot('10-new-assessment');
await page.goto(`${BASE}/patients/create`); await shot('11-new-patient');
await page.fill('#research_id', 'JD1985'); await page.fill('#alias', 'J.D.');
await page.selectOption('#gender', '1'); await page.fill('#birth_year', '1985');
await page.selectOption('#nli', 'T4'); await page.selectOption('#ais', 'A'); await page.fill('#ams', '50');
await page.click('form button[type=submit]');
await page.waitForURL('**/assessments/create'); await shot('12-patient-assessment-form');
const createUrl = page.url();
for (const pick of [1, 2]) {
    if (pick === 2) await page.goto(createUrl);
    for (const name of await page.$$eval('input[type=radio][name^="answers"]', (els) => [...new Set(els.map((e) => e.name))])) {
        await page.locator(`input[name="${name}"]`).nth(pick).check();
    }
    await page.check('input[name=accept_terms]');
    await page.click('form button[type=submit]');
    await page.waitForURL(/\/assessments\/[0-9A-Z]{26}$/);
    if (pick === 1) await shot('13-assessment-result');
}
await page.goto(createUrl.replace(/\/assessments\/create.*/, '')); await shot('14-patient-card');
await page.goto(`${BASE}/patients`); await shot('15-patients');
await page.goto(`${BASE}/dashboard`); await shot('16-dashboard');
await page.goto(`${BASE}/assessments`); await shot('17-history');
await page.goto(`${BASE}/profile`); await shot('18-profile');

await page.setViewportSize({ width: 390, height: 844 });
await page.goto(`${BASE}/dashboard`); await shot('19-mobile-dashboard');
await page.goto(BASE); await shot('20-mobile-landing');

console.log('console errors:', JSON.stringify(errors));
await browser.close();

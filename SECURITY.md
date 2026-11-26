# Безпека SCIMSCORE

## Що реалізовано

| Захист | Де |
|---|---|
| HTTP-заголовки: CSP з nonce (без `unsafe-inline`/`unsafe-eval`), X-Frame-Options, nosniff, Referrer-Policy, COOP/CORP, HSTS (prod) | `app/Http/Middleware/SecurityHeaders.php` |
| Сторінки не кешуються (`Cache-Control: no-store`) | там само |
| Сесії шифруються, cookie HttpOnly, Secure за замовчуванням, SameSite=Lax, 60 хв | `config/session.php`, `.env` |
| Обмеження частоти запитів: 120/хв на IP; ліміти для OTP | `AppServiceProvider::configureRateLimiting` |
| Production: debug примусово вимкнено, тільки HTTPS, заборона `migrate:fresh`/`db:wipe` | `AppServiceProvider::boot` |
| Журнал дій (audit log) з HMAC-ланцюжком: зміна чи видалення запису виявляється `php artisan audit:verify`; OTP, токени, медичні дані не логуються | `app/Support/Security/AuditLogger.php` |
| Blind index — пошук і перевірка дублів по зашифрованих полях без розшифрування | `app/Support/Security/BlindIndex.php` |
| Шифрування в БД (AES-256, `APP_KEY`): email лікарів, Research ID і alias пацієнтів, відповіді анкет. Пошук — тільки через blind index | `encrypted` casts у `app/Models`, `HasBlindIndexes` |
| Публічні ID у посиланнях — ULID (не вгадуються), внутрішні числові id назовні не видно | `public_id`, `#[RouteKey]` |
| Розмежування даних: лікар бачить і змінює лише своїх пацієнтів/оцінки; оцінки незмінні; видалення заборонене | `app/Policies` |
| Email і OTP-коди в таблицях OTP зберігаються лише як HMAC; для анонімних згод не зберігаються IP/User-Agent | `otp_requests`, `user_consents` |
| Nginx виконує лише `index.php`, не віддає dot-файли | `docker/nginx/default.conf` |
| CI: тести, стиль, `composer audit`, `npm audit`, пошук секретів (gitleaks); Dependabot | `.github/` |

| Адмінка: окрема таблиця й guard, вхід = код на email + TOTP (застосунок-автентифікатор), захист від повтору коду, 30 хв неактивності → вихід; лише перегляд пацієнтів/оцінок (маршрутів редагування немає); Research ID і alias не показуються й не експортуються; дії адміна в журналі | `app/Http/Controllers/Admin`, `app/Support/Security/Totp.php` |
| CSV/Excel: захист від formula injection | `CsvWriter`, `AssessmentExporter` |

## Ключі — зберігати окремо від бази

| Ключ | Втрата означає |
|---|---|
| `APP_KEY` | неможливо розшифрувати зашифровані поля |
| `BLIND_INDEX_KEY` | перестає працювати пошук і перевірка дублів |
| `AUDIT_HMAC_KEY` | неможливо перевірити цілісність журналу |

Production: ключі лише в AWS Secrets Manager + резервна копія офлайн. Ключі ніколи не змінюються після появи даних (для `APP_KEY` — ротація тільки через `APP_PREVIOUS_KEYS`).

## Обов'язково для production

- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `HSTS_ENABLED=true`, `TRUSTED_PROXIES` = діапазони Cloudflare/ALB.
- Користувач БД застосунку: на `audit_logs` лише `SELECT, INSERT` (без `UPDATE, DELETE`); міграції — окремим користувачем.
- Журнал дій додатково відправляти в CloudWatch Logs (захист від видалення останніх записів).
- Решта — за `SCIM_Security_Developer_Checklist` (AWS il-central-1, Cloudflare WAF, бекапи з тестом відновлення).

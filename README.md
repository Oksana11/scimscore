# SCIMSCORE

Laravel 13 · PHP 8.4 · MySQL 8.4 · Nginx · Mailpit (локальна пошта).

## Що потрібно встановити

- **Docker Desktop**: https://www.docker.com/products/docker-desktop/
  - Windows: під час встановлення залишити «Use WSL 2». Потім встановити Ubuntu (`wsl --install -d Ubuntu` у PowerShell від адміністратора). Проєкт тримати **всередині Ubuntu** (наприклад, `~/projects/scimscore`), а не на диску `C:`, інакше все працюватиме дуже повільно. Усі команди нижче виконувати в терміналі Ubuntu.
  - Docker Desktop → Settings → Resources → WSL integration → увімкнути для Ubuntu.
- **Git**.

Перевірка: `docker --version` і `docker compose version` відповідають без помилок.

## Перший запуск

```bash
cd ~/projects/scimscore

# 1. Файл налаштувань
cp .env.example .env
```

Відкрити `.env` і заповнити:

- `DB_PASSWORD` і `DB_ROOT_PASSWORD`: два **різні** паролі. Згенерувати можна командою `openssl rand -base64 24`.
- Linux / WSL: `UID` і `GID` мають збігатися з виводом `id -u` і `id -g` (зазвичай 1000).

```bash
# 2. Зібрати образ PHP і запустити контейнери
docker compose build
docker compose up -d

# 3. Встановити залежності PHP
docker compose exec app composer install

# 4. Ключ шифрування застосунку і таблиці БД
docker compose exec app php artisan key:generate
docker compose exec app php artisan security:generate-keys
docker compose exec app php artisan migrate --seed

# 5. Зібрати фронтенд
docker compose run --rm node npm install
docker compose run --rm node npm run build
```

Відкрити:
- сайт: http://localhost:8080
- листи, які надсилає сайт: http://localhost:8025

## Щоденна робота

| Що | Команда |
|---|---|
| Запустити | `docker compose up -d` |
| Зупинити | `docker compose down` |
| Artisan | `docker compose exec app php artisan …` |
| Composer | `docker compose exec app composer …` |
| Тести | `docker compose exec app php artisan test` |
| Логи | `docker compose logs -f app web` |
| Перевірити цілісність журналу дій | `docker compose exec app php artisan audit:verify` |
| Створити адміністратора | `docker compose exec app php artisan admin:create email@example.org "Ім'я"` |
| Скинути MFA адміністратора | `docker compose exec app php artisan admin:reset-mfa email@example.org` |
| Консоль MySQL | `docker compose exec db mysql -u scimscore -p scimscore` |

Підключення до БД з DBeaver/TablePlus: `127.0.0.1:3307`, користувач і пароль з `.env`.

## Безпека

Деталі: [SECURITY.md](SECURITY.md).


- `.env` ніколи не потрапляє в git (вже в `.gitignore`).
- Порти відкриті лише на `127.0.0.1`, тобто ззовні комп'ютера сайт і БД недоступні.
- Nginx виконує тільки `public/index.php`; файли, що починаються з крапки (`.env`, `.git`), не віддаються.
- Сесії шифруються (`SESSION_ENCRYPT=true`), час життя сесії 60 хв.
- Повністю видалити БД (усі дані!): `docker compose down -v`.

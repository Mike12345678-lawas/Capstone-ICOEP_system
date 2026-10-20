# Render Environment Variables

Add these in **Render Dashboard → your Web Service → Environment**.
Replace every `<placeholder>` with your real value **in Render only** — never commit real values to git.

| Key | Value |
|---|---|
| `APP_NAME` | `<your-app-name>` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `<base64:your-app-key>` (generate locally with `php artisan key:generate --show`) |
| `APP_URL` | `<https://your-service-name.onrender.com>` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `<your-aiven-host>` |
| `DB_PORT` | `<your-aiven-port>` |
| `DB_DATABASE` | `<your-database-name>` |
| `DB_USERNAME` | `<your-database-username>` |
| `DB_PASSWORD` | `<your-database-password>` |
| `LOG_CHANNEL` | `stderr` |
| `SESSION_DRIVER` | `file` |
| `CACHE_STORE` | `file` |
| `MAIL_MAILER` | `smtp` (or `log` if testing) |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `<your-gmail-account@gmail.com>` |
| `MAIL_PASSWORD` | `<your-16-char-gmail-app-password>` |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_FROM_ADDRESS` | `<your-gmail-account@gmail.com>` |
| `MAIL_FROM_NAME` | `ICOEP System` |

> For Gmail SMTP: generate a 16-character **App Password** in your Google Account (Security → 2-Step Verification → App passwords). Never commit real email credentials to git.

> This project uses Laravel 12, so `CACHE_DRIVER` is **not** needed (`CACHE_STORE` replaced it in Laravel 11+).

> The Aiven SSL certificate is read from `certs/aiven-ca.pem` inside the image (see `config/database.php`), so no SSL variable is needed.

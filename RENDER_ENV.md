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
| `GMAIL_RELAY_URL` | `https://script.google.com/macros/s/.../exec` (Recommended: sends to ANY @ciccebu.edu.ph school account!) |
| `MAIL_MAILER` | `resend` (Alternative if using Resend) |
| `RESEND_API_KEY` | `re_...` (Alternative if using Resend) |
| `MAIL_FROM_ADDRESS` | `onboarding@resend.dev` |
| `MAIL_FROM_NAME` | `ICOEP System` |

> ### 💡 Email on Render Free Tier
> Render's Free tier blocks outbound SMTP ports (25, 465, 587).
> You have two working HTTPS (Port 443) options:
> 
> **Option 1 (Best for School Accounts — Google Apps Script Relay):**
> Add `GMAIL_RELAY_URL` with your public Google Apps Script Web App URL. It sends directly via Google to **any student/faculty @ciccebu.edu.ph school account** with zero domain restrictions!
>
> **Option 2 (Resend):**
> Add `RESEND_API_KEY` and set `MAIL_MAILER=resend`. Note: Resend's free sandbox (`onboarding@resend.dev`) only sends to the single email registered on that Resend account.

> This project uses Laravel 12, so `CACHE_DRIVER` is **not** needed (`CACHE_STORE` replaced it in Laravel 11+).

> The Aiven SSL certificate is read from `certs/aiven-ca.pem` inside the image (see `config/database.php`), so no SSL variable is needed.

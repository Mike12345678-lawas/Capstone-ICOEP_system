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
| `MAIL_MAILER` | `resend` (Recommended for Render Free) |
| `RESEND_API_KEY` | `re_...` (From free account at https://resend.com) |
| `MAIL_FROM_ADDRESS` | `onboarding@resend.dev` (or your verified domain email) |
| `MAIL_FROM_NAME` | `ICOEP System` |

> ### 💡 Email on Render Free Tier
> Render's Free tier blocks outbound SMTP ports (25, 465, 587), so standard Gmail SMTP will time out. 
> Instead, **Resend** delivers emails over standard HTTPS (Port 443), which Render never blocks.
> 1. Sign up for a free account at [resend.com](https://resend.com) (3,000 free emails/month).
> 2. Create an API Key (starts with `re_...`).
> 3. Add `MAIL_MAILER=resend`, `RESEND_API_KEY=re_...`, `MAIL_FROM_ADDRESS=onboarding@resend.dev`, and `MAIL_FROM_NAME=ICOEP System` in Render Dashboard → Environment.
> 4. Test live delivery in your browser by visiting `/admin/mail-test` on your live site!

> This project uses Laravel 12, so `CACHE_DRIVER` is **not** needed (`CACHE_STORE` replaced it in Laravel 11+).

> The Aiven SSL certificate is read from `certs/aiven-ca.pem` inside the image (see `config/database.php`), so no SSL variable is needed.

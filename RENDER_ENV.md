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
| `MAIL_FROM_ADDRESS` | `verallo.s@ciccebu.edu.ph` |
| `MAIL_FROM_NAME` | `ICOEP System` |

> ### 💡 Email on Render Free Tier
> Render's Free tier blocks standard outbound SMTP ports (25, 465, 587).
> To deliver emails to **any student/faculty @ciccebu.edu.ph school account** without domain restrictions or blocks, add **`GMAIL_RELAY_URL`** with your public Google Apps Script Web App URL. It delivers over HTTPS (Port 443) with zero restrictions!
> 
> When your system is eventually deployed on the school's official host/server with open SMTP, simply set standard SMTP variables (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`).

> This project uses Laravel 12, so `CACHE_DRIVER` is **not** needed (`CACHE_STORE` replaced it in Laravel 11+).

> The Aiven SSL certificate is read from `certs/aiven-ca.pem` inside the image (see `config/database.php`), so no SSL variable is needed.

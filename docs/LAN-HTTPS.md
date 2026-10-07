# HTTPS & Camera Setup Guide for LAN Operations

Browsers strictly enforce the **Secure Context** policy (`isSecureContext`): camera access via `getUserMedia` is **only permitted on `https://` origins or `localhost` / `127.0.0.1`**.

When accessing the attendance scanner on a phone or other device via LAN IP (`http://<LAN-IP>:8000`), the browser classifies the connection as an insecure origin and leaves `navigator.mediaDevices` **undefined**. No JavaScript or PHP code can bypass this browser security restriction.

This document details four options to run the application securely for LAN camera scanning, ordered by recommendation.

---

## 1. Option 1 (Recommended for Single-Device / Same PC): Localhost

If the event organizer is using a laptop/PC equipped with a webcam (or USB scanner):
1. Serve the application:
   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. On that same computer, open:
   ```
   http://localhost:8000/events/<id>/scanner
   ```
   or
   ```
   http://127.0.0.1:8000/events/<id>/scanner
   ```
3. Browsers treat `localhost` and `127.0.0.1` as trusted secure contexts even over plain HTTP. Camera access will work immediately.

---

## 2. Option 2 (Recommended for Mobile Phones on LAN): `mkcert` + Local TLS Proxy

Use this setup so mobile phones and tablets on your local Wi-Fi / campus network can open `https://<LAN-IP>:8443` with full camera access.

### Step 2.1: Find your LAN IP Address
In Windows PowerShell / Command Prompt:
```powershell
ipconfig
```
Look for your IPv4 Address (e.g., `192.168.1.50`).

### Step 2.2: Install `mkcert` (Creates locally trusted SSL certificates)
Install `mkcert` via Chocolatey or Scoop:
```powershell
choco install mkcert
# or: scoop install mkcert
```
Initialize the local Certificate Authority (CA):
```powershell
mkcert -install
```

### Step 2.3: Generate a Certificate for your LAN IP and Localhost
In your project directory or a certs folder:
```powershell
mkcert localhost 127.0.0.1 192.168.1.50 ::1
```
*(Replace `192.168.1.50` with your actual LAN IP).*
This creates two files:
- `localhost+3.pem` (certificate)
- `localhost+3-key.pem` (private key)

### Step 2.4: Run the TLS Reverse Proxy
#### Choice A: Using `local-ssl-proxy` (Quick Node.js utility)
Install globally:
```powershell
npm install -g local-ssl-proxy
```
Run the proxy forwarding port 8443 (HTTPS) to port 8000 (Laravel HTTP):
```powershell
local-ssl-proxy --source 8443 --target 8000 --cert localhost+3.pem --key localhost+3-key.pem
```

#### Choice B: Using Caddy (Production-grade single binary)
Create a `Caddyfile`:
```caddy
https://192.168.1.50:8443 {
    tls localhost+3.pem localhost+3-key.pem
    reverse_proxy 127.0.0.1:8000 {
        header_up Host {upstream_hostport}
        header_up X-Forwarded-Proto https
        header_up X-Forwarded-For {remote_host}
    }
}
```
Run Caddy:
```powershell
caddy run
```

### Step 2.5: Install mkcert Root CA on Mobile Devices

To avoid untrusted certificate warnings on phones:
1. Locate mkcert's root CA path on your PC:
   ```powershell
   mkcert -CAROOT
   ```
   This displays a folder containing `rootCA.pem`.
2. Copy `rootCA.pem` to your phone (via AirDrop, email, USB transfer, or download from a quick local HTTP server).
3. **On Android:**
   - Go to **Settings** > **Security** > **Encryption & credentials** (or **Install credentials**).
   - Select **Install a certificate** > **CA certificate**.
   - Tap **Install anyway** and select `rootCA.pem`.
4. **On iOS (iPhone / iPad):**
   - Open `rootCA.pem` in Safari or Files to install the profile.
   - Go to **Settings** > **Profile Downloaded** > Tap **Install**.
   - Enable full trust: **Settings** > **General** > **About** > **Certificate Trust Settings** > Toggle switch for `mkcert root CA` to **ON**.

Now, phones navigating to `https://192.168.1.50:8443` will display a valid green lock and camera permissions will work seamlessly!

---

## 3. Option 3 (Fastest for Demos & Off-Campus Testing): Cloudflare Tunnel or Ngrok

If you need an instant HTTPS URL without installing custom CA certificates on each mobile phone:

### Cloudflare Tunnel (`cloudflared`) — Free, No Account Required
Download `cloudflared` for Windows (or `choco install cloudflared`), then run:
```powershell
cloudflared tunnel --url http://localhost:8000
```
`cloudflared` prints a public `https://<random-name>.trycloudflare.com` URL. Open that URL on the phone. HTTPS is automatically valid and the camera will prompt for permission immediately.

### Ngrok
```powershell
ngrok http 8000
```
Use the provided `https://` forwarding URL.

---

## 4. Option 4 (Testing Only - Android Chrome Shortcut)

> **⚠️ WARNING:** This is strictly an emergency debugging workaround. It **only** works on Android Chrome and does **NOT** work on iOS (Safari/Chrome).

1. On the Android phone, open Chrome and navigate to:
   ```
   chrome://flags/#unsafely-treat-insecure-origin-as-secure
   ```
2. In the text box, enter your LAN origin:
   ```
   http://192.168.1.50:8000
   ```
3. Set the dropdown to **Enabled**.
4. Tap **Relaunch** at the bottom.
5. Chrome will now treat that specific origin as secure, allowing `getUserMedia`.

---

## Laravel & Environment Configuration Behind Reverse Proxy

When terminating SSL at a reverse proxy (like `local-ssl-proxy`, Caddy, or Cloudflare), Laravel needs to know that incoming requests are trusted HTTPS requests.

### 1. Trusted Proxies in `bootstrap/app.php`
Configured in `bootstrap/app.php`:
```php
$middleware->trustProxies(at: '*');
```
This ensures `$request->isSecure()`, URL generation, and route helpers automatically produce `https://` URLs matching the client request.

### 2. Recommended `.env` Settings
When deploying behind a local HTTPS proxy or tunnel, update your `.env` values (examples provided in `env.example`):
```env
# Set APP_URL to match your public/LAN entry point:
APP_URL=https://192.168.1.50:8443

# When serving over HTTPS, secure session cookies must be enabled:
SESSION_SECURE_COOKIE=true
```
*(If testing on `http://localhost:8000`, keep `APP_URL=http://localhost:8000` and `SESSION_SECURE_COOKIE=false`).*

---

## Front-End & Vite Assets on LAN

In Laravel apps using Vite (`@vite(...)`):
- When running `npm run dev`, Vite starts a local dev server at `http://localhost:5173`.
- **Other LAN devices cannot connect to `localhost:5173`**, and if your site is loaded over HTTPS, loading HTTP scripts from `http://localhost:5173` will be **blocked by the browser as mixed content**.

### Recommended LAN Workflow: Production Bundle
Always build static assets before running LAN tests:
```powershell
npm run build
```
Once `npm run build` is run:
- Assets are compiled directly into `public/build/assets/`.
- No separate Vite Node server needs to run.
- All scripts (`app.js`, `qr-scanner.js`, `qr-generator.js`, and `app.css`) are served directly by Laravel/PHP via relative paths or asset URLs without mixed content issues.
- The app operates completely **offline-capable** with no external CDN dependencies.

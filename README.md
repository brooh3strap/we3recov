# we3recov

Static site + `/api/notify` (Telegram). Deploy to **Vercel**, **cPanel**, or a **VPS**. The form always posts to `/api/notify`; only configuration changes per host.

---

## Deploy to Vercel

1. **Push your repo to GitHub** (or connect another Git provider in Vercel).

2. **Import the project** in [Vercel](https://vercel.com): **Add New → Project** and select this repo. Leave framework preset as **Other** (static).

3. **Set environment variables** (for the notify API):
   - **Project → Settings → Environment Variables**
   - Add:
     - `TELEGRAM_BOT_TOKEN` = your Telegram bot token  
     - `TELEGRAM_CHAT_ID` = your chat or group ID  

4. **Deploy.** Vercel serves the static files (root, `explore/`, `_next/`, etc.) and runs `api/notify.js` as a serverless function at `/api/notify`.

No `vercel.json` required unless you need custom rewrites or headers.

---

## Deploy to cPanel

1. **Upload the project** (FTP or File Manager): upload the whole project so the **`api`** folder (and `.htaccess`) is in place.

2. **Configure Telegram** in the `api` folder:
   - Copy `api/config.sample.php` to `api/config.php`.
   - Edit `api/config.php` and set your Telegram bot token and chat ID. Save.

3. **Document root**: point your domain to the folder that contains `index.html` and the `api` folder.  
   `.htaccess` routes `/api/notify` to the PHP script and blocks direct access to `config.php`.

No env vars; Telegram credentials stay in `config.php` on the server.

---

## Deploy to a VPS (Node or static)

### Option A: Node server (local-style)

1. **Upload the project** to the VPS (e.g. with Git: `git clone ...`).

2. **Set environment variables** for Telegram:
   - `TELEGRAM_BOT_TOKEN`
   - `TELEGRAM_CHAT_ID`  
   (e.g. in `.env` or your process manager).

3. **Run the server:**
   ```bash
   node server.js
   ```
   Default port is **8080** (or set `PORT` in the environment).

4. **Keep it running**: use **PM2**, **systemd**, or a reverse proxy (e.g. Nginx) in front of Node.

### Option B: Nginx (or Apache) for static + PHP API

1. **Upload the project** to the VPS.

2. **Static site**: point the document root to the project folder so Nginx (or Apache) serves `index.html`, `explore/`, `_next/`, etc.

3. **`/api/notify`**:
   - **PHP**: put the `api` folder under the doc root and use your web server to route `/api/notify` to `api/notify-cpanel.php`. Configure Telegram in `api/config.php` as in the cPanel section.
   - **Node**: run only `node server.js` for the API and put Nginx in front; proxy `/api/notify` to Node and serve the rest as static files.

---

## Summary

| Host    | Static site        | `/api/notify`              | Config |
|---------|--------------------|---------------------------|--------|
| Vercel  | Auto (static)      | `api/notify.js` (serverless) | Env vars in Vercel |
| cPanel  | Your doc root      | `api/notify-cpanel.php`   | `api/config.php` |
| VPS     | Node or Nginx/Apache | `server.js` or PHP      | Env vars or `config.php` |

More detail: **api/README.md** and **HOSTING.md**.

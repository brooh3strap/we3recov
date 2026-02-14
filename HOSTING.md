# Hosting – same code on cPanel or Vercel

The form always posts to **`/api/notify`**. No code changes when you switch hosts. Only set your TG details and deploy.

---

## cPanel

1. Upload your project (including the **`api`** folder).
2. In the **`api`** folder: copy **`config.sample.php`** to **`config.php`**.
3. Edit **`config.php`** and add your Telegram bot token and chat ID. Save.
4. Done. `.htaccess` makes `/api/notify` use the PHP file and blocks `config.php` from the web.

No environment variables. TG details stay in `config.php` on the server.

---

## Vercel

1. Deploy your project.
2. In Vercel: **Project → Settings → Environment Variables**, add:
   - **`TELEGRAM_BOT_TOKEN`** = your bot token  
   - **`TELEGRAM_CHAT_ID`** = your chat/group ID  
3. Redeploy.

TG details stay in env; the frontend never sees them.

---

Same endpoint on both: **`/api/notify`**. Edit TG details for your host and deploy; no other code changes.

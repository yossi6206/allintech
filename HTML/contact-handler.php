<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$RESEND_API_KEY = 're_JASRqQ8L_mFfKnSj4eH6wmvQub7EisMeA';
$TO_EMAIL       = 'info@allintech.co.il';

$body    = json_decode(file_get_contents('php://input'), true);
$name    = htmlspecialchars(trim($body['name']    ?? ''), ENT_QUOTES);
$email   = filter_var(trim($body['email']   ?? ''), FILTER_SANITIZE_EMAIL);
$website = htmlspecialchars(trim($body['website']  ?? ''), ENT_QUOTES);
$message = htmlspecialchars(trim($body['message']  ?? ''), ENT_QUOTES);

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

function sendResend($apiKey, $payload) {
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => $httpCode >= 200 && $httpCode < 300, 'body' => json_decode($response, true)];
}

// 1. מייל אליך עם כל פרטי הפנייה
$notifyResult = sendResend($RESEND_API_KEY, [
    'from'     => 'AllinTech Contact <info@allintech.co.il>',
    'to'       => [$TO_EMAIL],
    'reply_to' => $email,
    'subject'  => 'פנייה חדשה מהאתר – ' . $name,
    'html'     =>
        '<div dir="rtl" style="font-family:Arial,sans-serif;font-size:15px;line-height:1.7">'
        . '<h2 style="color:#1a1a2e">פנייה חדשה מטופס יצירת קשר</h2>'
        . '<p><strong>שם:</strong> ' . $name . '</p>'
        . '<p><strong>אימייל:</strong> ' . $email . '</p>'
        . ($website ? '<p><strong>אתר:</strong> ' . $website . '</p>' : '')
        . '<p><strong>הודעה:</strong></p>'
        . '<p style="background:#f5f5f5;padding:12px;border-radius:6px">' . nl2br($message) . '</p>'
        . '</div>',
]);

if (!$notifyResult['ok']) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'notify_failed',
        'details' => $notifyResult['body'],
        'hint'    => 'Check Resend API key and sender domain'
    ]);
    exit;
}

// 2. מייל חוזר ללקוח
sendResend($RESEND_API_KEY, [
    'from'    => 'Yossi | AllinTech <info@allintech.co.il>',
    'to'      => [$email],
    'subject' => 'קיבלנו את פנייתך – AllinTech',
    'html'    =>
        '<div dir="rtl" style="font-family:Arial,sans-serif;font-size:15px;line-height:1.8;color:#1a1a1a">'
        . '<p>שלום ' . $name . ',</p>'
        . '<p>קיבלנו את פנייתך, תודה שפנית אלינו.</p>'
        . '<p>נחזור אליך בהקדם האפשרי.</p>'
        . '<br>'
        . '<p style="color:#555">יוסי | AllinTech<br>'
        . '<a href="mailto:' . $TO_EMAIL . '" style="color:#0066cc">' . $TO_EMAIL . '</a></p>'
        . '</div>',
]);

echo json_encode(['success' => true]);

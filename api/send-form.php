<?php
/**
 * Обработчик отправки форм
 * Отправляет данные на email и в Telegram бота
 */

// Загружаем конфигурацию
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Ошибка конфигурации. Создайте файл config.php на основе config.example.php'
    ]));
}

require_once __DIR__ . '/config.php';

// Устанавливаем заголовки CORS
header('Content-Type: application/json; charset=utf-8');

// Проверка origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Принимаем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'message' => 'Метод не разрешен'
    ]));
}

// ==============================================
// ЗАЩИТА ОТ СПАМА
// ==============================================

session_start();
$current_time = time();
$ip = $_SERVER['REMOTE_ADDR'];

// Проверка rate limit
if (isset($_SESSION['last_submit']) && ($current_time - $_SESSION['last_submit']) < RATE_LIMIT_SECONDS) {
    http_response_code(429);
    die(json_encode([
        'success' => false,
        'message' => 'Слишком частые запросы. Попробуйте через минуту.'
    ]));
}

// ==============================================
// ПОЛУЧЕНИЕ И ВАЛИДАЦИЯ ДАННЫХ
// ==============================================

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Некорректные данные'
    ]));
}

// Обязательные поля
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$formType = trim($data['formType'] ?? 'general');

// Валидация
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Имя должно содержать минимум 2 символа';
}

if (empty($phone) || !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
    $errors[] = 'Некорректный номер телефона';
}

if (!empty($errors)) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Ошибка валидации',
        'errors' => $errors
    ]));
}

// Honeypot проверка (защита от спам-ботов)
$honeypot = trim($data['website_url'] ?? '');
if (!empty($honeypot)) {
    // Бот заполнил скрытое поле - отклоняем запрос
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Spam detected'
    ]));
}

// Дополнительные поля (опционально)
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');
$object = trim($data['object'] ?? '');
$workType = trim($data['workType'] ?? '');
$diameter = trim($data['diameter'] ?? '');
$material = trim($data['material'] ?? '');
$thickness = trim($data['thickness'] ?? '');
$floor = trim($data['floor'] ?? '');
$quantity = trim($data['quantity'] ?? '');

// Защита от XSS
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// ==============================================
// ОПРЕДЕЛЕНИЕ EMAIL ПОЛУЧАТЕЛЯ
// ==============================================

$recipient_email = EMAIL_INFO; // по умолчанию

switch ($formType) {
    case 'callback':
    case 'ventilation':
        $recipient_email = EMAIL_KENNER;
        break;
    case 'airconditioner':
    case 'contact':
        $recipient_email = EMAIL_INFO;
        break;
    case 'drilling':
    case 'automation':
        $recipient_email = EMAIL_INFO;
        break;
    case 'airducts':
        $recipient_email = EMAIL_SALES;
        break;
}

// ==============================================
// ФОРМИРОВАНИЕ СООБЩЕНИЯ ДЛЯ EMAIL
// ==============================================

$subject = 'Новая заявка с сайта МДН Инжиниринг';

$email_message = "Получена новая заявка с сайта\n\n";
$email_message .= "Тип формы: " . getFormTypeName($formType) . "\n";
$email_message .= "Дата: " . date('d.m.Y H:i:s') . "\n\n";
$email_message .= "--- Данные клиента ---\n";
$email_message .= "Имя: $name\n";
$email_message .= "Телефон: $phone\n";

if ($email) {
    $email_message .= "Email: $email\n";
}

if ($object) {
    $email_message .= "Тип объекта: $object\n";
}

if ($workType) {
    $email_message .= "Тип работы: $workType\n";
}

if ($diameter) {
    $email_message .= "Диаметр/Толщина: $diameter мм\n";
}

if ($material) {
    $email_message .= "Материал: $material\n";
}

if ($thickness) {
    $email_message .= "Толщина стены: $thickness мм\n";
}

if ($floor) {
    $email_message .= "Этаж: $floor\n";
}

if ($quantity) {
    $email_message .= "Количество: $quantity\n";
}

if ($message) {
    $email_message .= "\nСообщение:\n$message\n";
}

$email_message .= "\n--- Техническая информация ---\n";
$email_message .= "IP адрес: $ip\n";
$email_message .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Неизвестно') . "\n";

// Заголовки для email
$headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
$headers .= "Reply-To: " . ($email ?: EMAIL_INFO) . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// ==============================================
// ОТПРАВКА EMAIL
// ==============================================

$email_sent = mail($recipient_email, $subject, $email_message, $headers);

// ==============================================
// ОТПРАВКА В TELEGRAM
// ==============================================

$telegram_sent = false;

if (TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE' && TELEGRAM_CHAT_ID !== 'YOUR_CHAT_ID_HERE') {
    $telegram_message = "🔔 <b>Новая заявка с сайта</b>\n\n";
    $telegram_message .= "📋 <b>Тип:</b> " . getFormTypeName($formType) . "\n";
    $telegram_message .= "👤 <b>Имя:</b> $name\n";
    $telegram_message .= "📞 <b>Телефон:</b> $phone\n";

    if ($email) {
        $telegram_message .= "📧 <b>Email:</b> $email\n";
    }

    if ($object) {
        $telegram_message .= "🏢 <b>Объект:</b> $object\n";
    }

    if ($workType) {
        $telegram_message .= "🔨 <b>Тип работы:</b> $workType\n";
    }

    if ($message) {
        $telegram_message .= "\n💬 <b>Сообщение:</b>\n$message\n";
    }

    $telegram_message .= "\n⏰ " . date('d.m.Y H:i:s');

    $telegram_sent = sendTelegramMessage($telegram_message);
}

// ==============================================
// ОТВЕТ КЛИЕНТУ
// ==============================================

// Обновляем время последней отправки
$_SESSION['last_submit'] = $current_time;

if ($email_sent || $telegram_sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.',
        'debug' => DEBUG_MODE ? [
            'email_sent' => $email_sent,
            'telegram_sent' => $telegram_sent,
        ] : null
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при отправке. Попробуйте позвонить нам напрямую.',
    ]);
}

// ==============================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ==============================================

/**
 * Отправка сообщения в Telegram
 */
function sendTelegramMessage($message) {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    return $result !== false;
}

/**
 * Получение имени типа формы
 */
function getFormTypeName($type) {
    $types = [
        'callback' => 'Обратный звонок',
        'contact' => 'Контактная форма',
        'ventilation' => 'Консультация по вентиляции',
        'airconditioner' => 'Подбор кондиционера',
        'drilling' => 'Алмазное бурение',
        'automation' => 'Автоматизация',
        'airducts' => 'Изготовление воздуховодов',
        'calculation' => 'Расчет стоимости',
        'general' => 'Общая форма'
    ];

    return $types[$type] ?? 'Неизвестный тип';
}

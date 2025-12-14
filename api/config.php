<?php
/**
 * Конфигурация для отправки форм
 */

// ==============================================
// НАСТРОЙКИ EMAIL
// ==============================================

define('EMAIL_INFO', 'info@mdn-eng.ru');
define('EMAIL_KENNER', 'kenner.a@mdn-eng.ru');
define('EMAIL_SALES', 'sales@mdn-eng.ru');

define('EMAIL_FROM', 'noreply@mdn-eng.ru');
define('EMAIL_FROM_NAME', 'МДН Инжиниринг');

// ==============================================
// НАСТРОЙКИ TELEGRAM БОТА
// ==============================================

define('TELEGRAM_BOT_TOKEN', '8275259909:AAGIcI87bWG_hg2kigq_XcOVLhOIvGdST9s');
define('TELEGRAM_CHAT_ID', '-4924281340');

// ==============================================
// НАСТРОЙКИ БЕЗОПАСНОСТИ
// ==============================================

$allowed_origins = [
    'https://mdn-eng.ru',
    'https://www.mdn-eng.ru',
    'http://mdn-eng.ru',
    'http://www.mdn-eng.ru',
];

define('DEBUG_MODE', false);
define('RATE_LIMIT_SECONDS', 60);

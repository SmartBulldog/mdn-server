# МДН Инжиниринг - Официальный сайт

Корпоративный сайт компании МДН Инжиниринг - профессиональные решения в сфере кондиционирования, вентиляции, автоматизации систем, алмазного бурения и изготовления воздуховодов.

## 🚀 Технологии

- **Frontend**: HTML5, CSS3 (Grid, Flexbox), Vanilla JavaScript
- **Backend**: PHP 7.4+ (отправка форм)
- **Интеграции**: Telegram Bot API, Email (SMTP/mail())
- **SEO**: JSON-LD, Open Graph, Canonical URLs, Sitemap
- **Безопасность**: Honeypot, Rate Limiting, XSS защита

## 📋 Содержание

- [Развертывание на сервере через SSH](#развертывание-на-сервере-через-ssh)
- [Настройка Telegram бота](#настройка-telegram-бота)
- [Настройка отправки Email](#настройка-отправки-email)
- [Структура проекта](#структура-проекта)
- [Обслуживание](#обслуживание)

---

## 🖥️ Развертывание на сервере через SSH

### Шаг 1: Подключение к серверу

```bash
# Подключитесь к вашему серверу
ssh username@your-server.ru

# Или если используете SSH ключ
ssh -i ~/.ssh/your_key username@your-server.ru
```

### Шаг 2: Установка необходимых пакетов

```bash
# Обновите систему
sudo apt update && sudo apt upgrade -y

# Установите необходимые пакеты
sudo apt install -y git nginx php-fpm php-cli php-mbstring php-curl

# Проверьте версию PHP
php -v
```

### Шаг 3: Клонирование проекта

```bash
# Перейдите в директорию веб-сервера
cd /var/www

# Клонируйте репозиторий
sudo git clone https://github.com/SmartBulldog/MDN-Enginiring.git
sudo mv MDN-Enginiring mdn-engineering

# Установите права доступа
sudo chown -R www-data:www-data /var/www/mdn-engineering
sudo chmod -R 755 /var/www/mdn-engineering
```

### Шаг 4: Настройка Nginx

Создайте конфигурацию сайта:

```bash
sudo nano /etc/nginx/sites-available/mdn-engineering
```

Вставьте конфигурацию:

```nginx
server {
    listen 80;
    server_name мдн-инжиниринг.рф www.мдн-инжиниринг.рф;
    
    root /var/www/mdn-engineering;
    index index.html index.php;

    # Логи
    access_log /var/log/nginx/mdn-engineering-access.log;
    error_log /var/log/nginx/mdn-engineering-error.log;

    # Основная локация
    location / {
        try_files $uri $uri/ =404;
    }

    # PHP обработка
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 404 страница
    error_page 404 /404.html;

    # Запрет доступа к скрытым файлам
    location ~ /\. {
        deny all;
    }

    # Запрет доступа к конфигам
    location ~ ^/(api/config\.php|\.git) {
        deny all;
    }

    # Кеширование статики
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Активируйте сайт:

```bash
# Создайте символическую ссылку
sudo ln -s /etc/nginx/sites-available/mdn-engineering /etc/nginx/sites-enabled/

# Проверьте конфигурацию
sudo nginx -t

# Перезапустите Nginx
sudo systemctl restart nginx
```

### Шаг 5: Установка SSL сертификата (Let's Encrypt)

```bash
# Установите Certbot
sudo apt install -y certbot python3-certbot-nginx

# Получите SSL сертификат
sudo certbot --nginx -d мдн-инжиниринг.рф -d www.мдн-инжиниринг.рф

# Автообновление сертификата
sudo certbot renew --dry-run
```

### Шаг 6: Настройка конфигурации сайта

```bash
# Перейдите в директорию API
cd /var/www/mdn-engineering/api

# Скопируйте пример конфигурации
sudo cp config.example.php config.php

# Отредактируйте конфигурацию
sudo nano config.php
```

Настройте параметры (см. разделы ниже про Telegram и Email).

### Шаг 7: Проверка работы

Откройте в браузере: `https://мдн-инжиниринг.рф`

---

## 🤖 Настройка Telegram бота

### Шаг 1: Создание бота

1. **Откройте Telegram** и найдите бота **@BotFather**
2. **Отправьте команду**:
   ```
   /newbot
   ```
3. **Введите имя бота**:
   ```
   МДН Инжиниринг Уведомления
   ```
4. **Введите username бота** (должен заканчиваться на `bot`):
   ```
   mdn_engineering_bot
   ```
5. **BotFather выдаст TOKEN**. Пример:
   ```
   1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
   ```
   
   ⚠️ **Сохраните этот TOKEN!**

### Шаг 2: Получение Chat ID

**Вариант A: Личные сообщения** (для одного человека)

1. Найдите бота **@userinfobot** в Telegram
2. Отправьте ему любое сообщение
3. Он ответит вашим **Chat ID** (например: `123456789`)

**Вариант Б: Группа** (рекомендуется для команды)

1. **Создайте группу** в Telegram (например, "Заявки МДН")
2. **Добавьте вашего бота** в группу
3. **Добавьте бота @getidsbot** в группу
4. Он автоматически отправит ID группы (отрицательное число, например: `-987654321`)

### Шаг 3: Настройка на сервере

```bash
# Подключитесь к серверу
ssh username@your-server.ru

# Отредактируйте config.php
sudo nano /var/www/mdn-engineering/api/config.php
```

Замените значения:

```php
// Вставьте ваш TOKEN
define('TELEGRAM_BOT_TOKEN', '1234567890:ABCdefGHIjklMNOpqrsTUVwxyz');

// Вставьте ваш Chat ID
define('TELEGRAM_CHAT_ID', '123456789');  // или '-987654321' для группы
```

Сохраните файл: `Ctrl+O`, `Enter`, `Ctrl+X`

### Шаг 4: Проверка

1. Откройте сайт в браузере
2. Заполните любую форму обратной связи
3. Нажмите "Отправить"
4. Проверьте Telegram - должно прийти уведомление:

```
🔔 Новая заявка с сайта

📋 Тип: Обратный звонок
👤 Имя: Иван Иванов
📞 Телефон: +7 913 123-45-67

⏰ 16.11.2025 14:30:25
```

### Дополнительные команды бота

Вы можете настроить команды через @BotFather:

```
/setcommands
```

Затем введите:
```
start - Начать работу
help - Помощь
status - Статус приема заявок
```

---

## 📧 Настройка отправки Email

### Проверка текущих настроек

Ваш сайт использует стандартную функцию PHP `mail()` для отправки писем.

### Шаг 1: Настройка email адресов

```bash
# Отредактируйте config.php
sudo nano /var/www/mdn-engineering/api/config.php
```

Настройте адреса:

```php
// Email-адреса для разных форм
define('EMAIL_INFO', 'info@mdn-eng.ru');           // Общие формы
define('EMAIL_KENNER', 'kenner.a@mdn-eng.ru');     // Главный инженер
define('EMAIL_SALES', 'sales@mdn-eng.ru');         // Продажи

// Email отправителя (ВАЖНО: должен быть с вашего домена!)
define('EMAIL_FROM', 'noreply@mdn-eng.ru');
define('EMAIL_FROM_NAME', 'МДН Инжиниринг');
```

### Шаг 2: Настройка почты на сервере (VPS/VDS)

Если у вас VPS/VDS, установите Postfix:

```bash
# Установка Postfix
sudo apt update
sudo apt install postfix mailutils -y

# При установке выберите "Internet Site"
# И укажите ваш домен: mdn-eng.ru

# Проверка работы
echo "Тестовое письмо" | mail -s "Тест" info@mdn-eng.ru
```

### Шаг 3: Настройка SPF записи (важно!)

Добавьте TXT запись в DNS вашего домена:

**Имя записи:** `@` или `mdn-eng.ru`  
**Тип:** `TXT`  
**Значение:**
```
v=spf1 +a +mx +ip4:ВАШ_IP_СЕРВЕРА ~all
```

Где `ВАШ_IP_СЕРВЕРА` - это IP вашего сервера (узнайте командой `curl ifconfig.me`)

### Шаг 4: Проверка работы email

1. Откройте сайт
2. Заполните форму
3. Проверьте:
   - Входящие письма
   - Папку "Спам"
   - Логи сервера

Проверка логов:

```bash
# Логи PHP
sudo tail -f /var/log/php7.4-fpm.log

# Логи Nginx
sudo tail -f /var/log/nginx/mdn-engineering-error.log

# Логи почты (Postfix)
sudo tail -f /var/log/mail.log
```

### Альтернатива: SMTP (если mail() не работает)

Если стандартная `mail()` не работает, используйте SMTP через PHPMailer:

```bash
# Установите Composer (если еще не установлен)
cd /var/www/mdn-engineering
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Установите PHPMailer
sudo composer require phpmailer/phpmailer
```

Создайте файл `api/smtp-sender.php`:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendEmailSMTP($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP настройки
        $mail->isSMTP();
        $mail->Host       = 'smtp.yandex.ru';  // или smtp.mail.ru, smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@mdn-eng.ru';
        $mail->Password   = 'ваш_пароль';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // Отправитель и получатель
        $mail->setFrom('noreply@mdn-eng.ru', 'МДН Инжиниринг');
        $mail->addAddress($to);
        
        // Содержимое
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}
```

Затем в `api/send-form.php` замените строку 205:

```php
// Было:
$email_sent = mail($recipient_email, $subject, $email_message, $headers);

// Стало:
require_once __DIR__ . '/smtp-sender.php';
$email_sent = sendEmailSMTP($recipient_email, $subject, $email_message);
```

---

## 📁 Структура проекта

```
MDN-Enginiring/
├── api/
│   ├── config.example.php      # Пример конфигурации
│   ├── config.php              # Конфигурация (не в Git!)
│   └── send-form.php           # Обработчик форм
├── css/
│   ├── style.css               # Основные стили
│   └── print.css               # Стили для печати
├── images/                     # Изображения
├── js/
│   ├── script.js               # Основной JS
│   ├── faq-data.js            # FAQ главной страницы
│   └── faq-*.js               # FAQ других страниц
├── index.html                  # Главная страница
├── ventilyaciya.html          # Вентиляция
├── kondicionirovanie.html     # Кондиционирование
├── avtomatizaciya.html        # Автоматизация
├── almaznoe-burenie.html      # Алмазное бурение
├── izgotovlenie-vozduhovodov.html  # Воздуховоды
├── kontakty.html              # Контакты
├── 404.html                   # Страница ошибки
├── robots.txt                 # Правила для роботов
├── sitemap.xml                # Карта сайта
└── README.md                  # Этот файл
```

---

## 🔧 Обслуживание

### Обновление сайта

```bash
# Подключитесь к серверу
ssh username@your-server.ru

# Перейдите в директорию сайта
cd /var/www/mdn-engineering

# Получите последние изменения
sudo git pull origin main

# Если изменились права
sudo chown -R www-data:www-data /var/www/mdn-engineering
```

### Просмотр логов

```bash
# Логи отправки форм
sudo tail -f /var/log/nginx/mdn-engineering-access.log

# Ошибки PHP
sudo tail -f /var/log/php7.4-fpm.log

# Ошибки Nginx
sudo tail -f /var/log/nginx/mdn-engineering-error.log

# Логи почты
sudo tail -f /var/log/mail.log
```

### Резервное копирование

Создайте скрипт резервного копирования:

```bash
sudo nano /root/backup-site.sh
```

Вставьте:

```bash
#!/bin/bash
BACKUP_DIR="/root/backups"
SITE_DIR="/var/www/mdn-engineering"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
tar -czf $BACKUP_DIR/mdn-site-$DATE.tar.gz $SITE_DIR

# Удаление старых бэкапов (старше 30 дней)
find $BACKUP_DIR -name "mdn-site-*.tar.gz" -mtime +30 -delete

echo "Backup created: mdn-site-$DATE.tar.gz"
```

Сделайте исполняемым и добавьте в cron:

```bash
sudo chmod +x /root/backup-site.sh

# Добавьте в crontab (каждый день в 2:00)
sudo crontab -e
```

Добавьте строку:
```
0 2 * * * /root/backup-site.sh
```

---

## 🛡️ Безопасность

### Защита config.php

```bash
# Убедитесь, что config.php защищен
sudo chmod 600 /var/www/mdn-engineering/api/config.php
sudo chown www-data:www-data /var/www/mdn-engineering/api/config.php
```

### Firewall

```bash
# Установите UFW (если еще не установлен)
sudo apt install ufw

# Разрешите SSH, HTTP, HTTPS
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'

# Включите firewall
sudo ufw enable
sudo ufw status
```

### Fail2Ban (защита от брутфорса)

```bash
# Установите Fail2Ban
sudo apt install fail2ban -y

# Настройте для Nginx
sudo nano /etc/fail2ban/jail.local
```

Добавьте:

```
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/*error.log

[nginx-noscript]
enabled = true
port = http,https
logpath = /var/log/nginx/*access.log
```

Перезапустите:

```bash
sudo systemctl restart fail2ban
```

---

## 🐛 Решение проблем

### Формы не отправляются

1. **Проверьте логи**:
   ```bash
   sudo tail -f /var/log/nginx/mdn-engineering-error.log
   ```

2. **Проверьте права на config.php**:
   ```bash
   ls -la /var/www/mdn-engineering/api/config.php
   ```

3. **Проверьте PHP errors**:
   ```bash
   sudo tail -f /var/log/php7.4-fpm.log
   ```

### Telegram не присылает уведомления

1. **Проверьте TOKEN и Chat ID** в `api/config.php`
2. **Проверьте, что бот добавлен в группу** (если используете группу)
3. **Проверьте интернет на сервере**:
   ```bash
   curl https://api.telegram.org/botYOUR_TOKEN/getMe
   ```

### Email не приходят

1. **Проверьте SPF запись**
2. **Проверьте папку "Спам"**
3. **Проверьте логи Postfix**:
   ```bash
   sudo tail -f /var/log/mail.log
   ```

---

## 📞 Поддержка

- **Сайт**: https://мдн-инжиниринг.рф
- **Email**: info@mdn-eng.ru
- **Telegram**: @mdn_engineering

---

## 📝 Лицензия

© 2025 ООО "МДН Инжиниринг". Все права защищены.

Сайт создан студией **VKV — New Vision**

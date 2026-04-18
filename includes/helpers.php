<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (!defined('SYSTEM_START_DISH_CATEGORY_NAME')) {
    define('SYSTEM_START_DISH_CATEGORY_NAME', '__SYSTEM_START_DISH__');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function safe_redirect_target($path, $fallback)
{
    if (!is_string($path) || $path === '' || substr($path, 0, 1) !== '/') {
        return $fallback;
    }

    return $path;
}

function add_flash($type, $message)
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = array();
    }

    $_SESSION['flash_messages'][] = array(
        'type' => $type,
        'message' => $message
    );
}

function pull_flashes()
{
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : array();
    unset($_SESSION['flash_messages']);

    return $messages;
}

function set_old_input($formKey, array $data)
{
    if (!isset($_SESSION['old_input'])) {
        $_SESSION['old_input'] = array();
    }

    $_SESSION['old_input'][$formKey] = $data;
}

function consume_old_input($formKey)
{
    if (!isset($_SESSION['old_input'][$formKey])) {
        return array();
    }

    $data = $_SESSION['old_input'][$formKey];
    unset($_SESSION['old_input'][$formKey]);

    return $data;
}

function old_value(array $source, $key, $default)
{
    return isset($source[$key]) && $source[$key] !== '' ? $source[$key] : $default;
}

function db_bind_params(mysqli_stmt $stmt, $types, array &$params)
{
    if ($types === '' || empty($params)) {
        return;
    }

    $bindValues = array($types);

    foreach ($params as $index => $value) {
        $bindValues[] = &$params[$index];
    }

    call_user_func_array(array($stmt, 'bind_param'), $bindValues);
}

function db_fetch_all(mysqli $mysqli, $sql, $types, array $params)
{
    $stmt = $mysqli->prepare($sql);
    db_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = array();

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function db_fetch_one(mysqli $mysqli, $sql, $types, array $params)
{
    $rows = db_fetch_all($mysqli, $sql, $types, $params);

    return empty($rows) ? null : $rows[0];
}

function system_start_dish_category_id(mysqli $mysqli)
{
    static $categoryId = null;

    if ($categoryId !== null) {
        return $categoryId;
    }

    $category = db_fetch_one(
        $mysqli,
        "SELECT `id_category`
         FROM `category`
         WHERE `name_category` = ?
         LIMIT 1",
        's',
        array(SYSTEM_START_DISH_CATEGORY_NAME)
    );

    if ($category) {
        $categoryId = (int) $category['id_category'];
        return $categoryId;
    }

    $stmt = $mysqli->prepare("INSERT INTO `category` (`name_category`) VALUES (?)");
    $name = SYSTEM_START_DISH_CATEGORY_NAME;
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $categoryId = (int) $stmt->insert_id;
    $stmt->close();

    return $categoryId;
}

function system_category_link_schedule_id(mysqli $mysqli)
{
    static $scheduleId = null;

    if ($scheduleId !== null) {
        return $scheduleId;
    }

    $schedule = db_fetch_one(
        $mysqli,
        "SELECT `id_table_schedule`
         FROM `table_schedule`
         WHERE `date_table_schedule` IS NULL
           AND `id_reservation` IS NULL
           AND `id_table` IS NULL
           AND `id_time_block` IS NULL
         ORDER BY `id_table_schedule` ASC
         LIMIT 1",
        '',
        array()
    );

    if ($schedule) {
        $scheduleId = (int) $schedule['id_table_schedule'];
        return $scheduleId;
    }

    $stmt = $mysqli->prepare(
        "INSERT INTO `table_schedule` (`date_table_schedule`, `id_reservation`, `id_table`, `id_time_block`)
         VALUES (NULL, NULL, NULL, NULL)"
    );
    $stmt->execute();
    $scheduleId = (int) $stmt->insert_id;
    $stmt->close();

    return $scheduleId;
}

function fetch_visible_categories(mysqli $mysqli)
{
    $systemCategoryId = system_start_dish_category_id($mysqli);

    return db_fetch_all(
        $mysqli,
        "SELECT `id_category`, `name_category`
         FROM `category`
         WHERE `id_category` <> ?
         ORDER BY `name_category` ASC",
        'i',
        array($systemCategoryId)
    );
}

function format_money($value)
{
    return number_format((float) $value, 0, ',', ' ') . ' руб.';
}

function format_date_ru($dateValue)
{
    if (!$dateValue) {
        return '-';
    }

    return date('d.m.Y', strtotime($dateValue));
}

function format_time_ru($timeValue)
{
    if (!$timeValue) {
        return '-';
    }

    return substr($timeValue, 0, 5);
}

function cart_items()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    return $_SESSION['cart'];
}

function cart_total_count()
{
    return array_sum(cart_items());
}

function fetch_cart_dishes(mysqli $mysqli)
{
    $cart = cart_items();
    $categoryLinkScheduleId = system_category_link_schedule_id($mysqli);

    if (empty($cart)) {
        return array();
    }

    $dishIds = array_map('intval', array_keys($cart));
    $sql = "SELECT
                d.id_dish,
                d.name_dish,
                d.cost_dish,
                d.photo_dish,
                GROUP_CONCAT(DISTINCT c.name_category ORDER BY c.name_category SEPARATOR ', ') AS categories
            FROM `dish` d
            LEFT JOIN `super_key` sk ON sk.id_dish = d.id_dish AND sk.id_table_schedule = {$categoryLinkScheduleId}
            LEFT JOIN `category` c ON c.id_category = sk.id_category
            WHERE d.id_dish IN (" . implode(',', $dishIds) . ")
            GROUP BY d.id_dish, d.name_dish, d.cost_dish, d.photo_dish";

    $rows = array();
    $result = $mysqli->query($sql);

    while ($row = $result->fetch_assoc()) {
        $dishId = (int) $row['id_dish'];
        $quantity = isset($cart[$dishId]) ? (int) $cart[$dishId] : 0;
        $row['quantity'] = $quantity;
        $row['line_total'] = $quantity * (float) $row['cost_dish'];
        $rows[$dishId] = $row;
    }

    $sortedRows = array();

    foreach ($dishIds as $dishId) {
        if (isset($rows[$dishId])) {
            $sortedRows[] = $rows[$dishId];
        }
    }

    return $sortedRows;
}

function cart_total_amount(mysqli $mysqli)
{
    $total = 0;

    foreach (fetch_cart_dishes($mysqli) as $item) {
        $total += (float) $item['line_total'];
    }

    return $total;
}

function blob_to_data_uri($blob)
{
    if ($blob === null || $blob === '') {
        return '';
    }

    $blobString = (string) $blob;
    $prefix = ltrim(substr($blobString, 0, 64));
    $isSvg = stripos($prefix, '<svg') === 0 || stripos($prefix, '<?xml') === 0;
    $mimeType = $isSvg ? 'image/svg+xml' : 'image/jpeg';

    return 'data:' . $mimeType . ';base64,' . base64_encode($blobString);
}

function checked($condition)
{
    return $condition ? 'checked' : '';
}

function selected($condition)
{
    return $condition ? 'selected' : '';
}

function number_to_words_ru($number)
{
    $units = array(
        0 => 'ноль',
        1 => 'один',
        2 => 'два',
        3 => 'три',
        4 => 'четыре',
        5 => 'пять',
        6 => 'шесть',
        7 => 'семь',
        8 => 'восемь',
        9 => 'девять'
    );

    $teens = array(
        10 => 'десять',
        11 => 'одиннадцать',
        12 => 'двенадцать',
        13 => 'тринадцать',
        14 => 'четырнадцать',
        15 => 'пятнадцать',
        16 => 'шестнадцать',
        17 => 'семнадцать',
        18 => 'восемнадцать',
        19 => 'девятнадцать'
    );

    $tens = array(
        2 => 'двадцать',
        3 => 'тридцать',
        4 => 'сорок',
        5 => 'пятьдесят',
        6 => 'шестьдесят',
        7 => 'семьдесят',
        8 => 'восемьдесят',
        9 => 'девяносто'
    );

    if ($number < 10) {
        return $units[$number];
    }

    if ($number < 20) {
        return $teens[$number];
    }

    $tensPart = (int) floor($number / 10);
    $unitsPart = $number % 10;

    return trim($tens[$tensPart] . ' ' . ($unitsPart > 0 ? $units[$unitsPart] : ''));
}

function refresh_registration_captcha()
{
    refresh_numeric_captcha('register_captcha');
}

function refresh_login_captcha()
{
    refresh_numeric_captcha('login_captcha');
}

function refresh_numeric_captcha($sessionKey)
{
    $number = random_int(10, 99);

    $_SESSION[$sessionKey] = array(
        'answer' => (string) $number,
        'words' => number_to_words_ru($number)
    );
}

function write_php_array_file($filePath, array $data)
{
    $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
    file_put_contents($filePath, $content, LOCK_EX);
}

function load_news_items()
{
    return db_fetch_all(
        db(),
        "SELECT
            `id_memory_news` AS id,
            `title_news` AS title,
            `excerpt_news` AS excerpt,
            `content_news` AS content,
            `publish_date`,
            `image_path` AS image
         FROM `memory_news`
         ORDER BY `publish_date` DESC, `id_memory_news` DESC",
        '',
        array()
    );
}

function load_restaurant_info()
{
    $filePath = __DIR__ . '/../data/restaurant_info.php';
    $defaults = array(
        'name' => 'Подворье',
        'subtitle' => 'Русская, белорусская и украинская кухня',
        'description' => 'Подворье — уютный ресторан русской, белорусской и украинской кухни с домашней подачей, спокойной атмосферой и блюдами, вдохновлёнными традициями семейного застолья.',
        'address' => 'Санкт-Петербург, Кузнецовская 19',
        'phone' => '+7 (812) 420-20-20',
        'email' => 'restaraunt@podvorye.local',
        'hours' => 'Ежедневно: 10:00 - 23:00'
    );

    if (!file_exists($filePath)) {
        return $defaults;
    }

    $info = require $filePath;

    if (!is_array($info)) {
        return $defaults;
    }

    return array_merge($defaults, $info);
}

function save_restaurant_info(array $info)
{
    $filePath = __DIR__ . '/../data/restaurant_info.php';
    write_php_array_file($filePath, $info);
}

function load_mail_config()
{
    $filePath = __DIR__ . '/../data/mail_config.php';
    $defaults = array(
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'secure' => 'tls',
        'username' => '',
        'password' => '',
        'timeout' => 20,
        'from_email' => '',
        'from_name' => 'Подворье',
        'to_email' => ''
    );

    if (!file_exists($filePath)) {
        return $defaults;
    }

    $config = require $filePath;

    if (!is_array($config)) {
        return $defaults;
    }

    return array_merge($defaults, $config);
}

function mail_header_encode($value)
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function smtp_read_response($socket)
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);

        if ($line === false) {
            break;
        }

        $response .= $line;

        if (preg_match('/^\d{3}\s/', $line)) {
            break;
        }
    }

    return $response;
}

function smtp_send_command($socket, $command, array $expectedCodes)
{
    fwrite($socket, $command . "\r\n");
    $response = smtp_read_response($socket);
    $statusCode = (int) substr($response, 0, 3);

    if (!in_array($statusCode, $expectedCodes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }

    return $response;
}

function smtp_send_mail(array $config, array $message)
{
    if (empty($config['enabled'])) {
        throw new RuntimeException('SMTP disabled');
    }

    $host = trim($config['host']);
    $port = (int) $config['port'];
    $secure = trim(strtolower($config['secure']));
    $username = trim($config['username']);
    $password = (string) $config['password'];
    $fromEmail = trim($config['from_email']);
    $toEmail = trim($config['to_email']);
    $timeout = (int) $config['timeout'];

    if ($host === '' || $port < 1 || $fromEmail === '' || $toEmail === '') {
        throw new RuntimeException('SMTP config incomplete');
    }

    $transport = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = stream_socket_client($transport, $errno, $errstr, $timeout > 0 ? $timeout : 20);

    if (!$socket) {
        throw new RuntimeException('SMTP connect failed: ' . $errstr);
    }

    stream_set_timeout($socket, $timeout > 0 ? $timeout : 20);

    $greeting = smtp_read_response($socket);
    $greetingCode = (int) substr($greeting, 0, 3);

    if ($greetingCode !== 220) {
        fclose($socket);
        throw new RuntimeException('SMTP greeting failed: ' . trim($greeting));
    }

    $ehloHost = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '' ? $_SERVER['HTTP_HOST'] : 'localhost';
    smtp_send_command($socket, 'EHLO ' . $ehloHost, array(250));

    if ($secure === 'tls') {
        smtp_send_command($socket, 'STARTTLS', array(220));

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new RuntimeException('SMTP TLS negotiation failed');
        }

        smtp_send_command($socket, 'EHLO ' . $ehloHost, array(250));
    }

    if ($username !== '' || $password !== '') {
        smtp_send_command($socket, 'AUTH LOGIN', array(334));
        smtp_send_command($socket, base64_encode($username), array(334));
        smtp_send_command($socket, base64_encode($password), array(235));
    }

    smtp_send_command($socket, 'MAIL FROM:<' . $fromEmail . '>', array(250));
    smtp_send_command($socket, 'RCPT TO:<' . $toEmail . '>', array(250, 251));
    smtp_send_command($socket, 'DATA', array(354));

    $fromHeader = trim($config['from_name']) !== ''
        ? mail_header_encode($config['from_name']) . ' <' . $fromEmail . '>'
        : $fromEmail;

    $headers = array(
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'To: <' . $toEmail . '>',
        'Reply-To: <' . $message['reply_to'] . '>',
        'Subject: ' . mail_header_encode($message['subject']),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit'
    );

    $bodyLines = preg_split("/\r\n|\r|\n/", $message['body']);

    foreach ($bodyLines as $index => $line) {
        if (isset($line[0]) && $line[0] === '.') {
            $bodyLines[$index] = '.' . $line;
        }
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $bodyLines) . "\r\n.";
    fwrite($socket, $payload . "\r\n");

    $response = smtp_read_response($socket);
    $statusCode = (int) substr($response, 0, 3);

    if ($statusCode !== 250) {
        fclose($socket);
        throw new RuntimeException('SMTP send failed: ' . trim($response));
    }

    smtp_send_command($socket, 'QUIT', array(221));
    fclose($socket);
    return true;
}

function dish_photo_from_upload($fieldName)
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    if ((int) $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
        return null;
    }

    return file_get_contents($_FILES[$fieldName]['tmp_name']);
}

<?php
require_once __DIR__ . '/../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register.php');
}

$mysqli = db();

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
$patronymic = isset($_POST['patronymic']) ? trim($_POST['patronymic']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$birthdate = isset($_POST['birthdate']) ? $_POST['birthdate'] : '';
$captchaAnswer = isset($_POST['captcha_answer']) ? trim($_POST['captcha_answer']) : '';

$oldInput = array(
    'email' => $email,
    'name' => $name,
    'surname' => $surname,
    'patronymic' => $patronymic,
    'phone' => $phone,
    'birthdate' => $birthdate
);

if ($email === '' || $password === '') {
    set_old_input('register', $oldInput);
    add_flash('error', 'Заполните e-mail и пароль.');
    redirect('/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_old_input('register', $oldInput);
    add_flash('error', 'Введите корректный адрес электронной почты.');
    redirect('/register.php');
}

if (strlen($password) < 6) {
    set_old_input('register', $oldInput);
    add_flash('error', 'Пароль должен содержать минимум 6 символов.');
    redirect('/register.php');
}

if (!isset($_SESSION['register_captcha']) || $captchaAnswer !== $_SESSION['register_captcha']['answer']) {
    refresh_registration_captcha();
    set_old_input('register', $oldInput);
    add_flash('error', 'Капча введена неверно. Введите число цифрами.');
    redirect('/register.php');
}

$existingUser = db_fetch_one(
    $mysqli,
    "SELECT `id_user` FROM `user` WHERE `email` = ? LIMIT 1",
    's',
    array($email)
);

if ($existingUser) {
    refresh_registration_captcha();
    set_old_input('register', $oldInput);
    add_flash('error', 'Пользователь с таким e-mail уже существует.');
    redirect('/register.php');
}

$passwordHash = md5($password);
$birthdateValue = $birthdate !== '' ? $birthdate : null;

$stmt = $mysqli->prepare(
    "INSERT INTO `user`
    (`phone`, `email`, `password`, `birthdate`, `name`, `surname`, `patronymic`)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    'sssssss',
    $phone,
    $email,
    $passwordHash,
    $birthdateValue,
    $name,
    $surname,
    $patronymic
);

$stmt->execute();
$newUserId = $stmt->insert_id;
$stmt->close();

refresh_auth_user($mysqli, $newUserId);
refresh_registration_captcha();
add_flash('success', 'Регистрация прошла успешно. Добро пожаловать!');
redirect('/cabinet.php');

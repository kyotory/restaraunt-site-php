<?php
require_once __DIR__ . '/../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

$mysqli = db();

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$captchaAnswer = isset($_POST['captcha_answer']) ? trim($_POST['captcha_answer']) : '';

set_old_input('login', array('email' => $email));

if ($email === '' || $password === '') {
    refresh_login_captcha();
    add_flash('error', 'Введите e-mail и пароль.');
    redirect('/login.php');
}

if (!isset($_SESSION['login_captcha']) || $captchaAnswer !== $_SESSION['login_captcha']['answer']) {
    refresh_login_captcha();
    add_flash('error', 'Капча введена неверно. Введите число цифрами.');
    redirect('/login.php');
}

if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
    logout_user();
    login_admin();
    refresh_login_captcha();
    unset($_SESSION['old_input']['login']);
    add_flash('success', 'Вход администратора выполнен.');
    redirect('/');
}

$user = db_fetch_one(
    $mysqli,
    "SELECT * FROM `user` WHERE `email` = ? LIMIT 1",
    's',
    array($email)
);

if (!$user) {
    refresh_login_captcha();
    add_flash('error', 'Пользователь с таким e-mail не найден.');
    redirect('/login.php');
}

$passwordOk = md5($password) === $user['password'] || $password === $user['password'];

if (!$passwordOk) {
    refresh_login_captcha();
    add_flash('error', 'Неверный пароль.');
    redirect('/login.php');
}

login_user($user);
refresh_login_captcha();
unset($_SESSION['old_input']['login']);
add_flash('success', 'Авторизация выполнена успешно.');
redirect('/cabinet.php');

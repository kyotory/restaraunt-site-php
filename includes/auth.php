<?php

function auth_user()
{
    return isset($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
}

function is_logged_in()
{
    return auth_user() !== null;
}

function login_user(array $user)
{
    $_SESSION['auth_user'] = array(
        'id_user' => (int) $user['id_user'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'name' => $user['name'],
        'surname' => $user['surname'],
        'patronymic' => $user['patronymic'],
        'birthdate' => $user['birthdate']
    );
}

function refresh_auth_user(mysqli $mysqli, $userId)
{
    $user = db_fetch_one($mysqli, "SELECT * FROM `user` WHERE `id_user` = ? LIMIT 1", 'i', array($userId));

    if ($user) {
        login_user($user);
    }
}

function logout_user()
{
    unset($_SESSION['auth_user'], $_SESSION['cart']);
}

function auth_user_label()
{
    $user = auth_user();

    if (!$user) {
        return '';
    }

    $fullName = trim($user['surname'] . ' ' . $user['name']);

    return $fullName !== '' ? $fullName : $user['email'];
}

function require_login()
{
    if (!is_logged_in()) {
        add_flash('warning', 'Для этой страницы сначала нужно авторизоваться.');
        redirect('/login.php');
    }
}

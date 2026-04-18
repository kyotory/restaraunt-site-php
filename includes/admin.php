<?php

define('ADMIN_EMAIL', 'admin@podvorye.local');
define('ADMIN_PASSWORD', 'Admin123!');

function is_admin_logged_in()
{
    return !empty($_SESSION['is_admin_logged_in']);
}

function login_admin()
{
    $_SESSION['is_admin_logged_in'] = true;
    $_SESSION['admin_email'] = ADMIN_EMAIL;
}

function logout_admin()
{
    unset($_SESSION['is_admin_logged_in'], $_SESSION['admin_email']);
}

function require_admin()
{
    if (!is_admin_logged_in()) {
        add_flash('warning', 'Для доступа к админ-панели войдите через страницу авторизации.');
        redirect('/login.php');
    }
}

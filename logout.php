<?php
require_once __DIR__ . '/includes/app.php';

logout_user();
add_flash('success', 'Вы вышли из личного кабинета.');
redirect('/');

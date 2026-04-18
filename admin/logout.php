<?php
require_once __DIR__ . '/../includes/app.php';

logout_admin();
add_flash('success', 'Вы вышли из админ-панели.');
redirect('/');

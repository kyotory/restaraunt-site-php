<?php
$restaurantInfo = load_restaurant_info();
$pageTitle = isset($pageTitle) ? $pageTitle : $restaurantInfo['name'];
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminArea = strpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/admin/') !== false;
$flashes = pull_flashes();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($restaurantInfo['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="brand-panel">
            <a class="brand" href="/">
                <img src="/assets/img/logo.svg" alt="Эмблема ресторана <?= e($restaurantInfo['name']) ?>">
                <div>
                    <strong><?= e($restaurantInfo['name']) ?></strong>
                    <span><?= e($restaurantInfo['subtitle']) ?></span>
                </div>
            </a>
            <div class="brand-tools">
                <?php if (!is_admin_logged_in()): ?>
                    <span class="badge">Начальные блюда: <?= cart_total_count() ?></span>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                    <span class="badge accent"><?= e(auth_user_label()) ?></span>
                <?php endif; ?>
                <?php if (is_admin_logged_in()): ?>
                    <span class="badge accent">Админ</span>
                <?php endif; ?>
            </div>
        </div>
        <nav class="site-nav">
            <a href="/" class="<?= !$isAdminArea && $currentPage === 'index.php' ? 'active' : '' ?>">Главная</a>
            <a href="/halls.php" class="<?= !$isAdminArea && $currentPage === 'halls.php' ? 'active' : '' ?>">Залы</a>
            <a href="/catalog.php" class="<?= !$isAdminArea && $currentPage === 'catalog.php' ? 'active' : '' ?>">Каталог</a>
            <a href="/news.php" class="<?= !$isAdminArea && $currentPage === 'news.php' ? 'active' : '' ?>">Новости</a>
            <?php if (!is_admin_logged_in()): ?>
                <a href="/cart.php" class="<?= !$isAdminArea && $currentPage === 'cart.php' ? 'active' : '' ?>">Начальные блюда</a>
                <a href="/booking.php" class="<?= !$isAdminArea && $currentPage === 'booking.php' ? 'active' : '' ?>">Бронирование</a>
            <?php endif; ?>
            <a href="/contacts.php" class="<?= !$isAdminArea && $currentPage === 'contacts.php' ? 'active' : '' ?>">Контакты</a>
            <?php if (is_admin_logged_in()): ?>
                <a href="/admin/index.php" class="<?= $isAdminArea ? 'active' : '' ?>">Админ-панель</a>
                <a href="/admin/logout.php">Выход</a>
            <?php elseif (is_logged_in()): ?>
                <a href="/cabinet.php" class="<?= !$isAdminArea && $currentPage === 'cabinet.php' ? 'active' : '' ?>">Кабинет</a>
                <a href="/logout.php">Выход</a>
            <?php else: ?>
                <a href="/login.php" class="<?= !$isAdminArea && $currentPage === 'login.php' ? 'active' : '' ?>">Вход</a>
                <a href="/register.php" class="<?= !$isAdminArea && $currentPage === 'register.php' ? 'active' : '' ?>">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="site-main">
        <?php foreach ($flashes as $flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

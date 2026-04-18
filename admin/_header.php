<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

$pageTitle = isset($adminPageTitle) ? $adminPageTitle : 'Админ-панель';
$adminCurrentPage = basename($_SERVER['PHP_SELF']);

require __DIR__ . '/../includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Админ-панель</h1>
    </div>

    <nav class="site-nav">
        <a href="/admin/index.php" class="<?= $adminCurrentPage === 'index.php' ? 'active' : '' ?>">Обзор</a>
        <a href="/admin/dishes.php" class="<?= $adminCurrentPage === 'dishes.php' ? 'active' : '' ?>">Блюда</a>
        <a href="/admin/categories.php" class="<?= $adminCurrentPage === 'categories.php' ? 'active' : '' ?>">Категории</a>
        <a href="/admin/reservations.php" class="<?= $adminCurrentPage === 'reservations.php' ? 'active' : '' ?>">Брони</a>
        <a href="/admin/content.php" class="<?= $adminCurrentPage === 'content.php' ? 'active' : '' ?>">Новости</a>
        <a href="/admin/restaurant.php" class="<?= $adminCurrentPage === 'restaurant.php' ? 'active' : '' ?>">Инфо о ресторане</a>
    </nav>
</section>

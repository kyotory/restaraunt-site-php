<?php
$adminPageTitle = 'Обзор';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Админ-панель</h1>
        <p>Здесь собраны все действия для управления блюдами, категориями, бронями, новостями и информацией о ресторане.</p>
    </div>

    <div class="menu-grid">
        <a class="menu-card" href="/admin/dishes.php"><strong>Блюда</strong><span>Добавление и изменение карточек блюд.</span></a>
        <a class="menu-card" href="/admin/categories.php"><strong>Категории</strong><span>Добавление новых категорий блюд.</span></a>
        <a class="menu-card" href="/admin/reservations.php"><strong>Брони</strong><span>Назначение столов и смена статусов заявок.</span></a>
        <a class="menu-card" href="/admin/content.php"><strong>Новости</strong><span>Добавление, изменение и удаление новостей ресторана.</span></a>
        <a class="menu-card" href="/admin/restaurant.php"><strong>Инфо о ресторане</strong><span>Изменение адреса, телефона, почты и описания.</span></a>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

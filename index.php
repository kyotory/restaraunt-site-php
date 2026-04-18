<?php
require_once __DIR__ . '/includes/app.php';

$mysqli = db();
$restaurantInfo = load_restaurant_info();
$pageTitle = 'Главная';
$allNews = load_news_items();
$systemCategoryId = system_start_dish_category_id($mysqli);

$stats = db_fetch_one(
    $mysqli,
    "SELECT
        (SELECT COUNT(*) FROM `dish`) AS dishes_count,
        (SELECT COUNT(*) FROM `category` WHERE `id_category` <> ?) AS categories_count,
        (SELECT COUNT(*) FROM `table`) AS tables_count",
    'i',
    array($systemCategoryId)
);

$latestNews = array_slice($allNews, 0, 3);

require __DIR__ . '/includes/header.php';
?>

<section class="hero-card">
    <div class="hero-copy">
        <p class="eyebrow">Ресторан восточнославянской кухни</p>
        <h1>Сайт ресторана <?= e($restaurantInfo['name']) ?></h1>
        <p><?= e($restaurantInfo['description']) ?></p>
        <div class="hero-actions">
            <a class="button" href="/catalog.php">Смотреть каталог</a>
            <a class="button secondary" href="/booking.php">Перейти к бронированию</a>
        </div>
    </div>
    <div class="hero-aside">
        <div class="stats-card">
            <span>Блюд в каталоге</span>
            <strong><?= (int) $stats['dishes_count'] ?></strong>
        </div>
        <div class="stats-card">
            <span>Категорий кухни</span>
            <strong><?= (int) $stats['categories_count'] ?></strong>
        </div>
        <div class="stats-card">
            <span>Столов в ресторане</span>
            <strong><?= (int) $stats['tables_count'] ?></strong>
        </div>
        <div class="stats-card">
            <span>Новостей на сайте</span>
            <strong><?= count($allNews) ?></strong>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-title">
        <h2>Меню сайта</h2>
    </div>
    <div class="menu-grid">
        <a class="menu-card" href="/halls.php"><strong>Залы</strong><span>Информация о залах ресторана и количестве столиков.</span></a>
        <?php if (!is_logged_in() && !is_admin_logged_in()): ?>
            <a class="menu-card" href="/register.php"><strong>Регистрация</strong><span>Создание учётной записи с капчей.</span></a>
            <a class="menu-card" href="/login.php"><strong>Авторизация</strong><span>Вход по e-mail и паролю.</span></a>
        <?php endif; ?>
        <?php if (!is_admin_logged_in()): ?>
            <a class="menu-card" href="/cabinet.php"><strong>Личный кабинет</strong><span>Данные пользователя и его брони.</span></a>
        <?php endif; ?>
        <a class="menu-card" href="/catalog.php"><strong>Каталог блюд</strong><span>Фильтрация, сортировка и поиск.</span></a>
        <?php if (!is_admin_logged_in()): ?>
            <a class="menu-card" href="/cart.php"><strong>Начальные блюда</strong><span>Блюда, которые подадут сразу после прихода.</span></a>
            <a class="menu-card" href="/booking.php"><strong>Бронирование</strong><span>Оформление заявки на посещение ресторана.</span></a>
        <?php endif; ?>
        <a class="menu-card" href="/contacts.php"><strong>Контакты</strong><span>Отправка пожеланий и замечаний.</span></a>
        <a class="menu-card" href="/news.php"><strong>Новости</strong><span>Новостная лента ресторана.</span></a>
    </div>
</section>

<section class="panel">
    <div class="section-title">
        <h2>Последние новости</h2>
        <p>Анонсы последних событий и обновлений ресторана.</p>
    </div>
    <div class="news-preview-grid">
        <?php if (!empty($latestNews)): ?>
            <?php foreach ($latestNews as $newsItem): ?>
                <article class="news-card compact">
                    <div class="news-body">
                        <span class="news-date"><?= e(format_date_ru($newsItem['publish_date'])) ?></span>
                        <h3><?= e($newsItem['title']) ?></h3>
                        <p><?= e($newsItem['excerpt']) ?></p>
                        <a class="text-link" href="/news.php">Читать в ленте</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Новостная лента пока не заполнена.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

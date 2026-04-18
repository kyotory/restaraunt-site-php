<?php
require_once __DIR__ . '/includes/app.php';

$pageTitle = 'Новости';
$newsList = load_news_items();

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Новостная лента</h1>
    </div>
    <div class="news-feed">
        <?php if (!empty($newsList)): ?>
            <?php foreach ($newsList as $news): ?>
                <article class="news-card">
                    <img src="<?= e($news['image']) ?>" alt="<?= e($news['title']) ?>">
                    <div class="news-body">
                        <span class="news-date"><?= e(format_date_ru($news['publish_date'])) ?></span>
                        <h2><?= e($news['title']) ?></h2>
                        <p class="lead"><?= e($news['excerpt']) ?></p>
                        <p><?= e($news['content']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Новости пока не добавлены.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

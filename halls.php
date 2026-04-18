<?php
require_once __DIR__ . '/includes/app.php';

$mysqli = db();
$pageTitle = 'Залы';

$halls = db_fetch_all(
    $mysqli,
    "SELECT
        f.id_floor,
        f.name_floor,
        f.photo_floor,
        f.description_floor,
        COALESCE(ts.tables_count, 0) AS tables_count,
        COALESCE(ts.seats_count, 0) AS seats_count
     FROM `floor` f
     LEFT JOIN (
        SELECT
            id_floor,
            COUNT(*) AS tables_count,
            SUM(place_count) AS seats_count
        FROM `table`
        GROUP BY id_floor
     ) ts ON ts.id_floor = f.id_floor
     ORDER BY f.id_floor ASC",
    '',
    array()
);

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Залы ресторана</h1>
        <p>На странице собрана информация о залах и количестве столиков в каждом из них.</p>
    </div>

    <div class="gallery-grid">
        <?php foreach ($halls as $hall): ?>
            <article class="gallery-card">
                <?php if ($hall['photo_floor'] !== null && $hall['photo_floor'] !== ''): ?>
                    <img src="<?= e(blob_to_data_uri($hall['photo_floor'])) ?>" alt="<?= e($hall['name_floor']) ?>">
                <?php else: ?>
                    <div class="gallery-photo-placeholder">Фото зала скоро появится</div>
                <?php endif; ?>
                <div class="gallery-card-body">
                    <div class="card-topline">
                        <h3><?= e($hall['name_floor']) ?></h3>
                        <span class="price-tag"><?= (int) $hall['tables_count'] ?> стол.</span>
                    </div>
                    <p><?= e($hall['description_floor']) ?></p>
                    <div class="meta-line">
                        <span>Количество столиков: <?= (int) $hall['tables_count'] ?></span>
                        <span>Всего мест: <?= (int) $hall['seats_count'] ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($halls)): ?>
            <div class="empty-state">
                <p>Информация о залах пока не заполнена.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

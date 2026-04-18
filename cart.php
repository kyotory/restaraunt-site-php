<?php
require_once __DIR__ . '/includes/app.php';

require_login();

$mysqli = db();
$pageTitle = 'Начальные блюда подачи';
$items = fetch_cart_dishes($mysqli);
$total = cart_total_amount($mysqli);

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Начальные блюда подачи</h1>
        <p>Здесь можно отметить блюда, которые гость хочет получить сразу после прихода в ресторан. После оформления брони они прикрепляются к бронированию и очищаются из списка.</p>
    </div>

    <?php if (!empty($items)): ?>
        <form action="/actions/cart.php" method="post" class="stack-form">
            <input type="hidden" name="redirect_to" value="/cart.php">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Фото</th>
                        <th>Блюдо</th>
                        <th>Категории</th>
                        <th>Цена</th>
                        <th>Удаление</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="photo-cell">
                                <?php if ($item['photo_dish'] !== null && $item['photo_dish'] !== ''): ?>
                                    <img src="<?= e(blob_to_data_uri($item['photo_dish'])) ?>" alt="<?= e($item['name_dish']) ?>">
                                <?php else: ?>
                                    <div class="photo-placeholder">Без фото</div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['name_dish']) ?></td>
                            <td><?= e($item['categories'] !== null && $item['categories'] !== '' ? $item['categories'] : 'Без категории') ?></td>
                            <td><?= e(format_money($item['cost_dish'])) ?></td>
                            <td>
                                <button class="button danger small" type="submit" formaction="/actions/cart.php" name="action" value="remove_<?= (int) $item['id_dish'] ?>">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary-line">
                <strong>Итого: <?= e(format_money($total)) ?></strong>
                <div class="hero-actions">
                    <button class="button danger" type="submit" formaction="/actions/cart.php" name="action" value="clear">Очистить</button>
                    <a class="button" href="/booking.php">Оформить бронь</a>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="empty-state">
            <p>Список начальных блюд пока пуст.</p>
            <a class="button" href="/catalog.php">Перейти в каталог</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

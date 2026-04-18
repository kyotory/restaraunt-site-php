<?php
require_once __DIR__ . '/includes/app.php';

$mysqli = db();
$pageTitle = 'Каталог блюд';
$categories = fetch_visible_categories($mysqli);
$categoryLinkScheduleId = system_category_link_schedule_id($mysqli);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$sortField = isset($_GET['sort_field']) ? $_GET['sort_field'] : 'cost';
$sortOrder = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'desc' ? 'DESC' : 'ASC';
$budgetOnly = isset($_GET['budget_only']);
$withPhoto = isset($_GET['with_photo']);

$allowedSortFields = array(
    'name' => 'd.name_dish',
    'cost' => 'd.cost_dish'
);

$sortSql = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : $allowedSortFields['cost'];

$sql = "SELECT
            d.id_dish,
            d.name_dish,
            d.photo_dish,
            d.description_dish,
            d.cost_dish,
            GROUP_CONCAT(DISTINCT c.name_category ORDER BY c.name_category SEPARATOR ', ') AS categories
        FROM `dish` d
        LEFT JOIN `super_key` sk ON sk.id_dish = d.id_dish AND sk.id_table_schedule = ?
        LEFT JOIN `category` c ON c.id_category = sk.id_category
        WHERE 1 = 1";

$types = 'i';
$params = array($categoryLinkScheduleId);

if ($search !== '') {
    $sql .= " AND (
        d.name_dish LIKE CONCAT('%', ?, '%')
        OR d.description_dish LIKE CONCAT('%', ?, '%')
    )";
    $types .= 'ss';
    $params[] = $search;
    $params[] = $search;
}

if ($categoryId > 0) {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM `super_key` sk_filter
        WHERE sk_filter.id_dish = d.id_dish
          AND sk_filter.id_table_schedule = ?
          AND sk_filter.id_category = ?
    )";
    $types .= 'ii';
    $params[] = $categoryLinkScheduleId;
    $params[] = $categoryId;
}

if ($budgetOnly) {
    $sql .= " AND d.cost_dish <= 500";
}

if ($withPhoto) {
    $sql .= " AND d.photo_dish IS NOT NULL AND d.photo_dish <> ''";
}

$sql .= " GROUP BY
            d.id_dish,
            d.name_dish,
            d.photo_dish,
            d.description_dish,
            d.cost_dish
          ORDER BY {$sortSql} {$sortOrder}, d.name_dish ASC";

$dishes = db_fetch_all($mysqli, $sql, $types, $params);
$currentQuery = http_build_query($_GET);
$redirectTarget = $currentQuery !== '' ? '/catalog.php?' . $currentQuery : '/catalog.php';

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Каталог блюд с фильтрацией и сортировкой</h1>
        <p>Выберите блюда, которые хотите добавить в начальные блюда подачи.</p>
    </div>

    <form class="filter-form" method="get">
        <div class="form-grid three">
            <label>
                <span>Поиск по названию или описанию</span>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Например, борщ">
            </label>

            <label>
                <span>Категория</span>
                <select name="category">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id_category'] ?>" <?= selected($categoryId === (int) $category['id_category']) ?>>
                            <?= e($category['name_category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Поле сортировки</span>
                <select name="sort_field">
                    <option value="cost" <?= selected($sortField === 'cost') ?>>Цена</option>
                    <option value="name" <?= selected($sortField === 'name') ?>>Название</option>
                </select>
            </label>
        </div>

        <div class="form-grid three compact">
            <label class="check-field">
                <input type="checkbox" name="budget_only" <?= checked($budgetOnly) ?>>
                <span>До 500 ₽</span>
            </label>
            <label class="check-field">
                <input type="checkbox" name="with_photo" <?= checked($withPhoto) ?>>
                <span>Только позиции с фото</span>
            </label>
            <span></span>
        </div>

        <div class="radio-group">
            <span>Порядок сортировки</span>
            <label><input type="radio" name="sort_order" value="asc" <?= checked($sortOrder === 'ASC') ?>> По возрастанию</label>
            <label><input type="radio" name="sort_order" value="desc" <?= checked($sortOrder === 'DESC') ?>> По убыванию</label>
        </div>

        <div class="hero-actions">
            <button class="button" type="submit">Применить фильтры</button>
            <a class="button secondary" href="/catalog.php">Сбросить</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-title">
        <h2>Полная информация о продуктах</h2>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Фото</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Категории</th>
                <th>Цена</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dishes as $dish): ?>
                <tr>
                    <td class="photo-cell">
                        <?php if ($dish['photo_dish'] !== null && $dish['photo_dish'] !== ''): ?>
                            <img src="<?= e(blob_to_data_uri($dish['photo_dish'])) ?>" alt="<?= e($dish['name_dish']) ?>">
                        <?php else: ?>
                            <div class="photo-placeholder">Без фото</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($dish['name_dish']) ?></strong></td>
                    <td><?= e($dish['description_dish']) ?></td>
                    <td><?= e($dish['categories'] !== null && $dish['categories'] !== '' ? $dish['categories'] : 'Без категории') ?></td>
                    <td><?= e(format_money($dish['cost_dish'])) ?></td>
                    <td>
                        <form action="/actions/cart.php" method="post" class="inline-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="dish_id" value="<?= (int) $dish['id_dish'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect_to" value="<?= e($redirectTarget) ?>">
                            <button class="button small" type="submit">В начальные блюда</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dishes)): ?>
                <tr>
                    <td colspan="6">По заданным условиям ничего не найдено.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

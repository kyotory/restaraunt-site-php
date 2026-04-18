<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

$mysqli = db();
$categoryLinkScheduleId = system_category_link_schedule_id($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dishId = isset($_POST['id_dish']) ? (int) $_POST['id_dish'] : 0;
    $isEdit = $dishId > 0;
    $nameDish = isset($_POST['name_dish']) ? trim($_POST['name_dish']) : '';
    $descriptionDish = isset($_POST['description_dish']) ? trim($_POST['description_dish']) : '';
    $costDish = isset($_POST['cost_dish']) ? (float) $_POST['cost_dish'] : 0;
    $categoryIds = isset($_POST['category_ids']) && is_array($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array();
    $photoBlob = dish_photo_from_upload('photo_dish');

    if ($nameDish === '' || $costDish <= 0) {
        add_flash('error', 'Заполните название блюда и его стоимость.');
        redirect($dishId > 0 ? '/admin/dishes.php?edit=' . $dishId : '/admin/dishes.php');
    }

    if ($isEdit) {
        if ($photoBlob !== null) {
            $stmt = $mysqli->prepare(
                "UPDATE `dish`
                 SET `name_dish` = ?, `photo_dish` = ?, `description_dish` = ?, `cost_dish` = ?
                 WHERE `id_dish` = ?"
            );
            $stmt->bind_param('sssdi', $nameDish, $photoBlob, $descriptionDish, $costDish, $dishId);
        } else {
            $stmt = $mysqli->prepare(
                "UPDATE `dish`
                 SET `name_dish` = ?, `description_dish` = ?, `cost_dish` = ?
                 WHERE `id_dish` = ?"
            );
            $stmt->bind_param('ssdi', $nameDish, $descriptionDish, $costDish, $dishId);
        }

        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO `dish` (`name_dish`, `photo_dish`, `description_dish`, `cost_dish`)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('sssd', $nameDish, $photoBlob, $descriptionDish, $costDish);
        $stmt->execute();
        $dishId = $stmt->insert_id;
        $stmt->close();
    }

    $deleteStmt = $mysqli->prepare(
        "DELETE FROM `super_key`
         WHERE `id_dish` = ? AND `id_table_schedule` = ?"
    );
    $deleteStmt->bind_param('ii', $dishId, $categoryLinkScheduleId);
    $deleteStmt->execute();
    $deleteStmt->close();

    if (!empty($categoryIds)) {
        $insertStmt = $mysqli->prepare(
            "INSERT INTO `super_key` (`id_dish`, `id_category`, `id_table_schedule`)
             VALUES (?, ?, ?)"
        );

        foreach ($categoryIds as $categoryId) {
            if ($categoryId < 1) {
                continue;
            }

            $insertStmt->bind_param('iii', $dishId, $categoryId, $categoryLinkScheduleId);
            $insertStmt->execute();
        }

        $insertStmt->close();
    }

    add_flash('success', $isEdit ? 'Информация о блюде сохранена.' : 'Блюдо добавлено.');
    redirect('/admin/dishes.php');
}

$categories = fetch_visible_categories($mysqli);

$editDishId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editDish = null;
$editDishCategories = array();

if ($editDishId > 0) {
    $editDish = db_fetch_one(
        $mysqli,
        "SELECT `id_dish`, `name_dish`, `photo_dish`, `description_dish`, `cost_dish`
         FROM `dish`
         WHERE `id_dish` = ?
         LIMIT 1",
        'i',
        array($editDishId)
    );

    $categoryRows = db_fetch_all(
        $mysqli,
        "SELECT `id_category`
         FROM `super_key`
         WHERE `id_dish` = ? AND `id_table_schedule` = ?",
        'ii',
        array($editDishId, $categoryLinkScheduleId)
    );

    foreach ($categoryRows as $row) {
        $editDishCategories[] = (int) $row['id_category'];
    }
}

$dishes = db_fetch_all(
    $mysqli,
    "SELECT
        d.id_dish,
        d.name_dish,
        d.photo_dish,
        d.description_dish,
        d.cost_dish,
        GROUP_CONCAT(DISTINCT c.name_category ORDER BY c.name_category SEPARATOR ', ') AS categories
     FROM `dish` d
     LEFT JOIN `super_key` sk ON sk.id_dish = d.id_dish AND sk.id_table_schedule = ?
     LEFT JOIN `category` c ON c.id_category = sk.id_category
     GROUP BY d.id_dish, d.name_dish, d.photo_dish, d.description_dish, d.cost_dish
     ORDER BY d.name_dish ASC",
    'i',
    array($categoryLinkScheduleId)
);

$adminPageTitle = 'Блюда';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Добавление и изменение блюд</h1>
        <p>Можно создать новое блюдо, загрузить фото, задать цену и привязать его к категориям.</p>
    </div>

    <form method="post" enctype="multipart/form-data" class="stack-form">
        <input type="hidden" name="id_dish" value="<?= $editDish ? (int) $editDish['id_dish'] : 0 ?>">
        <div class="form-grid two">
            <label>
                <span>Название блюда</span>
                <input type="text" name="name_dish" required value="<?= e($editDish ? $editDish['name_dish'] : '') ?>">
            </label>
            <label>
                <span>Стоимость</span>
                <input type="number" step="0.01" min="0" name="cost_dish" required value="<?= e($editDish ? $editDish['cost_dish'] : '') ?>">
            </label>
            <label class="full-width">
                <span>Описание</span>
                <textarea name="description_dish" rows="4"><?= e($editDish ? $editDish['description_dish'] : '') ?></textarea>
            </label>
            <label>
                <span>Фото блюда</span>
                <input type="file" name="photo_dish" accept="image/*">
            </label>
            <div>
                <span>Категории</span>
                <div class="checkbox-grid">
                    <?php foreach ($categories as $category): ?>
                        <label class="check-field">
                            <input type="checkbox" name="category_ids[]" value="<?= (int) $category['id_category'] ?>" <?= checked(in_array((int) $category['id_category'], $editDishCategories, true)) ?>>
                            <span><?= e($category['name_category']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="hero-actions">
            <button class="button" type="submit"><?= $editDish ? 'Сохранить изменения' : 'Добавить блюдо' ?></button>
            <?php if ($editDish): ?>
                <a class="button secondary" href="/admin/dishes.php">Отменить редактирование</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Фото</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Категории</th>
                <th>Цена</th>
                <th>Изменение</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dishes as $dish): ?>
                <tr>
                    <td class="photo-cell">
                        <?php if ($dish['photo_dish'] !== null && $dish['photo_dish'] !== ''): ?>
                            <img src="<?= e(blob_to_data_uri($dish['photo_dish'])) ?>" alt="<?= e($dish['name_dish']) ?>">
                        <?php else: ?>
                            <span class="muted">Без фото</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($dish['name_dish']) ?></td>
                    <td><?= e($dish['description_dish']) ?></td>
                    <td><?= e($dish['categories']) ?></td>
                    <td><?= e(format_money($dish['cost_dish'])) ?></td>
                    <td><a class="button secondary small" href="/admin/dishes.php?edit=<?= (int) $dish['id_dish'] ?>">Изменить</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dishes)): ?>
                <tr>
                    <td colspan="6">Блюда пока не добавлены.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

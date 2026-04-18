<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

$mysqli = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameCategory = isset($_POST['name_category']) ? trim($_POST['name_category']) : '';

    if ($nameCategory === '') {
        add_flash('error', 'Введите название категории.');
        redirect('/admin/categories.php');
    }

    if ($nameCategory === SYSTEM_START_DISH_CATEGORY_NAME) {
        add_flash('error', 'Это служебное название уже используется системой.');
        redirect('/admin/categories.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO `category` (`name_category`) VALUES (?)");
    $stmt->bind_param('s', $nameCategory);
    $stmt->execute();
    $stmt->close();

    add_flash('success', 'Категория добавлена.');
    redirect('/admin/categories.php');
}

$categories = fetch_visible_categories($mysqli);

$adminPageTitle = 'Категории';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Категории блюд</h1>
        <p>Раздел для добавления категорий, которые используются в каталоге и карточках блюд.</p>
    </div>

    <form method="post" class="stack-form">
        <div class="form-grid two">
            <label>
                <span>Название категории</span>
                <input type="text" name="name_category" required>
            </label>
        </div>
        <button class="button" type="submit">Добавить категорию</button>
    </form>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Категория</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= (int) $category['id_category'] ?></td>
                    <td><?= e($category['name_category']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="2">Категории пока не добавлены.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

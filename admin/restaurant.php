<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $info = array(
        'name' => isset($_POST['name']) ? trim($_POST['name']) : 'Подворье',
        'subtitle' => isset($_POST['subtitle']) ? trim($_POST['subtitle']) : '',
        'description' => isset($_POST['description']) ? trim($_POST['description']) : '',
        'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
        'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
        'hours' => isset($_POST['hours']) ? trim($_POST['hours']) : ''
    );

    save_restaurant_info($info);
    add_flash('success', 'Информация о ресторане обновлена.');
    redirect('/admin/restaurant.php');
}

$restaurantInfo = load_restaurant_info();
$adminPageTitle = 'Инфо о ресторане';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Информация о ресторане</h1>
        <p>Здесь можно изменить данные, которые используются на главной странице, в контактах и в подвале сайта.</p>
    </div>

    <form method="post" class="stack-form">
        <div class="form-grid two">
            <label>
                <span>Название</span>
                <input type="text" name="name" required value="<?= e($restaurantInfo['name']) ?>">
            </label>
            <label>
                <span>Подзаголовок</span>
                <input type="text" name="subtitle" required value="<?= e($restaurantInfo['subtitle']) ?>">
            </label>
            <label class="full-width">
                <span>Описание на главной</span>
                <textarea name="description" rows="4" required><?= e($restaurantInfo['description']) ?></textarea>
            </label>
            <label>
                <span>Адрес</span>
                <input type="text" name="address" required value="<?= e($restaurantInfo['address']) ?>">
            </label>
            <label>
                <span>Телефон</span>
                <input type="text" name="phone" required value="<?= e($restaurantInfo['phone']) ?>">
            </label>
            <label>
                <span>E-mail</span>
                <input type="text" name="email" required value="<?= e($restaurantInfo['email']) ?>">
            </label>
            <label>
                <span>Часы работы</span>
                <input type="text" name="hours" required value="<?= e($restaurantInfo['hours']) ?>">
            </label>
        </div>

        <button class="button" type="submit">Сохранить информацию</button>
    </form>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

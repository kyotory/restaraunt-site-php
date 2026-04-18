<?php
require_once __DIR__ . '/includes/app.php';

if (!isset($_SESSION['register_captcha']) || isset($_GET['refresh'])) {
    refresh_registration_captcha();
}

$pageTitle = 'Регистрация';
$old = consume_old_input('register');
$captcha = $_SESSION['register_captcha'];

require __DIR__ . '/includes/header.php';
?>

<section class="panel narrow">
    <div class="section-title">
        <h1>Регистрация пользователя</h1>
        <p>Для регистрации используйте e-mail, пароль и обязательную капчу с числом, записанным словами.</p>
    </div>

    <form action="/auth/register_handler.php" method="post" class="stack-form">
        <div class="form-grid two">
            <label>
                <span>E-mail</span>
                <input type="email" name="email" required value="<?= e(old_value($old, 'email', '')) ?>">
            </label>
            <label>
                <span>Пароль</span>
                <input type="password" name="password" required minlength="6">
            </label>
            <label>
                <span>Имя</span>
                <input type="text" name="name" value="<?= e(old_value($old, 'name', '')) ?>">
            </label>
            <label>
                <span>Фамилия</span>
                <input type="text" name="surname" value="<?= e(old_value($old, 'surname', '')) ?>">
            </label>
            <label>
                <span>Отчество</span>
                <input type="text" name="patronymic" value="<?= e(old_value($old, 'patronymic', '')) ?>">
            </label>
            <label>
                <span>Телефон</span>
                <input type="text" name="phone" value="<?= e(old_value($old, 'phone', '')) ?>">
            </label>
            <label>
                <span>Дата рождения</span>
                <input type="date" name="birthdate" value="<?= e(old_value($old, 'birthdate', '')) ?>">
            </label>
            <label>
                <span>Капча: число словами</span>
                <div class="captcha-box">
                    <strong><?= e($captcha['words']) ?></strong>
                    <a class="text-link" href="/register.php?refresh=1">Обновить число</a>
                </div>
            </label>
            <label>
                <span>Введите число цифрами</span>
                <input type="text" name="captcha_answer" inputmode="numeric" required>
            </label>
        </div>

        <button class="button" type="submit">Зарегистрироваться</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

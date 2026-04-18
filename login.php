<?php
require_once __DIR__ . '/includes/app.php';

if (is_admin_logged_in()) {
    redirect('/');
}

if (!isset($_SESSION['login_captcha']) || isset($_GET['refresh'])) {
    refresh_login_captcha();
}

$pageTitle = 'Авторизация';
$old = consume_old_input('login');
$captcha = $_SESSION['login_captcha'];

require __DIR__ . '/includes/header.php';
?>

<section class="panel narrow">
    <div class="section-title">
        <h1>Авторизация</h1>
        <p>Для входа используйте e-mail и пароль.</p>
    </div>

    <form action="/auth/login_handler.php" method="post" class="stack-form">
        <div class="form-grid two">
            <label>
                <span>E-mail</span>
                <input type="email" name="email" required value="<?= e(old_value($old, 'email', '')) ?>">
            </label>
            <label>
                <span>Пароль</span>
                <input type="password" name="password" required>
            </label>
            <label>
                <span>Капча: число словами</span>
                <div class="captcha-box">
                    <strong><?= e($captcha['words']) ?></strong>
                    <a class="text-link" href="/login.php?refresh=1">Обновить число</a>
                </div>
            </label>
            <label>
                <span>Введите число цифрами</span>
                <input type="text" name="captcha_answer" inputmode="numeric" required>
            </label>
        </div>
        <button class="button" type="submit">Войти</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

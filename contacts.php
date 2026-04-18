<?php
require_once __DIR__ . '/includes/app.php';

$pageTitle = 'Контакты';
$old = consume_old_input('contact');
$prefillName = '';
$prefillEmail = '';
$restaurantInfo = load_restaurant_info();

if (is_logged_in()) {
    $displayName = trim(auth_user()['surname'] . ' ' . auth_user()['name']);
    $prefillName = $displayName !== '' ? $displayName : auth_user()['email'];
    $prefillEmail = auth_user()['email'];
}

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Контакты</h1>
        <p>Здесь можно отправить пожелания и замечания через почтовую форму.</p>
    </div>

    <div class="split-layout">
        <div class="contact-card">
            <h2><?= e($restaurantInfo['name']) ?></h2>
            <p><?= e($restaurantInfo['address']) ?></p>
            <p><?= e($restaurantInfo['phone']) ?></p>
            <p><?= e($restaurantInfo['email']) ?></p>
            <p><?= e($restaurantInfo['hours']) ?></p>
        </div>

        <form action="/actions/contact_handler.php" method="post" class="stack-form">
            <div class="form-grid two">
                <label>
                    <span>Ваше имя</span>
                    <input type="text" name="client_name" required value="<?= e(old_value($old, 'client_name', $prefillName)) ?>">
                </label>
                <label>
                    <span>E-mail</span>
                    <input type="email" name="client_email" required value="<?= e(old_value($old, 'client_email', $prefillEmail)) ?>">
                </label>
                <label class="full-width">
                    <span>Тема</span>
                    <input type="text" name="message_subject" required value="<?= e(old_value($old, 'message_subject', '')) ?>">
                </label>
                <label class="full-width">
                    <span>Сообщение</span>
                    <textarea name="message_text" rows="6" required><?= e(old_value($old, 'message_text', '')) ?></textarea>
                </label>
            </div>
            <button class="button" type="submit">Отправить сообщение</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

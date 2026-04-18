<?php
require_once __DIR__ . '/../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/contacts.php');
}

$mailConfig = load_mail_config();

$clientName = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
$clientEmail = isset($_POST['client_email']) ? trim($_POST['client_email']) : '';
$messageSubject = isset($_POST['message_subject']) ? trim($_POST['message_subject']) : '';
$messageText = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

$oldInput = array(
    'client_name' => $clientName,
    'client_email' => $clientEmail,
    'message_subject' => $messageSubject,
    'message_text' => $messageText
);

if ($clientName === '' || $clientEmail === '' || $messageSubject === '' || $messageText === '') {
    set_old_input('contact', $oldInput);
    add_flash('error', 'Заполните все поля контактной формы.');
    redirect('/contacts.php');
}

if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
    set_old_input('contact', $oldInput);
    add_flash('error', 'Укажите корректный e-mail.');
    redirect('/contacts.php');
}

try {
    smtp_send_mail(
        $mailConfig,
        array(
            'subject' => $messageSubject,
            'reply_to' => $clientEmail,
            'body' => "Имя: {$clientName}\nE-mail: {$clientEmail}\nТема: {$messageSubject}\n\n{$messageText}"
        )
    );

    add_flash('success', 'Сообщение отправлено на почту ресторана.');
} catch (Throwable $exception) {
    add_flash('error', 'Почтовая отправка не настроена или не выполнена: ' . $exception->getMessage());
}

redirect('/contacts.php');

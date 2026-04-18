<?php
require_once __DIR__ . '/../includes/app.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cabinet.php');
}

$mysqli = db();
$action = isset($_POST['action']) ? $_POST['action'] : '';
$userId = auth_user()['id_user'];
$startDishCategoryId = system_start_dish_category_id($mysqli);

if ($action === 'update_profile') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $patronymic = isset($_POST['patronymic']) ? trim($_POST['patronymic']) : '';
    $birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';

    $oldInput = array(
        'email' => $email,
        'phone' => $phone,
        'name' => $name,
        'surname' => $surname,
        'patronymic' => $patronymic,
        'birthdate' => $birthdate
    );

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_old_input('profile', $oldInput);
        add_flash('error', 'Укажите корректный e-mail.');
        redirect('/cabinet.php');
    }

    $birthdateValue = $birthdate !== '' ? $birthdate : null;

    $stmt = $mysqli->prepare(
        "UPDATE `user`
         SET `email` = ?, `phone` = ?, `name` = ?, `surname` = ?, `patronymic` = ?, `birthdate` = ?
         WHERE `id_user` = ?"
    );
    $stmt->bind_param('ssssssi', $email, $phone, $name, $surname, $patronymic, $birthdateValue, $userId);
    $stmt->execute();
    $stmt->close();

    refresh_auth_user($mysqli, $userId);
    add_flash('success', 'Личные данные обновлены.');
    redirect('/cabinet.php');
}

if ($action === 'cancel_reservation') {
    $reservationId = isset($_POST['id_reservation']) ? (int) $_POST['id_reservation'] : 0;

    $reservation = db_fetch_one(
        $mysqli,
        "SELECT `id_reservation`
         FROM `reservation`
         WHERE `id_reservation` = ? AND `id_user` = ?
         LIMIT 1",
        'ii',
        array($reservationId, $userId)
    );

    if (!$reservation) {
        add_flash('error', 'Бронь не найдена или недоступна для отмены.');
        redirect('/cabinet.php');
    }

    try {
        $mysqli->begin_transaction();

        $deleteStartDishes = $mysqli->prepare(
            "DELETE sk
             FROM `super_key` sk
             INNER JOIN `table_schedule` ts ON ts.id_table_schedule = sk.id_table_schedule
             WHERE ts.id_reservation = ? AND sk.id_category = ?"
        );
        $deleteStartDishes->bind_param('ii', $reservationId, $startDishCategoryId);
        $deleteStartDishes->execute();
        $deleteStartDishes->close();

        $deleteSchedule = $mysqli->prepare("DELETE FROM `table_schedule` WHERE `id_reservation` = ?");
        $deleteSchedule->bind_param('i', $reservationId);
        $deleteSchedule->execute();
        $deleteSchedule->close();

        $deleteReservation = $mysqli->prepare("DELETE FROM `reservation` WHERE `id_reservation` = ?");
        $deleteReservation->bind_param('i', $reservationId);
        $deleteReservation->execute();
        $deleteReservation->close();

        $mysqli->commit();
    } catch (Throwable $exception) {
        $mysqli->rollback();
        add_flash('error', 'Не удалось отменить бронь: ' . $exception->getMessage());
        redirect('/cabinet.php');
    }

    add_flash('success', 'Бронь отменена.');
    redirect('/cabinet.php');
}

redirect('/cabinet.php');

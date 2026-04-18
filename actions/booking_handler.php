<?php
require_once __DIR__ . '/../includes/app.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/booking.php');
}

$mysqli = db();
$cart = cart_items();
$startDishCategoryId = system_start_dish_category_id($mysqli);

$eventDate = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$peopleCount = isset($_POST['people_count']) ? (int) $_POST['people_count'] : 0;
$floorId = isset($_POST['id_floor']) ? (int) $_POST['id_floor'] : 0;
$timeBlockId = isset($_POST['id_time_block']) ? (int) $_POST['id_time_block'] : 0;

$oldInput = array(
    'event_date' => $eventDate,
    'people_count' => $peopleCount,
    'id_floor' => $floorId,
    'id_time_block' => $timeBlockId
);

if ($eventDate === '' || $peopleCount < 1 || $floorId < 1 || $timeBlockId < 1) {
    set_old_input('booking', $oldInput);
    add_flash('error', 'Заполните все поля бронирования.');
    redirect('/booking.php');
}

if (strtotime($eventDate) < strtotime(date('Y-m-d'))) {
    set_old_input('booking', $oldInput);
    add_flash('error', 'Нельзя забронировать дату в прошлом.');
    redirect('/booking.php');
}

$floorRow = db_fetch_one(
    $mysqli,
    "SELECT `id_floor`, `name_floor` FROM `floor` WHERE `id_floor` = ? LIMIT 1",
    'i',
    array($floorId)
);

$timeBlock = db_fetch_one(
    $mysqli,
    "SELECT `id_time_block`, `start_time`, `end_time`
     FROM `time_block`
     WHERE `id_time_block` = ?
     LIMIT 1",
    'i',
    array($timeBlockId)
);

$availableTable = db_fetch_one(
    $mysqli,
    "SELECT `id_table`
     FROM `table`
     WHERE `id_floor` = ? AND `place_count` >= ?
     LIMIT 1",
    'ii',
    array($floorId, $peopleCount)
);

if (!$floorRow || !$timeBlock) {
    set_old_input('booking', $oldInput);
    add_flash('error', 'Выбранный зал или временной блок не найден.');
    redirect('/booking.php');
}

if ($eventDate === date('Y-m-d')) {
    $selectedStartTimestamp = strtotime($eventDate . ' ' . $timeBlock['start_time']);

    if ($selectedStartTimestamp <= time()) {
        set_old_input('booking', $oldInput);
        add_flash('error', 'На текущую дату можно выбрать только ещё не начавшийся временной блок.');
        redirect('/booking.php');
    }
}

if (!$availableTable) {
    set_old_input('booking', $oldInput);
    add_flash('error', 'В выбранном зале нет столов, подходящих по количеству гостей.');
    redirect('/booking.php');
}

$statusRow = db_fetch_one(
    $mysqli,
    "SELECT `id_reservation_status`
     FROM `reservation_status`
     WHERE `name_reservation_status` = 'Новое'
     LIMIT 1",
    '',
    array()
);

$statusId = $statusRow ? (int) $statusRow['id_reservation_status'] : null;

try {
    $mysqli->begin_transaction();

    $reservationStmt = $mysqli->prepare(
        "INSERT INTO `reservation`
        (`date_reservation`, `time_reservation`, `people_count`, `event_date`, `event_time`, `id_user`, `id_reservation_status`, `id_floor`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    $eventTime = $timeBlock['start_time'];
    $userId = auth_user()['id_user'];

    $reservationStmt->bind_param(
        'ssissiii',
        $currentDate,
        $currentTime,
        $peopleCount,
        $eventDate,
        $eventTime,
        $userId,
        $statusId,
        $floorId
    );
    $reservationStmt->execute();
    $reservationId = $reservationStmt->insert_id;
    $reservationStmt->close();

    $scheduleStmt = $mysqli->prepare(
        "INSERT INTO `table_schedule` (`date_table_schedule`, `id_reservation`, `id_table`, `id_time_block`)
         VALUES (?, ?, NULL, ?)"
    );
    $scheduleStmt->bind_param('sii', $eventDate, $reservationId, $timeBlockId);
    $scheduleStmt->execute();
    $tableScheduleId = (int) $scheduleStmt->insert_id;
    $scheduleStmt->close();

    if (!empty($cart)) {
        $dishStmt = $mysqli->prepare(
            "INSERT INTO `super_key` (`id_dish`, `id_category`, `id_table_schedule`)
             VALUES (?, ?, ?)"
        );

        foreach (array_keys($cart) as $dishId) {
            $dishId = (int) $dishId;
            $dishStmt->bind_param('iii', $dishId, $startDishCategoryId, $tableScheduleId);
            $dishStmt->execute();
        }

        $dishStmt->close();
    }

    $mysqli->commit();
} catch (Throwable $exception) {
    $mysqli->rollback();
    set_old_input('booking', $oldInput);
    add_flash('error', 'Не удалось оформить бронь: ' . $exception->getMessage());
    redirect('/booking.php');
}

$_SESSION['cart'] = array();
add_flash('success', 'Заявка на бронь отправлена. После обработки администратора она появится в вашем личном кабинете со столиком и статусом.');
redirect('/cabinet.php');

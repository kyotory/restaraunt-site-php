<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

$mysqli = db();
$startDishCategoryId = system_start_dish_category_id($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = isset($_POST['id_reservation']) ? (int) $_POST['id_reservation'] : 0;
    $statusId = isset($_POST['id_reservation_status']) ? (int) $_POST['id_reservation_status'] : 0;
    $tableId = isset($_POST['id_table']) && $_POST['id_table'] !== '' ? (int) $_POST['id_table'] : null;

    $reservation = db_fetch_one(
        $mysqli,
        "SELECT
            r.id_reservation,
            r.id_floor,
            r.people_count,
            r.event_date,
            ts.id_table_schedule,
            ts.id_time_block
         FROM `reservation` r
         LEFT JOIN `table_schedule` ts ON ts.id_reservation = r.id_reservation
         WHERE r.id_reservation = ?
         LIMIT 1",
        'i',
        array($reservationId)
    );

    if (!$reservation) {
        add_flash('error', 'Бронь не найдена.');
        redirect('/admin/reservations.php');
    }

    if ($statusId < 1) {
        add_flash('error', 'Выберите статус брони.');
        redirect('/admin/reservations.php');
    }

    if ($tableId !== null) {
        $tableRow = db_fetch_one(
            $mysqli,
            "SELECT `id_table`, `id_floor`, `place_count`
             FROM `table`
             WHERE `id_table` = ?
             LIMIT 1",
            'i',
            array($tableId)
        );

        if (!$tableRow || (int) $tableRow['id_floor'] !== (int) $reservation['id_floor']) {
            add_flash('error', 'Выбранный стол не относится к залу, который указал гость.');
            redirect('/admin/reservations.php');
        }

        if ((int) $tableRow['place_count'] < (int) $reservation['people_count']) {
            add_flash('error', 'У выбранного стола недостаточно мест.');
            redirect('/admin/reservations.php');
        }

        $conflict = db_fetch_one(
            $mysqli,
            "SELECT ts.id_table_schedule
             FROM `table_schedule` ts
             INNER JOIN `reservation` r ON r.id_reservation = ts.id_reservation
             INNER JOIN `reservation_status` rs ON rs.id_reservation_status = r.id_reservation_status
             WHERE ts.date_table_schedule = ?
               AND ts.id_time_block = ?
               AND ts.id_table = ?
               AND ts.id_reservation <> ?
               AND rs.name_reservation_status = 'Подтверждено'
             LIMIT 1",
            'siii',
            array($reservation['event_date'], $reservation['id_time_block'], $tableId, $reservationId)
        );

        if ($conflict) {
            add_flash('error', 'Этот стол уже занят подтверждённой бронью на тот же временной блок.');
            redirect('/admin/reservations.php');
        }
    }

    $statusStmt = $mysqli->prepare(
        "UPDATE `reservation`
         SET `id_reservation_status` = ?
         WHERE `id_reservation` = ?"
    );
    $statusStmt->bind_param('ii', $statusId, $reservationId);
    $statusStmt->execute();
    $statusStmt->close();

    if ($reservation['id_table_schedule']) {
        if ($tableId === null) {
            $scheduleStmt = $mysqli->prepare(
                "UPDATE `table_schedule`
                 SET `id_table` = NULL
                 WHERE `id_table_schedule` = ?"
            );
            $scheduleStmt->bind_param('i', $reservation['id_table_schedule']);
        } else {
            $scheduleStmt = $mysqli->prepare(
                "UPDATE `table_schedule`
                 SET `id_table` = ?
                 WHERE `id_table_schedule` = ?"
            );
            $scheduleStmt->bind_param('ii', $tableId, $reservation['id_table_schedule']);
        }
    } else {
        if ($tableId === null) {
            $scheduleStmt = $mysqli->prepare(
                "INSERT INTO `table_schedule` (`date_table_schedule`, `id_reservation`, `id_table`, `id_time_block`)
                 VALUES (?, ?, NULL, ?)"
            );
            $scheduleStmt->bind_param('sii', $reservation['event_date'], $reservationId, $reservation['id_time_block']);
        } else {
            $scheduleStmt = $mysqli->prepare(
                "INSERT INTO `table_schedule` (`date_table_schedule`, `id_reservation`, `id_table`, `id_time_block`)
                 VALUES (?, ?, ?, ?)"
            );
            $scheduleStmt->bind_param('siii', $reservation['event_date'], $reservationId, $tableId, $reservation['id_time_block']);
        }
    }

    $scheduleStmt->execute();
    $scheduleStmt->close();

    add_flash('success', 'Бронь обновлена.');
    redirect('/admin/reservations.php');
}

$statuses = db_fetch_all(
    $mysqli,
    "SELECT `id_reservation_status`, `name_reservation_status`
     FROM `reservation_status`
     ORDER BY `id_reservation_status` ASC",
    '',
    array()
);

$tableRows = db_fetch_all(
    $mysqli,
    "SELECT
        t.id_table,
        t.place_count,
        t.id_floor,
        f.name_floor
     FROM `table` t
     INNER JOIN `floor` f ON f.id_floor = t.id_floor
     ORDER BY f.name_floor ASC, t.place_count ASC, t.id_table ASC",
    '',
    array()
);

$tablesByFloor = array();

foreach ($tableRows as $tableRow) {
    $floorId = (int) $tableRow['id_floor'];

    if (!isset($tablesByFloor[$floorId])) {
        $tablesByFloor[$floorId] = array();
    }

    $tablesByFloor[$floorId][] = $tableRow;
}

$reservations = db_fetch_all(
    $mysqli,
    "SELECT
        r.id_reservation,
        r.date_reservation,
        r.time_reservation,
        r.people_count,
        r.event_date,
        r.event_time,
        r.id_floor,
        u.email,
        u.phone,
        u.name,
        u.surname,
        u.patronymic,
        f.name_floor,
        rs.id_reservation_status,
        rs.name_reservation_status,
        ts.id_table_schedule,
        ts.id_table,
        ts.id_time_block,
        tb.start_time,
        tb.end_time,
        GROUP_CONCAT(DISTINCT d.name_dish ORDER BY d.name_dish SEPARATOR '; ') AS ordered_dishes
     FROM `reservation` r
     LEFT JOIN `user` u ON u.id_user = r.id_user
     LEFT JOIN `floor` f ON f.id_floor = r.id_floor
     LEFT JOIN `reservation_status` rs ON rs.id_reservation_status = r.id_reservation_status
     LEFT JOIN `table_schedule` ts ON ts.id_reservation = r.id_reservation
     LEFT JOIN `time_block` tb ON tb.id_time_block = ts.id_time_block
     LEFT JOIN `super_key` sk ON sk.id_table_schedule = ts.id_table_schedule AND sk.id_category = ?
     LEFT JOIN `dish` d ON d.id_dish = sk.id_dish
     GROUP BY
        r.id_reservation,
        r.date_reservation,
        r.time_reservation,
        r.people_count,
        r.event_date,
        r.event_time,
        r.id_floor,
        u.email,
        u.phone,
        u.name,
        u.surname,
        u.patronymic,
        f.name_floor,
        rs.id_reservation_status,
        rs.name_reservation_status,
        ts.id_table_schedule,
        ts.id_table,
        ts.id_time_block,
        tb.start_time,
        tb.end_time
     ORDER BY r.event_date DESC, tb.start_time ASC, r.id_reservation DESC",
    'i',
    array($startDishCategoryId)
);

$adminPageTitle = 'Брони';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Администрирование броней</h1>
        <p>Администратор меняет статус заявки и назначает столик в выбранном гостем зале. Подтверждённые брони не могут пересекаться по одному столику и временному блоку.</p>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Гость</th>
                <th>Дата визита</th>
                <th>Зал</th>
                <th>Гости</th>
                <th>Интервал</th>
                <th>Начальные блюда</th>
                <th>Управление</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td>
                        <strong><?= e(trim($reservation['surname'] . ' ' . $reservation['name'])) ?></strong><br>
                        <span class="muted"><?= e($reservation['email']) ?></span><br>
                        <span class="muted"><?= e($reservation['phone']) ?></span>
                    </td>
                    <td><?= e(format_date_ru($reservation['event_date'])) ?></td>
                    <td><?= e($reservation['name_floor']) ?></td>
                    <td><?= (int) $reservation['people_count'] ?></td>
                    <td><?= e(format_time_ru($reservation['start_time'])) ?> - <?= e(format_time_ru($reservation['end_time'])) ?></td>
                    <td><?= e($reservation['ordered_dishes'] !== null && $reservation['ordered_dishes'] !== '' ? $reservation['ordered_dishes'] : 'Не выбраны') ?></td>
                    <td>
                        <form method="post" class="stack-form">
                            <input type="hidden" name="id_reservation" value="<?= (int) $reservation['id_reservation'] ?>">
                            <label>
                                <span>Статус</span>
                                <select name="id_reservation_status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= (int) $status['id_reservation_status'] ?>" <?= selected((int) $reservation['id_reservation_status'] === (int) $status['id_reservation_status']) ?>>
                                            <?= e($status['name_reservation_status']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Столик</span>
                                <select name="id_table">
                                    <option value="">Не назначен</option>
                                    <?php foreach (isset($tablesByFloor[(int) $reservation['id_floor']]) ? $tablesByFloor[(int) $reservation['id_floor']] : array() as $table): ?>
                                        <option value="<?= (int) $table['id_table'] ?>" <?= selected((int) $reservation['id_table'] === (int) $table['id_table']) ?>>
                                            №<?= (int) $table['id_table'] ?>, <?= (int) $table['place_count'] ?> мест
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button class="button small" type="submit">Сохранить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservations)): ?>
                <tr>
                    <td colspan="7">Заявок на бронирование пока нет.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

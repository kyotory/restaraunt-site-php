<?php
require_once __DIR__ . '/includes/app.php';

require_login();

$mysqli = db();
$pageTitle = 'Бронирование';
$cartItems = fetch_cart_dishes($mysqli);
$bookingOld = consume_old_input('booking');
$today = date('Y-m-d');

$floors = db_fetch_all(
    $mysqli,
    "SELECT `id_floor`, `name_floor`, `description_floor`
     FROM `floor`
     ORDER BY `name_floor` ASC",
    '',
    array()
);

$timeBlocks = db_fetch_all(
    $mysqli,
    "SELECT `id_time_block`, `start_time`, `end_time`
     FROM `time_block`
     ORDER BY `start_time` ASC",
    '',
    array()
);

$scheduleDate = isset($_GET['schedule_date']) ? $_GET['schedule_date'] : old_value($bookingOld, 'event_date', date('Y-m-d', strtotime('+1 day')));
$scheduleFloorId = isset($_GET['schedule_floor']) ? (int) $_GET['schedule_floor'] : (int) old_value($bookingOld, 'id_floor', 0);

$scheduleRows = db_fetch_all(
    $mysqli,
    "SELECT
        ts.date_table_schedule,
        tb.start_time,
        tb.end_time,
        t.id_table,
        t.place_count,
        f.name_floor,
        r.people_count
     FROM `table_schedule` ts
     INNER JOIN `reservation` r ON r.id_reservation = ts.id_reservation
     INNER JOIN `reservation_status` rs ON rs.id_reservation_status = r.id_reservation_status
     INNER JOIN `time_block` tb ON tb.id_time_block = ts.id_time_block
     INNER JOIN `table` t ON t.id_table = ts.id_table
     INNER JOIN `floor` f ON f.id_floor = t.id_floor
     WHERE ts.date_table_schedule = ?
       AND (? = 0 OR f.id_floor = ?)
       AND rs.name_reservation_status = 'Подтверждено'
     ORDER BY tb.start_time ASC, f.name_floor ASC, t.id_table ASC",
    'sii',
    array($scheduleDate, $scheduleFloorId, $scheduleFloorId)
);

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Оформление бронирования</h1>
        <p>Выберите зал, количество гостей, дату визита и временной блок. После отправки заявки администратор назначит стол и обновит статус брони.</p>
    </div>

    <div class="split-layout">
        <form action="/actions/booking_handler.php" method="post" class="stack-form">
            <div class="form-grid two">
                <label>
                    <span>Дата визита</span>
                    <input type="date" name="event_date" min="<?= e($today) ?>" required value="<?= e(old_value($bookingOld, 'event_date', date('Y-m-d', strtotime('+1 day')))) ?>">
                </label>
                <label>
                    <span>Количество гостей</span>
                    <input type="number" min="1" max="20" name="people_count" required value="<?= e(old_value($bookingOld, 'people_count', '4')) ?>">
                </label>
                <label>
                    <span>Зал</span>
                    <select name="id_floor" required>
                        <option value="">Выберите зал</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?= (int) $floor['id_floor'] ?>" <?= selected((int) old_value($bookingOld, 'id_floor', 0) === (int) $floor['id_floor']) ?>>
                                <?= e($floor['name_floor']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Временной блок</span>
                    <select name="id_time_block" required>
                        <option value="">Выберите временной блок</option>
                        <?php foreach ($timeBlocks as $timeBlock): ?>
                            <option value="<?= (int) $timeBlock['id_time_block'] ?>" <?= selected((int) old_value($bookingOld, 'id_time_block', 0) === (int) $timeBlock['id_time_block']) ?>>
                                <?= e(format_time_ru($timeBlock['start_time'])) ?> - <?= e(format_time_ru($timeBlock['end_time'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <button class="button" type="submit">Отправить заявку на бронь</button>
        </form>

        <aside class="summary-card">
            <h2>Начальные блюда подачи</h2>
            <?php if (!empty($cartItems)): ?>
                <ul class="plain-list">
                    <?php foreach ($cartItems as $item): ?>
                        <li><?= e($item['name_dish']) ?> — <?= e(format_money($item['line_total'])) ?></li>
                    <?php endforeach; ?>
                </ul>
                <strong>Итого: <?= e(format_money(cart_total_amount($mysqli))) ?></strong>
            <?php else: ?>
                <p>Начальные блюда не выбраны. Вы можете оформить бронь и без них.</p>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="panel">
    <div class="section-title">
        <h2>Подтверждённые брони на дату <?= e(format_date_ru($scheduleDate)) ?></h2>
        <p>Для удобства можно посмотреть уже занятые столики в выбранном зале.</p>
    </div>

    <form class="inline-form" method="get">
        <label>
            <span>Дата</span>
            <input type="date" name="schedule_date" min="<?= e($today) ?>" value="<?= e($scheduleDate) ?>">
        </label>
        <label>
            <span>Зал</span>
            <select name="schedule_floor">
                <option value="0">Все залы</option>
                <?php foreach ($floors as $floor): ?>
                    <option value="<?= (int) $floor['id_floor'] ?>" <?= selected($scheduleFloorId === (int) $floor['id_floor']) ?>>
                        <?= e($floor['name_floor']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button secondary small" type="submit">Показать</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Зал</th>
                <th>Стол</th>
                <th>Мест</th>
                <th>Интервал</th>
                <th>Гости</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($scheduleRows as $row): ?>
                <tr>
                    <td><?= e($row['name_floor']) ?></td>
                    <td>№<?= (int) $row['id_table'] ?></td>
                    <td><?= (int) $row['place_count'] ?></td>
                    <td><?= e(format_time_ru($row['start_time'])) ?> - <?= e(format_time_ru($row['end_time'])) ?></td>
                    <td><?= (int) $row['people_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($scheduleRows)): ?>
                <tr>
                    <td colspan="5">На выбранную дату подтверждённых броней пока нет.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

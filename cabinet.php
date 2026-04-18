<?php
require_once __DIR__ . '/includes/app.php';

require_login();

$mysqli = db();
$pageTitle = 'Личный кабинет';
$user = auth_user();
$fullName = trim($user['surname'] . ' ' . $user['name'] . ' ' . $user['patronymic']);
$profileOld = consume_old_input('profile');
$startDishCategoryId = system_start_dish_category_id($mysqli);

$reservations = db_fetch_all(
    $mysqli,
    "SELECT
        r.id_reservation,
        r.date_reservation,
        r.time_reservation,
        r.people_count,
        r.event_date,
        f.name_floor,
        rs.name_reservation_status,
        ts.id_table,
        tb.start_time,
        tb.end_time,
        GROUP_CONCAT(DISTINCT d.name_dish ORDER BY d.name_dish SEPARATOR '; ') AS ordered_dishes
     FROM `reservation` r
     LEFT JOIN `floor` f ON f.id_floor = r.id_floor
     LEFT JOIN `reservation_status` rs ON rs.id_reservation_status = r.id_reservation_status
     LEFT JOIN `table_schedule` ts ON ts.id_reservation = r.id_reservation
     LEFT JOIN `time_block` tb ON tb.id_time_block = ts.id_time_block
     LEFT JOIN `super_key` sk ON sk.id_table_schedule = ts.id_table_schedule AND sk.id_category = ?
     LEFT JOIN `dish` d ON d.id_dish = sk.id_dish
     WHERE r.id_user = ?
     GROUP BY
        r.id_reservation,
        r.date_reservation,
        r.time_reservation,
        r.people_count,
        r.event_date,
        f.name_floor,
        rs.name_reservation_status,
        ts.id_table,
        tb.start_time,
        tb.end_time
     ORDER BY r.event_date DESC, tb.start_time ASC, r.id_reservation DESC",
    'ii',
    array($startDishCategoryId, $user['id_user'])
);

require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Личный кабинет</h1>
        <p>Здесь собраны ваши личные данные и все заявки на бронирование.</p>
    </div>

    <div class="split-layout">
        <div class="summary-card">
            <h2>Данные пользователя</h2>
            <ul class="plain-list">
                <li><strong>ФИО:</strong> <?= e($fullName !== '' ? $fullName : 'Не указано') ?></li>
                <li><strong>E-mail:</strong> <?= e($user['email']) ?></li>
                <li><strong>Телефон:</strong> <?= e($user['phone'] !== '' ? $user['phone'] : 'Не указан') ?></li>
                <li><strong>Дата рождения:</strong> <?= e($user['birthdate'] ? format_date_ru($user['birthdate']) : 'Не указана') ?></li>
            </ul>
        </div>

        <div class="summary-card">
            <h2>Изменить личные данные</h2>
            <form action="/actions/cabinet_handler.php" method="post" class="stack-form">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-grid two">
                    <label>
                        <span>E-mail</span>
                        <input type="email" name="email" required value="<?= e(old_value($profileOld, 'email', $user['email'])) ?>">
                    </label>
                    <label>
                        <span>Телефон</span>
                        <input type="text" name="phone" value="<?= e(old_value($profileOld, 'phone', $user['phone'])) ?>">
                    </label>
                    <label>
                        <span>Имя</span>
                        <input type="text" name="name" value="<?= e(old_value($profileOld, 'name', $user['name'])) ?>">
                    </label>
                    <label>
                        <span>Фамилия</span>
                        <input type="text" name="surname" value="<?= e(old_value($profileOld, 'surname', $user['surname'])) ?>">
                    </label>
                    <label>
                        <span>Отчество</span>
                        <input type="text" name="patronymic" value="<?= e(old_value($profileOld, 'patronymic', $user['patronymic'])) ?>">
                    </label>
                    <label>
                        <span>Дата рождения</span>
                        <input type="date" name="birthdate" value="<?= e(old_value($profileOld, 'birthdate', $user['birthdate'])) ?>">
                    </label>
                </div>
                <div class="hero-actions">
                    <button class="button" type="submit">Сохранить данные</button>
                    <a class="button secondary" href="/booking.php">Оформить бронь</a>
                    <a class="button secondary" href="/cart.php">Начальные блюда подачи</a>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-title">
        <h2>Мои брони</h2>
        <p>После обработки администратором здесь появляется назначенный столик и актуальный статус.</p>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Заявка</th>
                <th>Дата визита</th>
                <th>Зал</th>
                <th>Гости</th>
                <th>Временной блок</th>
                <th>Столик</th>
                <th>Статус</th>
                <th>Начальные блюда</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td>
                        <?= e(format_date_ru($reservation['date_reservation'])) ?><br>
                        <span class="muted"><?= e(format_time_ru($reservation['time_reservation'])) ?></span>
                    </td>
                    <td><?= e(format_date_ru($reservation['event_date'])) ?></td>
                    <td><?= e($reservation['name_floor'] !== null && $reservation['name_floor'] !== '' ? $reservation['name_floor'] : 'Не указан') ?></td>
                    <td><?= (int) $reservation['people_count'] ?></td>
                    <td>
                        <?php if ($reservation['start_time'] && $reservation['end_time']): ?>
                            <?= e(format_time_ru($reservation['start_time'])) ?> - <?= e(format_time_ru($reservation['end_time'])) ?>
                        <?php else: ?>
                            Не назначен
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($reservation['id_table'] !== null): ?>
                            №<?= (int) $reservation['id_table'] ?>
                        <?php else: ?>
                            Столик назначит администратор
                        <?php endif; ?>
                    </td>
                    <td><?= e($reservation['name_reservation_status'] !== null && $reservation['name_reservation_status'] !== '' ? $reservation['name_reservation_status'] : 'Новая заявка') ?></td>
                    <td><?= e($reservation['ordered_dishes'] !== null && $reservation['ordered_dishes'] !== '' ? $reservation['ordered_dishes'] : 'Не выбраны') ?></td>
                    <td>
                        <form action="/actions/cabinet_handler.php" method="post" class="inline-form">
                            <input type="hidden" name="action" value="cancel_reservation">
                            <input type="hidden" name="id_reservation" value="<?= (int) $reservation['id_reservation'] ?>">
                            <button class="button danger small" type="submit">Отменить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservations)): ?>
                <tr>
                    <td colspan="9">У вас пока нет оформленных броней.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

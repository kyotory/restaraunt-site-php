<?php
require_once __DIR__ . '/../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cart.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$redirectTo = safe_redirect_target(isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '/cart.php', '/cart.php');

if (!is_logged_in()) {
    add_flash('warning', 'Чтобы выбрать начальные блюда подачи, сначала войдите в личный кабинет.');
    redirect('/login.php');
}

$mysqli = db();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

if ($action === 'add') {
    $dishId = isset($_POST['dish_id']) ? (int) $_POST['dish_id'] : 0;
    $dish = db_fetch_one($mysqli, "SELECT `id_dish`, `name_dish` FROM `dish` WHERE `id_dish` = ? LIMIT 1", 'i', array($dishId));

    if ($dish) {
        $_SESSION['cart'][$dishId] = 1;
        add_flash('success', 'Блюдо "' . $dish['name_dish'] . '" добавлено в начальные блюда подачи.');
    }

    redirect($redirectTo);
}

if ($action === 'update') {
    $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? $_POST['quantity'] : array();

    foreach ($quantities as $dishId => $quantity) {
        $dishId = (int) $dishId;
        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$dishId]);
        } else {
            $_SESSION['cart'][$dishId] = 1;
        }
    }

    add_flash('success', 'Список начальных блюд обновлён.');
    redirect($redirectTo);
}

if ($action === 'clear') {
    $_SESSION['cart'] = array();
    add_flash('success', 'Список начальных блюд очищен.');
    redirect($redirectTo);
}

if (strpos($action, 'remove_') === 0) {
    $dishId = (int) substr($action, 7);
    unset($_SESSION['cart'][$dishId]);
    add_flash('success', 'Блюдо удалено из начальных блюд подачи.');
}

redirect($redirectTo);

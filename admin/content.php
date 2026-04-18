<?php
require_once __DIR__ . '/../includes/app.php';

require_admin();

$mysqli = db();
$newsOld = consume_old_input('admin_news');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add_news' || $action === 'update_news') {
        $newsId = isset($_POST['id_memory_news']) ? (int) $_POST['id_memory_news'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $publishDate = isset($_POST['publish_date']) ? $_POST['publish_date'] : date('Y-m-d');
        $image = isset($_POST['image']) ? trim($_POST['image']) : '/assets/img/logo.svg';

        if ($title === '' || $content === '') {
            set_old_input('admin_news', $_POST);
            add_flash('error', 'Для новости заполните заголовок и текст.');
            redirect($newsId > 0 ? '/admin/content.php?edit=' . $newsId : '/admin/content.php');
        }

        if ($action === 'add_news') {
            $stmt = $mysqli->prepare(
                "INSERT INTO `memory_news`
                (`title_news`, `excerpt_news`, `content_news`, `publish_date`, `image_path`)
                VALUES (?, ?, ?, ?, ?)"
            );
            $imagePath = $image !== '' ? $image : '/assets/img/logo.svg';
            $stmt->bind_param('sssss', $title, $excerpt, $content, $publishDate, $imagePath);
            $stmt->execute();
            $stmt->close();

            add_flash('success', 'Новость добавлена.');
            redirect('/admin/content.php');
        }

        $stmt = $mysqli->prepare(
            "UPDATE `memory_news`
             SET `title_news` = ?, `excerpt_news` = ?, `content_news` = ?, `publish_date` = ?, `image_path` = ?
             WHERE `id_memory_news` = ?"
        );
        $imagePath = $image !== '' ? $image : '/assets/img/logo.svg';
        $stmt->bind_param('sssssi', $title, $excerpt, $content, $publishDate, $imagePath, $newsId);
        $stmt->execute();
        $stmt->close();

        add_flash('success', 'Новость обновлена.');
        redirect('/admin/content.php');
    }

    if ($action === 'delete_news') {
        $newsId = isset($_POST['id_memory_news']) ? (int) $_POST['id_memory_news'] : 0;
        $stmt = $mysqli->prepare("DELETE FROM `memory_news` WHERE `id_memory_news` = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $stmt->close();

        add_flash('success', 'Новость удалена.');
        redirect('/admin/content.php');
    }
}

$editNewsId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editNews = $editNewsId > 0 ? db_fetch_one(
    $mysqli,
    "SELECT *
     FROM `memory_news`
     WHERE `id_memory_news` = ?
     LIMIT 1",
    'i',
    array($editNewsId)
) : null;

$newsItems = db_fetch_all(
    $mysqli,
    "SELECT *
     FROM `memory_news`
     ORDER BY `publish_date` DESC, `id_memory_news` DESC",
    '',
    array()
);

$adminPageTitle = 'Новости';
require __DIR__ . '/_header.php';
?>

<section class="panel">
    <div class="section-title">
        <h1>Новости</h1>
    </div>

    <form method="post" class="stack-form">
        <input type="hidden" name="action" value="<?= $editNews ? 'update_news' : 'add_news' ?>">
        <?php if ($editNews): ?>
            <input type="hidden" name="id_memory_news" value="<?= (int) $editNews['id_memory_news'] ?>">
        <?php endif; ?>
        <div class="form-grid two">
            <label>
                <span>Заголовок</span>
                <input type="text" name="title" required value="<?= e(old_value($newsOld, 'title', $editNews ? $editNews['title_news'] : '')) ?>">
            </label>
            <label>
                <span>Дата публикации</span>
                <input type="date" name="publish_date" value="<?= e(old_value($newsOld, 'publish_date', $editNews ? $editNews['publish_date'] : date('Y-m-d'))) ?>">
            </label>
            <label class="full-width">
                <span>Короткий анонс</span>
                <input type="text" name="excerpt" value="<?= e(old_value($newsOld, 'excerpt', $editNews ? $editNews['excerpt_news'] : '')) ?>">
            </label>
            <label class="full-width">
                <span>Полный текст</span>
                <textarea name="content" rows="5" required><?= e(old_value($newsOld, 'content', $editNews ? $editNews['content_news'] : '')) ?></textarea>
            </label>
            <label class="full-width">
                <span>Изображение</span>
                <input type="text" name="image" value="<?= e(old_value($newsOld, 'image', $editNews ? $editNews['image_path'] : '/assets/img/logo.svg')) ?>">
            </label>
        </div>
        <div class="hero-actions">
            <button class="button" type="submit"><?= $editNews ? 'Сохранить изменения' : 'Добавить новость' ?></button>
            <?php if ($editNews): ?>
                <a class="button secondary" href="/admin/content.php">Отменить редактирование</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Анонс</th>
                <th>Текст</th>
                <th>Дата</th>
                <th>Изображение</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($newsItems as $item): ?>
                <tr>
                    <td><?= (int) $item['id_memory_news'] ?></td>
                    <td><?= e($item['title_news']) ?></td>
                    <td><?= e($item['excerpt_news']) ?></td>
                    <td><?= e($item['content_news']) ?></td>
                    <td><?= e(format_date_ru($item['publish_date'])) ?></td>
                    <td><?= e($item['image_path']) ?></td>
                    <td>
                        <a class="button secondary small" href="/admin/content.php?edit=<?= (int) $item['id_memory_news'] ?>">Изменить</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Удалить новость?');">
                            <input type="hidden" name="action" value="delete_news">
                            <input type="hidden" name="id_memory_news" value="<?= (int) $item['id_memory_news'] ?>">
                            <button class="button danger small" type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($newsItems)): ?>
                <tr>
                    <td colspan="7">Новости пока не добавлены.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>

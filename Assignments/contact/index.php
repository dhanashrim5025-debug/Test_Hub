<?php

require_once __DIR__ . '/db.php';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$database = getDatabaseConnection();
$errors = [];
$formData = [
    'id' => '',
    'name' => '',
    'email' => '',
    'message' => '',
];

$action = $_POST['action'] ?? ($_GET['action'] ?? 'create');

if ($action === 'delete' && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id !== false) {
        $database->prepare('DELETE FROM enquiries WHERE id = :id')->execute(['id' => $id]);
    }
    header('Location: index.php?deleted=1');
    exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id !== false) {
        $entry = $database->prepare('SELECT id, name, email, message FROM enquiries WHERE id = :id');
        $entry->execute(['id' => $id]);
        $row = $entry->fetch();

        if ($row) {
            $formData = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['id'] = trim($_POST['id'] ?? '');
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['message'] = trim($_POST['message'] ?? '');

    if ($formData['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email.';
    }

    if ($formData['message'] === '') {
        $errors['message'] = 'Please write a message.';
    }

    if (empty($errors)) {
        if (($action === 'update' || $action === 'edit') && $formData['id'] !== '') {
            $statement = $database->prepare('UPDATE enquiries SET name = :name, email = :email, message = :message WHERE id = :id');
            $statement->execute([
                'id' => $formData['id'],
                'name' => $formData['name'],
                'email' => $formData['email'],
                'message' => $formData['message'],
            ]);
            header('Location: index.php?updated=1');
            exit;
        }

        $statement = $database->prepare('INSERT INTO enquiries (name, email, message) VALUES (:name, :email, :message)');
        $statement->execute([
            'name' => $formData['name'],
            'email' => $formData['email'],
            'message' => $formData['message'],
        ]);

        header('Location: index.php?created=1');
        exit;
    }
}

$entries = $database->query('SELECT id, name, email, message, created_at FROM enquiries ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dhanashri Contact App</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Dhanashri Contact App</h2>

        <?php if (isset($_GET['created'])): ?>
            <div class="success">Message created successfully.</div>
        <?php elseif (isset($_GET['updated'])): ?>
            <div class="success">Message updated successfully.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="success">Message deleted successfully.</div>
        <?php endif; ?>

        <div class="card">
            <h2><?= ($action === 'edit' && $formData['id'] !== '') ? 'Update message' : 'Create message' ?></h2>

            <form method="post">
                <?php if ($formData['id'] !== ''): ?>
                    <input type="hidden" name="id" value="<?= escape((string) $formData['id']) ?>">
                    <input type="hidden" name="action" value="update">
                <?php else: ?>
                    <input type="hidden" name="action" value="create">
                <?php endif; ?>

                <label>
                    Name
                    <input type="text" name="name" value="<?= escape($formData['name']) ?>" placeholder="Enter your name">
                    <?php if (isset($errors['name'])): ?><div class="error"><?= escape($errors['name']) ?></div><?php endif; ?>
                </label>

                <label>
                    Email
                    <input type="email" name="email" value="<?= escape($formData['email']) ?>" placeholder="Enter your email">
                    <?php if (isset($errors['email'])): ?><div class="error"><?= escape($errors['email']) ?></div><?php endif; ?>
                </label>

                <label>
                    Message
                    <textarea name="message" placeholder="Write your message here"><?= escape($formData['message']) ?></textarea>
                    <?php if (isset($errors['message'])): ?><div class="error"><?= escape($errors['message']) ?></div><?php endif; ?>
                </label>

                <button type="submit">
                    <?= ($formData['id'] !== '') ? 'Update message' : 'Send message' ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h2>Messages</h2>

            <?php if (empty($entries)): ?>
                <div class="empty">No messages yet.</div>
            <?php else: ?>
                <ul class="entries">
                    <?php foreach ($entries as $entry): ?>
                        <li>
                            <div class="entry-header">
                                <div>
                                    <strong><?= escape($entry['name']) ?></strong>
                                    <small><?= escape($entry['email']) ?> • <?= escape(date('d M Y', strtotime($entry['created_at']))) ?></small>
                                </div>
                                <div class="entry-actions">
                                    <a href="index.php?action=edit&id=<?= (int) $entry['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this message?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <p><?= nl2br(escape($entry['message'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

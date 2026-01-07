<?php
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/todo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title'])) {
        addTodo($pdo, trim($_POST['title']));
    }

    if (isset($_POST['done_id'])) {
        markDone($pdo, (int)$_POST['done_id']);
    }

    if (isset($_POST['delete_id'])) {
        deleteTodo($pdo, (int)$_POST['delete_id']);
    }

    header('Location: /');
    exit;
}

$todos = getTodos($pdo);

require __DIR__ . '/../views/list.php';

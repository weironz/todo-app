<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Todo App</title>
</head>
<body>

<h2>待办事项</h2>

<form method="post">
    <input type="text" name="title" required>
    <button type="submit">添加</button>
</form>

<ul>
<?php foreach ($todos as $todo): ?>
    <li>
        <?php if ($todo['is_done']): ?>
            <s><?= htmlspecialchars($todo['title']) ?></s>
        <?php else: ?>
            <?= htmlspecialchars($todo['title']) ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="done_id" value="<?= $todo['id'] ?>">
                <button>完成</button>
            </form>
        <?php endif; ?>

        <form method="post" style="display:inline">
            <input type="hidden" name="delete_id" value="<?= $todo['id'] ?>">
            <button>删除</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

</body>
</html>

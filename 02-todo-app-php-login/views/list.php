<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>Todo App</title>
</head>
<body>

<!-- 当前登录用户信息 + 退出链接 -->
<p>
    当前用户：
    <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
    |
    <a href="/logout.php">退出登录</a>
</p>

<h2>待办事项</h2>

<!-- 添加 Todo -->
<form method="post">
    <input type="text" name="title" required placeholder="新的待办事项">
    <button type="submit">添加</button>
</form>

<hr>

<!-- Todo 列表 -->
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

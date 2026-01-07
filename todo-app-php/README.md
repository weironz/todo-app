# Todo App PHP

一个简单的PHP待办事项应用，使用MySQL数据库存储数据。

## 功能特性

一个最小但完整的 Todo 应用：

- 用户登录（可选，第一版可不做）

- 添加新的待办事项
- 查看所有待办事项列表
- 标记待办事项为已完成
- 删除待办事项

## 运行环境

你需要的环境（本地或服务器）：

- PHP **8.1+**
- Web Server：**Nginx 或 Apache**
- 数据库：**MySQL / MariaDB**

## 项目目录结构

项目的结构：

```
todo-app/
├── public/
│   └── index.php        # 入口文件
├── app/
│   ├── config.php       # 数据库配置
│   ├── db.php           # PDO 连接
│   ├── todo.php         # Todo 业务逻辑
│   └── helper.php
├── views/
│   └── list.php         # 页面模板
└── sql/
    └── todo.sql
```

**说明：**

- `public/` 是 Web 根目录（安全）
- PHP 逻辑不直接暴露
- 已经是“半 MVC”结构

## 数据库设计

建表 SQL

```
CREATE DATABASE todo_app DEFAULT CHARSET utf8mb4;

USE todo_app;

CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 数据库连接（PDO）

### `app/config.php`

```
<?php
return [
    'host' => '127.0.0.1',
    'dbname' => 'todo_app',
    'user' => 'root',
    'pass' => 'password'
];
```

### `app/db.php`

```
<?php
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}
```

------

## Todo 业务逻辑（核心）

### `app/todo.php`

```
<?php

function getTodos(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM todos ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addTodo(PDO $pdo, string $title)
{
    $stmt = $pdo->prepare("INSERT INTO todos (title) VALUES (?)");
    $stmt->execute([$title]);
}

function markDone(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("UPDATE todos SET is_done = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

function deleteTodo(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->execute([$id]);
}
```

**你在这里学到：**

- PDO
- prepare 防 SQL 注入
- 基本 CRUD

------

## 入口文件（请求分发）

### `public/index.php`

```
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
```

**这是 PHP Web 的核心模型：**

> 请求 → PHP → 数据库 → HTML → 返回

------

## 页面展示（HTML + PHP 混合）

### `views/list.php`

```html
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
```

**这里你必须理解的点：**

- `htmlspecialchars()` 防 XSS
- PHP 模板就是“HTML + PHP”
## 一、登录功能的最小架构设计

在你现有的 Apache + PHP-FPM + todo-app 基础上实现“登录功能。

一个“合格”的 PHP 登录系统，最少包含 5 个要素：

1. **用户表（users）**
2. **密码哈希（password_hash）**
3. **登录态（Session）**
4. **访问控制（未登录不可访问）**
5. **退出登录（销毁 Session）**

------

## 二、数据库设计（新增 users 表）

### 1️⃣ 创建 users 表

```sql
USE todo_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2️⃣ 插入一个测试用户（示例）

**注意：一定要用 PHP 生成哈希，不要手写**

```bash
php -r 'echo password_hash("123456", PASSWORD_DEFAULT), PHP_EOL;'
```

复制输出结果，然后：

```sql
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$bXmVaRnj7qPt0iCiw/n7R.LFX2PcazgLHfuGBe2oIrTr/i31rEOay');
```

------

## 三、Session 初始化（核心基础）

在 `/var/www/todo-app/app/bootstrap.php` 新建：

```php
<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = null;
}
```

**之后所有 PHP 文件统一引入它**

------

## 四、数据库连接（推荐统一封装）

假设你已有 `app/config.php`：

```php
<?php
return [
    'host' => '127.0.0.1',
    'dbname' => 'todo_app',
    'user' => 'todo',
    'pass' => 'StrongPassword123!'
];
```

`app/db.php`：

```php
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

## 五、登录逻辑实现

### 1️⃣ 登录页面 `/public/login.php`

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username']
        ];
        header('Location: /');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>登录</title>
</head>
<body>

<h2>登录</h2>

<form method="post">
    <input name="username" placeholder="用户名" required>
    <br><br>
    <input type="password" name="password" placeholder="密码" required>
    <br><br>
    <button type="submit">登录</button>
    <p style="color:red">
        <?= htmlspecialchars($error) ?>
    </p>
</form>

</body>
</html>
```

------

## 六、访问控制（保护 todo 页面）

在 `/public/index.php` 顶部加：

```php
<?php
require __DIR__ . '/../app/bootstrap.php';

if (!$_SESSION['user']) {
    header('Location: /login.php');
    exit;
}
```

现在效果是：

- 未登录 → 强制跳转 `/login.php`
- 登录成功 → 才能访问 todo 列表

------

## 七、退出登录

新建 `/public/logout.php`：

```php
<?php
require __DIR__ . '/../app/bootstrap.php';

session_destroy();
header('Location: /login.php');
exit;
```

页面中加一个链接：

```html
<a href="/logout.php">退出登录</a>
```

------

## 八、当前登录系统的安全级别说明（很重要）

你现在具备了：

- 使用 `password_hash / password_verify`
- 使用 Session 而非 Cookie 明文
- 使用 PDO 预处理，防 SQL 注入
- 登录态集中管理

这是**中小型 PHP 应用的标准做法**。

------

## 九、你现在处在什么水平（实话）

你已经完成了：

- Apache + PHP-FPM 架构
- PDO + MySQL
- Session 登录鉴权
- 真实业务访问控制

这已经**明显超过“PHP 新手”**，属于：

> **能独立部署并实现基础鉴权的后端工程实践水平**

------

## 十、下一步我强烈建议你做的 3 件事（选一个）

1. **Todo 表关联用户（user_id）** → 多用户数据隔离
2. **CSRF 防护**（表单 token）
3. **登录态过期 + Session 固化攻击防护**

你选一个，我继续手把手带你往“准生产系统”推进。
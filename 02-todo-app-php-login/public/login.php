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

## 前言
把当前 PHP 的 **默认文件 Session** 改成 **Redis 存储 Session**，这是生产环境常用做法，解决了多机部署、容器化和高并发的问题。

---

## 一、前置条件

1. **Redis 已安装并可用**
   假设 Redis 在本机，端口 6379，没有密码。如果有密码，需要在配置中加上。

2. PHP 已安装 Redis 扩展（php-redis）

```bash
sudo apt install php-redis
```

检查是否启用：

```bash
php -m | grep redis
```

如果输出 `redis`，说明安装成功。

> 你当前 Apache + PHP-FPM，不需要改 Apache 配置，只改 PHP 即可。

---

## 二、修改 bootstrap.php 初始化 Session

打开 `app/bootstrap.php`，改成使用 Redis 作为 Session 存储：

```php
<?php
// 使用 Redis 存储 PHP Session
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379');  // 如果有密码：tcp://:password@127.0.0.1:6379

// 开启 Session
session_start();
```

✅ 说明：

* `session.save_handler = redis`：告诉 PHP Session 用 Redis 存储
* `session.save_path`：Redis 连接地址
* `session_start()`：初始化 Session

> 这样，所有 `$_SESSION` 的内容都会直接存到 Redis，而不是 `/var/lib/php/sessions` 文件夹。

---

## 三、原理说明

* 浏览器 Cookie **不变**，依然存 `PHPSESSID`
* PHP 通过 `PHPSESSID` 去 Redis 找数据
* 示例数据在 Redis 中可能长这样：

```
key: PHPREDIS_SESSION:9f1c2a8b7e...
value: user|a:2:{s:2:"id";i:1;s:8:"username";s:5:"admin";}
TTL: 1440 秒（默认）
```

> 优势：
>
> 1. 多台服务器共享 Session
> 2. 不依赖磁盘 IO
> 3. 容器重启不丢失 Session（如果 Redis 独立）

---

## 四、可选优化

### 1️⃣ 修改 Session 过期时间

默认 1440 秒（24 分钟），你可以在 bootstrap.php 里加：

```php
ini_set('session.gc_maxlifetime', 3600); // 1小时
```

Redis 会自动设置过期时间（TTL）。

### 2️⃣ Redis Key 前缀

为了防止冲突，可以加前缀：

```php
ini_set('session.save_path', 'tcp://127.0.0.1:6379?prefix=todoapp_sess:');
```

Redis 中的 key 就会变成：

```
todoapp_sess:9f1c2a8b7e...
```

### 3️⃣ 高可用和集群

如果你有 Redis Cluster 或 Sentinel，只需要把 `session.save_path` 指向多个节点即可。

---

## 五、完整 bootstrap.php 示例

```php
<?php
// Redis Session 配置
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379?prefix=todoapp_sess:');
ini_set('session.gc_maxlifetime', 3600);  // 1小时过期

// 启动 Session
session_start();

// 错误显示（开发环境用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

> 你现在的 index.php / login.php / logout.php 不需要改动。
> Session 存 Redis 对现有逻辑完全透明。

---

## 六、验证方法

1. 登录成功后：

```bash
root@ubuntu06:~# redis-cli keys "*todoapp_sess*"
1) "todoapp_sess:8fn2hs0ev0t88nqp1gho3auodl"
```

你应该能看到 Session key。

2. 查看内容：

```bash
root@ubuntu06:~# redis-cli get todoapp_sess:8fn2hs0ev0t88nqp1gho3auodl
"user|a:2:{s:2:\"id\";i:1;s:8:\"username\";s:5:\"admin\";}"
```

应该可以看到序列化的 `$_SESSION['user']` 数据。
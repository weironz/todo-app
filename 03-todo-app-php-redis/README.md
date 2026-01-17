## 前言

架构图

```
                CLB
                 │
        ┌────────┴────────┐
        │                 │
      CVM01             CVM02
        │                 │
        └───────┬─────────┘
                │
      ┌─────────▼─────────┐
      │       Redis        │  ← Session / Cache
      └─────────┬─────────┘
                │
      ┌─────────▼─────────┐
      │       MySQL        │  ← 持久化数据
      └───────────────────┘

```

部署环境：腾讯云

## 资源准备

购买两台CVM

一套MySQL集群

一套Reids集群

一个CLB实例(http负载均衡)

## 安装Apache

```
sudo apt upgrade -y
sudo apt install apache2 -y
systemctl status apache2
systemctl enable apache2
```

## 安装 PHP

Ubuntu 24.04 默认 PHP **8.3**，基于php-fpm模块。

```
sudo apt update
sudo apt install -y \
  php \
  php-fpm \
  php-cli \
  php-common \
  php-mysql \
  php-curl \
  php-gd \
  php-mbstring \
  php-xml \
  php-zip \
  php-opcache \
  php-redis
```

确认已安装`php-mysql`插件及`php-redis`插件。

确认 PHP-FPM 服务状态

```
systemctl status php8.3-fpm
```

开机自启（通常已默认开启）：

```
sudo systemctl enable php8.3-fpm
```

 禁用 mod_php（非常重要）

```
sudo a2dismod php8.3
systemctl restart apache2
```

> 若你之前没装 `libapache2-mod-php`，这一步会提示不存在，**可忽略**

启用 FPM 相关模块

```
sudo a2enmod proxy proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
sudo systemctl reload apache2
```

- `proxy_fcgi`：Apache 通过 FastCGI 代理请求给 PHP-FPM
- `setenvif`：设置环境变量
- `php8.3-fpm.conf`：系统默认配置 FastCGI handler

## MySQL准备

创建库表和用户

```
mysql -u root -p123456 -h 172.16.32.36
```

创建 Todo 应用数据库与账号（关键）

```bash
mysql -u root -p
CREATE DATABASE todo_app DEFAULT CHARSET utf8mb4;

CREATE USER 'todo'@'%' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON todo_app.* TO 'todo'@'%';
FLUSH PRIVILEGES;
```

### 创建todo数据表

```bash
use todo_app;
CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 创建 users 表

```
USE todo_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

插入一个测试用户（示例）

**注意：一定要用 PHP 生成哈希，不要手写**

```
php -r 'echo password_hash("123456", PASSWORD_DEFAULT), PHP_EOL;'
```

复制输出结果，然后：

```
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$6RLXbzFB.UmewnPuZP5UnevbByn3T.pteuLn/4spZKYhtYL5.TRCK');
```

## 部署应用

```
git clone https://github.com/weironz/todo-app.git
cp -r todo-app/03-todo-app-php-redis/ /var/www/todo-app
```

## 修改应用配置文件

修改mysql配置

```
root@php-vm2:/var/www/todo-app/app# cat config.php 
<?php
return [
    'host' => '172.16.32.36',
    'dbname' => 'todo_app',
    'user' => 'todo',
    'pass' => 'StrongPassword123!'
];
```

修改redis配置

```
root@php-vm2:/var/www/todo-app/app# cat bootstrap.php 
<?php
// Redis Session 配置
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://172.16.0.57:6379?auth=123456&prefix=todoapp_sess_');
ini_set('session.gc_maxlifetime', 3600);  // 1小时过期

// 启动 Session
session_start();

// 错误显示（开发环境用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## 修改apache配置

编辑 `/etc/apache2/sites-available/todo.conf`：

```
<VirtualHost *:80>
    ServerName todo.clouddele.com

    DocumentRoot /var/www/todo-app/public

    <Directory /var/www/todo-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    # PHP-FPM 处理 .php 文件
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/todo-error.log
    CustomLog ${APACHE_LOG_DIR}/todo-access.log combined
</VirtualHost>
```

启用站点

```bash
a2ensite todo.conf
a2dissite 000-default.conf
apachectl configtest
systemctl reload apache2
```

## CLB配置

创建七层负载均衡

上传域名证书

配置转发规则

绑定后端两台CVM

## 应用验证

浏览器访问

```
https://todo.clouddele.com
```


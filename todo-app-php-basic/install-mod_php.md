# PHP Todo 应用完整安装与部署文档

> 适用系统：Ubuntu 24.04 LTS
> 架构：Apache + PHP + MySQL（LAMP）
> 目标：将 `todo-app` 项目以**生产级方式**部署并可通过 IP / 域名访问

------

## 一、系统准备

```bash
apt update
apt upgrade -y
```

确认系统版本：

```bash
cat /etc/os-release
```

------

## 二、安装 Apache

### 1. 安装

```bash
apt install -y apache2
```

### 2. 启动并设置开机自启

```bash
systemctl enable apache2
systemctl start apache2
```

### 3. 验证

```bash
curl http://127.0.0.1
```

出现 *Apache2 Ubuntu Default Page* 即成功。

------

## 三、安装 PHP（Apache 模式）

Ubuntu 24.04 默认 PHP 版本为 **8.3**。

```bash
apt install -y \
php \
libapache2-mod-php \
php-mysql \
php-cli \
php-common \
php-mbstring \
php-xml \
php-curl
```

重启 Apache：

```bash
systemctl restart apache2
```

### 验证 PHP

```bash
echo "<?php phpinfo();" > /var/www/html/info.php
curl http://127.0.0.1/info.php
rm /var/www/html/info.php
```

------

## 四、安装 MySQL

### 1. 安装

```bash
apt install -y mysql-server
```

### 2. 启动并设置开机自启

```bash
systemctl enable mysql
systemctl start mysql
```

### 3. 安全初始化

```bash
mysql_secure_installation
```

推荐选项：

- 设置 root 密码：Yes
- 删除匿名用户：Yes
- 禁止 root 远程登录：Yes
- 删除 test 数据库：Yes
- 重新加载权限表：Yes

------

## 五、创建 Todo 应用数据库与账号（关键）

```bash
mysql -u root -p
CREATE DATABASE todo_app DEFAULT CHARSET utf8mb4;

CREATE USER 'todo'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON todo_app.* TO 'todo'@'localhost';
FLUSH PRIVILEGES;
```

> ❗ **Web 应用禁止使用 MySQL root 用户**

------

## 六、创建数据表

```bash
mysql -u todo -p todo_app
CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

验证：

```sql
SHOW TABLES;
```

------

## 七、部署 todo-app 项目

### 1. 项目目录结构（标准）

```
/var/www/todo-app/
├── public/        # Web 根目录
│   └── index.php
├── app/
├── views/
└── sql/
```

### 2. 移动项目

```bash
mv /root/todo-app /var/www/todo-app
```

### 3. 设置权限

```bash
chown -R www-data:www-data /var/www/todo-app
chmod -R 755 /var/www/todo-app
```

------

## 八、配置 Apache 虚拟主机

### 1. 创建虚拟主机配置

```bash
vim /etc/apache2/sites-available/todo.conf
<VirtualHost *:80>
    ServerName todo.local
    ServerAlias www.todo.local
    ServerAlias 127.0.0.1
    ServerAlias localhost
    ServerAlias 192.168.73.6

    DocumentRoot /var/www/todo-app/public

    <Directory /var/www/todo-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/todo-error.log
    CustomLog ${APACHE_LOG_DIR}/todo-access.log combined
</VirtualHost>
```

### 2. 启用站点

```bash
a2ensite todo.conf
a2dissite 000-default.conf
apachectl configtest
systemctl reload apache2
```

------

## 九、PHP 数据库配置

### `app/config.php`

```php
<?php
return [
    'host' => '127.0.0.1',
    'dbname' => 'todo_app',
    'user' => 'todo',
    'pass' => 'StrongPassword123!'
];
```

------

## 十、访问验证

### 1. 通过 IP 访问

```
http://192.168.73.6
```

### 2. 查看日志（排障必备）

```bash
tail -f /var/log/apache2/todo-error.log
tail -f /var/log/apache2/todo-access.log
```


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

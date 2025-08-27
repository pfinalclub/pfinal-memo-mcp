# PHP Memo MCP 客户端

## 概述

这是一个用 PHP 编写的 Memo MCP 客户端，用于连接运行在 WebSocket 模式下的 Memo MCP 服务器。

## 功能特性

- ✅ WebSocket 连接支持
- ✅ JSON-RPC 2.0 协议
- ✅ Promise 异步处理
- ✅ 交互式命令行界面
- ✅ 完整的错误处理
- ✅ 超时机制
- ✅ 颜色化输出

## 安装

### 1. 安装依赖

```bash
cd client/php
composer install
```

### 2. 确保服务器运行

```bash
# 在项目根目录
php server.php
```

## 使用方法

### 1. 交互式控制台

```bash
php console.php
```

支持的命令：
- `list` - 获取所有备忘录
- `search <关键词>` - 搜索备忘录
- `help` - 显示帮助信息
- `exit` - 退出程序

### 2. 编程方式使用

```php
<?php
require_once 'vendor/autoload.php';

use PFinal\Memo\Client\MemoClient;
use React\EventLoop\Loop;

$client = new MemoClient('ws://127.0.0.1:8899');

$client->connect()->then(
    function () use ($client) {
        // 获取所有备忘录
        return $client->listMemos();
    }
)->then(
    function ($result) {
        echo "备忘录总数: " . $result['total'] . "\n";
        foreach ($result['data'] as $memo) {
            echo "ID: {$memo['id']}, 内容: {$memo['content']}\n";
        }
    }
)->otherwise(
    function ($error) {
        echo "错误: " . $error->getMessage() . "\n";
    }
);

Loop::run();
```

## 文件说明

- `MemoClient.php` - 核心客户端类
- `console.php` - 交互式控制台
- `composer.json` - 依赖管理
- `README.md` - 使用说明

## API 参考

### MemoClient 类

#### 构造函数
```php
public function __construct(string $serverUrl = 'ws://127.0.0.1:8899')
```

#### 方法

##### connect()
连接到 WebSocket 服务器
```php
public function connect(): PromiseInterface
```

##### listMemos()
获取所有备忘录
```php
public function listMemos(): PromiseInterface
```

##### searchMemos(string $keyword)
搜索备忘录
```php
public function searchMemos(string $keyword): PromiseInterface
```

##### disconnect()
断开连接
```php
public function disconnect(): void
```

##### isConnected()
检查连接状态
```php
public function isConnected(): bool
```

## 响应格式

### 成功响应
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "content": "备忘录内容",
            "created_at": "2024-01-01 12:00:00"
        }
    ],
    "total": 1
}
```

### 搜索响应
```json
{
    "success": true,
    "data": [...],
    "total": 1,
    "keyword": "搜索关键词"
}
```

## 错误处理

客户端使用 Promise 进行异步处理，所有错误都会通过 `otherwise()` 方法捕获：

```php
$client->listMemos()->then(
    function ($result) {
        // 处理成功结果
    }
)->otherwise(
    function ($error) {
        // 处理错误
        echo "错误: " . $error->getMessage() . "\n";
    }
);
```

## 故障排除

### 1. 连接失败
- 确保 PHP MCP 服务器正在运行
- 检查服务器 URL 是否正确
- 确认端口 8899 未被占用

### 2. 依赖问题
```bash
composer install
composer update
```

### 3. PHP 版本
确保使用 PHP 8.2 或更高版本。

## 开发说明

### 添加新功能
1. 在 `MemoClient` 类中添加新方法
2. 在 `console.php` 中添加对应的命令处理

### 调试
使用以下命令启动调试模式：
```bash
php -d display_errors=1 console.php
```

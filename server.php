<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use PFPMcp\Server;
use PFPMcp\Config\ServerConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 创建日志记录器
$logger = new Logger('memo-mcp-server');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

// 创建服务器配置
$config = new ServerConfig([
    'transport' => 'stdio',
    'host' => '127.0.0.1',    // 添加主机配置
    'port' => 8080,           // 添加端口配置
    'log_level' => 'info',
    'log_file' => 'php://stderr',
    'stdio' => [
        'mode' => 'optimized',
        'buffer_interval' => 10,
        'non_blocking' => true,
    ],
    'session' => [
        'backend' => 'memory',
        'ttl' => 3600,
    ],
    'security' => [
        'rate_limit' => 100,
        'rate_window' => 60,
    ],
    'performance' => [
        'max_connections' => 1000,
        'timeout' => 30,
    ]
]);

// 创建 MCP 服务器
$server = new Server($config, $logger);

// 注册备忘录工具
$server->registerTool(new MemoServer());

// 启动服务器
$logger->info('Starting Memo MCP server...');
$server->start();

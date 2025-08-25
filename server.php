<?php

require_once __DIR__ . '/vendor/autoload.php';

use PFPMcp\Server;
use PFPMcp\Config\ServerConfig;
use PFinal\Memo\MemoServer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

try {
    // 创建日志记录器
    $logger = new Logger('memo-mcp-server');
    $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

    // 创建完整配置
    $config = new ServerConfig([
        'transport' => 'stdio',
        'host' => '127.0.0.1',
        'port' => 8080,
        'log_level' => 'info',
        'session' => [
            'backend' => 'memory',
            'ttl' => 3600
        ],
        'security' => [
            'rate_limit' => 100,
            'rate_window' => 60
        ],
        'performance' => [
            'max_connections' => 1000,
            'timeout' => 30
        ]
    ]);

    // 创建服务器
    $server = new Server($config, $logger);

    // 注册备忘录工具
    $server->registerTool(new MemoServer());

    // 启动服务器
    $logger->info('Starting Memo MCP server...');
    $server->start();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    exit(1);
}

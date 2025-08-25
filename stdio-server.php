<?php

require_once __DIR__ . '/vendor/autoload.php';

use PFPMcp\Server;
use PFPMcp\Config\ServerConfig;
use PFinal\Memo\MemoServer;
use PFinal\Memo\MemoPrompt;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

try {
    // 创建日志记录器
    $logger = new Logger('memo-mcp-server');
    $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

    // 创建 stdio 配置
    $config = new ServerConfig([
        'transport' => 'stdio',
        'log_level' => 'info',
        'host' => '127.0.0.1',
        'port' => 8891,
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
    
    // 注册备忘录提示词
    $server->registerTool(new MemoPrompt());

    // 启动服务器
    $logger->info('Starting Memo MCP server (stdio mode)...');
    $server->start();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    exit(1);
}

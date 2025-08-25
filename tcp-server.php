<?php

/**
 * TCP 模式的 MCP 服务器
 * 使用 Workerman 实现 TCP 协议
 */

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

// 创建 Memo 服务器实例
$memoServer = new MemoServer();

// 创建 TCP Worker
$worker = new Worker('tcp://0.0.0.0:8888');
$worker->count = 4; // 4个进程

// 处理连接
$worker->onConnect = function($connection) {
    echo "新的 TCP 连接: " . $connection->getRemoteAddress() . "\n";
};

// 处理消息
$worker->onMessage = function($connection, $data) use ($memoServer) {
    try {
        // 解析 JSON 数据
        $request = json_decode($data, true);
        
        if (!$request) {
            $response = [
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error: Invalid JSON'
                ]
            ];
            $connection->send(json_encode($response) . "\n");
            return;
        }
        
        // 处理 MCP 请求
        $response = handleMcpRequest($memoServer, $request);
        
        // 发送响应
        $connection->send(json_encode($response) . "\n");
        
    } catch (Exception $e) {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
            'error' => [
                'code' => -32603,
                'message' => 'Internal error: ' . $e->getMessage()
            ]
        ];
        $connection->send(json_encode($response) . "\n");
    }
};

// 处理连接关闭
$worker->onClose = function($connection) {
    echo "TCP 连接关闭: " . $connection->getRemoteAddress() . "\n";
};

// 启动服务器
Worker::runAll();

/**
 * 处理 MCP 协议请求
 */
function handleMcpRequest($memoServer, $data) {
    $method = $data['method'] ?? '';
    $params = $data['params'] ?? [];
    $id = $data['id'] ?? null;
    
    switch ($method) {
        case 'initialize':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => false
                        ]
                    ],
                    'serverInfo' => [
                        'name' => 'Memo MCP Server (TCP)',
                        'version' => '1.0.0'
                    ]
                ]
            ];
            
        case 'tools/list':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'tools' => [
                        [
                            'name' => 'memo.list',
                            'description' => '获取所有备忘录',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => []
                            ]
                        ],
                        [
                            'name' => 'memo.search',
                            'description' => '搜索备忘录',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'keyword' => [
                                        'type' => 'string',
                                        'description' => '搜索关键词'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
        case 'tools/call':
            $toolName = $params['name'] ?? '';
            $arguments = $params['arguments'] ?? [];
            
            switch ($toolName) {
                case 'memo.list':
                    try {
                        $result = $memoServer->listMemos();
                        return [
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => [
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                                    ]
                                ]
                            ]
                        ];
                    } catch (Exception $e) {
                        return [
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32603,
                                'message' => 'Internal error: ' . $e->getMessage()
                            ]
                        ];
                    }
                    
                case 'memo.search':
                    try {
                        $keyword = $arguments['keyword'] ?? '';
                        $result = $memoServer->searchMemos($keyword);
                        return [
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => [
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                                    ]
                                ]
                            ]
                        ];
                    } catch (Exception $e) {
                        return [
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32603,
                                'message' => 'Internal error: ' . $e->getMessage()
                            ]
                        ];
                    }
                    
                default:
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => [
                            'code' => -32601,
                            'message' => 'Method not found: ' . $toolName
                        ]
                    ];
            }
            
        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found: ' . $method
                ]
            ];
    }
}

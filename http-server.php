<?php

/**
 * HTTP 模式的 MCP 服务器
 * 用于线上部署和公开使用
 */

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use Workerman\Worker;
use Workerman\Protocols\Http;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

// 创建 Memo 服务器实例
$memoServer = new MemoServer();

// 创建 HTTP Worker
$worker = new Worker('http://0.0.0.0:8891');
$worker->count = 4; // 4个进程

// 处理请求
$worker->onMessage = function($connection, Request $request) use ($memoServer) {
    // 设置 CORS 头
    $headers = [
        'Content-Type' => 'application/json',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
    ];
    
    // 处理 OPTIONS 请求（CORS 预检）
    if ($request->method() === 'OPTIONS') {
        $connection->send(new Response(200, $headers, ''));
        return;
    }
    
    // 只接受 POST 请求
    if ($request->method() !== 'POST') {
        $connection->send(new Response(405, $headers, json_encode([
            'error' => 'Method not allowed',
            'message' => 'Only POST requests are accepted'
        ])));
        return;
    }
    
    // 获取请求内容
    $content = $request->rawBody();
    $data = json_decode($content, true);
    
    if (!$data) {
        $connection->send(new Response(400, $headers, json_encode([
            'error' => 'Invalid JSON',
            'message' => 'Request body must be valid JSON'
        ])));
        return;
    }
    
    // 处理 MCP 请求
    $response = handleMcpRequest($memoServer, $data);
    
    $connection->send(new Response(200, $headers, json_encode($response)));
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
                        'name' => 'Memo MCP Server',
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
                                'properties' => []
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
                        $result = $memoServer->searchMemos();
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

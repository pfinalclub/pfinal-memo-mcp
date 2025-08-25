<?php

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;

// 创建 MemoServer 实例
$memoServer = new MemoServer();

// 移除调试输出，避免干扰 MCP 协议
// echo "Memo MCP Server 已启动，等待输入...\n";

// 监听标准输入
while (($line = fgets(STDIN)) !== false) {
    try {
        $data = trim($line);
        
        if (empty($data)) {
            continue;
        }
        
        $request = json_decode($data, true);
        
        if (!$request) {
            $response = [
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error'
                ]
            ];
        } else {
            $id = $request['id'] ?? null;
            $method = $request['method'] ?? '';
            $params = $request['params'] ?? [];
            
            switch ($method) {
                case 'tools/call':
                    $toolName = $params['name'] ?? '';
                    $arguments = $params['arguments'] ?? [];
                    
                    switch ($toolName) {
                        case 'memo.list':
                            $result = $memoServer->listMemos();
                            break;
                        case 'memo.search':
                            $keyword = $arguments['keyword'] ?? '';
                            $result = $memoServer->searchMemos($keyword);
                            break;
                        default:
                            $result = ['error' => "Unknown tool: {$toolName}"];
                    }
                    
                    $response = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $result
                    ];
                    break;
                    
                case 'initialize':
                    $response = [
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
                    break;
                    
                case 'tools/list':
                    $response = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'tools' => [
                                [
                                    'name' => 'memo.list',
                                    'description' => '获取所有备忘录'
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
                                        ],
                                        'required' => ['keyword']
                                    ]
                                ]
                            ]
                        ]
                    ];
                    break;
                    
                default:
                    $response = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => [
                            'code' => -32601,
                            'message' => 'Method not found'
                        ]
                    ];
            }
        }
        
        // 发送响应
        fwrite(STDOUT, json_encode($response) . "\n");
        
    } catch (Exception $e) {
        $errorResponse = [
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
            'error' => [
                'code' => -32603,
                'message' => 'Internal error',
                'data' => $e->getMessage()
            ]
        ];
        fwrite(STDOUT, json_encode($errorResponse) . "\n");
    }
}

<?php

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;

// 检查 MemoServer 是否已经启动
$serverStatusFile = sys_get_temp_dir() . '/memo-mcp-server-status.json';

// 检查服务器状态
function checkServerStatus() {
    global $serverStatusFile;
    
    if (file_exists($serverStatusFile)) {
        $status = json_decode(file_get_contents($serverStatusFile), true);
        if ($status && isset($status['pid']) && isset($status['start_time'])) {
            // 检查进程是否还在运行
            if (posix_kill($status['pid'], 0)) {
                // 检查进程启动时间是否匹配
                $currentPid = getmypid();
                if ($status['pid'] === $currentPid) {
                    return true; // 当前进程已经在运行
                }
                // 其他进程在运行，退出当前进程
                fwrite(STDERR, "Memo MCP Server 已经在运行 (PID: {$status['pid']})\n");
                exit(0);
            } else {
                // 进程不存在，清理状态文件
                unlink($serverStatusFile);
            }
        }
    }
    return false;
}

// 记录服务器启动状态
function recordServerStatus() {
    global $serverStatusFile;
    
    $status = [
        'pid' => getmypid(),
        'start_time' => time(),
        'version' => '1.0.0'
    ];
    
    file_put_contents($serverStatusFile, json_encode($status));
}

// 清理服务器状态
function cleanupServerStatus() {
    global $serverStatusFile;
    
    if (file_exists($serverStatusFile)) {
        unlink($serverStatusFile);
    }
}

// 检查服务器状态
if (checkServerStatus()) {
    fwrite(STDERR, "Memo MCP Server 已经在运行中\n");
    exit(0);
}

// 记录启动状态
recordServerStatus();

// 注册清理函数
register_shutdown_function('cleanupServerStatus');

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

<?php

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use PFinal\Memo\MemoPrompt;

/**
 * 极简 MCP Server - 专门解决 Cursor 连接问题
 */
class SimpleMcpServer
{
    private $memoServer;
    private $memoPrompt;
    
    public function __construct()
    {
        $this->memoServer = new MemoServer();
        $this->memoPrompt = new MemoPrompt();
    }
    
    public function run()
    {
        // 主循环
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->sendError(null, 'Invalid JSON');
                    continue;
                }
                
                $this->handleRequest($request);
            } catch (Exception $e) {
                $this->sendError($request['id'] ?? null, $e->getMessage());
            }
        }
    }
    
    private function handleRequest($request)
    {
        $method = $request['method'] ?? '';
        
        switch ($method) {
            case 'initialize':
                $this->handleInitialize($request);
                break;
                
            case 'tools/list':
                $this->handleToolsList($request);
                break;
                
            case 'tools/call':
                $this->handleToolCall($request);
                break;
                
            default:
                $this->sendError($request['id'] ?? null, "Unknown method: $method");
        }
    }
    
    private function handleInitialize($request)
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => [
                        'listChanged' => false
                    ]
                ],
                'serverInfo' => [
                    'name' => 'simple-memo-mcp',
                    'version' => '1.0.0'
                ]
            ]
        ];
        
        $this->sendResponse($response);
    }
    
    private function handleToolsList($request)
    {
        $tools = [
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
            ],
            [
                'name' => 'memo.create_prompt',
                'description' => '获取创建备忘录的提示词'
            ],
            [
                'name' => 'memo.search_prompt',
                'description' => '获取搜索备忘录的提示词'
            ],
            [
                'name' => 'memo.management_prompt',
                'description' => '获取备忘录管理相关的提示词'
            ],
            [
                'name' => 'memo.template_prompt',
                'description' => '获取备忘录模板提示词',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'template_type' => [
                            'type' => 'string',
                            'description' => '模板类型',
                            'default' => 'note'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'memo.help_prompt',
                'description' => '获取备忘录系统帮助信息'
            ]
        ];
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'tools' => $tools
            ]
        ];
        
        $this->sendResponse($response);
    }
    
    private function handleToolCall($request)
    {
        $params = $request['params'] ?? [];
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        
        $result = null;
        
        switch ($toolName) {
            case 'memo.list':
                $result = $this->memoServer->listMemos();
                break;
                
            case 'memo.search':
                $keyword = $arguments['keyword'] ?? '';
                $result = $this->memoServer->searchMemos($keyword);
                break;
                
            case 'memo.create_prompt':
                $result = $this->memoPrompt->getCreatePrompt();
                break;
                
            case 'memo.search_prompt':
                $result = $this->memoPrompt->getSearchPrompt();
                break;
                
            case 'memo.management_prompt':
                $result = $this->memoPrompt->getManagementPrompt();
                break;
                
            case 'memo.template_prompt':
                $templateType = $arguments['template_type'] ?? 'note';
                $result = $this->memoPrompt->getTemplatePrompt($templateType);
                break;
                
            case 'memo.help_prompt':
                $result = $this->memoPrompt->getHelpPrompt();
                break;
                
            default:
                $this->sendError($request['id'], "Tool not found: $toolName");
                return;
        }
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_UNESCAPED_UNICODE)
                    ]
                ]
            ]
        ];
        
        $this->sendResponse($response);
    }
    
    private function sendResponse($response)
    {
        echo json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
        fflush(STDOUT);
    }
    
    private function sendError($id, $message, $code = -32603)
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        
        $this->sendResponse($response);
    }
}

// 启动服务器
try {
    $server = new SimpleMcpServer();
    $server->run();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    exit(1);
}

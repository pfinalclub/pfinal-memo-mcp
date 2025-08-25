<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use PFinal\Memo\MemoPrompt;

/**
 * 简化的 MCP Server，专门为 Cursor 优化
 * 使用纯 stdio 模式，避免 Workerman 的进程管理问题
 */
class CursorMcpServer
{
    private $memoServer;
    private $memoPrompt;
    private $tools = [];
    private $currentClient = 'unknown';
    private $clientVersion = '1.0.0';
    
    public function __construct()
    {
        $this->memoServer = new MemoServer();
        $this->memoPrompt = new MemoPrompt();
        $this->registerTools();
    }
    
    private function registerTools()
    {
        // 注册备忘录工具
        $this->tools['memo.list'] = [$this->memoServer, 'listMemos'];
        $this->tools['memo.search'] = [$this->memoServer, 'searchMemos'];
        
        // 注册提示词工具
        $this->tools['memo.create_prompt'] = [$this->memoPrompt, 'getCreatePrompt'];
        $this->tools['memo.search_prompt'] = [$this->memoPrompt, 'getSearchPrompt'];
        $this->tools['memo.management_prompt'] = [$this->memoPrompt, 'getManagementPrompt'];
        $this->tools['memo.template_prompt'] = [$this->memoPrompt, 'getTemplatePrompt'];
        $this->tools['memo.help_prompt'] = [$this->memoPrompt, 'getHelpPrompt'];
    }
    
    public function run()
    {
        // 设置错误处理
        set_error_handler(function($severity, $message, $file, $line) {
            $this->sendError(null, $message, $severity);
        });
        
        // 设置异常处理
        set_exception_handler(function($exception) {
            $this->sendError(null, $exception->getMessage(), 500);
        });
        
        // 主循环
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->sendError(null, 'Invalid JSON: ' . json_last_error_msg());
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
        $id = $request['id'] ?? null;
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
                $this->sendError($id, "Unknown method: $method");
        }
    }
    
    private function handleInitialize($request)
    {
        // 检测客户端类型
        $clientInfo = $request['params']['clientInfo'] ?? [];
        $clientName = $clientInfo['name'] ?? 'unknown';
        $clientVersion = $clientInfo['version'] ?? '1.0.0';
        
        // 保存客户端信息
        $this->currentClient = strtolower($clientName);
        $this->clientVersion = $clientVersion;
        
        // 根据客户端类型调整配置
        $capabilities = $this->getClientCapabilities($this->currentClient);
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => $capabilities,
                'serverInfo' => [
                    'name' => 'memo-mcp-server',
                    'version' => '1.0.0'
                ]
            ]
        ];
        
        $this->sendResponse($response);
    }
    
    private function getClientCapabilities($clientName)
    {
        // 根据不同的客户端返回不同的能力配置
        switch (strtolower($clientName)) {
            case 'cursor':
                return [
                    'tools' => [
                        'listChanged' => false
                    ]
                ];
                
            case 'vscode':
            case 'code':
                return [
                    'tools' => [
                        'listChanged' => false
                    ]
                ];
                
            case 'neovim':
            case 'nvim':
                return [
                    'tools' => [
                        'listChanged' => false
                    ]
                ];
                
            default:
                // 默认配置，兼容所有客户端
                return [
                    'tools' => [
                        'listChanged' => false
                    ]
                ];
        }
    }
    
    private function handleToolsList($request)
    {
        // 检测客户端类型（从之前的初始化请求中获取）
        $clientName = $this->getCurrentClientName();
        
        $tools = [];
        foreach (array_keys($this->tools) as $name) {
            $tool = [
                'name' => $name,
                'description' => $this->getToolDescription($name)
            ];
            
            // 为需要参数的工具添加 inputSchema
            if ($name === 'memo.search') {
                $tool['inputSchema'] = [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => [
                            'type' => 'string',
                            'description' => '搜索关键词'
                        ]
                    ],
                    'required' => ['keyword']
                ];
            } elseif ($name === 'memo.template_prompt') {
                $tool['inputSchema'] = [
                    'type' => 'object',
                    'properties' => [
                        'template_type' => [
                            'type' => 'string',
                            'description' => '模板类型：meeting, task, reminder, note',
                            'default' => 'note'
                        ]
                    ]
                ];
            }
            
            // 根据客户端类型调整工具配置
            $tool = $this->adaptToolForClient($tool, $clientName);
            $tools[] = $tool;
        }
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'tools' => $tools
            ]
        ];
        
        $this->sendResponse($response);
    }
    
    private function getCurrentClientName()
    {
        return $this->currentClient;
    }
    
    private function adaptToolForClient($tool, $clientName)
    {
        // 根据客户端类型调整工具配置
        switch (strtolower($clientName)) {
            case 'cursor':
                // Cursor 特定的配置
                if (isset($tool['inputSchema'])) {
                    // 确保 Cursor 兼容的格式
                    $tool['inputSchema']['additionalProperties'] = false;
                }
                break;
                
            case 'vscode':
            case 'code':
                // VS Code 特定的配置
                if (isset($tool['inputSchema'])) {
                    // VS Code 可能需要不同的格式
                    $tool['inputSchema']['$schema'] = 'http://json-schema.org/draft-07/schema#';
                }
                break;
                
            case 'neovim':
            case 'nvim':
                // Neovim 特定的配置
                // Neovim 可能需要更简洁的描述
                $tool['description'] = $this->getShortDescription($tool['name']);
                break;
                
            default:
                // 默认配置
                break;
        }
        
        return $tool;
    }
    
    private function getShortDescription($toolName)
    {
        $shortDescriptions = [
            'memo.list' => '获取备忘录列表',
            'memo.search' => '搜索备忘录',
            'memo.create_prompt' => '创建提示词',
            'memo.search_prompt' => '搜索提示词',
            'memo.management_prompt' => '管理提示词',
            'memo.template_prompt' => '模板提示词',
            'memo.help_prompt' => '帮助信息'
        ];
        
        return $shortDescriptions[$toolName] ?? $this->getToolDescription($toolName);
    }
    
    private function handleToolCall($request)
    {
        $params = $request['params'] ?? [];
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        
        if (!isset($this->tools[$toolName])) {
            $this->sendError($request['id'], "Tool not found: $toolName");
            return;
        }
        
        try {
            $callback = $this->tools[$toolName];
            
            // 特殊处理 searchMemos 方法，确保 keyword 参数正确传递
            if ($toolName === 'memo.search') {
                $keyword = $arguments['keyword'] ?? '';
                $result = $callback($keyword);
            } elseif ($toolName === 'memo.template_prompt') {
                $templateType = $arguments['template_type'] ?? 'note';
                $result = $callback($templateType);
            } else {
                $result = call_user_func_array($callback, $arguments);
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
        } catch (Exception $e) {
            $this->sendError($request['id'], $e->getMessage());
        }
    }
    
    private function getToolDescription($toolName)
    {
        $descriptions = [
            'memo.list' => '获取所有备忘录',
            'memo.search' => '搜索备忘录',
            'memo.create_prompt' => '获取创建备忘录的提示词',
            'memo.search_prompt' => '获取搜索备忘录的提示词',
            'memo.management_prompt' => '获取备忘录管理相关的提示词',
            'memo.template_prompt' => '获取备忘录模板提示词',
            'memo.help_prompt' => '获取备忘录系统帮助信息'
        ];
        
        return $descriptions[$toolName] ?? 'Unknown tool';
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
    $server = new CursorMcpServer();
    $server->run();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    exit(1);
}

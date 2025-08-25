<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use PFinal\Memo\MemoPrompt;

/**
 * 调试版本的 MCP Server，专门为 Cursor 优化
 * 添加详细的日志输出以便调试
 */
class DebugMcpServer
{
    private $memoServer;
    private $memoPrompt;
    private $tools = [];
    
    public function __construct()
    {
        $this->memoServer = new MemoServer();
        $this->memoPrompt = new MemoPrompt();
        $this->registerTools();
        $this->log("Debug MCP Server initialized");
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
        
        $this->log("Registered " . count($this->tools) . " tools");
    }
    
    public function run()
    {
        $this->log("Starting MCP server...");
        
        // 设置错误处理
        set_error_handler(function($severity, $message, $file, $line) {
            $this->log("ERROR: $message in $file:$line", 'ERROR');
        });
        
        // 设置异常处理
        set_exception_handler(function($exception) {
            $this->log("EXCEPTION: " . $exception->getMessage(), 'ERROR');
        });
        
        // 主循环
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $this->log("Received request: " . substr($line, 0, 100) . "...");
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->log("JSON decode error: " . json_last_error_msg(), 'ERROR');
                    $this->sendError(null, 'Invalid JSON: ' . json_last_error_msg());
                    continue;
                }
                
                $this->handleRequest($request);
            } catch (Exception $e) {
                $this->log("Exception in request handling: " . $e->getMessage(), 'ERROR');
                $this->sendError($request['id'] ?? null, $e->getMessage());
            }
        }
        
        $this->log("MCP server stopped");
    }
    
    private function handleRequest($request)
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        
        $this->log("Handling request: $method (ID: $id)");
        
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
                $this->log("Unknown method: $method", 'WARNING');
                $this->sendError($id, "Unknown method: $method");
        }
    }
    
    private function handleInitialize($request)
    {
        $this->log("Handling initialize request");
        
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
                    'name' => 'memo-mcp-server',
                    'version' => '1.0.0'
                ]
            ]
        ];
        
        $this->log("Sending initialize response");
        $this->sendResponse($response);
    }
    
    private function handleToolsList($request)
    {
        $this->log("Handling tools/list request");
        
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
            
            $tools[] = $tool;
        }
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'tools' => $tools
            ]
        ];
        
        $this->log("Sending tools list with " . count($tools) . " tools");
        $this->sendResponse($response);
    }
    
    private function handleToolCall($request)
    {
        $params = $request['params'] ?? [];
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        
        $this->log("Handling tool call: $toolName with arguments: " . json_encode($arguments));
        
        if (!isset($this->tools[$toolName])) {
            $this->log("Tool not found: $toolName", 'ERROR');
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
            
            $this->log("Tool call successful: $toolName");
            $this->sendResponse($response);
        } catch (Exception $e) {
            $this->log("Tool call failed: " . $e->getMessage(), 'ERROR');
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
        $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE);
        $this->log("Sending response: " . substr($responseJson, 0, 100) . "...");
        echo $responseJson . "\n";
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
        
        $this->log("Sending error: $message", 'ERROR');
        $this->sendResponse($response);
    }
    
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        fwrite(STDERR, $logMessage);
    }
}

// 启动服务器
try {
    $server = new DebugMcpServer();
    $server->run();
} catch (Exception $e) {
    fwrite(STDERR, "Fatal error: " . $e->getMessage() . "\n");
    exit(1);
}

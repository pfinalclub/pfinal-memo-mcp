<?php declare(strict_types=1);

namespace PFinal\Memo\Client;

use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;

/**
 * PHP Memo MCP 客户端
 * 用于连接 WebSocket 模式的 Memo MCP 服务器
 */
class MemoClient
{
    private string $serverUrl;
    private ?WebSocket $connection = null;
    private bool $isConnected = false;
    private int $requestId = 0;
    private array $pendingRequests = [];
    private Connector $connector;

    public function __construct(string $serverUrl = 'ws://127.0.0.1:8899/memo')
    {
        $this->serverUrl = $serverUrl;
        $this->connector = new Connector();
    }

    /**
     * 获取服务器 URL
     */
    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }

    /**
     * 连接到 WebSocket 服务器
     */
    public function connect(): PromiseInterface
    {
        return $this->connector->connect($this->serverUrl)->then(
            function (WebSocket $connection) {
                $this->connection = $connection;
                $this->isConnected = true;
                
                // 设置消息处理器
                $connection->on('message', function ($message) {
                    $this->handleMessage($message);
                });
                
                // 设置关闭处理器
                $connection->on('close', function () {
                    $this->isConnected = false;
                    echo "连接已关闭\n";
                });
                
                echo "已连接到服务器: {$this->serverUrl}\n";
                return $connection;
            },
            function (\Exception $e) {
                echo "连接失败: " . $e->getMessage() . "\n";
                throw $e;
            }
        );
    }

    /**
     * 发送 JSON-RPC 请求
     */
    public function sendRequest(string $method, array $params = []): PromiseInterface
    {
        if (!$this->isConnected || !$this->connection) {
            throw new \RuntimeException('服务器未连接');
        }

        $id = ++$this->requestId;
        $request = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params
        ];

        return new \React\Promise\Promise(function ($resolve, $reject) use ($id, $request) {
            $this->pendingRequests[$id] = ['resolve' => $resolve, 'reject' => $reject];
            
            $this->connection->send(json_encode($request));
            
            // 设置超时
            Loop::addTimer(10, function () use ($id, $reject) {
                if (isset($this->pendingRequests[$id])) {
                    unset($this->pendingRequests[$id]);
                    $reject(new \RuntimeException('请求超时'));
                }
            });
        });
    }

    /**
     * 处理服务器消息
     */
    private function handleMessage($message): void
    {
        try {
            $response = json_decode($message, true);
            if (!$response) {
                echo "解析响应失败: {$message}\n";
                return;
            }

            $id = $response['id'] ?? null;
            if ($id && isset($this->pendingRequests[$id])) {
                $callback = $this->pendingRequests[$id];
                unset($this->pendingRequests[$id]);

                if (isset($response['error'])) {
                    $callback['reject'](new \RuntimeException($response['error']['message']));
                } else {
                    $callback['resolve']($response['result'] ?? $response);
                }
            }
        } catch (\Exception $e) {
            echo "处理消息失败: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 获取所有备忘录
     */
    public function listMemos(): PromiseInterface
    {
        return $this->sendRequest('tools/call', [
            'name' => 'memo.list',
            'arguments' => []
        ]);
    }

    /**
     * 搜索备忘录
     */
    public function searchMemos(string $keyword): PromiseInterface
    {
        return $this->sendRequest('tools/call', [
            'name' => 'memo.search',
            'arguments' => [
                'keyword' => $keyword
            ]
        ]);
    }

    /**
     * 断开连接
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
        $this->isConnected = false;
    }

    /**
     * 检查连接状态
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }
}

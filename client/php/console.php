<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PFinal\Memo\Client\MemoClient;
use React\EventLoop\Loop;

/**
 * Memo MCP 控制台客户端
 */
class MemoConsole
{
    private MemoClient $client;
    private bool $running = true;

    public function __construct(string $serverUrl = 'ws://127.0.0.1:8899/memo')
    {
        $this->client = new MemoClient($serverUrl);
    }

    /**
     * 启动控制台
     */
    public function start(): void
    {
        echo "🚀 Memo MCP 控制台客户端\n";
        echo "连接到服务器: {$this->client->getServerUrl()}\n\n";

        // 连接到服务器
        $this->client->connect()->then(
            function () {
                $this->showHelp();
                $this->runInteractive();
            },
            function (\Exception $e) {
                echo "❌ 连接失败: " . $e->getMessage() . "\n";
                exit(1);
            }
        );

        // 运行事件循环
        Loop::run();
    }

    /**
     * 运行交互式界面
     */
    private function runInteractive(): void
    {
        while ($this->running) {
            echo "\n" . $this->colorize('memo> ', 'green');
            $input = trim(fgets(STDIN));

            if (empty($input)) {
                continue;
            }

            $this->handleCommand($input);
        }
    }

    /**
     * 处理命令
     */
    private function handleCommand(string $command): void
    {
        $parts = explode(' ', $command, 2);
        $cmd = strtolower($parts[0]);
        $args = $parts[1] ?? '';

        switch ($cmd) {
            case 'list':
                $this->handleList();
                break;
            case 'search':
                if (empty($args)) {
                    echo $this->colorize("❌ 请输入搜索关键词\n", 'red');
                } else {
                    $this->handleSearch($args);
                }
                break;
            case 'help':
                $this->showHelp();
                break;
            case 'exit':
            case 'quit':
                $this->running = false;
                $this->client->disconnect();
                echo $this->colorize("👋 再见！\n", 'yellow');
                Loop::stop();
                break;
            default:
                echo $this->colorize("❌ 未知命令: {$cmd}\n", 'red');
                echo "输入 'help' 查看可用命令\n";
                break;
        }
    }

    /**
     * 处理 list 命令
     */
    private function handleList(): void
    {
        echo $this->colorize("📋 获取备忘录列表...\n", 'blue');
        
        $this->client->listMemos()->then(
            function ($result) {
                $this->displayMemos($result['data'], "所有备忘录 (共 {$result['total']} 条)");
            },
            function (\Exception $e) {
                echo $this->colorize("❌ 获取失败: " . $e->getMessage() . "\n", 'red');
            }
        );
    }

    /**
     * 处理 search 命令
     */
    private function handleSearch(string $keyword): void
    {
        echo $this->colorize("🔍 搜索关键词: '{$keyword}'...\n", 'blue');
        
        $this->client->searchMemos($keyword)->then(
            function ($result) use ($keyword) {
                $this->displayMemos($result['data'], "搜索结果 (关键词: '{$keyword}', 共 {$result['total']} 条)");
            },
            function (\Exception $e) {
                echo $this->colorize("❌ 搜索失败: " . $e->getMessage() . "\n", 'red');
            }
        );
    }

    /**
     * 显示备忘录
     */
    private function displayMemos(array $memos, string $title): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo $this->colorize("📝 {$title}\n", 'blue');
        echo str_repeat('=', 50) . "\n";

        if (empty($memos)) {
            echo $this->colorize("暂无备忘录\n", 'yellow');
            return;
        }

        foreach ($memos as $memo) {
            echo "\n" . $this->colorize("🔸 ID: {$memo['id']}\n", 'green');
            echo $this->colorize("📄 内容: ", 'cyan') . $memo['content'] . "\n";
            echo $this->colorize("📅 创建时间: ", 'cyan') . $memo['created_at'] . "\n";
            echo str_repeat('-', 30) . "\n";
        }
    }

    /**
     * 显示帮助信息
     */
    private function showHelp(): void
    {
        echo $this->colorize("\n📖 可用命令:\n", 'blue');
        echo $this->colorize("  list", 'cyan') . "     - 获取所有备忘录\n";
        echo $this->colorize("  search <关键词>", 'cyan') . " - 搜索备忘录\n";
        echo $this->colorize("  help", 'cyan') . "     - 显示此帮助信息\n";
        echo $this->colorize("  exit", 'cyan') . "     - 退出程序\n\n";
    }

    /**
     * 颜色化输出
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'reset' => "\033[0m"
        ];

        return ($colors[$color] ?? '') . $text . $colors['reset'];
    }
}

// 启动控制台
if (php_sapi_name() === 'cli') {
    $console = new MemoConsole();
    $console->start();
}

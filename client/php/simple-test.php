<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PFinal\Memo\Client\MemoClient;
use React\EventLoop\Loop;

/**
 * 简单的测试脚本
 */
class SimpleTest
{
    private MemoClient $client;

    public function __construct(string $serverUrl = 'ws://127.0.0.1:8899/memo')
    {
        $this->client = new MemoClient($serverUrl);
    }

    /**
     * 运行测试
     */
    public function run(): void
    {
        echo "🧪 开始测试 Memo MCP 客户端...\n\n";

        // 连接到服务器
        $this->client->connect()->then(
            function () {
                echo "✅ 连接成功\n\n";
                $this->runTests();
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
     * 运行测试用例
     */
    private function runTests(): void
    {
        // 测试 1: 获取所有备忘录
        echo "📋 测试 1: 获取所有备忘录\n";
        $this->client->listMemos()->then(
            function ($result) {
                echo "✅ 获取成功\n";
                echo "   总数: {$result['total']}\n";
                if (!empty($result['data'])) {
                    echo "   第一条: {$result['data'][0]['content']}\n";
                }
                echo "\n";
                
                // 继续下一个测试
                $this->testSearch();
            },
            function (\Exception $e) {
                echo "❌ 获取失败: " . $e->getMessage() . "\n\n";
                $this->testSearch();
            }
        );
    }

    /**
     * 测试搜索功能
     */
    private function testSearch(): void
    {
        echo "🔍 测试 2: 搜索备忘录\n";
        $this->client->searchMemos('第一')->then(
            function ($result) {
                echo "✅ 搜索成功\n";
                echo "   关键词: {$result['keyword']}\n";
                echo "   结果数: {$result['total']}\n";
                if (!empty($result['data'])) {
                    echo "   第一条: {$result['data'][0]['content']}\n";
                }
                echo "\n";
                
                // 继续下一个测试
                $this->testSearchEmpty();
            },
            function (\Exception $e) {
                echo "❌ 搜索失败: " . $e->getMessage() . "\n\n";
                $this->testSearchEmpty();
            }
        );
    }

    /**
     * 测试空搜索结果
     */
    private function testSearchEmpty(): void
    {
        echo "🔍 测试 3: 搜索不存在的关键词\n";
        $this->client->searchMemos('不存在的关键词')->then(
            function ($result) {
                echo "✅ 搜索成功\n";
                echo "   关键词: {$result['keyword']}\n";
                echo "   结果数: {$result['total']}\n";
                echo "\n";
                
                // 测试完成
                $this->finish();
            },
            function (\Exception $e) {
                echo "❌ 搜索失败: " . $e->getMessage() . "\n\n";
                $this->finish();
            }
        );
    }

    /**
     * 完成测试
     */
    private function finish(): void
    {
        echo "🎉 所有测试完成！\n";
        $this->client->disconnect();
        Loop::stop();
    }
}

// 运行测试
if (php_sapi_name() === 'cli') {
    $test = new SimpleTest();
    $test->run();
}

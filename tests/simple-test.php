<?php

/**
 * 简单的 MemoServer 测试脚本
 * 直接测试 MemoServer 类的功能，不涉及 MCP 协议
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PFinal\Memo\MemoServer;

echo "=== MemoServer 简单测试 ===\n\n";

try {
    // 创建 MemoServer 实例
    $memoServer = new MemoServer();
    echo "✅ MemoServer 实例创建成功\n\n";

    // 测试 listMemos 方法
    echo "📋 测试 listMemos 方法:\n";
    $result = $memoServer->listMemos();
    echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    // 测试 searchMemos 方法
    echo "🔍 测试 searchMemos 方法 (关键词: '示例'):\n";
    $result = $memoServer->searchMemos('示例');
    echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    // 测试 searchMemos 方法 - 无结果
    echo "🔍 测试 searchMemos 方法 (关键词: '不存在'):\n";
    $result = $memoServer->searchMemos('不存在');
    echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    // 测试 searchMemos 方法 - 空关键词
    echo "🔍 测试 searchMemos 方法 (空关键词):\n";
    $result = $memoServer->searchMemos('');
    echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    echo "✅ 所有测试完成！\n";

} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

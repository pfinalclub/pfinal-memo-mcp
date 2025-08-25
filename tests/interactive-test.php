<?php

/**
 * 交互式 MCP 测试脚本
 * 用于手动测试 Memo MCP Server 的功能
 */

echo "=== Memo MCP Server 交互式测试 ===\n";
echo "输入 JSON-RPC 请求，输入 'quit' 退出\n\n";

// 示例请求
echo "示例请求:\n";
echo "1. 初始化: {\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"initialize\",\"params\":{\"protocolVersion\":\"2024-11-05\",\"capabilities\":{\"tools\":[]},\"clientInfo\":{\"name\":\"test\",\"version\":\"1.0.0\"}}}\n";
echo "2. 工具列表: {\"jsonrpc\":\"2.0\",\"id\":2,\"method\":\"tools/list\"}\n";
echo "3. 获取备忘录: {\"jsonrpc\":\"2.0\",\"id\":3,\"method\":\"tools/call\",\"params\":{\"name\":\"memo.list\",\"arguments\":{}}}\n";
echo "4. 搜索备忘录: {\"jsonrpc\":\"2.0\",\"id\":4,\"method\":\"tools/call\",\"params\":{\"name\":\"memo.search\",\"arguments\":{\"keyword\":\"示例\"}}}\n\n";

// 启动 MCP 服务器进程
$descriptorspec = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

echo "启动 MCP 服务器...\n";
$process = proc_open("php stdio-server.php", $descriptorspec, $pipes);

if (!is_resource($process)) {
    die("无法启动服务器进程\n");
}

// 等待服务器启动
sleep(1);

// 检查进程状态
$status = proc_get_status($process);
if (!$status['running']) {
    die("服务器进程启动失败\n");
}

echo "服务器已启动，开始交互测试...\n\n";

// 交互式循环
while (true) {
    echo "请输入 JSON-RPC 请求 (或输入 'quit' 退出):\n";
    $input = trim(fgets(STDIN));
    
    if ($input === 'quit' || $input === 'exit') {
        break;
    }
    
    if (empty($input)) {
        continue;
    }
    
    // 发送请求
    $requestData = $input . "\n";
    if (fwrite($pipes[0], $requestData) === false) {
        echo "发送失败: " . error_get_last()['message'] . "\n";
        continue;
    }
    
    // 读取响应
    $response = fgets($pipes[1]);
    if ($response === false) {
        echo "读取响应失败\n";
        continue;
    }
    
    echo "服务器响应: " . $response . "\n";
}

// 关闭进程
echo "关闭服务器...\n";
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

echo "测试结束！\n";

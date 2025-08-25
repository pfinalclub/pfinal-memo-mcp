<?php

/**
 * 简单的 Cursor MCP Server 测试
 */

echo "测试 Cursor MCP Server...\n";

// 启动服务器进程
$descriptorspec = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

$process = proc_open("php cursor-memo-server.php", $descriptorspec, $pipes);

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

echo "✓ 服务器进程已启动 (PID: {$status['pid']})\n";

// 测试初始化
$initRequest = [
    "jsonrpc" => "2.0",
    "id" => 1,
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => ["tools" => []],
        "clientInfo" => [
            "name" => "cursor-client",
            "version" => "1.0.0"
        ]
    ]
];

fwrite($pipes[0], json_encode($initRequest) . "\n");
$response = fgets($pipes[1]);
$initResult = json_decode($response, true);

if (isset($initResult['result'])) {
    echo "✓ 初始化成功\n";
} else {
    echo "✗ 初始化失败: " . $response . "\n";
}

// 测试工具列表
$listRequest = [
    "jsonrpc" => "2.0",
    "id" => 2,
    "method" => "tools/list"
];

fwrite($pipes[0], json_encode($listRequest) . "\n");
$response = fgets($pipes[1]);
$listResult = json_decode($response, true);

if (isset($listResult['result']['tools'])) {
    $tools = $listResult['result']['tools'];
    echo "✓ 工具列表获取成功，共 " . count($tools) . " 个工具:\n";
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
} else {
    echo "✗ 工具列表获取失败: " . $response . "\n";
}

// 测试 memo.list
$listToolRequest = [
    "jsonrpc" => "2.0",
    "id" => 3,
    "method" => "tools/call",
    "params" => [
        "name" => "memo.list",
        "arguments" => []
    ]
];

fwrite($pipes[0], json_encode($listToolRequest) . "\n");
$response = fgets($pipes[1]);
$listToolResult = json_decode($response, true);

if (isset($listToolResult['result'])) {
    echo "✓ memo.list 调用成功\n";
} else {
    echo "✗ memo.list 调用失败: " . $response . "\n";
}

// 关闭进程
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

echo "测试完成！\n";

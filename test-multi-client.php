<?php

/**
 * 测试多客户端支持
 */

echo "测试多客户端支持...\n";

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

sleep(1);

// 检查进程状态
$status = proc_get_status($process);
if (!$status['running']) {
    die("服务器进程启动失败\n");
}

echo "✓ 服务器进程已启动 (PID: {$status['pid']})\n";

// 测试 Cursor 客户端
echo "\n=== 测试 Cursor 客户端 ===\n";
$initRequest = [
    "jsonrpc" => "2.0",
    "id" => 1,
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => ["tools" => []],
        "clientInfo" => [
            "name" => "cursor",
            "version" => "1.0.0"
        ]
    ]
];

fwrite($pipes[0], json_encode($initRequest) . "\n");
$response = fgets($pipes[1]);
$initResult = json_decode($response, true);

if (isset($initResult['result'])) {
    echo "✓ Cursor 初始化成功\n";
} else {
    echo "✗ Cursor 初始化失败\n";
}

// 测试 VS Code 客户端
echo "\n=== 测试 VS Code 客户端 ===\n";
$initRequestVscode = [
    "jsonrpc" => "2.0",
    "id" => 2,
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => ["tools" => []],
        "clientInfo" => [
            "name" => "vscode",
            "version" => "1.80.0"
        ]
    ]
];

fwrite($pipes[0], json_encode($initRequestVscode) . "\n");
$response = fgets($pipes[1]);
$initResultVscode = json_decode($response, true);

if (isset($initResultVscode['result'])) {
    echo "✓ VS Code 初始化成功\n";
} else {
    echo "✗ VS Code 初始化失败\n";
}

// 测试 Neovim 客户端
echo "\n=== 测试 Neovim 客户端 ===\n";
$initRequestNvim = [
    "jsonrpc" => "2.0",
    "id" => 3,
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => ["tools" => []],
        "clientInfo" => [
            "name" => "neovim",
            "version" => "0.9.0"
        ]
    ]
];

fwrite($pipes[0], json_encode($initRequestNvim) . "\n");
$response = fgets($pipes[1]);
$initResultNvim = json_decode($response, true);

if (isset($initResultNvim['result'])) {
    echo "✓ Neovim 初始化成功\n";
} else {
    echo "✗ Neovim 初始化失败\n";
}

// 测试工具列表
echo "\n=== 测试工具列表 ===\n";
$listRequest = [
    "jsonrpc" => "2.0",
    "id" => 4,
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
    echo "✗ 工具列表获取失败\n";
}

// 关闭进程
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

echo "\n测试完成！\n";

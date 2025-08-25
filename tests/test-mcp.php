<?php

/**
 * MCP 客户端测试脚本
 * 用于测试 Memo MCP Server 的功能
 */

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

echo "服务器进程已启动 (PID: {$status['pid']})\n";

// 发送初始化请求
$initRequest = [
    "jsonrpc" => "2.0",
    "id" => 1,
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => [
            "tools" => []
        ],
        "clientInfo" => [
            "name" => "test-client",
            "version" => "1.0.0"
        ]
    ]
];

echo "发送初始化请求...\n";
$requestData = json_encode($initRequest) . "\n";
if (fwrite($pipes[0], $requestData) === false) {
    echo "写入失败: " . error_get_last()['message'] . "\n";
} else {
    echo "请求已发送: " . $requestData;
}

// 读取响应
$response = fgets($pipes[1]);
if ($response === false) {
    echo "读取响应失败\n";
} else {
    echo "服务器响应: " . $response;
}

// 发送工具列表请求
$listRequest = [
    "jsonrpc" => "2.0",
    "id" => 2,
    "method" => "tools/list"
];

echo "发送工具列表请求...\n";
$requestData = json_encode($listRequest) . "\n";
if (fwrite($pipes[0], $requestData) === false) {
    echo "写入失败: " . error_get_last()['message'] . "\n";
} else {
    echo "请求已发送: " . $requestData;
}

$response = fgets($pipes[1]);
if ($response === false) {
    echo "读取响应失败\n";
} else {
    echo "工具列表响应: " . $response;
}

// 测试 memo.list 工具
$listToolRequest = [
    "jsonrpc" => "2.0",
    "id" => 3,
    "method" => "tools/call",
    "params" => [
        "name" => "memo.list",
        "arguments" => []
    ]
];

echo "测试 memo.list 工具...\n";
$requestData = json_encode($listToolRequest) . "\n";
if (fwrite($pipes[0], $requestData) === false) {
    echo "写入失败: " . error_get_last()['message'] . "\n";
} else {
    echo "请求已发送: " . $requestData;
}

$response = fgets($pipes[1]);
if ($response === false) {
    echo "读取响应失败\n";
} else {
    echo "memo.list 响应: " . $response;
}

// 测试 memo.search 工具
$searchToolRequest = [
    "jsonrpc" => "2.0",
    "id" => 4,
    "method" => "tools/call",
    "params" => [
        "name" => "memo.search",
        "arguments" => [
            "keyword" => "示例"
        ]
    ]
];

echo "测试 memo.search 工具...\n";
$requestData = json_encode($searchToolRequest) . "\n";
if (fwrite($pipes[0], $requestData) === false) {
    echo "写入失败: " . error_get_last()['message'] . "\n";
} else {
    echo "请求已发送: " . $requestData;
}

$response = fgets($pipes[1]);
if ($response === false) {
    echo "读取响应失败\n";
} else {
    echo "memo.search 响应: " . $response;
}

// 关闭进程
echo "关闭服务器进程...\n";
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

echo "测试完成！\n";

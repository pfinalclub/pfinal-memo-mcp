<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// 测试 memo.list
$listRequest = json_encode([
    'method' => 'tools/call',
    'params' => [
        'name' => 'memo.list',
        'arguments' => []
    ]
]) . "\n";

fwrite(STDOUT, $listRequest);
$response = fgets(STDIN);
echo "List Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

// 测试 memo.search
$searchRequest = json_encode([
    'method' => 'tools/call',
    'params' => [
        'name' => 'memo.search',
        'arguments' => [
            'keyword' => '第一'
        ]
    ]
]) . "\n";

fwrite(STDOUT, $searchRequest);
$response = fgets(STDIN);
echo "Search Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

<?php
require_once __DIR__ . '/../vendor/autoload.php';
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\Inline\Text;

$file = __DIR__ . '/Models/docs/adb.md';
$outputFile = __DIR__ . '/adb_parsed.json';

/**
 * 将 Markdown 解析为 JSON 格式
 */
if (!file_exists($file)) {
    echo "文件不存在: $file\n";
    exit(1);
}

$markdown = file_get_contents($file);
echo "读取文件: $file\n";
echo "内容长度: " . strlen($markdown) . " 字符\n\n";

// 设置解析环境
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());

$parser = new MarkdownParser($environment);
$document = $parser->parse($markdown);

// 递归遍历 AST 节点并转换为数组
function nodeToArray(Node $node)
{
    $data = [
        'type' => get_class($node),
        'literal' => '',
        'children' => []
    ];

    // 只有实现了 StringContainerInterface 的节点才有 getLiteral 方法
    if (method_exists($node, 'getLiteral')) {
        $data['literal'] = $node->getLiteral();
    }

    foreach ($node->children() as $child) {
        $data['children'][] = nodeToArray($child);
    }

    return $data;
}

// 统计节点类型的辅助函数
function analyzeNodes(Node $node, &$typeCounts) {
    $type = get_class($node);
    if (!isset($typeCounts[$type])) {
        $typeCounts[$type] = 0;
    }
    $typeCounts[$type]++;
    
    foreach ($node->children() as $child) {
        analyzeNodes($child, $typeCounts);
    }
}

// 转换为 JSON
$result = nodeToArray($document);

// 分析节点类型
$typeCounts = [];
analyzeNodes($document, $typeCounts);

echo "Markdown 解析为 JSON 成功！\n";
echo "节点总数: " . countNodes($document) . "\n\n";

echo "节点类型统计:\n";
foreach ($typeCounts as $type => $count) {
    echo "  " . basename(str_replace('\\', '/', $type)) . ": $count\n";
}
echo "\n";

// 统计节点数量的辅助函数
function countNodes(Node $node) {
    $count = 1;
    foreach ($node->children() as $child) {
        $count += countNodes($child);
    }
    return $count;
}

// 输出到文件
$jsonOutput = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($outputFile, $jsonOutput);

echo "JSON 已保存到: $outputFile\n";
echo "JSON 大小: " . strlen($jsonOutput) . " 字符\n\n";

// 显示 JSON 预览（前1000字符）
echo "JSON 预览 (前1000字符):\n";
echo substr($jsonOutput, 0, 1000) . "...\n";
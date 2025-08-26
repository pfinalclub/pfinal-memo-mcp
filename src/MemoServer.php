<?php declare(strict_types=1);

namespace PFinal\Memo;

use PFinal\Memo\Models\Memo;
use PFPMcp\Attributes\McpTool;
use PFPMcp\Attributes\Schema;

class MemoServer
{
    /** @var Memo[] */
    private array $memos = [];

    public function __construct()
    {
        // 添加一些示例数据
        $this->memos = [
            new Memo(1, '这是第一条备忘录'),
            new Memo(2, '这是第二条备忘录'),
        ];
    }

    #[McpTool(
        name: 'memo.list',
        description: '获取所有备忘录'
    )]
    public function listMemos(): array
    {
        return [
            'success' => true,
            'data' => array_map(fn(Memo $memo) => $memo->toArray(), $this->memos),
            'total' => count($this->memos)
        ];
    }

    #[McpTool(
        name: 'memo.search',
        description: '搜索备忘录'
    )]
    public function searchMemos(
        #[Schema(description: '搜索关键词')]
        string $keyword
    ): array {
        $results = array_filter(
            $this->memos,
            fn(Memo $memo) => str_contains($memo->getContent(), $keyword)
        );

        return [
            'success' => true,
            'data' => array_map(fn(Memo $memo) => $memo->toArray(), $results),
            'total' => count($results),
            'keyword' => $keyword
        ];
    }
}

<?php
/*
 * @Author: pfinal liuxuzhu@smm.cn
 * @Date: 2025-08-22 15:52:49
 * @LastEditors: pfinal liuxuzhu@smm.cn
 * @LastEditTime: 2025-08-22 17:19:47
 * @FilePath: /pfinal-memo-mcp/src/MemoServer.php
 * @Description: Memo MCP Server 主类
 */

namespace PFinal\Memo;

use PFPMcp\Attributes\McpTool;
use PFPMcp\Attributes\Schema;
use PFinal\Memo\Models\Memo;
use Exception;

class MemoServer
{
    private $memos = [];
    
    public function __construct()
    {
        // 初始化一些示例数据
        $this->memos[] = new Memo('这是一个示例备忘录');
        $this->memos[] = new Memo('另一个测试备忘录');
    }

    /**
     * 获取所有备忘录
     * 
     * @return array 备忘录列表
     */
    #[McpTool(
        name: 'memo.list',
        description: '获取所有备忘录'
    )]
    public function listMemos(): array
    {
        try {
            return [
                'success' => true,
                'memos' => $this->getAllMemos(),
                'count' => count($this->memos)
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 搜索备忘录
     * 
     * @param string $keyword 搜索关键词
     * @return array 搜索结果
     */
    #[McpTool(
        name: 'memo.search',
        description: '搜索备忘录'
    )]
    public function searchMemos(
        #[Schema(description: '搜索关键词')]
        string $keyword
    ): array {
        try {
            if (empty($keyword)) {
                return ['error' => '搜索关键词不能为空'];
            }
            
            $results = $this->searchMemosByKeyword($keyword);
            return [
                'success' => true,
                'memos' => $results,
                'count' => count($results),
                'keyword' => $keyword
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getAllMemos()
    {
        return array_map(function ($memo) {
            return $memo->toArray();
        }, $this->memos);
    }

    private function searchMemosByKeyword($keyword)
    {
        return array_map(
            function ($memo) {
                return $memo->toArray();
            },
            array_filter($this->memos, function ($memo) use ($keyword) {
                return stripos($memo->getContent(), $keyword) !== false;
            })
        );
    }
}

<?php

namespace PFinal\Memo;

use PFPMcp\Attributes\McpTool;
use PFPMcp\Attributes\Schema;

/**
 * Memo Prompt 类
 * 提供备忘录相关的提示词功能
 */
class MemoPrompt
{
    /**
     * 获取备忘录创建提示
     * 
     * @return array 提示信息
     */
    #[McpTool(
        name: 'memo.create_prompt',
        description: '获取创建备忘录的提示词'
    )]
    public function getCreatePrompt(): array
    {
        return [
            'success' => true,
            'prompt' => [
                'name' => 'memo_create',
                'description' => '创建新的备忘录',
                'content' => '请提供备忘录的内容。备忘录应该简洁明了，包含重要信息。',
                'examples' => [
                    '今天下午3点开会讨论项目进度',
                    '记得明天提交周报',
                    '购买生日礼物给妈妈'
                ],
                'format' => '纯文本格式，建议长度在50-200字之间'
            ]
        ];
    }

    /**
     * 获取备忘录搜索提示
     * 
     * @return array 提示信息
     */
    #[McpTool(
        name: 'memo.search_prompt',
        description: '获取搜索备忘录的提示词'
    )]
    public function getSearchPrompt(): array
    {
        return [
            'success' => true,
            'prompt' => [
                'name' => 'memo_search',
                'description' => '搜索备忘录',
                'content' => '请输入搜索关键词。系统会在所有备忘录中查找包含该关键词的内容。',
                'examples' => [
                    '会议',
                    '项目',
                    '生日',
                    '购物'
                ],
                'tips' => [
                    '搜索不区分大小写',
                    '支持部分匹配',
                    '可以搜索中文和英文'
                ]
            ]
        ];
    }

    /**
     * 获取备忘录管理提示
     * 
     * @return array 提示信息
     */
    #[McpTool(
        name: 'memo.management_prompt',
        description: '获取备忘录管理相关的提示词'
    )]
    public function getManagementPrompt(): array
    {
        return [
            'success' => true,
            'prompt' => [
                'name' => 'memo_management',
                'description' => '备忘录管理指南',
                'content' => '备忘录管理系统使用指南：',
                'features' => [
                    '创建备忘录：记录重要信息和待办事项',
                    '搜索备忘录：快速找到需要的信息',
                    '查看所有备忘录：浏览所有记录的内容',
                    '管理备忘录：组织和管理您的备忘录'
                ],
                'best_practices' => [
                    '使用简洁明了的语言',
                    '包含关键信息和时间',
                    '定期整理和更新备忘录',
                    '使用关键词便于搜索'
                ]
            ]
        ];
    }

    /**
     * 获取备忘录模板提示
     * 
     * @param string $template_type 模板类型
     * @return array 提示信息
     */
    #[McpTool(
        name: 'memo.template_prompt',
        description: '获取备忘录模板提示词'
    )]
    public function getTemplatePrompt(
        #[Schema(description: '模板类型：meeting, task, reminder, note')]
        string $template_type = 'note'
    ): array
    {
        $templates = [
            'meeting' => [
                'name' => '会议备忘录模板',
                'content' => '会议主题：\n时间：\n地点：\n参会人员：\n议程：\n决议：\n后续行动：',
                'description' => '用于记录会议信息的标准模板'
            ],
            'task' => [
                'name' => '任务备忘录模板',
                'content' => '任务名称：\n优先级：\n截止时间：\n负责人：\n任务描述：\n完成标准：\n备注：',
                'description' => '用于记录任务信息的标准模板'
            ],
            'reminder' => [
                'name' => '提醒备忘录模板',
                'content' => '提醒事项：\n提醒时间：\n重要程度：\n相关联系人：\n备注：',
                'description' => '用于设置提醒的标准模板'
            ],
            'note' => [
                'name' => '通用备忘录模板',
                'content' => '标题：\n内容：\n标签：\n创建时间：\n备注：',
                'description' => '通用备忘录模板'
            ]
        ];

        $template = $templates[$template_type] ?? $templates['note'];

        return [
            'success' => true,
            'template_type' => $template_type,
            'prompt' => $template
        ];
    }

    /**
     * 获取备忘录帮助提示
     * 
     * @return array 提示信息
     */
    #[McpTool(
        name: 'memo.help_prompt',
        description: '获取备忘录系统帮助信息'
    )]
    public function getHelpPrompt(): array
    {
        return [
            'success' => true,
            'prompt' => [
                'name' => 'memo_help',
                'description' => '备忘录系统帮助',
                'content' => '欢迎使用备忘录系统！',
                'available_commands' => [
                    'memo.list' => '查看所有备忘录',
                    'memo.search' => '搜索备忘录',
                    'memo.create_prompt' => '获取创建备忘录提示',
                    'memo.search_prompt' => '获取搜索备忘录提示',
                    'memo.management_prompt' => '获取管理指南',
                    'memo.template_prompt' => '获取备忘录模板',
                    'memo.help_prompt' => '获取帮助信息'
                ],
                'usage_tips' => [
                    '使用 memo.list 查看所有备忘录',
                    '使用 memo.search 搜索特定内容',
                    '使用各种 prompt 获取操作指导',
                    '备忘录会自动保存，无需手动保存'
                ]
            ]
        ];
    }
}

#!/bin/bash

# 轻量级 Memo MCP Server 启动脚本
# 用于 Cursor 集成，避免 Workerman 常驻进程问题

echo "启动轻量级 Memo MCP Server..."

# 检查 PHP 是否可用
if ! command -v php &> /dev/null; then
    echo "错误: PHP 未安装或不在 PATH 中"
    exit 1
fi

# 检查文件是否存在
if [ ! -f "cursor-memo-server.php" ]; then
    echo "错误: cursor-memo-server.php 文件不存在"
    exit 1
fi

# 检查 vendor 目录是否存在
if [ ! -d "vendor" ]; then
    echo "错误: vendor 目录不存在，请先运行 composer install"
    exit 1
fi

# 设置环境变量
export PHP_MEMORY_LIMIT="128M"
export PHP_MAX_EXECUTION_TIME="0"

echo "环境变量设置完成:"
echo "  PHP_MEMORY_LIMIT: $PHP_MEMORY_LIMIT"
echo "  PHP_MAX_EXECUTION_TIME: $PHP_MAX_EXECUTION_TIME"

# 启动服务器
echo "正在启动轻量级 MCP Server..."
php cursor-memo-server.php

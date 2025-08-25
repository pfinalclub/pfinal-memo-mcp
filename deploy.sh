#!/bin/bash

# MCP 服务器部署脚本

set -e

echo "=== MCP 服务器部署脚本 ==="

# 检查参数
if [ "$1" = "local" ]; then
    echo "部署到本地环境..."
    DEPLOY_TYPE="local"
elif [ "$1" = "prod" ]; then
    echo "部署到生产环境..."
    DEPLOY_TYPE="prod"
else
    echo "用法: $0 {local|prod}"
    echo "  local - 本地开发环境"
    echo "  prod  - 生产环境"
    exit 1
fi

# 检查 Docker 是否安装
if ! command -v docker &> /dev/null; then
    echo "❌ Docker 未安装"
    exit 1
fi

# 检查 docker-compose 是否安装
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose 未安装"
    exit 1
fi

# 创建必要的目录
echo "创建目录..."
mkdir -p data logs

if [ "$DEPLOY_TYPE" = "local" ]; then
    # 本地部署
    echo "构建本地镜像..."
    docker build -t memo-mcp-server:local .
    
    echo "启动本地服务..."
    docker-compose up -d
    
    echo "✅ 本地部署完成"
    echo "服务器地址: http://localhost:8080"
    echo "使用配置: cursor-recommended-config.json"
    
elif [ "$DEPLOY_TYPE" = "prod" ]; then
    # 生产部署
    echo "构建生产镜像..."
    docker build -f Dockerfile.prod -t memo-mcp-server:prod .
    
    echo "启动生产服务..."
    docker-compose -f docker-compose.prod.yml up -d
    
    echo "✅ 生产部署完成"
    echo "服务器地址: http://your-server:8080"
    echo "使用配置: cursor-http-config.json"
fi

# 健康检查
echo "等待服务启动..."
sleep 10

echo "健康检查..."
if curl -f http://localhost:8080 > /dev/null 2>&1; then
    echo "✅ 服务运行正常"
else
    echo "❌ 服务启动失败"
    echo "查看日志: docker-compose logs memo-mcp-server"
    exit 1
fi

echo ""
echo "=== 部署完成 ==="
echo ""
echo "测试命令:"
echo "curl -X POST -H 'Content-Type: application/json' -d '{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"initialize\",\"params\":{\"protocolVersion\":\"2024-11-05\",\"capabilities\":{\"tools\":[]},\"clientInfo\":{\"name\":\"test\",\"version\":\"1.0.0\"}}}' http://localhost:8080"
echo ""
echo "查看日志:"
echo "docker-compose logs -f memo-mcp-server"
echo ""
echo "停止服务:"
echo "docker-compose down"

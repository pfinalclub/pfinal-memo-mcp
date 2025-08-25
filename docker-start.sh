#!/bin/bash

# Memo MCP Docker 启动脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 打印带颜色的消息
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Memo MCP Docker 启动脚本${NC}"
    echo -e "${BLUE}================================${NC}"
}

# 检查 Docker 是否安装
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker 未安装，请先安装 Docker"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose 未安装，请先安装 Docker Compose"
        exit 1
    fi
}

# 创建必要目录
create_directories() {
    print_message "创建必要目录..."
    mkdir -p logs data
    chmod 755 logs data
    print_message "目录创建完成"
}

# 显示帮助信息
show_help() {
    echo "用法: $0 [选项]"
    echo ""
    echo "选项:"
    echo "  dev     启动开发环境"
    echo "  prod    启动生产环境"
    echo "  tcp     启动 TCP 模式"
    echo "  all     启动所有服务（生产环境 + Nginx + Redis + MySQL）"
    echo "  stop    停止所有服务"
    echo "  logs    查看日志"
    echo "  status  查看服务状态"
    echo "  clean   清理资源"
    echo "  help    显示此帮助信息"
    echo ""
    echo "示例:"
    echo "  $0 dev     # 启动开发环境"
    echo "  $0 prod    # 启动生产环境"
    echo "  $0 all     # 启动完整环境"
}

# 启动开发环境
start_dev() {
    print_message "启动开发环境..."
    docker-compose --profile dev up -d
    print_message "开发环境启动完成"
    print_message "访问地址: http://localhost:8891"
}

# 启动生产环境
start_prod() {
    print_message "启动生产环境..."
    docker-compose --profile prod up -d
    print_message "生产环境启动完成"
    print_message "访问地址: http://localhost:8888"
}

# 启动 TCP 模式
start_tcp() {
    print_message "启动 TCP 模式..."
    docker-compose --profile tcp up -d
    print_message "TCP 模式启动完成"
    print_message "TCP 地址: localhost:8889"
}

# 启动所有服务
start_all() {
    print_message "启动完整环境..."
    docker-compose --profile prod
    print_message "完整环境启动完成"
    print_message "HTTP 地址: http://localhost:8888"
    print_message "TCP 地址: localhost:8889"
}

# 停止服务
stop_services() {
    print_message "停止所有服务..."
    docker-compose down
    print_message "服务已停止"
}

# 查看日志
show_logs() {
    print_message "显示服务日志..."
    docker-compose logs -f
}

# 查看状态
show_status() {
    print_message "服务状态:"
    docker-compose ps
    echo ""
    print_message "容器健康状态:"
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

# 清理资源
clean_resources() {
    print_warning "这将删除所有容器、镜像和数据卷，确定继续吗？(y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_message "清理资源..."
        docker-compose down -v --rmi all
        docker system prune -f
        print_message "资源清理完成"
    else
        print_message "取消清理操作"
    fi
}

# 主函数
main() {
    print_header
    
    # 检查 Docker
    check_docker
    
    # 创建目录
    create_directories
    
    # 处理命令行参数
    case "${1:-help}" in
        dev)
            start_dev
            ;;
        prod)
            start_prod
            ;;
        tcp)
            start_tcp
            ;;
        all)
            start_all
            ;;
        stop)
            stop_services
            ;;
        logs)
            show_logs
            ;;
        status)
            show_status
            ;;
        clean)
            clean_resources
            ;;
        help|*)
            show_help
            ;;
    esac
}

# 执行主函数
main "$@"

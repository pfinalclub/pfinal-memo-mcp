# Docker 部署指南

## 概述

本项目提供了完整的 Docker 支持，包括开发环境、生产环境和多种运行模式。

## 快速开始

### 1. 开发环境

```bash
# 启动开发环境（HTTP 模式）
docker-compose --profile dev up -d

# 查看日志
docker-compose --profile dev logs -f memo-mcp-dev

# 停止服务
docker-compose --profile dev down
```

### 2. 生产环境

```bash
# 启动生产环境
docker-compose --profile prod up -d

# 查看日志
docker-compose --profile prod logs -f memo-mcp-prod

# 停止服务
docker-compose --profile prod down
```

### 3. TCP 模式

```bash
# 启动 TCP 模式
docker-compose --profile tcp up -d

# 查看日志
docker-compose --profile tcp logs -f memo-mcp-tcp

# 停止服务
docker-compose --profile tcp down
```

## 服务说明

### 核心服务

- **memo-mcp-dev**: 开发环境 HTTP 服务器
- **memo-mcp-prod**: 生产环境 HTTP 服务器  
- **memo-mcp-tcp**: TCP 模式服务器

### 可选服务

- **nginx**: Nginx 反向代理
- **redis**: Redis 缓存服务
- **mysql**: MySQL 数据库服务

## 端口映射

| 服务 | 容器端口 | 主机端口 | 说明 |
|------|----------|----------|------|
| memo-mcp-dev | 8888 | 8888 | 开发环境 HTTP |
| memo-mcp-prod | 8888 | 8888 | 生产环境 HTTP |
| memo-mcp-tcp | 8888 | 8889 | TCP 模式 |
| nginx | 80,443 | 80,443 | 反向代理 |
| redis | 6379 | 6379 | 缓存服务 |
| mysql | 3306 | 3306 | 数据库服务 |

## 环境变量

### 通用环境变量

- `APP_ENV`: 应用环境 (development/production)
- `PHP_MEMORY_LIMIT`: PHP 内存限制
- `SERVER_MODE`: 服务器模式 (http/tcp)

### MySQL 环境变量

- `MYSQL_ROOT_PASSWORD`: 根密码
- `MYSQL_DATABASE`: 数据库名
- `MYSQL_USER`: 用户名
- `MYSQL_PASSWORD`: 用户密码

## 数据持久化

### 卷挂载

- `./logs:/var/log/memo-mcp`: 日志文件
- `./data:/var/www/html/data`: 应用数据
- `redis-data:/data`: Redis 数据
- `mysql-data:/var/lib/mysql`: MySQL 数据

### 创建必要目录

```bash
# 创建日志和数据目录
mkdir -p logs data

# 设置权限
chmod 755 logs data
```

## 构建镜像

```bash
# 构建镜像
docker build -t memo-mcp:latest .

# 查看镜像
docker images | grep memo-mcp

# 运行容器
docker run -d -p 8888:8888 --name memo-mcp memo-mcp:latest
```

## 健康检查

容器包含健康检查，可以通过以下命令查看状态：

```bash
# 查看健康状态
docker ps --format "table {{.Names}}\t{{.Status}}"

# 查看健康检查日志
docker inspect memo-mcp-prod | grep -A 10 "Health"
```

## 故障排除

### 1. 端口冲突

如果端口被占用，可以修改 `docker-compose.yml` 中的端口映射：

```yaml
ports:
  - "8889:8888"  # 改为其他端口
```

### 2. 权限问题

```bash
# 修复权限
sudo chown -R $USER:$USER logs data
chmod -R 755 logs data
```

### 3. 内存不足

增加 PHP 内存限制：

```yaml
environment:
  - PHP_MEMORY_LIMIT=2G
```

### 4. 查看详细日志

```bash
# 查看容器日志
docker-compose logs -f [service-name]

# 进入容器调试
docker-compose exec [service-name] sh
```

## 生产部署建议

1. **使用生产环境配置**：
   ```bash
   docker-compose --profile prod up -d
   ```

2. **配置 Nginx 反向代理**：
   ```bash
   docker-compose --profile prod --profile nginx up -d
   ```

3. **启用数据持久化**：
   - 确保 `data` 和 `logs` 目录已创建
   - 配置数据库备份策略

4. **监控和日志**：
   - 配置日志轮转
   - 设置监控告警
   - 定期检查容器健康状态

## 清理资源

```bash
# 停止并删除容器
docker-compose down

# 删除镜像
docker rmi memo-mcp:latest

# 清理未使用的资源
docker system prune -f
```

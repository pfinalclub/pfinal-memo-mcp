# Memo MCP Server 使用指南

## 🐳 环境说明

- **宿主机**: macOS，没有 PHP 环境
- **PHP 环境**: 安装在 Docker 容器中
- **容器 ID**: `d5f7356fe506`

## 🚀 快速开始

### 1. 验证环境

首先确认 Docker 容器正在运行：

```bash
docker ps | grep d5f7356fe506
```

### 2. 测试 MCP 服务器

使用启动脚本测试服务器：

```bash
# 测试初始化
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{"tools":[]},"clientInfo":{"name":"test","version":"1.0.0"}}}' | ./memo-mcp.sh

# 测试工具列表
echo '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | ./memo-mcp.sh

# 测试 memo.list
echo '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"memo.list","arguments":{}}}' | ./memo-mcp.sh
```

### 3. Cursor 配置

您的 Cursor 配置已经正确设置：

```json
{
  "mcpServers": {
    "memo-mcp": {
      "command": "/Users/pfinal/www/pfinal-memo-mcp/memo-mcp.sh",
      "args": [],
      "env": {}
    }
  }
}
```

## 📋 使用方法

### 在 Cursor 中使用

1. **重启 Cursor** - 确保配置生效
2. **验证连接** - 检查 MCP Tools 是否显示为绿色
3. **使用工具**：

#### 获取所有备忘录
```
请使用 memo.list 获取所有备忘录
```

#### 搜索备忘录
```
请使用 memo.search 搜索包含"示例"的备忘录
```

### 命令行使用

#### 直接使用脚本
```bash
# 获取所有备忘录
echo '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"memo.list","arguments":{}}}' | ./memo-mcp.sh

# 搜索备忘录
echo '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"memo.search","arguments":{"keyword":"示例"}}}' | ./memo-mcp.sh
```

#### 使用 Docker 命令
```bash
# 直接使用 Docker 命令
echo '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"memo.list","arguments":{}}}' | docker exec -i d5f7356fe506 php /var/www/html/pfinal-memo-mcp/stdio-server.php
```

## 🔧 故障排除

### 问题：MCP 工具显示红色

**解决方案：**
1. 检查容器是否运行：
   ```bash
   docker ps | grep d5f7356fe506
   ```

2. 测试脚本是否工作：
   ```bash
   ./memo-mcp.sh
   ```

3. 重启 Cursor

### 问题：脚本无法执行

**解决方案：**
1. 检查脚本权限：
   ```bash
   chmod +x memo-mcp.sh
   ```

2. 检查脚本路径：
   ```bash
   ls -la memo-mcp.sh
   ```

### 问题：容器连接失败

**解决方案：**
1. 检查容器状态：
   ```bash
   docker ps
   ```

2. 重启容器：
   ```bash
   docker restart d5f7356fe506
   ```

## 📁 文件说明

### 核心文件
- `memo-mcp.sh` - 启动脚本（桥接宿主机和容器）
- `stdio-server.php` - MCP 服务器实现
- `src/MemoServer.php` - 备忘录服务类
- `src/Models/Memo.php` - 备忘录数据模型

### 配置文件
- `/Users/pfinal/.cursor/mcp.json` - Cursor MCP 配置
- `config/` - 各种配置文件

## 🎯 预期结果

### memo.list 响应
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "success": true,
    "memos": [
      {
        "id": "memo_xxx",
        "content": "这是一个示例备忘录",
        "created_at": "2025-08-22 10:50:56",
        "updated_at": "2025-08-22 10:50:56"
      }
    ],
    "count": 2
  }
}
```

### memo.search 响应
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "result": {
    "success": true,
    "memos": [...],
    "count": 1,
    "keyword": "示例"
  }
}
```

## 💡 最佳实践

1. **使用启动脚本** - `memo-mcp.sh` 提供了最简洁的接口
2. **定期测试** - 确保容器和脚本正常工作
3. **备份配置** - 保存重要的配置文件
4. **监控日志** - 查看容器日志排查问题

## 🔄 更新和维护

### 更新代码
```bash
# 在容器中更新代码
docker exec -it d5f7356fe506 bash
cd /var/www/html/pfinal-memo-mcp
git pull
composer install
```

### 重启服务
```bash
# 重启容器
docker restart d5f7356fe506

# 或重启 MCP 服务
# 在 Cursor 中重新连接
```

现在您可以正常使用 Memo MCP Server 了！

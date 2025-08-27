# Cursor MCP 集成使用说明

## 概述

本项目提供了完整的 Cursor 编辑器集成方案，通过 MCP (Model Context Protocol) 协议连接备忘录服务。

## 架构说明

```
Cursor Editor
    ↓ (stdio)
cursor-stdio-client.js (Node.js 桥接)
    ↓ (WebSocket)
PHP MCP Server (端口 8899)
    ↓
Memo 数据服务
```

## 安装步骤

### 1. 安装 Node.js 依赖

```bash
cd client/node
npm install
```

### 2. 启动 PHP MCP 服务器

```bash
cd /path/to/pfinal-memo-mcp
php server.php
```

服务器将在 `ws://127.0.0.1:8899` 启动。

### 3. 配置 Cursor

将以下配置添加到 Cursor 的 MCP 配置中：

```json
{
  "mcpServers": {
    "memo-server": {
      "command": "node",
      "args": ["/absolute/path/to/cursor-stdio-client.js"],
      "env": {},
      "description": "Memo MCP Server - 备忘录服务",
      "transport": "stdio"
    }
  }
}
```

**重要：** 确保 `args` 中的路径是 `cursor-stdio-client.js` 文件的绝对路径。

## 使用方法

### 在 Cursor 中使用

配置完成后，您可以在 Cursor 中直接使用以下工具：

1. **memo.list** - 获取所有备忘录
2. **memo.search** - 搜索备忘录（需要提供 keyword 参数）

## 文件说明

- `cursor-stdio-client.js` - Cursor stdio 桥接客户端
- `package.json` - Node.js 项目配置
- `README-Cursor.md` - 本说明文档

## 故障排除

### 1. 连接失败

确保 PHP 服务器正在运行：
```bash
php server.php
```

### 2. 端口冲突

如果端口 8899 被占用，可以修改 `server.php` 中的端口配置。

### 3. Node.js 版本

确保使用 Node.js 18.0.0 或更高版本。

### 4. 路径问题

确保 Cursor 配置中的路径是绝对路径，并且文件存在。

## 技术细节

- **传输协议**: WebSocket (服务器) + stdio (Cursor 集成)
- **数据格式**: JSON-RPC 2.0
- **认证**: 无（开发环境）
- **超时**: 5 秒
- **重连**: 自动重连机制
- **WebSocket URL**: `ws://127.0.0.1:8899`

## 开发说明

### 添加新工具

1. 在 `src/MemoServer.php` 中添加新的工具方法
2. 使用 `#[McpTool]` 属性定义工具
3. 使用 `#[Schema]` 属性定义参数

## 更新日志

### v1.0.0
- 初始版本
- 支持 memo.list 和 memo.search 工具
- 完整的 Cursor 集成
- 自动重连和错误处理

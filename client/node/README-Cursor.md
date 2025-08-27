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
npm install
```

### 2. 启动 PHP MCP 服务器

```bash
php server.php start
```

服务器将在 `ws://127.0.0.1:8899/memo` 启动。

### 3. 配置 Cursor

将 `cursor-config.json` 的内容复制到 Cursor 的 MCP 配置中：

1. 打开 Cursor 设置
2. 找到 MCP 配置部分
3. 添加以下配置：

```json
{
    "mcpServers": {
        "memo-server": {
            "command": "node",
            "args": ["cursor-stdio-client.js"],
            "env": {},
            "description": "Memo MCP Server - 备忘录服务",
            "transport": "stdio"
        }
    }
}
```

## 使用方法

### 在 Cursor 中使用

配置完成后，您可以在 Cursor 中直接使用以下工具：

1. **memo.list** - 获取所有备忘录
2. **memo.search** - 搜索备忘录（需要提供 keyword 参数）

### 命令行测试

您也可以使用命令行客户端进行测试：

```bash
# 交互模式
npm run interactive

# 获取所有备忘录
npm run list

# 搜索备忘录
npm run search "关键词"
```

## 文件说明

- `cursor-stdio-client.js` - Cursor stdio 桥接客户端
- `cursor-client.js` - 完整的 WebSocket 客户端（用于测试）
- `cursor-config.json` - Cursor 配置文件
- `package.json` - Node.js 项目配置

## 故障排除

### 1. 连接失败

确保 PHP 服务器正在运行：
```bash
php server.php start
```

### 2. 端口冲突

如果端口 8899 被占用，可以修改 `server.php` 中的端口配置。

### 3. Node.js 版本

确保使用 Node.js 18.0.0 或更高版本。

## 开发说明

### 添加新工具

1. 在 `src/MemoServer.php` 中添加新的工具方法
2. 在 `cursor-stdio-client.js` 的 `handleToolCall` 方法中添加处理逻辑
3. 在 `handleToolList` 方法中添加工具定义

### 调试

使用以下命令启动调试模式：
```bash
node cursor-stdio-client.js
```

## 技术细节

- **传输协议**: WebSocket (服务器) + stdio (Cursor 集成)
- **数据格式**: JSON-RPC 2.0
- **认证**: 无（开发环境）
- **超时**: 10 秒
- **重连**: 自动重连机制

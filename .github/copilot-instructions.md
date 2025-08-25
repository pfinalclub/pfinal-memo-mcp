---
Source: .ruler/instructions.md
---
# Memo MCP Server 使用说明

## 概述
本项目是一个基于 **Workerman** 和 **pfinalclub/php-mcp** 扩展包实现的 **MCP Server**，它提供了一个简易的备忘录（Memo）服务。  
可以通过 MCP 协议调用 `memo.search`、`memo.list` 等工具来管理备忘录。

## 功能列表

1. `memo.search`
   - **描述**: 搜索备忘录
   - **参数**: 无
   - **返回值**: 包含所有备忘录对象的数组

2. `memo.list`
   - **描述**: 获取所有备忘录
   - **参数**: 无
   - **返回值**: 包含所有备忘录对象的数组

## 运行方式
1. 安装依赖
   ```bash
   composer install
  ```
2. 启动 MCP Server

```bash
    php server.php
```
3. MCP Client 可以通过 stdio + JSON-RPC 协议与之交互。


## 目录结构

```
project-root/
├── .ruler/
│   ├── ruler.toml
│   └── instructions.md
├── src/
│   └── MemoServer.php
├── server.php
├── composer.json
└── vendor/

```

### 开发指南
- 所有工具通过 pfinalclub/php-mcp 注册
- 使用 Workerman 实现 MCP 主循环
- 数据暂存可选用内存数组，或后续扩展到 SQLite/MySQL

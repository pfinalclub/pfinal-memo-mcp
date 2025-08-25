# Cursor MCP 配置文件说明

本目录包含用于 Cursor 编辑器的 MCP 服务器配置文件。

## 配置文件说明

### 1. `cursor-recommended-config.json` (推荐)
- **用途**: 推荐的生产环境配置
- **特点**: 使用 Docker 容器中的 stdio-server.php
- **适用场景**: 大多数用户推荐使用此配置

### 2. `cursor-http-config.json` (HTTP 模式)
- **用途**: HTTP 传输模式配置
- **特点**: 通过 HTTP 协议与服务器通信
- **适用场景**: 生产环境部署，支持负载均衡

### 3. `cursor-local-config.json` (本地开发)
- **用途**: 本地开发环境配置
- **特点**: 直接使用本地 PHP 环境
- **适用场景**: 本地有 PHP 环境的开发者

## 使用方法

1. 选择适合您环境的配置文件
2. 将配置内容复制到 Cursor 的设置中：
   - 打开 Cursor 设置 (`Cmd/Ctrl + ,`)
   - 找到 `MCP Servers` 部分
   - 粘贴配置内容
3. 重启 Cursor 以加载新配置

## 配置示例

```json
{
  "mcpServers": {
    "memo-mcp": {
      "command": "docker",
      "args": [
        "exec",
        "-i",
        "d5f7356fe506",
        "php",
        "/var/www/html/pfinal-memo-mcp/stdio-server.php"
      ],
      "env": {}
    }
  }
}
```

## 注意事项

- 确保 Docker 容器正在运行
- 确保容器 ID 正确
- 确保服务器文件路径正确
- 重启 Cursor 后配置才会生效

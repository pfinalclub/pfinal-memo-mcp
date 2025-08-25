# 多客户端 MCP 配置示例

## 概述

`cursor-memo-server.php` 现在支持多个 MCP 客户端，包括 Cursor、VS Code、Neovim 等。服务器会根据客户端类型自动调整配置和响应格式。

## 支持的客户端

### 1. Cursor IDE

**配置文件位置**: `~/.cursor/mcp.json`

```json
{
  "mcpServers": {
    "memo-mcp": {
      "command": "php",
      "args": [
        "/Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server.php"
      ],
      "env": {
        "PHP_MEMORY_LIMIT": "512M"
      }
    }
  }
}
```

### 2. VS Code

**配置文件位置**: `~/.vscode/settings.json` 或工作区设置

```json
{
  "mcp.servers": {
    "memo-mcp": {
      "command": "php",
      "args": [
        "/Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server.php"
      ],
      "env": {
        "PHP_MEMORY_LIMIT": "512M"
      }
    }
  }
}
```

### 3. Neovim

**配置文件位置**: `~/.config/nvim/init.lua` 或 `init.vim`

```lua
-- 使用 nvim-mcp 插件
require('mcp').setup({
  servers = {
    memo_mcp = {
      cmd = { "php", "/Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server.php" },
      env = {
        PHP_MEMORY_LIMIT = "512M"
      }
    }
  }
})
```

### 4. Helix Editor

**配置文件位置**: `~/.config/helix/config.toml`

```toml
[mcp-servers.memo-mcp]
command = "php"
args = ["/Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server.php"]
env = { PHP_MEMORY_LIMIT = "512M" }
```

## 客户端特定功能

### Cursor 特定优化
- 工具描述使用完整的中文描述
- 参数验证更严格
- 支持 `additionalProperties: false`

### VS Code 特定优化
- 添加 JSON Schema 引用
- 兼容 VS Code 的 MCP 扩展

### Neovim 特定优化
- 使用简洁的工具描述
- 优化命令行界面显示

## 测试多客户端支持

运行测试脚本验证多客户端支持：

```bash
php test-multi-client.php
```

## 可用工具

所有客户端都支持以下工具：

1. **memo.list** - 获取所有备忘录
2. **memo.search** - 搜索备忘录
3. **memo.create_prompt** - 获取创建备忘录的提示词
4. **memo.search_prompt** - 获取搜索备忘录的提示词
5. **memo.management_prompt** - 获取备忘录管理相关的提示词
6. **memo.template_prompt** - 获取备忘录模板提示词
7. **memo.help_prompt** - 获取备忘录系统帮助信息

## 故障排除

### 常见问题

1. **连接失败**
   - 检查 PHP 路径是否正确
   - 确保文件有执行权限
   - 验证文件路径是否存在

2. **工具不显示**
   - 重启编辑器
   - 检查 MCP 配置格式
   - 查看编辑器日志

3. **权限问题**
   ```bash
   chmod +x /Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server.php
   ```

### 调试模式

如果需要调试，可以使用调试版本：

```json
{
  "mcpServers": {
    "memo-mcp-debug": {
      "command": "php",
      "args": [
        "/Users/pfinal/www/pfinal-memo-mcp/cursor-memo-server-debug.php"
      ],
      "env": {
        "PHP_MEMORY_LIMIT": "512M"
      }
    }
  }
}
```

## 更新日志

- **v1.0.0**: 初始版本，支持 Cursor
- **v1.1.0**: 添加多客户端支持
- **v1.2.0**: 优化客户端特定配置

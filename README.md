# Memo MCP Server

一个基于 **Workerman** 和 **pfinalclub/php-mcp** 扩展包实现的 **MCP Server**，提供简易的备忘录（Memo）查询服务。

## 功能特性

- ✅ 获取所有备忘录 (`memo.list`)
- ✅ 搜索备忘录 (`memo.search`)
- ✅ 备忘录创建提示 (`memo.create_prompt`)
- ✅ 备忘录搜索提示 (`memo.search_prompt`)
- ✅ 备忘录管理指南 (`memo.management_prompt`)
- ✅ 备忘录模板 (`memo.template_prompt`)
- ✅ 系统帮助信息 (`memo.help_prompt`)
- ✅ 完整的错误处理
- ✅ 数据验证
- ✅ 支持 MCP 协议

## 系统要求

- PHP >= 8.2
- Composer
- Workerman >= 4.0

## 安装

1. 克隆项目
```bash
git clone <repository-url>
cd pfinal-memo-mcp
```

2. 安装依赖
```bash
composer install
```


## 运行

### 方案一：Cursor 集成（推荐）

启动轻量级 MCP Server（避免 Workerman 常驻进程）：
```bash
php cursor-memo-server.php
```

或者使用启动脚本：
```bash
./start-cursor-server.sh
```

这个版本不使用 Workerman，直接处理 stdio 通信，避免常驻进程问题。

### 方案二：本地开发

启动简化的 MCP Server：
```bash
php stdio-server.php
```

这个版本直接使用 stdio 传输，更轻量级且稳定。

### 方案二：Docker 部署

1. 本地 Docker 部署
```bash
chmod +x deploy.sh
./deploy.sh local
```

2. 生产环境部署
```bash
./deploy.sh prod
```

### 方案三：HTTP 模式部署

启动 HTTP 服务器：
```bash
php http-server.php
```

这个版本支持 HTTP 协议，适合线上部署。

### 方案四：使用完整版服务器

启动完整的 MCP Server：
```bash
php server.php
```

这个版本使用 `pfinalclub/php-mcp` 包，提供完整的 MCP 协议支持和高级功能。

## 测试

### 1. 轻量级服务器测试（推荐）
```bash
php tests/test-cursor-server.php
```

### 2. 基本功能测试
```bash
php tests/test-mcp.php
```

### 3. 兼容性测试
```bash
php test-fix.php
```

### 4. 服务器测试
```bash
php test-server.php
```

## 使用示例

### 获取所有备忘录
```json
{
  "method": "tools/call",
  "params": {
    "name": "memo.list",
    "arguments": {}
  }
}
```

### 搜索备忘录
```json
{
  "method": "tools/call",
  "params": {
    "name": "memo.search",
    "arguments": {
      "keyword": "测试"
    }
  }
}
```

## 项目结构

```
pfinal-memo-mcp/
├── .ruler/              # 项目规则配置
│   ├── ruler.toml
│   └── instructions.md
├── src/                 # 源代码
│   ├── MemoServer.php   # 主服务器类
│   └── Models/
│       └── Memo.php     # 备忘录模型
├── tests/               # 测试文件
│   └── test-mcp.php     # MCP 协议测试
├── server.php           # 完整版服务器（推荐）
├── stdio-server.php     # 简化版服务器
├── test-fix.php         # 兼容性测试
├── test-server.php      # 服务器测试
├── fix-php-compatibility.php # 兼容性修复脚本
├── composer.json        # 依赖管理
└── README.md           # 项目说明
```

## 配置说明

### 完整版服务器配置
`server.php` 使用 `pfinalclub/php-mcp` 包，需要完整的配置参数：

```php
$config = new ServerConfig([
    'transport' => 'stdio',        // 传输协议
    'host' => '127.0.0.1',         // 主机地址
    'port' => 8080,                // 端口号
    'log_level' => 'info',         // 日志级别
    'session' => [                 // 会话配置
        'backend' => 'memory',
        'ttl' => 3600
    ],
    'security' => [                // 安全配置
        'rate_limit' => 100,
        'rate_window' => 60
    ],
    'performance' => [             // 性能配置
        'max_connections' => 1000,
        'timeout' => 30
    ]
]);
```

## 故障排除

### 问题：`Property cannot have type ?callable`
**原因：** PHP 版本兼容性问题
**解决方案：** 运行 `php fix-php-compatibility.php` 修复兼容性问题

### 问题：`php server.php` 没有反应
**原因：** 配置参数不完整
**解决方案：** 确保提供完整的配置参数，包括 session、security、performance 等

### 问题：配置验证失败
**原因：** 缺少必需的配置项
**解决方案：** 参考上面的配置示例，提供所有必需的参数

## 开发

### 添加新功能
1. 在 `MemoServer.php` 中添加新的方法
2. 使用 `#[McpTool]` 属性定义工具
3. 更新服务器中的路由逻辑
4. 更新文档

### 数据持久化
当前使用内存存储，可以扩展支持：
- SQLite
- MySQL
- Redis
- 文件存储

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request！
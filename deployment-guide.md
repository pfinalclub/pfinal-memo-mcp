# MCP 服务器部署指南

## 方案一：本地开发环境

### 当前配置（推荐）
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

**优点**：
- 简单可靠
- 不需要额外进程管理
- 适合个人开发

**缺点**：
- 依赖 Docker 容器
- 只能在本地使用

## 方案二：容器化部署（推荐用于团队）

### 1. 创建 Dockerfile
```dockerfile
FROM php:8.3-cli

WORKDIR /app

# 安装依赖
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制项目文件
COPY . /app/

# 安装 PHP 依赖
RUN composer install --no-dev --optimize-autoloader

# 暴露端口（如果需要 HTTP 模式）
EXPOSE 8080

# 启动命令
CMD ["php", "stdio-server.php"]
```

### 2. 创建 docker-compose.yml
```yaml
version: '3.8'

services:
  memo-mcp-server:
    build: .
    container_name: memo-mcp-server
    ports:
      - "8080:8080"  # 如果需要 HTTP 模式
    volumes:
      - ./data:/app/data
    restart: unless-stopped
    stdin_open: true
    tty: true
```

### 3. Cursor/VSCode 配置
```json
{
  "mcpServers": {
    "memo-mcp": {
      "command": "docker",
      "args": [
        "exec",
        "-i",
        "memo-mcp-server",
        "php",
        "/app/stdio-server.php"
      ],
      "env": {}
    }
  }
}
```

## 方案三：HTTP 模式部署（推荐用于线上）

### 1. 创建 HTTP 服务器版本
```php
<?php
// http-server.php
require_once __DIR__ . '/vendor/autoload.php';

use PFinal\Memo\MemoServer;
use Workerman\Worker;
use Workerman\Protocols\Http;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

$memoServer = new MemoServer();

$worker = new Worker('http://0.0.0.0:8080');
$worker->count = 4;

$worker->onMessage = function($connection, Request $request) use ($memoServer) {
    $content = $request->rawBody();
    $data = json_decode($content, true);
    
    if (!$data) {
        $connection->send(new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid JSON'])));
        return;
    }
    
    // 处理 MCP 请求
    $response = handleMcpRequest($memoServer, $data);
    
    $connection->send(new Response(200, ['Content-Type' => 'application/json'], json_encode($response)));
};

Worker::runAll();

function handleMcpRequest($memoServer, $data) {
    $method = $data['method'] ?? '';
    $params = $data['params'] ?? [];
    
    switch ($method) {
        case 'initialize':
            return [
                'jsonrpc' => '2.0',
                'id' => $data['id'],
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => ['tools' => ['listChanged' => false]],
                    'serverInfo' => ['name' => 'Memo MCP Server', 'version' => '1.0.0']
                ]
            ];
            
        case 'tools/list':
            return [
                'jsonrpc' => '2.0',
                'id' => $data['id'],
                'result' => [
                    'tools' => [
                        [
                            'name' => 'memo.list',
                            'description' => '获取所有备忘录',
                            'inputSchema' => ['type' => 'object', 'properties' => []]
                        ],
                        [
                            'name' => 'memo.search',
                            'description' => '搜索备忘录',
                            'inputSchema' => ['type' => 'object', 'properties' => []]
                        ]
                    ]
                ]
            ];
            
        case 'tools/call':
            $toolName = $params['name'] ?? '';
            $arguments = $params['arguments'] ?? [];
            
            switch ($toolName) {
                case 'memo.list':
                    $result = $memoServer->listMemos();
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $data['id'],
                        'result' => [
                            'content' => [
                                ['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE)]
                            ]
                        ]
                    ];
                    
                case 'memo.search':
                    $result = $memoServer->searchMemos();
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $data['id'],
                        'result' => [
                            'content' => [
                                ['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE)]
                            ]
                        ]
                    ];
                    
                default:
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $data['id'],
                        'error' => ['code' => -32601, 'message' => 'Method not found']
                    ];
            }
            
        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $data['id'],
                'error' => ['code' => -32601, 'message' => 'Method not found']
            ];
    }
}
```

### 2. 部署到服务器
```bash
# 构建镜像
docker build -t memo-mcp-server .

# 运行容器
docker run -d \
  --name memo-mcp-server \
  -p 8080:8080 \
  -v $(pwd)/data:/app/data \
  --restart unless-stopped \
  memo-mcp-server
```

### 3. Cursor/VSCode 配置（HTTP 模式）
```json
{
  "mcpServers": {
    "memo-mcp": {
      "command": "curl",
      "args": [
        "-X",
        "POST",
        "-H",
        "Content-Type: application/json",
        "-d",
        "@-",
        "http://your-server:8080"
      ],
      "env": {}
    }
  }
}
```

## 方案四：云服务部署

### 1. 使用 Docker Hub
```bash
# 推送镜像到 Docker Hub
docker tag memo-mcp-server your-username/memo-mcp-server
docker push your-username/memo-mcp-server
```

### 2. 使用云服务（如 AWS ECS、Google Cloud Run）
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  memo-mcp-server:
    image: your-username/memo-mcp-server:latest
    ports:
      - "8080:8080"
    environment:
      - NODE_ENV=production
    restart: unless-stopped
```

### 3. 使用 Kubernetes
```yaml
# k8s-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: memo-mcp-server
spec:
  replicas: 3
  selector:
    matchLabels:
      app: memo-mcp-server
  template:
    metadata:
      labels:
        app: memo-mcp-server
    spec:
      containers:
      - name: memo-mcp-server
        image: your-username/memo-mcp-server:latest
        ports:
        - containerPort: 8080
        env:
        - name: NODE_ENV
          value: "production"
---
apiVersion: v1
kind: Service
metadata:
  name: memo-mcp-server-service
spec:
  selector:
    app: memo-mcp-server
  ports:
  - port: 80
    targetPort: 8080
  type: LoadBalancer
```

## 推荐方案

### 个人开发：方案一（当前）
- 简单可靠
- 适合个人使用

### 团队开发：方案二
- 容器化部署
- 便于团队共享

### 线上服务：方案三或四
- HTTP 模式更稳定
- 支持负载均衡
- 便于监控和管理

## 安全考虑

1. **认证机制**：添加 API Key 或 JWT 认证
2. **HTTPS**：使用 SSL/TLS 加密
3. **限流**：添加请求频率限制
4. **日志**：记录访问日志
5. **监控**：添加健康检查和监控

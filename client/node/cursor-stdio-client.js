import WebSocket from "ws";

// --- 工具方法 ---
function writeResponse(response) {
  const payload = JSON.stringify(response);
  debugLog("Writing response to stdout:", payload);
  
  // 发送纯 JSON 格式，不带 Content-Length 头部
  process.stdout.write(payload + '\n');
  
  debugLog("Response written successfully");
}

function debugLog(...args) {
  console.error("[DEBUG]", ...args);
}

// --- 主类 ---
class CursorStdioBridge {
  constructor(wsUrl) {
    this.wsUrl = wsUrl;
    this.ws = null;
    this.buffer = "";
    this.pendingRequests = {};
    this.isConnected = false;
    this.connectionTimeout = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
  }

  async connect() {
    debugLog("Attempting to connect to:", this.wsUrl);
    
    // 设置连接超时
    this.connectionTimeout = setTimeout(() => {
      if (!this.isConnected) {
        debugLog("Connection timeout, retrying...");
        this.handleConnectionFailure();
      }
    }, 5000);

    this.ws = new WebSocket(this.wsUrl);

    this.ws.on("open", () => {
      clearTimeout(this.connectionTimeout);
      this.isConnected = true;
      this.reconnectAttempts = 0;
      debugLog("WebSocket connected to:", this.wsUrl);
      this.setupWsHandling();
    });

    this.ws.on("close", () => {
      this.isConnected = false;
      debugLog("WebSocket disconnected, retrying in 3s...");
      setTimeout(() => this.connect(), 3000);
    });

    this.ws.on("error", (err) => {
      debugLog("WebSocket error:", err.message);
      this.handleConnectionFailure();
    });
  }

  handleConnectionFailure() {
    clearTimeout(this.connectionTimeout);
    this.isConnected = false;
    
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      debugLog(`Reconnection attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts}`);
      setTimeout(() => this.connect(), 3000);
    } else {
      debugLog("Max reconnection attempts reached");
    }
  }

  setupStdioHandling() {
    console.error("[DEBUG] Setting up stdio handling...");
    
    let buffer = "";
    process.stdin.on("data", (chunk) => {
      buffer += chunk.toString();
      debugLog("Received stdin chunk, buffer length:", buffer.length);
      
      // 尝试解析纯 JSON 格式（Cursor 可能使用这种格式）
      const lines = buffer.split('\n');
      buffer = lines.pop() || ''; // 保留最后一行（可能不完整）
      
      for (const line of lines) {
        if (line.trim()) {
          try {
            const parsed = JSON.parse(line);
            debugLog("Parsed JSON message:", parsed.method, "ID:", parsed.id);
            this.handleStdioMessage(parsed);
          } catch (error) {
            debugLog("Failed to parse JSON line:", line);
          }
        }
      }
      
      // 如果还有数据，尝试标准 MCP stdio 格式
      if (buffer) {
        while (true) {
          const match = buffer.match(/Content-Length: (\d+)\r\n\r\n/);
          if (!match) {
            debugLog("No Content-Length header found in remaining buffer");
            break;
          }
          
          const contentLength = parseInt(match[1]);
          const headerEnd = buffer.indexOf("\r\n\r\n") + 4;
          const messageStart = headerEnd;
          const messageEnd = messageStart + contentLength;
          
          if (buffer.length < messageEnd) {
            debugLog("Message incomplete, waiting for more data");
            break;
          }
          
          const message = buffer.substring(messageStart, messageEnd);
          buffer = buffer.substring(messageEnd);
          
          try {
            const parsed = JSON.parse(message);
            debugLog("Parsed MCP stdio message:", parsed.method, "ID:", parsed.id);
            this.handleStdioMessage(parsed);
          } catch (error) {
            console.error("[ERROR] Failed to parse MCP stdio message:", error);
            console.error("[ERROR] Message was:", message);
          }
        }
      }
    });
    
    console.error("[DEBUG] Stdio handling setup complete");
  }

  setupWsHandling() {
    this.ws.on("message", (data) => {
      try {
        const msg = JSON.parse(data.toString());
        debugLog("Received from WS:", msg);
        
        // 如果是响应消息，直接转发给 Cursor
        if (msg.id !== undefined) {
          writeResponse(msg);
          // 清理待处理请求
          delete this.pendingRequests[msg.id];
        } else if (msg.method === "notifications/initialized") {
          // 忽略 notifications/initialized 消息，不返回任何响应
          debugLog("Ignoring notifications/initialized message");
          return;
        } else if (msg.method === "initialized") {
          // 不需要响应
          return;
        } else {
          // 其他消息作为事件转发
          writeResponse({
            jsonrpc: "2.0",
            method: "event/ws_message",
            params: msg,
          });
        }
      } catch (err) {
        debugLog("Failed to parse WebSocket message:", err.message);
      }
    });

    setInterval(() => {
      if (this.ws.readyState === WebSocket.OPEN) {
        this.ws.ping();
      }
    }, 30000);
  }

  handleStdioMessage(msg) {
    debugLog("Processing stdio message:", msg.method, "ID:", msg.id);
    
    if (msg.method === "initialize") {
      debugLog("Handling initialize request...");
      const response = {
        jsonrpc: "2.0",
        id: msg.id,
        result: {
          protocolVersion: "2024-11-05",
          capabilities: {
            tools: {}
          },
          serverInfo: { name: "cursor-bridge", version: "0.1.0" },
        },
      };
      debugLog("Sending initialize response:", response);
      writeResponse(response);
      debugLog("Initialize response sent successfully");
    } else if (msg.method === "tools/list") {
      // 如果 WebSocket 未连接，返回错误
      if (!this.isConnected || this.ws?.readyState !== WebSocket.OPEN) {
        const response = {
          jsonrpc: "2.0",
          id: msg.id,
          error: { 
            code: -32603, 
            message: "WebSocket not connected. Please ensure the PHP MCP server is running on ws://127.0.0.1:8899" 
          },
        };
        writeResponse(response);
        return;
      }

      // 转发 tools/list 请求到 WebSocket 服务器
      this.ws.send(JSON.stringify(msg));
      this.pendingRequests[msg.id] = msg;
      
      // 设置超时处理
      setTimeout(() => {
        if (this.pendingRequests[msg.id]) {
          debugLog(`Request timeout for ID: ${msg.id}`);
          delete this.pendingRequests[msg.id];
          
          // 返回超时错误
          const response = {
            jsonrpc: "2.0",
            id: msg.id,
            error: { 
              code: -32603, 
              message: "Request timeout. WebSocket server did not respond." 
            },
          };
          writeResponse(response);
        }
      }, 5000); // 5秒超时
      
    } else if (msg.method === "tools/call") {
      // 如果 WebSocket 未连接，返回错误
      if (!this.isConnected || this.ws?.readyState !== WebSocket.OPEN) {
        const response = {
          jsonrpc: "2.0",
          id: msg.id,
          error: { 
            code: -32603, 
            message: "WebSocket not connected. Please ensure the PHP MCP server is running on ws://127.0.0.1:8899" 
          },
        };
        writeResponse(response);
        return;
      }

      // 转发 tools/call 请求到 WebSocket 服务器
      this.ws.send(JSON.stringify(msg));
      this.pendingRequests[msg.id] = msg;
      
      // 设置超时处理
      setTimeout(() => {
        if (this.pendingRequests[msg.id]) {
          debugLog(`Request timeout for ID: ${msg.id}`);
          delete this.pendingRequests[msg.id];
          
          // 返回超时错误
          const response = {
            jsonrpc: "2.0",
            id: msg.id,
            error: { 
              code: -32603, 
              message: "Request timeout. WebSocket server did not respond." 
            },
          };
          writeResponse(response);
        }
      }, 5000); // 5秒超时
      
    } else if (msg.method === "notifications/initialized") {
      // 忽略 notifications/initialized 消息，不返回任何响应
      debugLog("Ignoring notifications/initialized message");
      return;
    } else if (msg.method === "initialized") {
      // 不需要响应
      return;
    } else {
      const response = {
        jsonrpc: "2.0",
        id: msg.id,
        error: { code: -32601, message: "Method not found" },
      };
      writeResponse(response);
    }
  }
}

// --- 启动 ---
const wsUrl = "ws://127.0.0.1:8899";
const bridge = new CursorStdioBridge(wsUrl);

// 立即设置 stdio 处理，不等待 WebSocket 连接
bridge.setupStdioHandling();

// 发送启动完成通知
console.error("[DEBUG] Cursor stdio client starting...");

// 然后尝试连接 WebSocket
bridge.connect();

// 添加进程事件处理
process.on('uncaughtException', (err) => {
  console.error('[ERROR] Uncaught Exception:', err);
  process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('[ERROR] Unhandled Rejection at:', promise, 'reason:', reason);
  process.exit(1);
});

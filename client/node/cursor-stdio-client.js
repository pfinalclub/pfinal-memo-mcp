import WebSocket from "ws";

// --- 工具方法 ---
function writeResponse(response) {
  const json = JSON.stringify(response);
  const payload = `Content-Length: ${Buffer.byteLength(
    json,
    "utf8"
  )}\r\n\r\n${json}`;
  process.stdout.write(payload);
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
  }

  async connect() {
    this.ws = new WebSocket(this.wsUrl);

    this.ws.on("open", () => {
      debugLog("WebSocket connected to:", this.wsUrl);
      this.setupStdioHandling();
      this.setupWsHandling();
    });

    this.ws.on("close", () => {
      debugLog("WebSocket disconnected, retrying in 3s...");
      setTimeout(() => this.connect(), 3000);
    });

    this.ws.on("error", (err) => {
      debugLog("WebSocket error:", err.message);
    });
  }

  setupStdioHandling() {
    process.stdin.setEncoding("utf8");
    process.stdin.on("data", (chunk) => {
      this.buffer += chunk;

      while (true) {
        const headerEnd = this.buffer.indexOf("\r\n\r\n");
        if (headerEnd === -1) break;

        const header = this.buffer.substring(0, headerEnd);
        const match = header.match(/Content-Length: (\d+)/i);
        if (!match) {
          this.buffer = this.buffer.substring(headerEnd + 4);
          continue;
        }

        const length = parseInt(match[1], 10);
        const start = headerEnd + 4;
        if (this.buffer.length < start + length) break;

        const message = this.buffer.substring(start, start + length);
        this.buffer = this.buffer.substring(start + length);

        try {
          const msg = JSON.parse(message);
          debugLog("Received from Cursor:", msg);
          this.handleStdioMessage(msg);
        } catch (err) {
          debugLog("Failed to parse message:", err.message);
        }
      }
    });
  }

  setupWsHandling() {
    this.ws.on("message", (data) => {
      try {
        const msg = JSON.parse(data.toString());
        debugLog("Received from WS:", msg);
        // 转发给 Cursor
        writeResponse({
          jsonrpc: "2.0",
          method: "event/ws_message",
          params: msg,
        });
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
    if (msg.method === "initialize") {
      const response = {
        jsonrpc: "2.0",
        id: msg.id,
        result: {
          protocolVersion: "2024-11-05",
          capabilities: {},
          serverInfo: { name: "cursor-bridge", version: "0.1.0" },
        },
      };
      writeResponse(response);
    } else if (msg.method === "tools/list") {
      const response = {
        jsonrpc: "2.0",
        id: msg.id,
        result: {
          tools: [
            {
              name: "send_to_ws",
              description: "Forward message to WebSocket",
              inputSchema: {
                type: "object",
                properties: {
                  data: { type: "string" },
                },
                required: ["data"],
              },
            },
          ],
        },
      };
      writeResponse(response);
    } else if (msg.method === "tools/call") {
      if (
        msg.params?.name === "send_to_ws" &&
        this.ws?.readyState === WebSocket.OPEN
      ) {
        this.ws.send(JSON.stringify(msg.params.arguments));
        const response = {
          jsonrpc: "2.0",
          id: msg.id,
          result: {
            content: [{ type: "text", text: "Message sent to WebSocket" }],
          },
        };
        writeResponse(response);
      }
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
const wsUrl = "ws://127.0.0.1:8899/ws";
const bridge = new CursorStdioBridge(wsUrl);
bridge.connect();

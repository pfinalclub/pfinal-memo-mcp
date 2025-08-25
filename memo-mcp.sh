#!/bin/bash
# 重定向 stderr 到 /dev/null，避免调试信息干扰
exec docker exec -i d5f7356fe506 php /var/www/html/pfinal-memo-mcp/stdio-server.php 2>/dev/null

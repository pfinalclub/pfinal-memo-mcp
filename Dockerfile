# 使用官方 PHP 8.2 CLI 镜像作为基础镜像
FROM php:8.2-cli-alpine

# 设置工作目录
WORKDIR /var/www/html/pfinal-memo-mcp

# 安装系统依赖
RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    zip \
    pdo_mysql \
    && docker-php-ext-enable \
    zip \
    pdo_mysql

# 安装 pcntl 扩展（需要额外的依赖）
RUN apk add --no-cache \
    linux-headers \
    && docker-php-ext-install pcntl \
    && docker-php-ext-enable pcntl

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制 composer 文件
COPY composer.json composer.lock ./

# 安装 PHP 依赖
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 复制项目文件
COPY . .

# 设置权限
RUN chmod -R 755 /var/www/html/pfinal-memo-mcp

# 创建日志目录
RUN mkdir -p /var/log/pfinal-memo-mcp

# 暴露端口
EXPOSE 8888

# 健康检查（暂时禁用，因为 Workerman 本身很稳定）
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD ps aux | grep "http-server.php" | grep -v grep || exit 1

# 启动命令
CMD ["php", "http-server.php", "start"]

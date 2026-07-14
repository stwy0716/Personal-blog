# 个人主页系统 Dockerfile
# 基于 PHP 8.1 + Apache

FROM php:8.1-apache

# 安装系统依赖和 PHP 扩展
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install -j$(nproc) \
        mbstring \
        zip \
    && rm -rf /var/lib/apt/lists/*

# 启用 Apache mod_rewrite（用于 URL 伪静态）
RUN a2enmod rewrite

# 复制项目文件到 Apache 文档根目录
COPY . /var/www/html/

# 设置目录权限
# data/   - 存储 JSON 数据文件，需要可写
# uploads/ - 存储用户上传的文件，需要可写
# backups/ - 存储备份文件，需要可写
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/backups

# 设置 Apache 默认文档
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 暴露 80 端口
EXPOSE 80
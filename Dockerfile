# PHP 8.2 CLI
FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files
COPY . /app

# Set working directory
WORKDIR /app

# Expose port (Railway will set $PORT)
EXPOSE 8080

# Start PHP built-in server
# Railway sets PORT environment variable
CMD php -S 0.0.0.0:${PORT:-8080}

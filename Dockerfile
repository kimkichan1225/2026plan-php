# PHP 8.2 CLI
FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files
COPY . /app

# Set working directory
WORKDIR /app

# Make start script executable
RUN chmod +x /app/start.sh

# Expose port (Railway will set $PORT)
EXPOSE 8080

# Start PHP server using start script
CMD ["/app/start.sh"]

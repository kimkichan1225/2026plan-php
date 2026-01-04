#!/bin/sh

# Railway sets PORT environment variable
# Default to 8080 if not set
PORT=${PORT:-8080}

echo "Starting PHP server on 0.0.0.0:$PORT"

# Start PHP built-in server
exec php -S 0.0.0.0:$PORT

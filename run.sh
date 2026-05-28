#!/bin/bash
echo "Starting Orion Config Pro..."
python3 main.py > server.log 2>&1 &
echo "Starting PHP Development Server on http://localhost:8080"
php -S localhost:8080 -t .

#!/bin/bash

# Database initialization script
echo "Initializing SQLite database..."

# Remove any existing database files
rm -f database/database.sqlite
rm -f database/database.sqlite-shm
rm -f database/database.sqlite-wal

# Create new database file
touch database/database.sqlite

# Set proper permissions
chmod 664 database/database.sqlite

# Initialize the database with proper SQLite settings
sqlite3 database/database.sqlite << 'EOF'
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
PRAGMA cache_size = -64000;
PRAGMA temp_store = MEMORY;
PRAGMA busy_timeout = 60000;
PRAGMA foreign_keys = ON;
VACUUM;
EOF

echo "Database initialized successfully!"
echo "You can now run: php artisan migrate:fresh --seed" 
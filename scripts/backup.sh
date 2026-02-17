#!/bin/bash
# ===========================================
# Backup Script for Tzucha System
# ===========================================

# Configuration
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/site/backups"  # Azure: /home/site/backups
DB_NAME="tzucha"
DB_USER="root"
DB_PASS="your-password-here"  # Change this!
DB_HOST="localhost"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Backup database
echo "Backing up database..."
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

if [ $? -eq 0 ]; then
    echo "✅ Database backup completed: db_$DATE.sql.gz"
else
    echo "❌ Database backup failed!"
    exit 1
fi

# Backup uploads directory
echo "Backing up uploads..."
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C /path/to/tzucha uploads/

if [ $? -eq 0 ]; then
    echo "✅ Uploads backup completed: uploads_$DATE.tar.gz"
else
    echo "❌ Uploads backup failed!"
fi

# Keep only last 30 days of backups
echo "Cleaning old backups (keeping last 30 days)..."
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "uploads_*.tar.gz" -mtime +30 -delete

echo "✅ Backup process completed!"
echo "Files saved to: $BACKUP_DIR"

# Optional: Send notification
# curl -X POST "https://your-webhook-url" -d "Backup completed: $DATE"

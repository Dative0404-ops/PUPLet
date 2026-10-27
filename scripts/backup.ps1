# backup.ps1
# This script performs a backup of the Supabase PostgreSQL database.
# Requirement: 'pg_dump' must be installed and in your PATH.

$HOST = "db.upjzwlhopfdrnqiicuqc.supabase.co"
$USER = "postgres"
$DB = "postgres"
$PORT = "5432"
$FILENAME = "database_backup_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".sql"

Write-Host "Starting backup for $HOST..." -ForegroundColor Cyan

# Set the PGPASSWORD environment variable temporarily for pg_dump
$env:PGPASSWORD = "@Icepie09459963502"

try {
    pg_dump -h $HOST -p $PORT -U $USER -d $DB -f $FILENAME
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Backup successful! Saved as: $FILENAME" -ForegroundColor Green
    } else {
        Write-Host "Backup failed with exit code: $LASTEXITCODE" -ForegroundColor Red
    }
} catch {
    Write-Host "Error: pg_dump not found or failed to execute." -ForegroundColor Red
} finally {
    $env:PGPASSWORD = $null
}

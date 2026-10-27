# restore.ps1
# This script restores a SQL backup to the Supabase PostgreSQL database.
# Requirement: 'psql' must be installed and in your PATH.

if ($args.Count -eq 0) {
    Write-Host "Usage: .\restore.ps1 <backup_file.sql>" -ForegroundColor Yellow
    exit
}

$FILENAME = $args[0]
$HOST = "db.upjzwlhopfdrnqiicuqc.supabase.co"
$USER = "postgres"
$DB = "postgres"
$PORT = "5432"

if (-Not (Test-Path $FILENAME)) {
    Write-Host "Error: File $FILENAME not found." -ForegroundColor Red
    exit
}

Write-Host "Starting restore from $FILENAME to $HOST..." -ForegroundColor Cyan

# Set the PGPASSWORD environment variable temporarily for psql
$env:PGPASSWORD = "@Icepie09459963502"

try {
    psql -h $HOST -p $PORT -U $USER -d $DB -f $FILENAME
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Restore successful!" -ForegroundColor Green
    } else {
        Write-Host "Restore failed with exit code: $LASTEXITCODE" -ForegroundColor Red
    }
} catch {
    Write-Host "Error: psql not found or failed to execute." -ForegroundColor Red
} finally {
    $env:PGPASSWORD = $null
}

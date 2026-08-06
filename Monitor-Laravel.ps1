# Monitor-Laravel.ps1
# Script de monitoreo del contenedor Laravel desde PowerShell (SPRINT 2)

$containerName = "laravel-app"

Write-Host "=================================================" -ForegroundColor Cyan
Write-Host "  MONITOR LARAVEL - SPRINT 2 (Sistemas Operativos)" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host ""

$status = docker inspect -f '{{.State.Status}}' $containerName 2>$null

if (-not $status) {
    Write-Host "ERROR: El contenedor '$containerName' no existe." -ForegroundColor Red
    Write-Host "Ejecuta 'docker-compose up -d' primero desde WSL." -ForegroundColor Yellow
    exit 1
}

if ($status -ne "running") {
    Write-Host "ADVERTENCIA: El contenedor existe pero su estado es: $status" -ForegroundColor Yellow
    exit 1
}

Write-Host "Contenedor '$containerName' esta corriendo." -ForegroundColor Green
Write-Host ""

Write-Host "--- Estado de los daemons (Supervisor) ---" -ForegroundColor Cyan
docker exec $containerName supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status

Write-Host ""

Write-Host "--- Ultimas lecturas de monitor.log ---" -ForegroundColor Cyan
docker exec $containerName tail -n 10 storage/logs/monitor.log

Write-Host ""

Write-Host "--- Verificando acceso web (http://localhost:8000) ---" -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000" -UseBasicParsing -TimeoutSec 5
    Write-Host "Servidor respondio con codigo: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "No se pudo conectar al servidor Laravel." -ForegroundColor Red
}

Write-Host ""
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host "  Monitoreo completado - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

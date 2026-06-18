# Configuración de Colas de Laravel - Sistema de Citas

## Descripción General

El sistema utiliza colas de Laravel con prioridades para procesar tareas en segundo plano:

- **Cola HIGH (Alta Prioridad)**: Notificaciones de citas inmediatas, enlaces de Zoom, confirmaciones urgentes
- **Cola LOW (Baja Prioridad)**: Reportes semanales, auditorías, tareas de mantenimiento

## Configuración en EC2

### 1. Instalación de Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### 2. Copiar archivos de configuración

```bash
sudo cp supervisor/laravel-queue-high.conf /etc/supervisor/conf.d/
sudo cp supervisor/laravel-queue-low.conf /etc/supervisor/conf.d/
```

### 3. Crear directorio de logs

```bash
sudo mkdir -p /var/log/laravel
sudo chown www-data:www-data /var/log/laravel
sudo chmod 755 /var/log/laravel
```

### 4. Recargar Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-high:*
sudo supervisorctl start laravel-queue-low:*
```

### 5. Verificar estado

```bash
sudo supervisorctl status
```

## Uso en la Aplicación

### Enviar tareas a cola HIGH (Notificaciones de citas)

```php
use App\Notifications\NewAppointmentNotification;
use Illuminate\Support\Facades\Notification;

Notification::route('mail', $user->email)
    ->queue('high')
    ->notify(new NewAppointmentNotification($appointment));
```

### Enviar tareas a cola LOW (Reportes)

```php
use App\Jobs\GenerateWeeklyReport;

GenerateWeeklyReport::dispatch($clinic)
    ->onQueue('low');
```

## Monitoreo

### Ver logs en tiempo real

```bash
# Cola HIGH
tail -f /var/log/laravel/queue-high.log

# Cola LOW
tail -f /var/log/laravel/queue-low.log
```

### Reiniciar workers

```bash
sudo supervisorctl restart laravel-queue-high:*
sudo supervisorctl restart laravel-queue-low:*
```

### Detener workers

```bash
sudo supervisorctl stop laravel-queue-high:*
sudo supervisorctl stop laravel-queue-low:*
```

## Configuración de Variables de Entorno

En tu archivo `.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=mysql
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90
```

## Parámetros de Configuración

### Cola HIGH
- **Procesos**: 4 workers
- **Sleep**: 3 segundos (verifica más frecuentemente)
- **Timeout**: 90 segundos
- **Reintentos**: 3 intentos

### Cola LOW
- **Procesos**: 2 workers
- **Sleep**: 5 segundos (verifica menos frecuentemente)
- **Timeout**: 300 segundos (5 minutos)
- **Reintentos**: 3 intentos

## Troubleshooting

### Los jobs no se procesan

1. Verificar que Supervisor está corriendo:
   ```bash
   sudo service supervisor status
   ```

2. Verificar logs:
   ```bash
   sudo tail -f /var/log/laravel/queue-high.log
   sudo tail -f /var/log/laravel/queue-low.log
   ```

3. Verificar tabla de jobs:
   ```bash
   php artisan queue:failed
   ```

### Limpiar jobs fallidos

```bash
php artisan queue:flush
php artisan queue:failed-table
```

### Reintentar jobs fallidos

```bash
php artisan queue:retry all
```

## Escalabilidad

Para aumentar la capacidad:

1. Aumentar `numprocs` en los archivos de configuración de Supervisor
2. Recargar Supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   ```

## Integración con CI/CD

En tu pipeline de deployment, asegúrate de:

1. Copiar archivos de Supervisor
2. Recargar Supervisor
3. Verificar que los workers están corriendo

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessPendingZoomMeetings;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Le dice a Laravel que ejecute el Job cada 10 minutos.
// withoutOverlapping(): si una corrida anterior sigue activa (ej. Zoom respondió
// lento, o hay muchos pendientes), la siguiente NO arranca encima.
// El lock se libera automáticamente si el job se cae sin terminar; el segundo
// parámetro (en minutos) es un tope de seguridad por si el lock queda "pegado".
Schedule::job(new ProcessPendingZoomMeetings)
    ->everyTenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer(); // si en algún momento tienes más de un servidor/EC2 corriendo el scheduler

    //se crea un archivo para usar 2gb de disco por si se acaba la RAM
    //La Swap de 2GB (Ya configurada): Si el backup consume mucha memoria, la Swap absorberá el impacto impidiendo que MySQL se apague.
    //sudo dd if=/dev/zero of=/swapfile bs=1M count=2048


// Realiza un backup de la DB diariamente a las 12:00 AM y limpia los antiguos
Schedule::command('backup:clean')
    ->timezone('America/Bogota') // 👈 Crucial para que corra a tu hora local real
    ->daily()
    ->at('00:00')
    ->withoutOverlapping(); // Evita que se solapen si tarda de más

Schedule::command('backup:run --only-db')
    ->timezone('America/Bogota') // 👈 Crucial
    ->daily()
    ->at('00:05')
    ->withoutOverlapping();

// Limpia el caché de ciudades a las 3 AM diariamente
Schedule::call(function () {
    Cache::tags(['geonames_cities'])->flush();
    \Log::info('✅ Caché de ciudades limpiado exitosamente');
})
->timezone('America/Bogota')
->daily()
->at('03:00')
->name('clear-cities-cache')
->withoutOverlapping();
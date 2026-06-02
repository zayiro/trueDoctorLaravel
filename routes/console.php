<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessPendingZoomMeetings;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Le dice a Laravel que ejecute el Job cada 10 minutos
Schedule::job(new ProcessPendingZoomMeetings)->everyTenMinutes();

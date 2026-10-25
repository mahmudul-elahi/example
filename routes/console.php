<?php

declare(strict_types=1);

use App\Models\OtpCode;
use App\Models\Story;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('model:prune', ['--model' => [OtpCode::class]])->daily();
Schedule::command('model:prune', ['--model' => [Story::class]])->hourly();

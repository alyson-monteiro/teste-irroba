<?php

//php artisan schedule:work

use Illuminate\Support\Facades\Schedule;

Schedule::command('sqs:listen')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

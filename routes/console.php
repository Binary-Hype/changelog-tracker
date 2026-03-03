<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:check-releases')->everyFifteenMinutes();
Schedule::command('app:retry-notifications')->hourly();

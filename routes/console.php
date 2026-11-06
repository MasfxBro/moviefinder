<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('catalog:refresh --limit=100')->dailyAt('03:00');
Schedule::command('usage:prune')->dailyAt('03:30');
Schedule::command('users:prune-unverified')->dailyAt('03:50');

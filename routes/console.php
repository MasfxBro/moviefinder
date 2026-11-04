<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('users:prune-unverified')->dailyAt('03:50');

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AiMonitoringService;
use Illuminate\View\View;

class AdminAiMonitoringController extends Controller
{
    public function __invoke(AiMonitoringService $monitoring): View
    {
        return view('admin.ai-monitoring.index', [
            'profitByPlan' => $monitoring->profitByPlan(),
        ]);
    }
}

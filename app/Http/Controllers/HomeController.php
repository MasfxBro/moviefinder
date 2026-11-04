<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly Settings $settings
    ) {}

    public function __invoke(Request $request): View
    {
        if ($request->user()) {
            return view('home');
        }

        $plans = [
            'free' => $this->getPlanData('free'),
            'plus' => $this->getPlanData('plus'),
            'pro' => $this->getPlanData('pro'),
        ];

        $posters = Cache::remember('v1:landing:posters', 86400, function () {
            try {
                return DB::table('titles')
                    ->whereNotNull('poster_path')
                    ->orderByDesc('popularity')
                    ->limit(8)
                    ->get(['title', 'poster_path']);
            } catch (\Exception $e) {
                return collect();
            }
        });

        return view('welcome', [
            'plans' => $plans,
            'posters' => $posters,
        ]);
    }

    private function getPlanData(string $key): array
    {
        return [
            'price' => $this->settings->get("plan.$key.price_idr"),
            'daily_units' => $this->settings->get("plan.$key.daily_units"),
            'smart_searches' => $this->settings->get("plan.$key.smart_searches_daily"),
            'results_per_answer' => $this->settings->get("plan.$key.title_cards"),
            'payment_methods' => config("billing.methods.$key.manual", []),
        ];
    }
}

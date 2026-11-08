<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $build = file_exists(base_path('BUILD_ID'))
            ? trim((string) file_get_contents(base_path('BUILD_ID')))
            : (string) config('app.build', 'dev');

        return response()->json([
            'build' => $build !== '' ? $build : 'dev',
        ])->header('Cache-Control', 'no-store');
    }
}

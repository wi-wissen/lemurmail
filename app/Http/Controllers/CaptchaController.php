<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;

/** Challenge endpoint of the ALTCHA widget. */
class CaptchaController extends Controller
{
    public function __invoke(CaptchaService $captcha): JsonResponse
    {
        return response()->json($captcha->challenge())->header('Cache-Control', 'no-store');
    }
}

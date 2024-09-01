<?php

namespace Psi\S3EventSns\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psi\S3EventSns\Services\AwsSnsService;
use Throwable;

class SnsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AwsSnsService $service): JsonResponse
    {
        try {
            $service->handle();

            return response()->json(['success' => true]);

        } catch (Throwable $e) {

            return response()->json(['error' => $e->getMessage()], 500);

        }

    }
}

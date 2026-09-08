<?php

namespace App\Http\Controllers\Api\v1\Pos;

use App\Http\Controllers\Controller;
use App\Services\Fiscal\FiscalLedgerService;
use App\Models\DailyClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DayClosureApiController extends Controller
{
    public function __construct(
        protected FiscalLedgerService $fiscalService
    ) {}

    /**
     * GET /api/v1/pos/z-closure/summary
     */
    public function getShiftSummary(Request $request): JsonResponse
    {
        $summary = $this->fiscalService->getShiftSummary();
        return response()->json($summary, 200);
    }

    /**
     * POST /api/v1/pos/z-closure/confirm
     */
    public function closeDay(Request $request): JsonResponse
    {
        try {
            $closure = $this->fiscalService->processDailyClosure();

            return response()->json([
                'success' => true,
                'message' => "Z-Report #{$closure->z_number} generated and emailed successfully!",
                'closure' => [
                    'id'        => $closure->id,
                    'z_number'  => $closure->z_number,
                    'total_ttc' => round((float) $closure->total_ttc, 2),
                    'total_ht'  => round((float) $closure->total_ht, 2),
                    'total_tva' => round((float) $closure->total_tva, 2),
                    'hash'      => $closure->hash,
                    'closed_at' => $closure->closed_at->toISOString(),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/pos/z-closure/history
     */
    public function getClosureHistory(Request $request): JsonResponse
    {
        $closures = DailyClosure::orderBy('z_number', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $closures->count(),
            'data'    => $closures,
        ], 200);
    }
}
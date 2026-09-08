<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Fiscal\FiscalLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DailyClosureController extends Controller
{
    public function __construct(
        protected FiscalLedgerService $fiscalService
    ) {}

    /**
     * Process and generate Daily Z-Report from Admin Dashboard.
     */
    public function closeDay(Request $request): RedirectResponse
    {
        try {
            $closure = $this->fiscalService->processDailyClosure();

            return redirect()->back()->with(
                'success',
                "Z-Report #{$closure->z_number} generated successfully! All open tickets have been sealed and archived."
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
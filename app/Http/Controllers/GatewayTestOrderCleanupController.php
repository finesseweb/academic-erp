<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Services\TestDataCleanupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GatewayTestOrderCleanupController extends Controller
{
    public function __construct(private readonly TestDataCleanupService $service) {}

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('test_data_cleanup.manage'), 403);

        $request->validate([
            'confirmation_code' => ['required', 'string', 'in:GATEWAY-TEST-ORDERS'],
        ]);

        $university = University::query()->firstOrFail();
        $deleted = $this->service->cleanupGatewayTestOrders($university->id, $request->user()->id);

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Gateway Test Orders cleaned successfully ({$deleted} record(s)). Gateway configuration was preserved.",
        ]);
    }
}

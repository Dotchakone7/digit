<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Server-to-server notifications from payment providers. */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway, PaymentManager $gateways, PaymentService $payments): JsonResponse
    {
        if (! $gateways->isAvailable($gateway)) {
            return response()->json(['message' => 'Unknown gateway'], 404);
        }

        try {
            $result = $gateways->gateway($gateway)->parseWebhook($request);
            $payment = $payments->handleWebhook($gateway, $result);
        } catch (InvalidWebhookSignature $e) {
            Log::warning('Rejected payment webhook', ['gateway' => $gateway, 'ip' => $request->ip(), 'reason' => $e->getMessage()]);

            return response()->json(['message' => 'Invalid signature'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Unknown payment'], 404);
        }

        return response()->json(['received' => true, 'status' => $payment->status->value]);
    }
}

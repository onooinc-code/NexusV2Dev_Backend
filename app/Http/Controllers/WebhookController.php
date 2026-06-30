<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\PeopleConnect\WahaWebhookIngestionService;

class WebhookController extends Controller
{
    protected WahaWebhookIngestionService $ingestionService;

    public function __construct(WahaWebhookIngestionService $ingestionService)
    {
        $this->ingestionService = $ingestionService;
    }

    public function handleWahaWebhook(Request $request)
    {
        // 1. Validate shared-secret / signature header
        $expectedSecret = app(\App\Services\SettingCacheService::class)->get('waha_webhook_secret', config('services.waha.webhook_secret'));
        
        if ($expectedSecret) {
            $rawPayload = $request->getContent();
            $verified = false;

            // Try validating using X-Webhook-Hmac header (HMAC-SHA512 of raw request body)
            if ($request->hasHeader('x-webhook-hmac')) {
                $providedHmac = $request->header('x-webhook-hmac');
                $calculatedHmac = hash_hmac('sha512', $rawPayload, $expectedSecret);
                if (hash_equals($calculatedHmac, $providedHmac)) {
                    $verified = true;
                }
            }
            // Try validating using X-WAHA-Signature header (HMAC-SHA256 of raw request body)
            elseif ($request->hasHeader('x-waha-signature')) {
                $providedHmac = $request->header('x-waha-signature');
                $calculatedHmac = hash_hmac('sha256', $rawPayload, $expectedSecret);
                if (hash_equals($calculatedHmac, $providedHmac)) {
                    $verified = true;
                }
            }
            // Try validating using X-Hub-Signature-256 header (HMAC-SHA256 of raw request body, optionally prefixed with 'sha256=')
            elseif ($request->hasHeader('x-hub-signature-256')) {
                $providedHmac = $request->header('x-hub-signature-256');
                if (str_starts_with($providedHmac, 'sha256=')) {
                    $providedHmac = substr($providedHmac, 7);
                }
                $calculatedHmac = hash_hmac('sha256', $rawPayload, $expectedSecret);
                if (hash_equals($calculatedHmac, $providedHmac)) {
                    $verified = true;
                }
            }
            // Fallback: Check for secret token in query parameters or headers (basic matching)
            else {
                $providedSecret = $request->header('x-waha-webhook-secret') 
                               ?? $request->header('authorization')
                               ?? $request->query('secret')
                               ?? $request->query('token');

                if ($providedSecret) {
                    $cleanSecret = str_ireplace('Bearer ', '', $providedSecret);
                    if (hash_equals($expectedSecret, $cleanSecret)) {
                        $verified = true;
                    }
                }
            }

            if (!$verified) {
                Log::warning('WAHA Webhook rejected: Invalid signature or secret', [
                    'ip' => $request->ip(),
                    'has_x_webhook_hmac' => $request->hasHeader('x-webhook-hmac'),
                    'has_x_waha_signature' => $request->hasHeader('x-waha-signature'),
                    'has_x_hub_signature_256' => $request->hasHeader('x-hub-signature-256'),
                ]);
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        // 2. Validate payload structure
        $validated = $request->validate([
            'event' => 'required|string',
            'session' => 'required|string',
            'payload' => 'required|array',
            'payload.id' => 'required_if:event,message|string',
            'payload.timestamp' => 'sometimes|integer',
            'payload.from' => 'sometimes|string',
            'payload.to' => 'sometimes|string',
            'payload.body' => 'sometimes|string',
        ]);

        // 3. Delegate to ingestion service
        try {
            $this->ingestionService->ingest($request->all());
            
            return response()->json([
                'message' => 'Webhook payload queued for processing'
            ], 202);
            
        } catch (\Exception $e) {
            Log::error('WAHA Webhook ingestion failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
}

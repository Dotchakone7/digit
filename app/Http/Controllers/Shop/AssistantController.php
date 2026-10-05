<?php

namespace App\Http\Controllers\Shop;

use App\Assistant\AssistantManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistantController extends Controller
{
    public function message(Request $request, AssistantManager $assistant): JsonResponse
    {
        abort_unless($assistant->enabled(), 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        try {
            $reply = $assistant->driver()->reply($data['message'], $request->user());
        } catch (Throwable $e) {
            Log::error('Assistant failure', ['error' => $e->getMessage()]);

            return response()->json(['text' => 'Je rencontre un souci technique. Contactez notre service client.', 'links' => [['label' => 'Nous contacter', 'url' => route('contact')]]]);
        }

        return response()->json($reply->toArray());
    }
}

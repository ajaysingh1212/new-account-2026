<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiBusinessAssistant;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function index()
    {
        return view('admin.ai.index');
    }

    public function chat(Request $request, AiBusinessAssistant $assistant)
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:12'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $latest = collect($data['messages'])->where('role', 'user')->last()['content'] ?? '';
            if (preg_match('/(?<!\d)(\d{8,16})(?!\d)/u', $latest, $match)
                && preg_match('/serial|crm|stock|diagnos|history|सीरियल|क्रमांक|स्टॉक/iu', $latest)) {
                $serial = $match[1];
                $isHindi = preg_match('/[\x{0900}-\x{097F}]/u', $latest)
                    || preg_match('/\b(kya|batao|bataiye|kab|hua|tha|thi|pahle|pehle|stock|hai|hoga|kyun)\b/i', $latest);
                return response()->json(['reply' => $assistant->diagnoseSerialReply($request->user(), $serial, (bool) $isHindi)]);
            }
            return response()->json($assistant->reply($request->user(), $data['messages']));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => $e->getMessage() ?: 'AI request failed.'], 502);
        }
    }

    public function confirm(Request $request, string $draft, AiBusinessAssistant $assistant)
    {
        return response()->json($assistant->confirmDraft($request->user(), $draft), 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;

class ReferralController extends Controller
{
    public function attach(Request $request, ReferralService $referralService): JsonResponse {
        $master = $request->attributes->get('current_master');

        if (empty($master)) {
            return response()->json(['error' => 'master not found'], 401);
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $referral = $referralService->registerReferral($master, $data['code']);

        if (empty($referral)) {
            return response()->json(['error' => 'invalid code'], 422);
        }

        return response()->json([
            'id' => $referral->id,
            'referred_master_id' => $referral->referred_master_id,
            'referrer_master_id' => $referral->referrer_master_id,
            'status' => $referral->status,
        ]);
    }
}

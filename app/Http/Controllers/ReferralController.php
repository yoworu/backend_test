<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\Referral;
use App\Models\ReferralEarning;
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

    public function my(Request $request): JsonResponse
    {
        $master = $request->attributes->get('current_master');

        if (empty($master)) {
            return response()->json(['error' => 'master not found'], 401);
        }

        $referrals = $master->referrals()->with('referredMaster')->get()->map(fn (Referral $referral) => [
            'name' => $referral->referredMaster->name,
            'attached_at' => $referral->created_at,
            'counted' => $referral->status == Referral::STATUS_REWARDED,
            'earned' => ReferralEarning::query()
                ->where('referral_earnings.referrer_master_id', $master->id)
                ->where('referral_earnings.referred_master_id', $referral->referred_master_id),
        ]);

        return response()->json([
            'referrals' => $referrals,
        ]);

    }

    public function earnings(Request $request): JsonResponse
    {
        $master = $request->attributes->get('current_master');

        if (empty($master)) {
            return response()->json(['error' => 'master not found'], 401);
        }

        return response()->json([
            'total' => $master->referralEarnings()->sum('amount'),
            'pending' => $master->referralEarnings()->where('status', ReferralEarning::STATUS_PENDING)->sum('amount'),
            'paid' => $master->referralEarnings()->where('status', ReferralEarning::STATUS_PAID)->sum('amount'),
            'counted' => $master->referrals()->active()->count(),
        ]);
    }
}

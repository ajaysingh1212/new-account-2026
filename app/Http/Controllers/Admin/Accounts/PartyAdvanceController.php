<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\EntryVisibilityService;
use App\Services\PartyAdvanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyAdvanceController extends Controller
{
    public function available(Request $request, EntryVisibilityService $visibility, PartyAdvanceService $advances)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'flow' => ['required', Rule::in(['sales','purchase'])],
        ]);

        $party = $visibility->scopeForUser(Party::query(), Party::class)->findOrFail($data['party_id']);
        $direction = $data['flow'] === 'sales' ? 'in' : 'out';

        return response()->json([
            'party' => [
                'id' => $party->id,
                'display_name' => $party->display_name,
            ],
            'advances' => $advances->availableForParty($party->id, $direction),
        ]);
    }
}

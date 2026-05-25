<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $buyers = $visibility->scopeForUser(Buyer::with('creator')->latest(), Buyer::class)->get();
        return view('admin.buyers.index', compact('buyers'));
    }

    public function create()
    {
        return view('admin.buyers.create', ['buyerCode' => $this->nextNo()]);
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'buyer_code' => ['nullable','max:40'],
            'name' => ['required','max:255'],
            'phone' => ['nullable','max:50'],
            'email' => ['nullable','email','max:255'],
            'address' => ['nullable','string'],
            'status' => ['required','in:active,inactive'],
        ]);

        $buyer = Buyer::create(array_merge($data, [
            'company_id' => auth()->user()->current_company_id,
            'buyer_code' => $data['buyer_code'] ?: $this->nextNo(),
            'created_by' => auth()->id(),
        ]));

        $visibility->syncFromRequest($request, $buyer);

        return redirect()->route('admin.buyers.index')->with('success', 'Buyer master saved.');
    }

    public function edit(Buyer $buyer, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($buyer);
        return view('admin.buyers.edit', compact('buyer'));
    }

    public function update(Request $request, Buyer $buyer, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($buyer);
        $data = $request->validate([
            'buyer_code' => ['required','max:40'],
            'name' => ['required','max:255'],
            'phone' => ['nullable','max:50'],
            'email' => ['nullable','email','max:255'],
            'address' => ['nullable','string'],
            'status' => ['required','in:active,inactive'],
        ]);

        $buyer->update($data);
        $visibility->syncFromRequest($request, $buyer);

        return redirect()->route('admin.buyers.index')->with('success', 'Buyer master updated.');
    }

    private function nextNo(): string
    {
        $count = Buyer::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'BUY-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}

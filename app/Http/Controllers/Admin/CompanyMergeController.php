<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyMergeController extends Controller
{
    public function index()
    {
        $merges = CompanyMerge::with(['company', 'mergedWith', 'creator'])->latest()->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('admin.company-merges.index', compact('merges', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id'            => ['required', 'exists:companies,id'],
            'merged_with_company_id'=> ['required', 'exists:companies,id', 'different:company_id'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        // Prevent duplicate (both directions)
        $exists = CompanyMerge::where(function ($q) use ($data) {
            $q->where('company_id', $data['company_id'])
              ->where('merged_with_company_id', $data['merged_with_company_id']);
        })->orWhere(function ($q) use ($data) {
            $q->where('company_id', $data['merged_with_company_id'])
              ->where('merged_with_company_id', $data['company_id']);
        })->exists();

        if ($exists) {
            return back()->withErrors(['company_id' => 'Ye merge already exist karta hai.'])->withInput();
        }

        CompanyMerge::create(array_merge($data, ['created_by' => auth()->id()]));

        return redirect()->route('admin.company-merges.index')->with('success', 'Companies merge ho gayi.');
    }

    public function destroy(CompanyMerge $companyMerge)
    {
        request()->validate([
            'super_admin_password' => ['required', 'string'],
        ]);

        abort_unless(Hash::check(request('super_admin_password'), auth()->user()->password), 403, 'Super admin password galat hai.');

        $companyMerge->delete();
        return back()->with('success', 'Merge hata diya gaya.');
    }
}

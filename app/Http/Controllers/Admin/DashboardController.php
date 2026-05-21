<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $stats = [];

        if ($user->isSuperAdmin()) {
            $stats = [
                'companies'   => Company::count(),
                'users'       => User::count(),
                'roles'       => Role::count(),
                'admins'      => User::where('user_type', 'admin')->count(),
                'active_companies' => Company::where('is_active', true)->count(),
            ];
            $recentLogs = AuditLog::with('user','company')
                ->latest('created_at')->take(15)->get();
            $companies = Company::withCount('users')->latest()->take(6)->get();
        } else {
            $companyId = $user->current_company_id;
            $stats = [
                'users'  => User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId))->count(),
                'roles'  => Role::where('company_id', $companyId)->count(),
                'active' => User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId))
                                ->where('is_active', true)->count(),
            ];
            $recentLogs = AuditLog::with('user')
                ->where('company_id', $companyId)
                ->latest('created_at')->take(10)->get();
            $companies = collect();
        }

        return view('admin.dashboard', compact('stats','recentLogs','companies'));
    }
}

<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;

class AuditLogController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $query = AuditLog::with('user','company')->latest('created_at');

        if (!$authUser->isSuperAdmin()) {
            $query->where('company_id', $authUser->current_company_id);
        }

        if (request('user_id'))    $query->where('user_id', request('user_id'));
        if (request('action'))     $query->where('action', request('action'));
        if (request('date_from'))  $query->whereDate('created_at', '>=', request('date_from'));
        if (request('date_to'))    $query->whereDate('created_at', '<=', request('date_to'));

        $logs = $query->paginate(25);
        $users = $authUser->isSuperAdmin() ? User::all() : User::whereHas('userRoles', fn($q) => $q->where('company_id', $authUser->current_company_id))->get();
        $companies = $authUser->isSuperAdmin() ? Company::all() : collect();

        return view('admin.audit-logs.index', compact('logs','users','companies'));
    }
}

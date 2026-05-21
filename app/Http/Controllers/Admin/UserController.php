<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        if ($authUser->isSuperAdmin()) {
            $users = User::with('currentCompany')->latest()->paginate(15);
        } else {
            $companyId = $authUser->current_company_id;
            $users = User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId))
                ->with(['userRoles' => fn($q) => $q->where('company_id', $companyId)->with('role')])
                ->latest()->paginate(15);
        }
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $authUser = auth()->user();
        $companies = $authUser->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $authUser->current_company_id)->get();

        $roles = $authUser->isSuperAdmin()
            ? Role::with('company')->get()
            : Role::where('company_id', $authUser->current_company_id)->get();

        return view('admin.users.create', compact('companies','roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:8|confirmed',
            'user_type'          => 'required|in:admin,user',
            'company_id'         => 'required|exists:companies,id',
            'role_ids'           => 'nullable|array',
            'role_ids.*'         => 'exists:roles,id',
            'phone'              => 'nullable|string|max:20',
        ]);

        $authUser = auth()->user();

        // Super admin can create admins; company admin can only create users
        if (!$authUser->isSuperAdmin() && $request->user_type === 'super_admin') {
            abort(403);
        }

        $user = User::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'password'           => Hash::make($request->password),
            'user_type'          => $request->user_type,
            'phone'              => $request->phone,
            'current_company_id' => $request->company_id,
            'is_active'          => true,
        ]);

        // Attach company
        UserCompany::create(['user_id' => $user->id, 'company_id' => $request->company_id]);

        // Attach roles
        if ($request->role_ids) {
            foreach ($request->role_ids as $roleId) {
                UserRole::create([
                    'user_id'    => $user->id,
                    'role_id'    => $roleId,
                    'company_id' => $request->company_id,
                ]);
            }
        }

        AuditLog::log('created', [
            'model'       => User::class,
            'model_id'    => $user->id,
            'description' => "User created: {$user->name}",
            'new_values'  => $user->toArray(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        $authUser = auth()->user();
        $companies = $authUser->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $authUser->current_company_id)->get();

        $roles = $authUser->isSuperAdmin()
            ? Role::with('company')->get()
            : Role::where('company_id', $authUser->current_company_id)->get();

        $userRoleIds = UserRole::where('user_id', $user->id)
            ->where('company_id', $user->current_company_id)
            ->pluck('role_id')->toArray();

        return view('admin.users.edit', compact('user','companies','roles','userRoleIds'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|min:8|confirmed',
            'user_type'  => 'required|in:admin,user',
            'company_id' => 'required|exists:companies,id',
            'role_ids'   => 'nullable|array',
        ]);

        $old = $user->toArray();
        $data = $request->only('name','email','user_type','phone','is_active');
        $data['current_company_id'] = $request->company_id;
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        // Update roles
        UserRole::where('user_id', $user->id)->where('company_id', $request->company_id)->delete();
        if ($request->role_ids) {
            foreach ($request->role_ids as $roleId) {
                UserRole::create(['user_id' => $user->id, 'role_id' => $roleId, 'company_id' => $request->company_id]);
            }
        }

        AuditLog::log('updated', [
            'model' => User::class, 'model_id' => $user->id,
            'description' => "User updated: {$user->name}",
            'old_values' => $old, 'new_values' => $user->fresh()->toArray(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) abort(403, 'Cannot delete Super Admin.');

        AuditLog::log('deleted', ['model' => User::class, 'model_id' => $user->id, 'description' => "User deleted: {$user->name}"]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted!');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['status' => $user->is_active, 'message' => 'Status updated']);
    }
}

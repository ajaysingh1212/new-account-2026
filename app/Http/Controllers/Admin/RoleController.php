<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $roles = $user->isSuperAdmin()
            ? Role::with('company','permissions')->withCount('userRoles')->latest()->paginate(15)
            : Role::where('company_id', $user->current_company_id)
                ->with('permissions')->withCount('userRoles')->latest()->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = $this->assignablePermissions()->groupBy('module');
        $companies = auth()->user()->isSuperAdmin()
            ? Company::where('is_active', true)->orderBy('name')->get()
            : Company::where('id', auth()->user()->current_company_id)->get();

        return view('admin.roles.create', compact('permissions','companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'company_id'      => 'nullable|exists:companies,id',
            'permission_ids' => 'nullable|array',
        ]);

        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? $request->company_id : $user->current_company_id;
        abort_if(!$companyId, 422, 'Company is required for role creation.');

        $role = Role::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'description'=> $request->description,
            'company_id' => $companyId,
        ]);

        $role->permissions()->sync($this->allowedPermissionIds($request->permission_ids ?? []));

        AuditLog::log('created', ['model' => Role::class, 'model_id' => $role->id, 'description' => "Role created: {$role->name}"]);
        return redirect()->route('admin.roles.index')->with('success', 'Role created!');
    }

    public function edit(Role $role)
    {
        $this->authorizeCompany($role);
        $permissions = $this->assignablePermissions()->groupBy('module');
        $companies = auth()->user()->isSuperAdmin()
            ? Company::where('is_active', true)->orderBy('name')->get()
            : Company::where('id', auth()->user()->current_company_id)->get();
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role','permissions','rolePermissionIds','companies'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeCompany($role);
        $request->validate(['name' => 'required|string|max:255']);

        $role->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $role->permissions()->sync($this->allowedPermissionIds($request->permission_ids ?? []));

        AuditLog::log('updated', ['model' => Role::class, 'model_id' => $role->id, 'description' => "Role updated: {$role->name}"]);
        return redirect()->route('admin.roles.index')->with('success', 'Role updated!');
    }

    public function destroy(Role $role)
    {
        $this->authorizeCompany($role);
        $role->delete();
        AuditLog::log('deleted', ['model' => Role::class, 'model_id' => $role->id, 'description' => "Role deleted: {$role->name}"]);
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted!');
    }

    private function authorizeCompany(Role $role): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $role->company_id !== $user->current_company_id) {
            abort(403);
        }
    }

    private function assignablePermissions()
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return Permission::orderBy('module')->orderBy('name')->get();
        }

        $managementModules = ['users','roles','permissions','companies','audit'];

        return Permission::whereNotIn('module', $managementModules)
            ->whereIn('id', function ($query) use ($user) {
            $roleIds = $user->userRoles()
                ->where('company_id', $user->current_company_id)
                ->select('role_id');

            $query->select('permission_id')
                ->from('role_permission')
                ->whereIn('role_id', $roleIds);
        })->orderBy('module')->orderBy('name')->get();
    }

    private function allowedPermissionIds(array $permissionIds): array
    {
        $requested = array_map('intval', $permissionIds);
        $allowed = $this->assignablePermissions()->pluck('id')->map(fn($id) => (int) $id)->all();

        return array_values(array_intersect($requested, $allowed));
    }
}

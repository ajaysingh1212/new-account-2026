<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'permission_ids' => 'nullable|array',
        ]);

        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? $request->company_id : $user->current_company_id;

        $role = Role::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'description'=> $request->description,
            'company_id' => $companyId,
        ]);

        if ($request->permission_ids) {
            $role->permissions()->sync($request->permission_ids);
        }

        AuditLog::log('created', ['model' => Role::class, 'model_id' => $role->id, 'description' => "Role created: {$role->name}"]);
        return redirect()->route('admin.roles.index')->with('success', 'Role created!');
    }

    public function edit(Role $role)
    {
        $this->authorizeCompany($role);
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role','permissions','rolePermissionIds'));
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

        $role->permissions()->sync($request->permission_ids ?? []);

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
}

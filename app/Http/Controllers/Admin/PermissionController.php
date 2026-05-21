<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        $modules = Permission::distinct()->pluck('module');
        return view('admin.permissions.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|unique:permissions,slug',
            'module' => 'required|string|max:100',
        ]);

        Permission::create($request->only('name','slug','module','description'));
        return redirect()->route('admin.permissions.index')->with('success', 'Permission created!');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted!');
    }
}

<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('createdBy')->withCount('users','roles')->latest()->paginate(12);
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email',
            'phone'           => 'nullable|string|max:20',
            'gst_number'      => 'nullable|string|max:20',
            'admin_name'      => 'required|string|max:255',
            'admin_email'     => 'required|email|unique:users,email',
            'admin_password'  => 'required|min:8',
            'logo'            => 'nullable|image|max:2048',
            'has_crm_access'  => 'nullable|boolean',
        ]);

        // Upload logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        // Create company
        $company = Company::create(array_merge(
            $request->only('name','email','phone','address','gst_number','pan_number','website','currency'),
            [
                'logo' => $logoPath,
                'has_crm_access' => $request->boolean('has_crm_access', true),
                'created_by' => auth()->id(),
            ]
        ));

        // Create admin user
        $admin = User::create([
            'name'               => $request->admin_name,
            'email'              => $request->admin_email,
            'password'           => Hash::make($request->admin_password),
            'user_type'          => 'admin',
            'current_company_id' => $company->id,
            'is_active'          => true,
        ]);

        UserCompany::create(['user_id' => $admin->id, 'company_id' => $company->id]);
        $adminRole = $this->ensureCompanyAdminRole($company->id);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $adminRole->id, 'company_id' => $company->id]);

        AuditLog::log('created', ['model' => Company::class, 'model_id' => $company->id, 'description' => "Company created: {$company->name}"]);
        return redirect()->route('admin.companies.index')->with('success', 'Company & Admin created!');
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'has_crm_access' => 'nullable|boolean',
        ]);

        $data = $request->only('name','email','phone','address','gst_number','pan_number','website','currency','is_active');
        $data['has_crm_access'] = $request->boolean('has_crm_access');

        if ($request->hasFile('logo')) {
            if ($company->logo) Storage::disk('public')->delete($company->logo);
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);
        return redirect()->route('admin.companies.index')->with('success', 'Company updated!');
    }

    public function destroy(Company $company)
    {
        request()->validate([
            'super_admin_password' => ['required', 'string'],
        ]);

        abort_unless(Hash::check(request('super_admin_password'), auth()->user()->password), 403, 'Super admin password galat hai.');

        $company->delete();
        return redirect()->route('admin.companies.index')->with('success', 'Company deleted!');
    }

    private function ensureCompanyAdminRole(int $companyId): Role
    {
        $role = Role::firstOrCreate(
            ['company_id' => $companyId, 'slug' => 'company-admin'],
            ['name' => 'Company Admin', 'description' => 'Default full-access admin role for this company.', 'is_active' => true]
        );

        $role->permissions()->sync(Permission::whereNotIn('module', ['permissions','companies'])->pluck('id')->all());

        return $role;
    }
}

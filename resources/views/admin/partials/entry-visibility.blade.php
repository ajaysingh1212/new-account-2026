    @php
        $entry = $entry ?? new \App\Models\SalesInvoice();
    @endphp
    @php
        $currentUser = auth()->user();
        $companyId = $entry->company_id ?? $currentUser->current_company_id;
        $entryVisibility = $entry->exists
            ? \App\Models\EntryVisibility::where('entry_type', $entry::class)->where('entry_id', $entry->getKey())->first()
            : null;
        $selectedRoles = array_map('intval', old('visible_to_roles', $entryVisibility->visible_to_roles ?? []));
        $selectedUsers = array_map('intval', old('visible_to_users', $entryVisibility->visible_to_users ?? []));
        $visibilityRoles = $companyId ? \App\Models\Role::where('company_id', $companyId)->orderBy('name')->get() : collect();
        $visibilityUsers = $companyId ? \App\Models\User::where('current_company_id', $companyId)->where('id', '!=', $currentUser->id)->orderBy('name')->get() : collect();
    @endphp
    @if(($currentUser->isAdmin() || $currentUser->isSuperAdmin()) && $companyId)
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white">
            <strong><i class="fas fa-eye mr-1 text-primary"></i> Entry Visibility</strong>
        </div>
        <div class="card-body">
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="visibleToAllCompany" name="visible_to_all_company" value="1" @checked(old('visible_to_all_company', $entryVisibility?->visible_to_all_company))>
                <label class="custom-control-label" for="visibleToAllCompany">Visible to all users in this company</label>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Visible To Roles</label>
                    <select name="visible_to_roles[]" class="form-control select2" multiple style="width:300px;">
                        @foreach($visibilityRoles as $role)
                            <option value="{{ $role->id }}" @selected(in_array((int) $role->id, $selectedRoles, true))>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Visible To Specific Users</label>
                    <select name="visible_to_users[]" class="form-control select2" multiple style="width:300px;">
                        @foreach($visibilityUsers as $user)
                            <option value="{{ $user->id }}" @selected(in_array((int) $user->id, $selectedUsers, true))>{{ $user->name }} | {{ $user->email }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <small class="text-muted">Admin and Super Admin can always see company entries. Normal users see their own entries plus entries shared here.</small>
        </div>
    </div>
    @endif

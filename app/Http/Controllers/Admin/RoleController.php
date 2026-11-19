<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\ActivityLog;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);

        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get()]);
    }

    public function create(Request $request)
    {
        $this->guard($request);

        return $this->form(new Role);
    }

    public function store(RoleRequest $request)
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ActivityLog::record('role_created', null, 'Role '.$role->name.' created');

        return redirect()->route('admin.roles.edit', $role)->with('success', 'Role created.');
    }

    public function edit(Request $request, Role $role)
    {
        $this->guard($request);

        return $this->form($role->load('permissions'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        abort_if($role->name === 'Super Admin', 403, 'The Super Admin role always has every permission.');
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ActivityLog::record('role_updated', null, 'Role '.$role->name.' updated');

        return back()->with('success', 'Role saved.');
    }

    public function destroy(Request $request, Role $role)
    {
        $this->guard($request);
        abort_if(in_array($role->name, array_keys(Permissions::defaultRoles()), true), 403, 'Built-in roles cannot be deleted.');
        $role->delete();
        ActivityLog::record('role_deleted', null, 'Role '.$role->name.' deleted');

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    private function form(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'resources' => Permissions::CONTENT_RESOURCES,
            'actions' => Permissions::ACTIONS,
            'special' => Permissions::SPECIAL,
            'granted' => $role->exists ? $role->permissions->pluck('name')->all() : [],
        ]);
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('roles.manage'), 403);
    }
}

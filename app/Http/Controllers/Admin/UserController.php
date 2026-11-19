<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', ['items' => User::with('roles:id,name')->orderBy('name')->paginate(30)]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', ['item' => new User(['is_active' => true]), 'roles' => $this->roles()]);
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $user = User::create(collect($data)->except(['roles', 'password_confirmation'])->all());
        $user->syncRoles($this->allowedRoles($data['roles']));
        ActivityLog::record('user_created', $user, 'User created', ['roles' => $data['roles']]);

        return redirect()->route('admin.users.edit', $user)->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('admin.users.form', ['item' => $user->load('roles'), 'roles' => $this->roles()]);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        // Users can't lock themselves out by deactivating or demoting their own account.
        if ($user->is($request->user())) {
            $data['is_active'] = true;
            $data['roles'] = $user->getRoleNames()->all();
        }
        $user->update(collect($data)->except(['roles', 'password_confirmation'])->all());
        $user->syncRoles($this->allowedRoles($data['roles']));
        ActivityLog::record('user_updated', $user, 'User updated', ['roles' => $data['roles'], 'password_changed' => isset($data['password'])]);

        return back()->with('success', 'User saved.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();
        ActivityLog::record('user_deleted', $user);

        return redirect()->route('admin.users.index')->with('success', 'User removed.');
    }

    private function roles()
    {
        return Role::orderBy('name')->pluck('name')
            ->reject(fn ($r) => $r === 'Super Admin' && ! auth()->user()->hasRole('Super Admin'));
    }

    /** Only Super Admins may grant Super Admin. */
    private function allowedRoles(array $roles): array
    {
        return auth()->user()->hasRole('Super Admin') ? $roles : array_values(array_diff($roles, ['Super Admin']));
    }
}

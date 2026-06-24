<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }
    }

    public function index()
    {
        $this->authorizeAdmin();

        $users = User::with('roles')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $roles = Role::orderBy('name')->get();

        $enableRestaurantModules = filter_var(
            env('GEME_ENABLE_RESTAURANT_MODULES', env('WAWI_ENABLE_RESTAURANT_MODULES', false)),
            FILTER_VALIDATE_BOOL
        );
        if (!$enableRestaurantModules) {
            $roles = $roles->reject(fn ($role) => in_array($role->name, ['cocina', 'barra'], true));
        }

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'pin' => 'nullable|string|max:10|unique:users,pin',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'pin' => $data['pin'] ?? null,
        ]);

        $user->roles()->sync($data['roles'] ?? []);

        return redirect()->route('users.index');
    }

    public function edit(User $user)
    {
        $this->authorizeAdmin();

        $roles = Role::orderBy('name')->get();

        $enableRestaurantModules = filter_var(
            env('GEME_ENABLE_RESTAURANT_MODULES', env('WAWI_ENABLE_RESTAURANT_MODULES', false)),
            FILTER_VALIDATE_BOOL
        );
        if (!$enableRestaurantModules) {
            $roles = $roles->reject(fn ($role) => in_array($role->name, ['cocina', 'barra'], true));
        }

        $userRoleIds = $user->roles()->pluck('roles.id')->toArray();

        return view('users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'pin' => 'nullable|string|max:10|unique:users,pin,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->pin = $data['pin'] ?? null;
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $user->roles()->sync($data['roles'] ?? []);

        return redirect()->route('users.index');
    }
}

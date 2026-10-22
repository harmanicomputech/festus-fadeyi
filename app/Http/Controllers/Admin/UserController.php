<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Dashboard accounts: administrators and welcome-desk (check-in only) staff. */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => User::query()->orderBy('role')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'checkin'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $data['email'] = strtolower($data['email']);
        User::query()->create($data);

        return back()->with('status', 'Account created for '.$data['email'].'.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'checkin'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own administrator access.');
        }

        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('status', 'Account updated for '.$user->email.'.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();

        return back()->with('status', 'Account removed.');
    }
}

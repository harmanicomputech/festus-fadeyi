@extends('layouts.admin', ['title' => 'Users'])
@section('content')
    <div class="page-head"><div><p class="eyebrow">Access</p><h1>Users</h1>
        <p><strong>Admin</strong>: full dashboard. <strong>Check-in</strong>: welcome desk only.</p></div></div>

    <div class="grid grid--main" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)">
        <section class="panel">
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Role / new password</th><th>Last sign-in</th><th></th></tr></thead>
                @foreach ($users as $u)
                    <tr>
                        <td>{{ $u->name }}@if ($u->is(auth()->user())) <span class="badge badge--on">you</span>@endif</td>
                        <td class="small">{{ $u->email }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}" class="grid" style="gap:6px">
                                @csrf @method('PUT')
                                <select class="select" name="role" aria-label="Role for {{ $u->email }}">
                                    <option value="admin" @selected($u->role === 'admin')>Admin</option>
                                    <option value="checkin" @selected($u->role === 'checkin')>Check-in</option>
                                </select>
                                <input class="input" type="password" name="password" placeholder="New password (optional)" autocomplete="new-password" aria-label="New password">
                                <input class="input" type="password" name="password_confirmation" placeholder="Confirm new password" autocomplete="new-password" aria-label="Confirm new password">
                                <button class="btn btn--ghost btn--sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td class="small muted nowrap">{{ $u->last_login_at?->format('j M, g:i A') ?? '—' }}</td>
                        <td>@unless ($u->is(auth()->user()))<form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Remove {{ $u->email }}?">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Remove</button></form>@endunless</td>
                    </tr>
                @endforeach
            </table></div>
        </section>
        <section class="panel">
            <h2>Add an account</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid" style="gap:10px">
                @csrf
                <div class="field"><label for="u-name">Full name</label><input class="input" id="u-name" name="name" required value="{{ old('name') }}"></div>
                <div class="field"><label for="u-email">Email</label><input class="input" id="u-email" name="email" type="email" required value="{{ old('email') }}"></div>
                <div class="field"><label for="u-role">Role</label><select class="select" id="u-role" name="role"><option value="checkin">Check-in (welcome desk)</option><option value="admin">Admin (full access)</option></select></div>
                <div class="field"><label for="u-pass">Password</label><input class="input" id="u-pass" name="password" type="password" required autocomplete="new-password"></div>
                <div class="field"><label for="u-pass2">Confirm password</label><input class="input" id="u-pass2" name="password_confirmation" type="password" required autocomplete="new-password"></div>
                <p class="small muted" style="margin:0">At least 12 characters, mixed case and a number. Share passwords privately.</p>
                <button class="btn" type="submit">Create account</button>
            </form>
        </section>
    </div>
@endsection

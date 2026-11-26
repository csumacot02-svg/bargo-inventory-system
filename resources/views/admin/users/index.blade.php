@extends('layouts.app')

@section('title', 'User Management')
@section('heading', 'User Management')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.users.create') }}" class="btn-primary">Create staff or admin</a>
</div>
<div class="mb-5 grid gap-3 sm:grid-cols-4">
    <div class="stat-tile"><p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Pending</p><p class="font-display mt-2 text-2xl font-semibold">{{ $counts['pending'] }}</p></div>
    <div class="stat-tile"><p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Active</p><p class="font-display mt-2 text-2xl font-semibold">{{ $counts['active'] }}</p></div>
    <div class="stat-tile"><p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Rejected</p><p class="font-display mt-2 text-2xl font-semibold">{{ $counts['rejected'] }}</p></div>
    <div class="stat-tile"><p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Suspended</p><p class="font-display mt-2 text-2xl font-semibold">{{ $counts['suspended'] }}</p></div>
</div>

<form method="GET" class="panel mb-5 flex flex-wrap gap-2 p-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email, ID" class="form-input max-w-xs">
    <select name="role" class="form-input max-w-[160px]">
        <option value="">All roles</option>
        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
        <option value="staff" @selected(request('role') === 'staff')>Staff</option>
    </select>
    <select name="status" class="form-input max-w-[160px]">
        <option value="">All statuses</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Verified</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td class="font-medium">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roleLabel() }}</td>
                    <td>
                        <span class="badge {{ $user->status === 'active' ? 'badge-ok' : ($user->status === 'pending' ? 'badge-pending' : 'badge-out') }}">
                            {{ $user->statusLabel() }}
                        </span>
                    </td>
                    <td>{{ $user->hasVerifiedEmail() ? 'Yes' : 'No' }}</td>
                    <td class="space-x-2 text-right whitespace-nowrap">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-semibold text-slsu-blue hover:underline">View</a>
                        @if($user->status === 'pending')
                            <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">@csrf<button class="text-sm font-semibold text-slsu-green hover:underline">Approve</button></form>
                            <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="inline">@csrf<button class="text-sm font-semibold text-slsu-maroon hover:underline">Reject</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection

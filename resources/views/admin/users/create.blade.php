@extends('layouts.app')

@section('title', 'Create user')
@section('heading', 'Create staff or administrator')

@section('content')
<p class="mb-4 text-sm text-slsu-navy/70">Extra administrators are created here only — public /register/admin is closed.</p>

<form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="panel max-w-xl space-y-4 p-6">
    @csrf

    <div class="flex flex-col items-center gap-3">
        <label for="avatar" class="group relative inline-block h-24 w-24 cursor-pointer" title="Click to upload profile photo">
            <span class="block h-24 w-24 overflow-hidden rounded-full ring-2 ring-slsu-navy/25 transition group-hover:ring-slsu-blue">
                <img id="avatar-preview" src="https://ui-avatars.com/api/?name=?&background=0D2E6B&color=fff&size=96"
                     alt="Profile preview"
                     class="h-full w-full object-cover"
                     style="width:96px;height:96px;max-width:96px;max-height:96px;">
            </span>
            <span class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center rounded-full bg-slsu-navy/45 text-white transition group-hover:bg-slsu-navy/65">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><circle cx="12" cy="13" r="3" /></svg>
                <span class="mt-1 text-[10px] font-semibold tracking-wide uppercase">Upload</span>
            </span>
        </label>
        <input id="avatar" name="avatar" type="file" accept="image/*" class="sr-only" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;">
        <p class="text-center text-xs font-medium text-slsu-blue">Click the photo to upload</p>
        <p class="text-center text-xs text-slsu-navy/55">Optional — JPG/PNG/WEBP only, max 5 MB</p>
        @error('avatar')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Full name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="form-input">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">ID Number</label>
        <input type="text" name="id_number" value="{{ old('id_number') }}" required class="form-input" placeholder="e.g. 2310121-1">
        <p class="mt-1 text-xs text-slsu-navy/60">The user will sign in with this ID number.</p>
        @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    @include('auth.partials.password-field', ['id' => 'password', 'label' => 'Temporary password', 'autocomplete' => 'new-password'])
    @include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Confirm password', 'autocomplete' => 'new-password'])

    <div>
        <label class="form-label">Role</label>
        <select name="role" id="role" required class="form-input">
            <option value="staff" @selected(old('role') === 'staff')>BARGO Staff</option>
            <option value="admin" @selected(old('role') === 'admin')>BARGO Administrator</option>
        </select>
    </div>

    <div>
        <label class="form-label">Employee ID <span class="text-slsu-navy/50">(optional)</span></label>
        <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-input">
    </div>

    <div>
        <label class="form-label">Department <span class="text-slsu-navy/50">(optional)</span></label>
        <input type="text" name="department" value="{{ old('department') }}" class="form-input">
    </div>

    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="confirm_admin" value="1" class="mt-1">
        <span>I confirm this person should receive administrator access (required when role is Administrator).</span>
    </label>

    <div class="flex gap-3">
        <button class="btn-primary" type="submit">Create account</button>
        <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.getElementById('avatar').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatar-preview');
        preview.src = e.target.result;
        preview.style.width = '96px';
        preview.style.height = '96px';
        preview.style.maxWidth = '96px';
        preview.style.maxHeight = '96px';
        preview.style.objectFit = 'cover';
    };
    reader.readAsDataURL(file);
});
document.getElementById('name').addEventListener('input', function () {
    const name = this.value.trim() || '?';
    const initial = encodeURIComponent(name.charAt(0).toUpperCase());
    const preview = document.getElementById('avatar-preview');
    if (preview.src.includes('ui-avatars.com')) {
        preview.src = `https://ui-avatars.com/api/?name=${initial}&background=0D2E6B&color=fff&size=96`;
    }
});
</script>
@endsection

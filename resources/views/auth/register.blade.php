@extends('layouts.guest')

@section('title', 'Create '.$intentLabel.' Account')
@section('auth_subtitle', 'Registration · '.$intentLabel)

@section('auth_content')
<a href="{{ route('login.role', ['intent' => $intent]) }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-slsu-navy/70 transition hover:text-slsu-blue">
    <span aria-hidden="true">←</span> Back
</a>

<h1 class="font-display text-2xl font-semibold text-slsu-navy">Create {{ $intentLabel }} Account</h1>
<p class="mt-1 text-sm text-slsu-navy/70">
    Fill in your details below. Your account type is fixed and cannot be changed here.
</p>

@if ($intent === 'admin')
    <div class="mt-4 rounded-lg border border-slsu-maroon/25 bg-slsu-maroon/10 px-3 py-2.5 text-xs leading-relaxed text-slsu-maroon">
        Administrator registration does <strong>not</strong> grant privileges immediately.
        After you verify with the 6-digit email code, an existing administrator must approve this account.
    </div>
@elseif (in_array($intent, ['staff', 'bargo'], true))
    <div class="mt-4 rounded-lg border border-slsu-blue/25 bg-slsu-blue/10 px-3 py-2.5 text-xs leading-relaxed text-slsu-navy/80">
        New staff accounts stay <strong>pending</strong> until an administrator approves them. After approval, staff can run the full BARGO desk without the administrator present.
    </div>
@endif

<form
    method="POST"
    action="{{ route('register.role.store', ['intent' => $intent]) }}"
    enctype="multipart/form-data"
    class="mt-6 space-y-4"
    onsubmit="this.querySelector('[type=submit]').disabled=true; this.querySelector('[type=submit]').textContent='Creating account…';"
>
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
        <label class="form-label" for="name">Full Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-input" placeholder="Juan Dela Cruz">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label" for="id_number">ID Number</label>
        <input id="id_number" name="id_number" type="text" value="{{ old('id_number') }}" required class="form-input" placeholder="e.g. 2310121-1">
        <p class="mt-1 text-xs text-slsu-navy/60">You will use this ID number to sign in.</p>
        @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label" for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="form-input" placeholder="name@gmail.com">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    @include('auth.partials.password-field', [
        'id' => 'password',
        'label' => 'Password',
        'autocomplete' => 'new-password',
    ])
    @include('auth.partials.password-field', [
        'id' => 'password_confirmation',
        'label' => 'Confirm Password',
        'autocomplete' => 'new-password',
    ])

    <button type="submit" class="btn-primary w-full">Create Account</button>
</form>

<div class="mt-6 border-t border-slsu-navy/10 pt-5 text-sm text-slsu-navy/70">
    Already have an account?
    <a href="{{ route('login.role', ['intent' => $intent]) }}" class="font-semibold text-slsu-blue hover:underline">Sign In</a>
</div>

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

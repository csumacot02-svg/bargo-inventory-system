@extends('layouts.app')

@section('title', 'Edit Profile')
@section('heading', 'Edit Personal Information')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="panel space-y-5 p-6">
        @csrf
        @method('PATCH')

        <div class="flex flex-col items-center gap-3">
            <label for="avatar" class="group relative inline-block h-24 w-24 cursor-pointer" title="Click to upload profile photo">
                <span class="block h-24 w-24 overflow-hidden rounded-full ring-2 ring-slsu-navy/25 transition group-hover:ring-slsu-blue">
                    <img id="avatar-preview" src="{{ $user->avatarUrl() }}"
                         alt="{{ $user->name }}"
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
            <p class="text-center text-xs text-slsu-navy/55">Optional — JPG/PNG/WEBP only, max 5 MB. Leave blank to keep existing photo.</p>
            @error('avatar')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="name">Full Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="form-input">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="id_number">ID Number</label>
            <input id="id_number" name="id_number" type="text" value="{{ old('id_number', $user->id_number) }}" required class="form-input">
            <p class="mt-1 text-xs text-slsu-navy/60">You use this to sign in.</p>
            @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label">Email</label>
            <input type="email" value="{{ $user->email }}" disabled class="form-input bg-slsu-navy/5 text-slsu-navy/60">
            <p class="mt-1 text-xs text-slsu-navy/60">Email is used for verification and password reset. Contact an admin to change it.</p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ route('profile.show') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
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
</script>
@endsection

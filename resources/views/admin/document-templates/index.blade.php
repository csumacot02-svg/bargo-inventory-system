@extends('layouts.app')

@section('title', 'Payment Order Template')
@section('heading', 'Payment Order Template')

@section('content')
<div class="panel overflow-hidden">
    <div class="flex flex-col gap-4 border-b border-slsu-navy/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            @if($usingCustom && $active)
                <p class="font-display text-lg font-semibold text-slsu-navy truncate">{{ $active->original_filename }}</p>
                <p class="mt-0.5 text-xs text-slsu-navy/55">
                    Active · {{ optional($active->activated_at)->format('M d, Y g:i A') }}
                    @if($active->uploader)
                        · {{ $active->uploader->name }}
                    @endif
                </p>
            @else
                <p class="font-display text-lg font-semibold text-slsu-navy">System default</p>
                <p class="mt-0.5 text-xs text-slsu-navy/55">Campus Payment Order form</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('document-templates.download-default') }}" class="btn-secondary">Download default</a>
            @if($usingCustom)
                <a href="{{ route('document-templates.download-active') }}" class="btn-secondary">Download active</a>
                <form method="POST" action="{{ route('document-templates.reset') }}" onsubmit="return confirm('Restore the default Payment Order template?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-accent">Restore default</button>
                </form>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('document-templates.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-end">
        @csrf
        <div class="min-w-0 flex-1">
            <label class="form-label" for="template">Upload template</label>
            <input type="file" name="template" id="template" class="form-input" accept=".xlsx,.xls,.blade.php,.php,.pdf,.doc,.docx" required>
            @error('template')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-primary shrink-0">Upload</button>
    </form>
</div>

@if($history->isNotEmpty())
    <section class="panel mt-5 overflow-hidden">
        <div class="border-b border-slsu-navy/10 px-5 py-3">
            <h2 class="font-display text-base font-semibold text-slsu-navy">Recent uploads</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>By</th>
                        <th>Activated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history->take(8) as $row)
                        @php
                            $isActive = $active && (int) $active->id === (int) $row->id;
                            $fileExists = \Illuminate\Support\Facades\File::exists(storage_path('app/bargo/templates/'.basename($row->disk_path)));
                        @endphp
                        <tr>
                            <td class="font-medium">
                                {{ $row->original_filename }}
                                @if($isActive)
                                    <span class="badge badge-ok ml-1 normal-case tracking-normal">Active</span>
                                @endif
                            </td>
                            <td>{{ $row->uploader?->name ?? '—' }}</td>
                            <td>{{ optional($row->activated_at)->format('M d, Y g:i A') }}</td>
                            <td>
                                <div class="table-actions">
                                    @unless($isActive)
                                        <form method="POST" action="{{ route('document-templates.reactivate', $row) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="table-action table-action--primary"
                                                @disabled(! $fileExists)
                                                title="{{ $fileExists ? 'Make this the active template' : 'File missing' }}"
                                            >Reactivate</button>
                                        </form>
                                    @endunless
                                    <form
                                        method="POST"
                                        action="{{ route('document-templates.destroy', $row) }}"
                                        onsubmit="return confirm('Delete this upload{{ $isActive ? ' and stop using it' : '' }}?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action table-action--danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BargoDocumentTemplate;
use App\Services\BargoDocumentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BargoDocumentTemplateController extends Controller
{
    public function index(BargoDocumentTemplateService $templates): View
    {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);

        $slug = BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT;
        $active = $templates->activeTemplate($slug);
        $history = BargoDocumentTemplate::query()
            ->where('slug', $slug)
            ->with('uploader')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('admin.document-templates.index', [
            'slug' => $slug,
            'label' => 'Order of Payment / Release',
            'active' => $active,
            'history' => $history,
            'usingCustom' => $templates->templateFilePath($slug) !== null,
            'activeIsExcel' => $templates->isExcelTemplate($slug),
            'activeIsBlade' => $templates->isBladeTemplate($slug),
        ]);
    }

    public function store(Request $request, BargoDocumentTemplateService $templates): RedirectResponse
    {
        abort_unless($request->user()?->canManageDocumentTemplates(), 403);

        $request->validate([
            'template' => ['required', 'file', 'max:15360'],
        ], [
            'template.required' => 'Choose a file to upload.',
            'template.max' => 'The file must be 15 MB or smaller.',
        ]);

        $uploaded = $request->file('template');
        $original = $uploaded->getClientOriginalName();
        $filename = $templates->normalizeStoredFilename($original);
        $isBlade = str_ends_with(strtolower($original), '.blade.php')
            || str_ends_with(strtolower($filename), '.blade.php');

        if ($isBlade) {
            $contents = (string) file_get_contents($uploaded->getRealPath());

            try {
                $templates->assertSafeTemplateContents($contents);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['template' => $e->getMessage()]);
            }
        }

        $path = $templates->storageDirectory().DIRECTORY_SEPARATOR.$filename;
        File::put($path, file_get_contents($uploaded->getRealPath()));

        $payload = [
            'slug' => BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT,
            'label' => 'Order of Payment / Release',
            'disk_path' => $filename,
            'original_filename' => $original,
            'uploaded_by' => $request->user()->id,
            'activated_at' => now(),
        ];

        try {
            BargoDocumentTemplate::query()->create($payload);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $existing = BargoDocumentTemplate::query()
                ->where('slug', BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT)
                ->latest('activated_at')
                ->first();

            if ($existing) {
                $existing->update($payload);
            } else {
                throw new \RuntimeException('Could not save Payment Order template.');
            }
        }

        return back()->with('success', 'Template uploaded: '.$original);
    }

    public function reactivate(
        BargoDocumentTemplate $documentTemplate,
        BargoDocumentTemplateService $templates,
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);
        abort_unless($documentTemplate->slug === BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT, 404);

        if (! $templates->pathFor($documentTemplate)) {
            return back()->with('error', 'That upload file is missing and cannot be reactivated.');
        }

        $documentTemplate->update([
            'activated_at' => now(),
        ]);

        return back()->with('success', 'Activated: '.$documentTemplate->original_filename);
    }

    public function destroy(
        BargoDocumentTemplate $documentTemplate,
        BargoDocumentTemplateService $templates,
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);
        abort_unless($documentTemplate->slug === BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT, 404);

        $diskPath = $documentTemplate->disk_path;
        $wasActive = $templates->activeTemplate()?->is($documentTemplate);

        $documentTemplate->delete();

        $stillUsed = BargoDocumentTemplate::query()
            ->where('disk_path', $diskPath)
            ->exists();

        if (! $stillUsed) {
            $templates->deleteStoredFile($diskPath);
        }

        $message = 'Deleted: '.$documentTemplate->original_filename;
        if ($wasActive && ! $templates->activeTemplate()) {
            $message .= ' System default is now active.';
        }

        return back()->with('success', $message);
    }

    public function reset(BargoDocumentTemplateService $templates): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);

        $slug = BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT;
        $templates->clearStoredTemplates();
        BargoDocumentTemplate::query()->where('slug', $slug)->delete();

        return back()->with('success', 'Default Payment Order template restored.');
    }

    public function downloadDefault(BargoDocumentTemplateService $templates): BinaryFileResponse
    {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);

        if (File::exists($templates->defaultExcelPath())) {
            return response()->download($templates->defaultExcelPath(), 'ORDER-PAYMENT.xlsx');
        }

        $path = resource_path('views/documents/order-of-payment.blade.php');

        return response()->download($path, 'order-of-payment-default.blade.php');
    }

    public function downloadActive(BargoDocumentTemplateService $templates): BinaryFileResponse
    {
        abort_unless(auth()->user()?->canManageDocumentTemplates(), 403);

        $active = $templates->activeTemplate();
        $path = $templates->templateFilePath();
        abort_unless($active && $path, 404);

        return response()->download($path, $active->original_filename ?: basename($path));
    }
}

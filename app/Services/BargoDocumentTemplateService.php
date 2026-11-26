<?php

namespace App\Services;

use App\Models\BargoDocumentTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewResponse;

class BargoDocumentTemplateService
{
    public function storageDirectory(): string
    {
        $dir = storage_path('app/bargo/templates');

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir;
    }

    public function defaultExcelPath(): string
    {
        return resource_path('templates/ORDER-PAYMENT.xlsx');
    }

    public function absolutePath(string $diskPath): string
    {
        return $this->storageDirectory().DIRECTORY_SEPARATOR.basename($diskPath);
    }

    public function activeTemplate(?string $slug = null): ?BargoDocumentTemplate
    {
        $slug = $slug ?? BargoDocumentTemplate::SLUG_ORDER_OF_PAYMENT;

        return BargoDocumentTemplate::query()
            ->where('slug', $slug)
            ->latest('activated_at')
            ->latest('id')
            ->first();
    }

    public function templateFilePath(?string $slug = null): ?string
    {
        $active = $this->activeTemplate($slug);
        if (! $active) {
            return null;
        }

        return $this->pathFor($active);
    }

    public function pathFor(BargoDocumentTemplate $template): ?string
    {
        $path = $this->absolutePath($template->disk_path);

        return File::exists($path) ? $path : null;
    }

    public function activeExtension(?string $slug = null): ?string
    {
        $path = $this->templateFilePath($slug);

        if (! $path) {
            return null;
        }

        $basename = strtolower(basename($path));

        if (str_ends_with($basename, '.blade.php')) {
            return 'blade.php';
        }

        return pathinfo($basename, PATHINFO_EXTENSION) ?: null;
    }

    public function isExcelTemplate(?string $slug = null): bool
    {
        $ext = $this->activeExtension($slug);

        return in_array($ext, ['xlsx', 'xls'], true);
    }

    public function isBladeTemplate(?string $slug = null): bool
    {
        return $this->activeExtension($slug) === 'blade.php';
    }

    public function excelTemplatePath(?string $slug = null): string
    {
        $custom = $this->templateFilePath($slug);

        if ($custom && $this->isExcelTemplate($slug)) {
            return $custom;
        }

        $default = $this->defaultExcelPath();

        if (! File::exists($default)) {
            throw new \RuntimeException('Default ORDER-PAYMENT.xlsx template is missing. Place it in resources/templates/.');
        }

        return $default;
    }

    public function clearStoredTemplates(): void
    {
        $dir = $this->storageDirectory();

        foreach (File::files($dir) as $file) {
            File::delete($file->getPathname());
        }
    }

    public function deleteStoredFile(string $diskPath): void
    {
        $path = $this->absolutePath($diskPath);
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function render(string $defaultView, array $data, ?string $slug = null): ViewResponse
    {
        $customPath = $this->templateFilePath($slug);

        if ($customPath && $this->isBladeTemplate($slug)) {
            View::addNamespace('bargo_custom', dirname($customPath));
            $name = pathinfo($customPath, PATHINFO_FILENAME);

            return view('bargo_custom::'.$name, $data);
        }

        return view($defaultView, $data);
    }

    /**
     * @return list<string>
     */
    public function forbiddenBladePatterns(): array
    {
        return [
            '@php',
            'eval(',
            'shell_exec',
            'proc_open',
            'passthru',
            'base64_decode',
            '<script',
        ];
    }

    public function assertSafeTemplateContents(string $contents): void
    {
        $lower = strtolower($contents);

        foreach ($this->forbiddenBladePatterns() as $pattern) {
            if (str_contains($lower, strtolower($pattern))) {
                throw new \InvalidArgumentException('Template contains disallowed content: '.$pattern);
            }
        }

        if (! str_contains($contents, 'inventoryRequest') && ! str_contains($contents, '$document')) {
            throw new \InvalidArgumentException('Template must reference request data (`$inventoryRequest` or `$document`).');
        }
    }

    public function normalizeStoredFilename(string $originalName): string
    {
        $original = strtolower($originalName);
        $stamp = now()->format('YmdHis');

        if (str_ends_with($original, '.blade.php')) {
            return $stamp.'_order-of-payment.blade.php';
        }

        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'template';
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-z0-9]+/', '', (string) $ext) ?: 'bin';

        return $stamp.'_'.$base.'.'.$ext;
    }
}

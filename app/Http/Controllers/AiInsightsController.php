<?php

namespace App\Http\Controllers;

use App\Services\DeepSeekTrendAnalyzer;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiInsightsController extends Controller
{
    public function index(DeepSeekTrendAnalyzer $analyzer): View
    {
        $snapshot = $analyzer->buildInventorySnapshot();
        $analysis = session('ai_analysis');

        if (! is_array($analysis)) {
            $analysis = $analyzer->cachedAnalysis();
        }

        return view('ai.index', [
            'snapshot' => $snapshot,
            'analysis' => $analysis,
            'charts' => $analyzer->buildCharts($snapshot, is_array($analysis) ? $analysis : null),
            'cacheMinutes' => $analyzer->cacheMinutes(),
        ]);
    }

    public function analyze(Request $request, DeepSeekTrendAnalyzer $analyzer): RedirectResponse
    {
        abort_unless($request->user()->canAccessReports(), 403);

        $force = $request->boolean('force');
        $analysis = $analyzer->analyze($force);
        $fromCache = (bool) ($analysis['cached'] ?? false);
        $flashKey = ($analysis['source'] ?? '') === 'deepseek' ? 'success' : 'error';

        Audit::log(
            $request->user(),
            'ai_analyze',
            'ai',
            ($fromCache ? 'Served cached AI insights' : 'Ran AI insights')
                .' ('.($analysis['source'] ?? 'unknown').', force='.($force ? 'yes' : 'no').'). '
                .($analysis['message'] ?? ''),
            $request
        );

        return redirect()
            ->route('ai.index')
            ->with('ai_analysis', $analysis)
            ->with($flashKey, $analysis['message'] ?? 'Analysis complete.');
    }
}

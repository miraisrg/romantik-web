<?php

namespace App\Http\Controllers;

use App\Services\RuleCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RuleCatalogController extends Controller
{
    /**
     * Menampilkan daftar dan katalog aturan deterministik RBS.
     */
    public function index(Request $request, RuleCatalogService $catalogService): View
    {
        $rules = $catalogService->all();
        $sections = $catalogService->sections();
        $metadata = $catalogService->metadata();
        $totalRules = $catalogService->count();

        $selectedRuleId = trim((string) $request->query('rule', ''));
        $selectedRule = $selectedRuleId !== '' ? $catalogService->find($selectedRuleId) : null;

        return view('rules.index', [
            'rules' => $rules,
            'sections' => $sections,
            'metadata' => $metadata,
            'totalRules' => $totalRules,
            'selectedRuleId' => $selectedRuleId,
            'selectedRule' => $selectedRule,
        ]);
    }
}

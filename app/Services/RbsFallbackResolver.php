<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

class RbsFallbackResolver
{
    /**
     * Resolves not_evaluable rules from raw ROMANTIK form JSON using the local RBS engine.
     *
     * @return array<int, array{rule_id: string, status: string, applicability: ?string, reason: ?string, message: ?string, evidence: mixed}>
     */
    public function resolve(string $rawJson): array
    {
        if (trim($rawJson) === '') {
            return [];
        }

        $candidates = [
            [
                'python' => 'd:\College\Skripsi\Topik ROMANTIK\Pengolahan Data\romantik-ai-api\.venv\Scripts\python.exe',
                'dir' => 'D:\College\Skripsi\Topik ROMANTIK\romantik-ai-api',
            ],
            [
                'python' => 'D:\College\Skripsi\Topik ROMANTIK\romantik-ai-api\.venv\Scripts\python.exe',
                'dir' => 'D:\College\Skripsi\Topik ROMANTIK\romantik-ai-api',
            ],
            [
                'python' => 'd:\College\Skripsi\Topik ROMANTIK\Pengolahan Data\romantik-ai-api\.venv\Scripts\python.exe',
                'dir' => 'D:\College\Skripsi\Topik ROMANTIK\Pengolahan Data\romantik-ai-api',
            ],
            [
                'python' => 'python',
                'dir' => 'D:\College\Skripsi\Topik ROMANTIK\romantik-ai-api',
            ],
            [
                'python' => 'python3',
                'dir' => 'D:\College\Skripsi\Topik ROMANTIK\romantik-ai-api',
            ],
        ];

        $configuredPython = config('services.romantik_ai.python_binary');
        $configuredDir = config('services.romantik_ai.api_dir');
        if (! empty($configuredPython) && ! empty($configuredDir)) {
            array_unshift($candidates, [
                'python' => (string) $configuredPython,
                'dir' => (string) $configuredDir,
            ]);
        }

        $pythonCode = <<<'PY'
import sys, json
from app.preprocessing.canonicalizer import canonicalize_record
from app.rbs.engine import run_current_rbs

try:
    raw = json.load(sys.stdin)
    canon = canonicalize_record(raw)
    results = run_current_rbs(canon)
    ne = [
        {
            "rule_id": r["rule_id"],
            "status": r.get("status", "not_evaluable"),
            "applicability": r.get("applicability"),
            "reason": r.get("reason"),
            "message": r.get("message"),
            "evidence": r.get("evidence"),
        }
        for r in results
        if r.get("status") == "not_evaluable"
    ]
    print(json.dumps(ne))
except Exception as e:
    sys.stderr.write(str(e))
    sys.exit(1)
PY;

        foreach ($candidates as $candidate) {
            $python = $candidate['python'];
            $dir = $candidate['dir'];

            if ($python !== 'python' && $python !== 'python3' && ! file_exists($python)) {
                continue;
            }
            if (! is_dir($dir)) {
                continue;
            }

            try {
                $process = new Process([$python, '-c', $pythonCode], $dir);
                $process->setTimeout(5);
                $process->setInput($rawJson);
                $process->run();

                if ($process->isSuccessful()) {
                    $decoded = json_decode($process->getOutput(), true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return [];
    }
}

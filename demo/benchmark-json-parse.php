<?php

declare(strict_types=1);

// Benchmark: How long does it take to parse a large Iconify JSON collection?

echo "=== JSON Collection Parse Benchmark ===\n\n";

// Generate test files of various sizes
$sizes = [1000, 3000, 7000];
$longBody = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/><path d="M0 0h24v24H0z" fill="none"/>';

foreach ($sizes as $count) {
    $icons = [];
    for ($i = 0; $i < $count; $i++) {
        $icons['icon-' . $i] = ['body' => $longBody];
    }

    $json = json_encode(['prefix' => 'bench', 'icons' => $icons, 'width' => 24, 'height' => 24]);
    $tmpFile = sys_get_temp_dir() . '/bench-' . $count . '.json';
    file_put_contents($tmpFile, $json);
    $fileSize = filesize($tmpFile);

    // Measure: file_get_contents + json_decode
    $memBefore = memory_get_usage(true);
    $start = microtime(true);
    $data = json_decode(file_get_contents($tmpFile), true);
    $elapsed = (microtime(true) - $start) * 1000;
    $memAfter = memory_get_usage(true);

    $fileMB = round($fileSize / 1024 / 1024, 2);
    $memMB = round(($memAfter - $memBefore) / 1024 / 1024, 2);

    echo sprintf(
        "%d icons | File: %s MB | Parse: %.1f ms | Memory: %s MB\n",
        $count, $fileMB, $elapsed, $memMB
    );

    unset($data);
    unlink($tmpFile);
}

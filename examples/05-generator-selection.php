<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 5: Multiple PDF generators (driver selection)
 *
 * This example demonstrates how to explicitly select which PDF generator
 * to use: 'auto' (default), 'libreoffice', or 'php' (mPDF).
 */

// Create HTML template
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Selection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .driver { background: #e3f2fd; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>PDF Generator: {{ driver }}</h1>
    <div class="driver">
        <p>This PDF was generated using: <strong>{{ driver }}</strong></p>
        <p>Generated at: {{ timestamp }}</p>
    </div>
</body>
</html>
HTML;

$templatePath = sys_get_temp_dir() . '/driver_template.html';
file_put_contents($templatePath, $htmlTemplate);

try {
    echo "Testing different PDF generators...\n\n";

    // Test 1: Auto selection (default)
    echo "1. Auto selection (will choose best available):\n";
    $converter = new PdfConverter($templatePath, ['driver' => 'auto']);
    $pdf = $converter->render([
        'driver' => 'Auto (Best Available)',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    $outputPath = __DIR__ . '/output/05-auto.pdf';
    if (!is_dir(dirname($outputPath))) {
        mkdir(dirname($outputPath), 0o777, true);
    }
    $pdf->save($outputPath);
    echo "   ✅ Generated: {$outputPath}\n\n";

    // Test 2: PHP (mPDF) explicitly
    echo "2. PHP generator (mPDF - pure PHP, always available):\n";
    $converter = new PdfConverter($templatePath, ['driver' => 'php']);
    $pdf = $converter->render([
        'driver' => 'mPDF (Pure PHP)',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    $outputPath = __DIR__ . '/output/05-mpdf.pdf';
    $pdf->save($outputPath);
    echo "   ✅ Generated: {$outputPath}\n\n";

    // Test 3: LibreOffice (may not be available)
    echo "3. LibreOffice generator (requires LibreOffice installation):\n";
    try {
        $converter = new PdfConverter($templatePath, ['driver' => 'libreoffice']);
        $pdf = $converter->render([
            'driver' => 'LibreOffice Headless',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        $outputPath = __DIR__ . '/output/05-libreoffice.pdf';
        $pdf->save($outputPath);
        echo "   ✅ Generated: {$outputPath}\n";
        echo "   (LibreOffice is installed and working!)\n\n";
    } catch (\Exception $e) {
        echo "   ⚠️  LibreOffice not available: {$e->getMessage()}\n";
        echo "   (This is expected if LibreOffice is not installed)\n\n";
    }

    echo "Summary:\n";
    echo "- 'auto': Let the library choose the best generator (recommended)\n";
    echo "- 'php': Force mPDF (pure PHP, always works, good for HTML)\n";
    echo "- 'libreoffice': Force LibreOffice (best fidelity for ODT/ODS, requires installation)\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
} finally {
    @unlink($templatePath);
}

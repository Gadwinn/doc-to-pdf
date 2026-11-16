<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 1: Basic HTML to PDF conversion
 *
 * This example demonstrates the simplest use case: convert a static HTML
 * template to PDF without any variables.
 */

// Create simple HTML template
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Basic Document</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Hello World</h1>
    <p>This is a basic HTML to PDF conversion example.</p>
    <p>No variables, just static content.</p>
</body>
</html>
HTML;

// Save template to file
$templatePath = sys_get_temp_dir() . '/basic_template.html';
file_put_contents($templatePath, $htmlTemplate);

try {
    // Create converter
    $converter = new PdfConverter($templatePath);

    // Generate PDF (no data needed for static content)
    $pdf = $converter->render();

    // Save to file
    $outputPath = __DIR__ . '/output/01-basic.pdf';
    if (!is_dir(dirname($outputPath))) {
        mkdir(dirname($outputPath), 0o777, true);
    }
    $pdf->save($outputPath);

    echo "✅ PDF generated successfully: {$outputPath}\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
} finally {
    // Clean up
    @unlink($templatePath);
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 4: Output PDF directly to browser
 *
 * This example demonstrates how to send the PDF directly to the browser
 * for inline viewing or download instead of saving to disk.
 */

// Create simple HTML template
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browser Output Example</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #e74c3c; }
        .info { background: #fff3cd; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>PDF Generated at {{ timestamp }}</h1>
    
    <div class="info">
        <p>This PDF was generated on-the-fly and sent directly to your browser.</p>
        <p>No file was saved on the server!</p>
    </div>
    
    <h2>Request Information</h2>
    <p><strong>User Agent:</strong> {{ user_agent }}</p>
    <p><strong>IP Address:</strong> {{ ip_address }}</p>
</body>
</html>
HTML;

// Save template to file
$templatePath = sys_get_temp_dir() . '/browser_template.html';
file_put_contents($templatePath, $htmlTemplate);

try {
    // Create converter
    $converter = new PdfConverter($templatePath);

    // Prepare data
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ];

    // Generate PDF
    $pdf = $converter->render($data);

    // Check if running in CLI or web context
    if (php_sapi_name() === 'cli') {
        // CLI: Save to file
        $outputPath = __DIR__ . '/output/04-browser-output.pdf';
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0o777, true);
        }
        $pdf->save($outputPath);
        echo "✅ PDF generated: {$outputPath}\n";
        echo "   (In a web context, this would be sent to the browser)\n";
    } else {
        // Web: Output to browser
        // For inline viewing, use 'inline'
        // For download, use 'attachment'
        $pdf->output('document.pdf', 'inline');
        exit;
    }
} catch (\Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "❌ Error: {$e->getMessage()}\n";
    } else {
        header('Content-Type: text/plain');
        echo "Error generating PDF: {$e->getMessage()}";
    }
} finally {
    // Clean up
    @unlink($templatePath);
}

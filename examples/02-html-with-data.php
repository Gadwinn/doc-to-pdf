<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 2: HTML to PDF with template variables
 *
 * This example demonstrates using Twig template variables to inject
 * dynamic data into the PDF.
 */

// Create HTML template with variables
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ documentTitle }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #2c3e50; }
        .info { background: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; }
        .signature { margin-top: 50px; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ documentTitle }}</h1>
    
    <div class="info">
        <p><strong>Customer:</strong> {{ customer.name }}</p>
        <p><strong>Email:</strong> {{ customer.email }}</p>
        <p><strong>Date:</strong> {{ date }}</p>
    </div>
    
    <h2>Message</h2>
    <p>{{ message }}</p>
    
    <div class="signature">
        <p>Best regards,<br>{{ senderName }}</p>
    </div>
</body>
</html>
HTML;

// Save template to file
$templatePath = sys_get_temp_dir() . '/variables_template.html';
file_put_contents($templatePath, $htmlTemplate);

try {
    // Create converter
    $converter = new PdfConverter($templatePath);

    // Prepare data
    $data = [
        'documentTitle' => 'Customer Notification',
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ],
        'date' => date('Y-m-d H:i:s'),
        'message' => 'Thank you for your recent purchase. Your order has been processed successfully.',
        'senderName' => 'ACME Corporation',
    ];

    // Generate PDF with data
    $pdf = $converter->render($data);

    // Save to file
    $outputPath = __DIR__ . '/output/02-variables.pdf';
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

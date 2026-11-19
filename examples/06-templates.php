<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 6: ready-made templates
 *
 * Renders the three templates of examples/templates/ (a letter, an invoice
 * that computes its totals, a multi-page report with a page-numbered footer)
 * with sample data. Copy one of them as a starting point for your own.
 */

$documents = [
    'simple' => [
        'title' => 'Change of opening hours',
        'date' => '19 November 2026',
        'paragraphs' => [
            'From 1 December, our shop will be open from 9 am to 7 pm, Monday to Saturday.',
            'Orders placed online are not affected and keep being shipped the same day.',
        ],
        'author' => 'The ACME team',
    ],
    'invoice' => [
        'seller' => ['name' => 'ACME Ltd', 'address' => "12 Market Street\n75001 Paris"],
        'customer' => ['name' => 'Alice Martin', 'address' => "4 Station Road\n69002 Lyon"],
        'invoice' => ['number' => 'INV-2026-0042', 'date' => '2026-11-19', 'due' => '2026-12-19'],
        'currency' => 'EUR',
        'tax_rate' => 20,
        'lines' => [
            ['description' => 'Website maintenance (November)', 'quantity' => 1, 'price' => 450],
            ['description' => 'Hosting', 'quantity' => 3, 'price' => 19.9],
            ['description' => 'Training session <2 hours>', 'quantity' => 2, 'price' => 120],
        ],
        'notes' => 'Payment by bank transfer within 30 days.',
    ],
    'report' => [
        'title' => 'Monthly activity report',
        'period' => 'October 2026',
        'kpis' => [
            ['value' => '12 480', 'label' => 'Visits'],
            ['value' => '318', 'label' => 'Orders'],
            ['value' => '2.5 %', 'label' => 'Conversion'],
            ['value' => '41 k€', 'label' => 'Revenue'],
        ],
        'sections' => [
            [
                'title' => 'Summary',
                'text' => 'Traffic grew steadily over the month, mostly from organic search.',
            ],
            [
                'title' => 'Traffic sources',
                'columns' => ['Source', 'Visits', 'Change'],
                'rows' => [
                    ['name' => 'Search engines', 'value' => 7310, 'change' => 8.4],
                    ['name' => 'Direct', 'value' => 3120, 'change' => 1.2],
                    ['name' => 'Social networks', 'value' => 1450, 'change' => -3.7],
                    ['name' => 'Newsletters', 'value' => 600, 'change' => 15.0],
                ],
            ],
        ],
    ],
];

$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0o777, true);
}

foreach ($documents as $name => $data) {
    try {
        $pdf = (new PdfConverter(__DIR__ . "/templates/{$name}.html"))->render($data);
        $pdf->save("{$outputDir}/06-{$name}.pdf");
        echo "✅ {$name}: {$outputDir}/06-{$name}.pdf\n";
    } catch (\Exception $e) {
        echo "❌ {$name}: {$e->getMessage()}\n";
    }
}

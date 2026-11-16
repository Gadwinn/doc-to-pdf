<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use G\DocToPdf\Converter\PdfConverter;

/**
 * Example 3: HTML to PDF with loops and conditions
 *
 * This example demonstrates advanced Twig features: loops, conditions,
 * and filters for generating complex documents like invoices.
 */

// Create HTML invoice template
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ invoice.number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company { font-weight: bold; }
        .customer { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #3498db; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .total-row { font-weight: bold; background: #f8f9fa; }
        .total { font-size: 1.2em; text-align: right; margin-top: 20px; }
        .discount-notice { color: #27ae60; font-style: italic; }
        .footer { margin-top: 50px; font-size: 0.9em; color: #7f8c8d; text-align: center; }
    </style>
</head>
<body>
    <h1>INVOICE #{{ invoice.number }}</h1>
    
    <div class="header">
        <div class="company">
            <strong>{{ company.name }}</strong><br>
            {{ company.address }}<br>
            {{ company.city }}, {{ company.zip }}
        </div>
        <div class="customer">
            <strong>Bill to:</strong><br>
            {{ customer.name }}<br>
            {{ customer.address }}<br>
            {{ customer.city }}, {{ customer.zip }}
        </div>
    </div>
    
    <p><strong>Date:</strong> {{ invoice.date }}</p>
    {% if invoice.due_date %}
    <p><strong>Due Date:</strong> {{ invoice.due_date }}</p>
    {% endif %}
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            {% for item in items %}
            <tr>
                <td>{{ item.description }}</td>
                <td>{{ item.quantity }}</td>
                <td>${{ item.unit_price|number_format(2, '.', ',') }}</td>
                <td>${{ (item.quantity * item.unit_price)|number_format(2, '.', ',') }}</td>
            </tr>
            {% endfor %}
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">Subtotal</td>
                <td>${{ subtotal|number_format(2, '.', ',') }}</td>
            </tr>
            {% if discount > 0 %}
            <tr class="total-row">
                <td colspan="3">Discount ({{ discount }}%)</td>
                <td>-${{ (subtotal * discount / 100)|number_format(2, '.', ',') }}</td>
            </tr>
            {% endif %}
            <tr class="total-row">
                <td colspan="3">Tax ({{ tax_rate }}%)</td>
                <td>${{ tax_amount|number_format(2, '.', ',') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong>${{ total|number_format(2, '.', ',') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    {% if discount > 0 %}
    <p class="discount-notice">
        🎉 A {{ discount }}% discount has been applied to this invoice!
    </p>
    {% endif %}
    
    {% if notes %}
    <div style="margin-top: 30px;">
        <strong>Notes:</strong>
        <p>{{ notes }}</p>
    </div>
    {% endif %}
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ company.name }} | {{ company.email }} | {{ company.phone }}</p>
    </div>
</body>
</html>
HTML;

// Save template to file
$templatePath = sys_get_temp_dir() . '/invoice_template.html';
file_put_contents($templatePath, $htmlTemplate);

try {
    // Create converter
    $converter = new PdfConverter($templatePath);

    // Prepare invoice data
    $items = [
        [
            'description' => 'Web Development Services',
            'quantity' => 40,
            'unit_price' => 85.00,
        ],
        [
            'description' => 'UI/UX Design',
            'quantity' => 20,
            'unit_price' => 75.00,
        ],
        [
            'description' => 'Hosting & Maintenance (1 year)',
            'quantity' => 1,
            'unit_price' => 500.00,
        ],
    ];

    $subtotal = array_reduce($items, function ($sum, $item) {
        return $sum + ($item['quantity'] * $item['unit_price']);
    }, 0);

    $discount = 10; // 10% discount
    $discountAmount = $subtotal * $discount / 100;
    $afterDiscount = $subtotal - $discountAmount;

    $taxRate = 20; // 20% tax
    $taxAmount = $afterDiscount * $taxRate / 100;
    $total = $afterDiscount + $taxAmount;

    $data = [
        'invoice' => [
            'number' => 'INV-2024-001',
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
        ],
        'company' => [
            'name' => 'ACME Web Solutions',
            'address' => '123 Tech Street',
            'city' => 'San Francisco',
            'zip' => 'CA 94105',
            'email' => 'billing@acme.com',
            'phone' => '+1 (555) 123-4567',
        ],
        'customer' => [
            'name' => 'John Doe Enterprises',
            'address' => '456 Business Ave',
            'city' => 'New York',
            'zip' => 'NY 10001',
        ],
        'items' => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'tax_rate' => $taxRate,
        'tax_amount' => $taxAmount,
        'total' => $total,
        'notes' => 'Payment is due within 30 days. Please include invoice number with payment.',
    ];

    // Generate PDF with data
    $pdf = $converter->render($data);

    // Save to file
    $outputPath = __DIR__ . '/output/03-invoice.pdf';
    if (!is_dir(dirname($outputPath))) {
        mkdir(dirname($outputPath), 0o777, true);
    }
    $pdf->save($outputPath);

    echo "✅ Invoice PDF generated successfully: {$outputPath}\n";
    echo "   Subtotal: \${$subtotal}\n";
    echo "   Discount: -{$discount}%\n";
    echo "   Tax: +{$taxRate}%\n";
    echo "   Total: \${$total}\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
} finally {
    // Clean up
    @unlink($templatePath);
}

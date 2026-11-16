# DocToPdf

A PHP library for converting document templates (HTML, ODT, ODS) to PDF with template variable support.

[![License](https://img.shields.io/badge/license-MIT-green.svg)](composer.json)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://php.net)

## Features

- 📄 **Multiple Input Formats**: HTML, ODT (OpenDocument Text), ODS (OpenDocument Spreadsheet)
- 🎨 **Template Engine**: Twig for HTML templates with variables, loops, and conditions
- 📋 **ODT/ODS Support**: Use odtphp for OpenDocument format templating
- 🔄 **Dual PDF Generation**: LibreOffice (best fidelity) + mPDF (pure PHP fallback)
- 🚀 **Auto-detection**: Automatically detects file formats using magic bytes
- 💪 **Fluent API**: Clean, readable code with method chaining
- ✅ **Type-safe**: Full PHP 8.3+ strict types support
- 🧪 **Well-tested**: Comprehensive PHPUnit test coverage

## Requirements

- PHP 8.3 or higher
- GD extension (for image processing in PDFs)
- ZipArchive extension (for ODT/ODS handling)
- Optional: LibreOffice (for best PDF rendering quality from ODT/ODS)

## Installation

```bash
composer require gadwinn/doc-to-pdf
```

## Quick Start

### Basic HTML to PDF

```php
use G\DocToPdf\Converter\PdfConverter;

$converter = new PdfConverter('template.html');
$pdf = $converter->render();
$pdf->save('output.pdf');
```

### HTML with Variables

```php
$htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <h1>Hello {{ name }}!</h1>
    <p>Welcome to {{ company }}</p>
</body>
</html>
HTML;

file_put_contents('template.html', $htmlTemplate);

$converter = new PdfConverter('template.html');
$pdf = $converter->render([
    'name' => 'John Doe',
    'company' => 'ACME Corp'
]);
$pdf->save('welcome.pdf');
```

### Invoice with Loops

```php
$converter = new PdfConverter('invoice.html');
$pdf = $converter->render([
    'invoice_number' => 'INV-2024-001',
    'customer' => 'John Doe',
    'items' => [
        ['description' => 'Service A', 'quantity' => 2, 'price' => 100],
        ['description' => 'Service B', 'quantity' => 1, 'price' => 250],
    ],
    'total' => 450
]);
$pdf->save('invoice.pdf');
```

Template (`invoice.html`):
```html
<table>
{% for item in items %}
    <tr>
        <td>{{ item.description }}</td>
        <td>{{ item.quantity }}</td>
        <td>${{ item.price }}</td>
    </tr>
{% endfor %}
</table>
<p><strong>Total: ${{ total }}</strong></p>
```

### Output to Browser

```php
$converter = new PdfConverter('template.html');
$pdf = $converter->render(['name' => 'John']);

// For download
$pdf->output('document.pdf', 'attachment');

// For inline viewing
$pdf->output('document.pdf', 'inline');
```

## Advanced Usage

### Format Auto-detection

The library automatically detects file formats:

```php
// Automatically detects HTML
$converter = new PdfConverter('template.html');

// Automatically detects ODT
$converter = new PdfConverter('template.odt');

// Automatically detects ODS
$converter = new PdfConverter('template.ods');
```

### Generator Selection

Control which PDF generator to use:

```php
// Auto (default): best available generator
$converter = new PdfConverter('template.html', [
    'driver' => 'auto'
]);

// Force pure PHP (mPDF)
$converter = new PdfConverter('template.html', [
    'driver' => 'php'
]);

// Force LibreOffice (requires installation)
$converter = new PdfConverter('template.odt', [
    'driver' => 'libreoffice',
    'libreoffice_path' => '/usr/bin/libreoffice' // optional custom path
]);
```

### Custom Configuration

```php
$converter = new PdfConverter('template.html', [
    'driver' => 'auto',
    'temp_dir' => '/custom/temp/path',
    'twig_options' => [
        'cache' => '/path/to/cache',
        'strict_variables' => true,
    ],
]);
```

### ODT Templates

ODT templates use a different syntax (odtphp):

```php
// template.odt content (XML):
// <text:p>{customer_name}</text:p>
// <text:p>{invoice_date}</text:p>

$converter = new PdfConverter('template.odt');
$pdf = $converter->render([
    'customer_name' => 'John Doe',
    'invoice_date' => '2024-01-15'
]);
$pdf->save('invoice.pdf');
```

For repeating sections in ODT:
```php
$converter = new PdfConverter('template.odt');
$pdf = $converter->render([
    'items' => [
        ['description' => 'Item 1', 'price' => '100'],
        ['description' => 'Item 2', 'price' => '200'],
    ]
]);
```

## Examples

Check the `examples/` directory for complete working examples:

- `01-basic-html.php` - Simple static HTML to PDF
- `02-html-with-data.php` - HTML template with variables
- `03-invoice.php` - Complex invoice with loops, conditions, and calculations
- `04-browser-output.php` - Sending PDF directly to browser
- `05-generator-selection.php` - Testing different PDF generators

Run examples:
```bash
php examples/01-basic-html.php
php examples/03-invoice.php
```

## API Reference

### PdfConverter

Main class for document conversion.

#### Constructor

```php
public function __construct(string $templatePath, array $options = [])
```

**Parameters:**
- `$templatePath` - Path to template file (HTML, ODT, or ODS)
- `$options` - Configuration options:
  - `driver` - PDF generator: `'auto'`, `'php'`, `'libreoffice'` (default: `'auto'`)
  - `libreoffice_path` - Custom LibreOffice binary path (optional)
  - `temp_dir` - Temporary files directory (default: system temp)
  - `twig_options` - Options for Twig renderer (for HTML templates)

**Throws:**
- `InvalidArgumentException` - If template file doesn't exist
- `DocToPdfException` - If format is unsupported

#### render()

```php
public function render(array $data = []): Pdf
```

Generate PDF from template with data.

**Parameters:**
- `$data` - Template variables (associative array)

**Returns:** `Pdf` object

**Throws:** `DocToPdfException` on conversion failure

### Pdf

Result object representing generated PDF.

#### save()

```php
public function save(string $filePath): void
```

Save PDF to file.

#### output()

```php
public function output(string $filename, string $disposition = 'inline'): void
```

Send PDF to browser.

**Parameters:**
- `$filename` - Filename for browser
- `$disposition` - `'inline'` (view) or `'attachment'` (download)

#### getContent()

```php
public function getContent(): string
```

Get PDF content as string.

## Architecture

The library uses a clean, SOLID architecture:

```
Input File
    ↓
FormatDetector (auto-detect format)
    ↓
Reader (HTML/ODT/ODS)
    ↓
Renderer (Twig for HTML)
    ↓
Generator (mPDF/LibreOffice)
    ↓
PDF Output
```

**Components:**
- **FormatDetector**: Auto-detects file formats using magic bytes
- **Readers**: Extract content from different formats
- **Renderer**: Template engine (Twig for HTML)
- **Generators**: PDF generation strategies (mPDF, LibreOffice)
- **PdfConverter**: Orchestrates the entire conversion process

## Testing

Run tests:
```bash
# All tests
composer test

# Unit tests only
composer test-unit

# Code style
composer test-cs

# Static analysis
composer test-stan

# Magic number detection
composer test-mnd

# Code coverage
composer test-coverage
```

## Security notes

- **HTML templates escape their data**: `{{ name }}` is HTML-escaped, so a value typed by a user cannot inject markup (or make mPDF load a remote or local file through an `<img>` tag). Use `{{ content|raw }}` only for HTML you trust, or pass `'twig_options' => ['autoescape' => false]` to restore the old behaviour.
- Filled ODT/ODS documents are written to the temporary directory under a random name and always removed, even when the conversion fails.

## License

MIT

## Support

For issues and feature requests, please contact the package maintainer.

## Changelog

### v1.0.0 (2024-01-07)

- Initial release
- HTML to PDF conversion with Twig templating
- ODT/ODS to PDF conversion with odtphp
- Dual generator support (mPDF + LibreOffice)
- Auto format detection
- Comprehensive test coverage


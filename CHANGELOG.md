# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### To be released
- Version tagging v1.0.0

## [1.0.0] - 2025-01-07

### Added
- Initial release of gadwinn/doc-to-pdf library
- Support for HTML to PDF conversion with Twig templating
- Support for ODT (OpenDocument Text) to PDF conversion
- Support for ODS (OpenDocument Spreadsheet) to PDF conversion
- Auto format detection using magic bytes
- Dual PDF generation strategy:
  - LibreOffice headless (best fidelity, priority 100)
  - mPDF pure PHP (fallback, priority 50)
- Twig template engine integration with:
  - Variable substitution
  - Loops (for)
  - Conditions (if/else)
  - Filters (number_format, etc.)
- ODT/ODS templating with odtphp:
  - Simple variable substitution
  - Segment-based loops for repeating data
- Comprehensive test suite:
  - 75 unit tests
  - 71.58% code coverage
  - All quality checks passing (PSR-12, PHPStan level 9)
- Complete API documentation with PHPDoc
- Working examples:
  - Basic HTML to PDF
  - HTML with template variables
  - Complex invoice with loops and conditions
  - Browser output (inline/download)
  - Generator selection (auto/php/libreoffice)
- Clean SOLID architecture:
  - FormatDetector: Auto-detection of file formats
  - Readers: Extract content from different formats
  - Renderer: Twig template processing
  - Generators: PDF generation strategies
  - PdfConverter: Main orchestrator
  - Pdf: Result object with save/output methods

### Technical Details
- PHP 8.2+ requirement
- Strict types throughout
- PSR-12 coding standards
- PHPStan level 9 compliance
- Comprehensive error handling with specific exceptions
- Type-safe implementation with full PHP 8.2+ features

### Dependencies
- twig/twig ^3.0: Template engine
- sboden/odtphp ^3.0: ODT/ODS handling
- mpdf/mpdf ^8.0: Pure PHP PDF generation

### Development Tools
- PHPUnit: Unit testing framework
- PHPStan: Static analysis (level 9)
- PHP_CodeSniffer: PSR-12 compliance
- PHPMND: Magic number detection
- PHPDocumentor: API documentation generation

[Unreleased]: https://github.com/gadwinn/doc-to-pdf/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/gadwinn/doc-to-pdf/releases/tag/v1.0.0

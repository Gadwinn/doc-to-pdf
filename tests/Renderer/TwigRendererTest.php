<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Renderer;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Renderer\TwigRenderer;
use PHPUnit\Framework\TestCase;

class TwigRendererTest extends TestCase
{
    private TwigRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TwigRenderer();
    }

    public function testRenderSimpleVariable(): void
    {
        $template = 'Hello {{ name }}!';
        $data = ['name' => 'World'];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('Hello World!', $result);
    }

    public function testRenderLoop(): void
    {
        $template = '{% for item in items %}{{ item }},{% endfor %}';
        $data = ['items' => ['A', 'B', 'C']];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('A,B,C,', $result);
    }

    public function testRenderLoopWithProperties(): void
    {
        $template = '{% for item in items %}{{ item.name }}: ${{ item.price }}; {% endfor %}';
        $data = [
            'items' => [
                ['name' => 'Apple', 'price' => '1.00'],
                ['name' => 'Banana', 'price' => '0.50'],
            ],
        ];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('Apple: $1.00; Banana: $0.50; ', $result);
    }

    public function testRenderConditionTrue(): void
    {
        $template = '{% if active %}Active{% else %}Inactive{% endif %}';
        $data = ['active' => true];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('Active', $result);
    }

    public function testRenderConditionFalse(): void
    {
        $template = '{% if active %}Active{% else %}Inactive{% endif %}';
        $data = ['active' => false];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('Inactive', $result);
    }

    public function testRenderNestedData(): void
    {
        $template = '{{ user.name }} ({{ user.email }})';
        $data = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $result = $this->renderer->render($template, $data);

        $this->assertSame('John Doe (john@example.com)', $result);
    }

    public function testStrictVariablesThrowsException(): void
    {
        $template = '{{ undefined_variable }}';
        $data = [];

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Twig rendering error:');

        $this->renderer->render($template, $data);
    }

    public function testCustomOptions(): void
    {
        $renderer = new TwigRenderer(['strict_variables' => false]);
        $template = '{{ undefined_variable }}';
        $data = [];

        $result = $renderer->render($template, $data);

        // With strict_variables = false, undefined variables render as empty
        $this->assertSame('', $result);
    }

    public function testComplexTemplate(): void
    {
        $template = <<<'TWIG'
        Invoice #{{ invoice_number }}
        Customer: {{ customer.name }}
        Items:
        {% for item in items %}
        - {{ item.name }}: ${{ item.price }} x {{ item.qty }} = ${{ item.price * item.qty }}
        {% endfor %}
        Total: ${{ total }}
        {% if paid %}PAID{% else %}UNPAID{% endif %}
        TWIG;

        $data = [
            'invoice_number' => '12345',
            'customer' => ['name' => 'ABC Corp'],
            'items' => [
                ['name' => 'Widget', 'price' => 10, 'qty' => 2],
                ['name' => 'Gadget', 'price' => 15, 'qty' => 1],
            ],
            'total' => 35,
            'paid' => false,
        ];

        $result = $this->renderer->render($template, $data);

        $this->assertStringContainsString('Invoice #12345', $result);
        $this->assertStringContainsString('ABC Corp', $result);
        $this->assertStringContainsString('Widget: $10 x 2 = $20', $result);
        $this->assertStringContainsString('Gadget: $15 x 1 = $15', $result);
        $this->assertStringContainsString('Total: $35', $result);
        $this->assertStringContainsString('UNPAID', $result);
    }

    public function testInvalidSyntaxThrowsException(): void
    {
        $template = '{% for item in items %}{{ item }{% endfor %}'; // Missing closing %}
        $data = ['items' => ['A', 'B']];

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Twig rendering error:');

        $this->renderer->render($template, $data);
    }

    public function testEmptyTemplate(): void
    {
        $result = $this->renderer->render('', []);

        $this->assertSame('', $result);
    }

    public function testEmptyData(): void
    {
        $template = 'Static text without variables';
        
        $result = $this->renderer->render($template, []);

        $this->assertSame('Static text without variables', $result);
    }

    public function testDataIsHtmlEscapedByDefault(): void
    {
        $renderer = new \G\DocToPdf\Renderer\TwigRenderer();

        $html = $renderer->render('<p>{{ name }}</p>', ['name' => '<img src="file:///etc/passwd">']);

        $this->assertSame('<p>&lt;img src=&quot;file:///etc/passwd&quot;&gt;</p>', $html);
    }

    public function testRawFilterAndAutoescapeOptionAllowTrustedHtml(): void
    {
        $data = ['content' => '<b>bold</b>'];

        $this->assertSame('<b>bold</b>', (new \G\DocToPdf\Renderer\TwigRenderer())->render('{{ content|raw }}', $data));
        $this->assertSame(
            '<b>bold</b>',
            (new \G\DocToPdf\Renderer\TwigRenderer(['autoescape' => false]))->render('{{ content }}', $data)
        );
    }
}

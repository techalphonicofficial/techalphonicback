<?php

namespace Tests\Unit;

use App\Services\TemplateRenderer;
use App\Services\TocService;
use PHPUnit\Framework\TestCase;

class TemplateRendererTest extends TestCase
{
    public function test_replaces_known_variables_and_strips_unknown(): void
    {
        $r = new TemplateRenderer;
        $this->assertSame('SEO in Noida', $r->render('{{service_name}} in {{ location_name }}', ['service_name' => 'SEO', 'location_name' => 'Noida']));
        $this->assertSame('SEO in ', $r->render('{{service_name}} in {{unknown}}', ['service_name' => 'SEO']));
    }

    public function test_renders_nested_arrays(): void
    {
        $out = (new TemplateRenderer)->render([['title' => 'Hi {{location_name}}']], ['location_name' => 'Delhi']);
        $this->assertSame('Hi Delhi', $out[0]['title']);
    }

    public function test_toc_adds_unique_ids(): void
    {
        $res = (new TocService)->process('<h2>Intro</h2><p>x</p><h3>Detail</h3><h2>Intro</h2>');
        $this->assertCount(3, $res['toc']);
        $this->assertStringContainsString('id="intro"', $res['html']);
        $this->assertStringContainsString('id="intro-2"', $res['html']);
    }
}

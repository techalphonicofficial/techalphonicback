<?php

namespace Tests\Unit;

use App\Support\LegacyHtml;
use PHPUnit\Framework\TestCase;

class LegacyHtmlTest extends TestCase
{
    public function test_post_drops_toc_widget_classes_and_makes_links_relative(): void
    {
        $html = '<p class="wp-block-paragraph" style="color:red">Intro with <a href="https://www.techalphonic.com/ai-seo-services">AI SEO</a> and <a href="https://example.com/x">a source</a>.</p>'
            .'<div id="ez-toc-container"><p class="ez-toc-title">Table of Contents</p><ul><li>One</li></ul></div>'
            .'<h2 class="wp-block-heading"><span class="ez-toc-section" id="One"></span>One</h2><p></p><script>alert(1)</script>';

        $out = (new LegacyHtml)->post($html);

        $this->assertStringNotContainsString('Table of Contents', $out);
        $this->assertStringNotContainsString('class=', $out);
        $this->assertStringNotContainsString('style=', $out);
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringContainsString('<a href="/ai-seo-services/">AI SEO</a>', $out);
        $this->assertStringContainsString('<a href="https://example.com/x" target="_blank" rel="noopener">', $out);
        $this->assertStringContainsString('<h2>One</h2>', $out);
        $this->assertStringNotContainsString('<p></p>', $out);
    }

    public function test_elementor_keeps_copy_and_splits_faqs_from_process_steps(): void
    {
        $w = fn ($type, $inner) => '<div class="elementor-element elementor-widget" data-widget_type="'.$type.'"><div class="elementor-widget-container">'.$inner.'</div></div>';
        $html = $w('heading.default', '<h1>Gaming App Development Company</h1>')
            .$w('text-editor.default', 'We build fast, engaging games.')
            .$w('button.default', '<a href="#">Start Your Project</a>')
            .$w('form.default', '<form><input name="x"></form>')
            .$w('elementskit-heading.default', '<h2>Our Process</h2>')
            .$w('icon-box.default', '<h3 class="elementor-icon-box-title">Market research</h3><p class="elementor-icon-box-description">We study your audience.</p>')
            .$w('icon-box.default', '<h3 class="elementor-icon-box-title">Market research</h3><p class="elementor-icon-box-description">We study your audience.</p>')
            .$w('image.default', '<img src="https://www.techalphonic.com/wp-content/uploads/2026/05/a.webp">')
            .$w('elementskit-accordion.default', '<a class="elementskit-btn-link">01/ Planning</a><div class="elementskit-card-body"><p>We plan.</p></div>'
                .'<a class="elementskit-btn-link">How long does it take?</a><div class="elementskit-card-body"><p>Usually 8 weeks.</p></div>');

        $r = (new LegacyHtml)->elementor($html);

        $this->assertSame('Gaming App Development Company', $r['title']);
        $this->assertSame('We build fast, engaging games.', $r['intro']);
        $this->assertStringNotContainsString('Start Your Project', $r['content']);
        $this->assertStringNotContainsString('<form', $r['content']);
        $this->assertSame(1, substr_count($r['content'], 'Market research'), 'duplicate widgets are removed');
        $this->assertSame(['https://www.techalphonic.com/wp-content/uploads/2026/05/a.webp'], $r['images']);
        $this->assertSame([['title' => 'Planning', 'text' => 'We plan.']], $r['process']);
        $this->assertCount(1, $r['faqs']);
        $this->assertSame('How long does it take?', $r['faqs'][0]['q']);
    }
}

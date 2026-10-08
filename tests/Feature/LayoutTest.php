<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Layout rules that are easy to break with a one-line CSS change and hard to
 * spot by eye, so they are asserted here.
 */
class LayoutTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(public_path('css/style.css'));
    }

    /** The declarations inside the `.container.section` rule, if the rule exists. */
    private function containerSectionRule(): ?string
    {
        $css = $this->css();
        $start = strpos($css, '.container.section{');

        if ($start === false) {
            return null;
        }

        $end = strpos($css, '}', $start);

        return $end === false ? null : substr($css, $start, $end - $start);
    }

    public function test_section_pages_keep_the_bootstrap_container_gutters(): void
    {
        // .section sets the padding shorthand to 4rem 0, which would otherwise strip
        // the container's horizontal gutters and let .row's negative margins push
        // content past the viewport edge.
        $this->assertNotNull(
            $this->containerSectionRule(),
            '.container.section must exist to restore the horizontal padding'
        );
    }

    public function test_the_container_gutter_override_keeps_both_sides(): void
    {
        $rule = $this->containerSectionRule() ?? '';

        $this->assertStringContainsString('padding-left', $rule);
        $this->assertStringContainsString('padding-right', $rule);
    }
}

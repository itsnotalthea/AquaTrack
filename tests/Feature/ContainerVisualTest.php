<?php

namespace Tests\Feature;

use App\Models\ContainerInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContainerVisualTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    private function html(string $uri = '/staff/containers'): string
    {
        return $this->actingAs($this->staff())->get($uri)->assertOk()->getContent();
    }

    private function setCounts(int $full, int $empty): void
    {
        ContainerInventory::updateOrCreate(['status' => ContainerInventory::AT_STATION_FULL], ['quantity' => $full]);
        ContainerInventory::updateOrCreate(['status' => ContainerInventory::AT_STATION_EMPTY], ['quantity' => $empty]);
    }

    /**
     * The water is drawn as a group pushed down from the top of the jug, with a
     * clip the same height as the water. Both come straight from the fill ratio,
     * so reading them back off the page is the honest way to assert the geometry.
     *
     * @return array{top:int,height:int}
     */
    private function waterGeometry(string $html): array
    {
        preg_match('/<g data-water-rect\s+transform="translate\(0,\s*(\d+)\)"/', $html, $group);
        preg_match('/<clipPath id="waterBody">\s*<rect x="-150" y="0" width="400" height="(\d+)"/s', $html, $clip);

        return ['top' => (int) ($group[1] ?? -1), 'height' => (int) ($clip[1] ?? -1)];
    }

    public function test_water_is_animated_with_a_wavy_surface(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('class="aq-wave"', $html);
        $this->assertStringContainsString('class="aq-water"', $html);
        $this->assertStringContainsString('class="aq-glint"', $html);
    }

    public function test_water_body_and_wave_are_clipped_to_the_jug(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('clip-path="url(#containerClip)"', $html);
        $this->assertStringContainsString('clip-path="url(#waterBody)"', $html);
    }

    public function test_water_group_is_translated_so_the_fill_sits_on_the_jug_floor(): void
    {
        $this->setCounts(50, 50);

        ['top' => $top, 'height' => $height] = $this->waterGeometry($this->html());

        $this->assertGreaterThanOrEqual(0, $top);
        $this->assertGreaterThanOrEqual(0, $height);
        // the jug floor sits at y=132, so the water must finish exactly on it
        $this->assertSame(132, $top + $height);
    }

    public function test_water_never_renders_above_the_jug_or_below_the_floor(): void
    {
        foreach ([[0, 0], [0, 10], [1, 0], [10, 0], [3, 7]] as [$full, $empty]) {
            $this->setCounts($full, $empty);

            ['top' => $top, 'height' => $height] = $this->waterGeometry($this->html());

            $this->assertGreaterThanOrEqual(0, $top, "water overflows the jug neck for {$full}/{$empty}");
            $this->assertGreaterThanOrEqual(1, $height, "water has no height for {$full}/{$empty}");
            $this->assertLessThanOrEqual(132, $top + $height);
        }
    }

    public function test_the_polling_script_moves_the_water_with_a_transform(): void
    {
        $js = file_get_contents(public_path('js/aquatrack.js'));

        // the wave rides on the same group, so the script must move the group,
        // not just the body rect, or the surface would detach from the water
        $this->assertMatchesRegularExpression(
            '/data-water-rect\][\'"]\)\.attr\(\s*[\'"]transform[\'"]/',
            $js
        );
    }

    public function test_the_animations_are_defined_and_respect_reduced_motion(): void
    {
        $css = file_get_contents(public_path('css/style.css'));

        foreach (['aq-wave-slide', 'aq-swell', 'aq-glint-sweep'] as $name) {
            $this->assertStringContainsString('@keyframes '.$name, $css);
        }

        $this->assertMatchesRegularExpression(
            '/@media\(prefers-reduced-motion:reduce\)\{[^}]*\.aq-wave/',
            $css
        );
    }

    public function test_the_visual_renders_for_admin_and_staff(): void
    {
        $this->assertStringContainsString(
            'aq-wave',
            $this->actingAs(User::factory()->admin()->create())
                ->get('/admin/dashboard')->assertOk()->getContent(),
            'admin dashboard lost the water visual'
        );

        $this->assertStringContainsString(
            'aq-wave',
            $this->actingAs($this->staff())
                ->get('/staff/dashboard')->assertOk()->getContent(),
            'staff dashboard lost the water visual'
        );
    }
}

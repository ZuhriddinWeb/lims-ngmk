<?php

namespace Tests\Feature;

use App\Models\Lab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_labs_and_stats(): void
    {
        Lab::create([
            'code' => 'ГМЗ-5', 'name_ru' => 'Завод 5', 'name_uz' => 'Zavod 5',
            'name_en' => 'Plant 5', 'status' => 'live', 'url' => 'http://example.test', 'sort_order' => 1,
        ]);
        Lab::create([
            'code' => 'ГМЗ-1', 'name_ru' => 'Завод 1', 'name_uz' => 'Zavod 1',
            'name_en' => 'Plant 1', 'status' => 'soon', 'url' => null, 'sort_order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('labs', 2)
                ->where('stats.total', 2)
                ->where('stats.live', 1)
                ->where('stats.soon', 1)
            );
    }
}

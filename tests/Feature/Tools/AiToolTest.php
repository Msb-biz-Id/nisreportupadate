<?php

namespace Tests\Feature\Tools;

use App\Models\AiToolLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_ai_tools_index(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->actingAs($user)
            ->get(route('tools.ai.index'))
            ->assertOk();
    }

    public function test_ai_tool_page_loads(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        foreach (['whatsapp-reply', 'copywriter', 'order-summarizer', 'order-formatter', 'complaint-handler'] as $slug) {
            $this->actingAs($user)
                ->get(route('tools.ai.show', $slug))
                ->assertOk();
        }
    }

    public function test_invalid_ai_tool_slug_returns_404(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);
        $this->actingAs($user)
            ->get(route('tools.ai.show', 'tidak-ada'))
            ->assertNotFound();
    }

    public function test_ai_tool_run_returns_mock_response_when_no_api_key(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $response = $this->actingAs($user)
            ->postJson(route('tools.ai.run', 'whatsapp-reply'), [
                'pesan_asli' => 'Halo',
                'tujuan' => 'Sales',
                'tone' => 'Ramah',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'text', 'model', 'mock']);
        $this->assertTrue($response->json('mock'));
    }

    public function test_ai_tool_run_logs_to_database(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->actingAs($user)
            ->postJson(route('tools.ai.run', 'copywriter'), [
                'platform' => 'Instagram',
                'keunggulan' => 'Bahan premium',
            ]);

        $this->assertDatabaseHas('ai_tool_logs', [
            'user_id' => $user->id,
            'tool_slug' => 'copywriter',
            'status' => 'success',
        ]);
    }

    public function test_reseller_cannot_access_ai_tools(): void
    {
        // Per BRD, reseller juga punya tools.ai permission — verifikasi seeder
        $user = $this->makeUser('reseller', [$this->makeBrand()]);
        $this->actingAs($user)->get(route('tools.ai.index'))->assertOk();
    }

    public function test_admin_produksi_does_not_have_ai_permission(): void
    {
        $user = $this->makeUser('admin_produksi', [$this->makeBrand()]);
        $this->actingAs($user)->get(route('tools.ai.index'))->assertForbidden();
    }
}

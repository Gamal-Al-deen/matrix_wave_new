<?php

namespace Tests\Feature\Admin;

use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_and_customers_cannot_manage_social_links(): void
    {
        $link = $this->makeLink();

        $this->get(route('admin.social-links.index'))->assertRedirect(route('admin.login'));

        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.social-links.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.social-links.store'), [
                'platform' => 'facebook',
                'url' => 'https://example.test/unauthorized',
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.social-links.status', $link))
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete(route('admin.social-links.destroy', $link))
            ->assertForbidden();
    }

    public function test_administrators_can_create_update_toggle_and_delete_social_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.social-links.create'))
            ->assertOk()
            ->assertSee('إضافة رابط اجتماعي')
            ->assertSee('فيسبوك');

        $this->actingAs($admin)
            ->post(route('admin.social-links.store'), [
                'platform' => ' Facebook ',
                'url' => ' https://example.test/matrix-wave ',
                'sort_order' => '3',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $link = SocialLink::query()->sole();

        $this->assertSame('facebook', $link->platform->value);
        $this->assertSame('facebook', $link->icon_key);
        $this->assertSame('https://example.test/matrix-wave', $link->url);
        $this->assertSame(3, $link->sort_order);
        $this->assertTrue($link->is_active);

        $this->actingAs($admin)
            ->get(route('admin.social-links.index'))
            ->assertOk()
            ->assertSee('فيسبوك')
            ->assertSee('https://example.test/matrix-wave');

        $this->actingAs($admin)
            ->get(route('admin.social-links.edit', $link))
            ->assertOk()
            ->assertSee('تعديل الرابط الاجتماعي');

        $this->actingAs($admin)
            ->put(route('admin.social-links.update', $link), [
                'platform' => 'instagram',
                'url' => 'https://example.test/instagram',
                'sort_order' => '1',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.social-links.edit', $link))
            ->assertSessionHas('status');

        $link->refresh();

        $this->assertSame('instagram', $link->platform->value);
        $this->assertSame('instagram', $link->icon_key);
        $this->assertSame(1, $link->sort_order);
        $this->assertFalse($link->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.social-links.status', $link))
            ->assertRedirect();

        $this->assertTrue($link->refresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.social-links.destroy', $link))
            ->assertRedirect(route('admin.social-links.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('social_links', ['id' => $link->id]);
    }

    public function test_social_link_validation_rejects_unknown_platforms_unsafe_urls_and_negative_order(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.social-links.store'), [
                'platform' => 'unknown-platform',
                'url' => 'javascript:alert(1)',
                'sort_order' => '-1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors(['platform', 'url', 'sort_order']);

        $this->actingAs($admin)
            ->post(route('admin.social-links.store'), [
                'platform' => 'x',
                'url' => 'example.test/not-absolute',
                'sort_order' => '0',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('social_links', 0);
    }

    public function test_only_active_links_appear_in_the_public_footer_in_sort_order(): void
    {
        $this->makeLink([
            'platform' => 'facebook',
            'url' => 'https://example.test/facebook',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $this->makeLink([
            'platform' => 'instagram',
            'url' => 'https://example.test/instagram',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->makeLink([
            'platform' => 'x',
            'url' => 'https://example.test/inactive',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('تابعنا')
            ->assertSeeInOrder([
                'https://example.test/instagram',
                'https://example.test/facebook',
            ], false)
            ->assertDontSee('https://example.test/inactive', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('aria-label="إنستغرام — يفتح في نافذة جديدة"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLink(array $overrides = []): SocialLink
    {
        return SocialLink::query()->create(array_merge([
            'platform' => 'facebook',
            'url' => 'https://example.test/profile',
            'icon_key' => 'facebook',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }
}

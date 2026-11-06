<?php

namespace Tests\Feature;

use App\Models\AboutPageContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_about_page_renders_saved_plain_text_safely(): void
    {
        $this->makeAbout([
            'title' => 'قصتنا',
            'body' => "سطر أول\nسطر ثانٍ <script>alert(1)</script>",
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('قصتنا')
            ->assertSee('سطر أول')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_about_page_has_a_safe_fallback_when_content_is_missing(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('من نحن')
            ->assertSee('لم يُضف محتوى بعد');
    }

    public function test_only_administrators_can_edit_about_content(): void
    {
        $this->makeAbout();

        $this->get(route('admin.about.edit'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.about.edit'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.about.update'), [
                'title' => 'غير مصرح',
                'body' => 'غير مصرح',
            ])
            ->assertForbidden();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.about.edit'))
            ->assertOk()
            ->assertSee('صفحة من نحن')
            ->assertSee('لا تُنفَّذ أكواد HTML أو JavaScript');
    }

    public function test_administrators_can_update_about_content_and_the_editor_is_recorded(): void
    {
        $this->makeAbout();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.about.update'), [
                'title' => '  عن متجر الموجة  ',
                'body' => '  نحن متجر عربي يقدم تجربة طلب واضحة.  ',
            ])
            ->assertRedirect(route('admin.about.edit'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $about = AboutPageContent::current();

        $this->assertSame('عن متجر الموجة', $about->title);
        $this->assertSame('نحن متجر عربي يقدم تجربة طلب واضحة.', $about->body);
        $this->assertSame($admin->id, $about->updated_by);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('عن متجر الموجة')
            ->assertSee('نحن متجر عربي يقدم تجربة طلب واضحة.');
    }

    public function test_about_content_validation_rejects_empty_values_without_changing_saved_content(): void
    {
        $this->makeAbout([
            'title' => 'العنوان المحفوظ',
            'body' => 'المحتوى المحفوظ',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.about.update'), [
                'title' => '   ',
                'body' => '   ',
            ])
            ->assertSessionHasErrors(['title', 'body']);

        $about = AboutPageContent::current();

        $this->assertSame('العنوان المحفوظ', $about->title);
        $this->assertSame('المحتوى المحفوظ', $about->body);
        $this->assertNull($about->updated_by);
    }

    public function test_about_link_is_available_on_the_public_storefront(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.url('/about').'"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeAbout(array $overrides = []): AboutPageContent
    {
        return AboutPageContent::query()->forceCreate(array_merge([
            'id' => AboutPageContent::SINGLETON_ID,
            'title' => 'من نحن',
            'body' => 'محتوى تجريبي',
            'updated_by' => null,
        ], $overrides));
    }
}

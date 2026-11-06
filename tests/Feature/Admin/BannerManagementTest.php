<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_guests_are_redirected_and_customers_cannot_manage_banners(): void
    {
        $this->get(route('admin.banners.index'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.banners.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.banners.store'), [
                'alt_text' => 'وصف',
                'sort_order' => '0',
                'image' => $this->png('banner.png'),
            ])
            ->assertForbidden();
    }

    public function test_administrators_can_view_banner_pages(): void
    {
        $banner = $this->makeBanner([
            'title' => 'بانر الصفحة الرئيسية',
            'alt_text' => 'وصف بانر الصفحة الرئيسية',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('بانر الصفحة الرئيسية')
            ->assertSee('وصف بانر الصفحة الرئيسية');

        $this->actingAs($admin)
            ->get(route('admin.banners.create'))
            ->assertOk()
            ->assertSee('إضافة بانر')
            ->assertSee('صورة البانر');

        $this->actingAs($admin)
            ->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertSee('تعديل البانر')
            ->assertSee('استبدال الصورة');
    }

    public function test_administrators_can_create_a_scheduled_banner_with_an_image(): void
    {
        $start = now()->addDay()->startOfMinute();
        $end = now()->addDays(10)->startOfMinute();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.banners.store'), [
                'title' => '  عرض الصيف  ',
                'alt_text' => '  صورة عرض الصيف  ',
                'sort_order' => '3',
                'is_active' => '1',
                'starts_at' => $start->format('Y-m-d\TH:i'),
                'ends_at' => $end->format('Y-m-d\TH:i'),
                'image' => $this->png('banner.png'),
            ]);

        $banner = Banner::query()->sole();

        $response->assertRedirect(route('admin.banners.edit', $banner))
            ->assertSessionHas('status');

        $this->assertSame('عرض الصيف', $banner->title);
        $this->assertSame('صورة عرض الصيف', $banner->alt_text);
        $this->assertSame(3, $banner->sort_order);
        $this->assertTrue($banner->is_active);
        $this->assertTrue($banner->starts_at->equalTo($start));
        $this->assertTrue($banner->ends_at->equalTo($end));
        $this->assertStringStartsWith('banners/'.$banner->id.'/', $banner->image_storage_path);
        $this->assertNotEmpty($banner->image_public_url);
        Storage::disk('supabase')->assertExists($banner->image_storage_path);
    }

    public function test_banners_are_inactive_by_default_and_schedule_is_optional(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.banners.store'), [
                'alt_text' => 'بانر دون جدولة',
                'sort_order' => '0',
                'image' => $this->png('banner.png'),
            ])
            ->assertRedirect();

        $banner = Banner::query()->sole();

        $this->assertFalse($banner->is_active);
        $this->assertNull($banner->starts_at);
        $this->assertNull($banner->ends_at);
    }

    public function test_banner_validation_rejects_invalid_fields_and_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'alt_text' => '',
                'sort_order' => '-1',
                'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['alt_text', 'sort_order', 'ends_at', 'image']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'alt_text' => 'صورة غير صالحة',
                'sort_order' => '0',
                'image' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_administrators_can_update_banner_metadata_without_replacing_the_image(): void
    {
        $banner = $this->makeBanner([
            'title' => 'عنوان قديم',
            'image_storage_path' => 'banners/existing.png',
        ]);
        $imagePath = $banner->image_storage_path;
        $end = now()->addDays(5)->startOfMinute();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.banners.update', $banner), [
                'title' => 'عنوان محدّث',
                'alt_text' => 'وصف محدّث',
                'sort_order' => '9',
                'is_active' => '1',
                'starts_at' => '',
                'ends_at' => $end->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.banners.edit', $banner))
            ->assertSessionHasNoErrors();

        $banner->refresh();

        $this->assertSame('عنوان محدّث', $banner->title);
        $this->assertSame('وصف محدّث', $banner->alt_text);
        $this->assertSame(9, $banner->sort_order);
        $this->assertTrue($banner->is_active);
        $this->assertNull($banner->starts_at);
        $this->assertTrue($banner->ends_at->equalTo($end));
        $this->assertSame($imagePath, $banner->image_storage_path);
    }

    public function test_administrators_can_toggle_banner_status(): void
    {
        $banner = $this->makeBanner(['is_active' => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.banners.status', $banner))
            ->assertRedirect();

        $this->assertFalse($banner->refresh()->is_active);
    }

    public function test_replacing_a_banner_image_removes_the_previous_file(): void
    {
        $banner = $this->makeBanner();
        $oldPath = $this->attachImage($banner);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.banners.image.replace', $banner), [
                'image' => $this->png('replacement.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $banner->refresh();

        $this->assertNotSame($oldPath, $banner->image_storage_path);
        Storage::disk('supabase')->assertMissing($oldPath);
        Storage::disk('supabase')->assertExists($banner->image_storage_path);
    }

    public function test_deleting_a_banner_removes_its_image(): void
    {
        $banner = $this->makeBanner();
        $path = $this->attachImage($banner);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('supabase')->assertMissing($path);
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::ONE_PIXEL_PNG),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBanner(array $attributes = []): Banner
    {
        return Banner::query()->create(array_merge([
            'title' => 'بانر تجريبي',
            'image_storage_path' => 'banners/manual/test.png',
            'image_public_url' => 'https://example.test/banner.png',
            'alt_text' => 'وصف البانر التجريبي',
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ], $attributes));
    }

    private function attachImage(Banner $banner): string
    {
        $path = 'banners/'.$banner->id.'/'.uniqid().'.png';

        Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));

        $banner->update([
            'image_storage_path' => $path,
            'image_public_url' => 'https://example.test/'.$path,
        ]);

        return $path;
    }
}

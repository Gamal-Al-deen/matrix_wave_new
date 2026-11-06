<?php

namespace Tests\Feature\Storage;

use App\Support\ImageRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ImageRulesTest extends TestCase
{
    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_a_valid_png_passes_validation(): void
    {
        $validator = Validator::make(
            ['image' => $this->pngUpload()],
            ['image' => ImageRules::make()],
        );

        $this->assertTrue($validator->passes(), (string) $validator->errors());
    }

    public function test_a_disguised_php_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php echo "x";');

        $validator = Validator::make(['image' => $file], ['image' => ImageRules::make()]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $file = $this->pngUpload()->size((int) config('media.max_kilobytes') + 1024);

        $validator = Validator::make(['image' => $file], ['image' => ImageRules::make()]);

        $this->assertTrue($validator->fails());
    }

    public function test_the_image_is_required_by_default_but_optional_when_requested(): void
    {
        $this->assertTrue(Validator::make([], ['image' => ImageRules::make()])->fails());
        $this->assertTrue(Validator::make([], ['image' => ImageRules::make(false)])->passes());
    }

    private function pngUpload(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::ONE_PIXEL_PNG));
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StorageCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:storage-check {--disk= : اسم القرص (افتراضيًا قرص الوسائط)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'التحقق من الكتابة والقراءة والحذف في تخزين Supabase دون طباعة أي أسرار';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('media.disk', 'supabase'));
        $path = trim((string) config('media.folders.healthcheck', 'healthchecks'), '/').'/'.Str::uuid()->toString().'.txt';
        $payload = 'matrix-wave-storage-check '.now()->toIso8601String();

        try {
            Storage::disk($disk)->put($path, $payload);

            $contents = Storage::disk($disk)->get($path);

            if ($contents !== $payload) {
                $this->error('فشل التحقق: المحتوى المقروء لا يطابق المحتوى المكتوب.');

                return self::FAILURE;
            }

            Storage::disk($disk)->delete($path);

            if (Storage::disk($disk)->exists($path)) {
                $this->error('فشل التحقق: تعذر حذف الملف التجريبي.');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            $this->error('فشل التحقق من التخزين: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("تم التحقق من التخزين بنجاح على القرص [{$disk}].");

        return self::SUCCESS;
    }
}

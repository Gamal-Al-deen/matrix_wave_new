<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin
                            {email? : البريد الإلكتروني للمدير}
                            {--name= : اسم المدير}
                            {--password= : كلمة المرور (يُفضّل تركها فارغة لإدخالها بشكل مخفي)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إنشاء حساب مدير أو ترقية حساب موجود إلى مدير';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('البريد الإلكتروني للمدير');

        if (! is_string($email) || ! Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->passes()) {
            $this->error('البريد الإلكتروني غير صالح.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim($email));
        $existing = User::query()->where('email', $email)->first();

        if ($existing?->isAdmin()) {
            $this->info('هذا الحساب مدير بالفعل: '.$email);

            return self::SUCCESS;
        }

        if ($existing && ! $this->confirm('يوجد حساب عميل بهذا البريد. هل تريد ترقيته إلى مدير؟', false)) {
            $this->warn('لم يتم تغيير أي شيء.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('كلمة المرور (8 أحرف على الأقل)');

        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('كلمة المرور يجب أن تكون 8 أحرف على الأقل.');

            return self::FAILURE;
        }

        if (! $this->option('password') && $password !== $this->secret('تأكيد كلمة المرور')) {
            $this->error('كلمتا المرور غير متطابقتين.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('اسم المدير', 'مدير الموقع');

        $user = $existing ?? new User;
        $user->name = is_string($name) && trim($name) !== '' ? trim($name) : 'مدير الموقع';
        $user->email = $email;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->forceFill(['role' => UserRole::Admin]);
        $user->save();

        $this->info('تم إنشاء حساب المدير بنجاح: '.$email);
        $this->line('يمكنك الآن تسجيل الدخول من صفحة الدخول بعد اكتمال مرحلة المصادقة.');

        return self::SUCCESS;
    }
}

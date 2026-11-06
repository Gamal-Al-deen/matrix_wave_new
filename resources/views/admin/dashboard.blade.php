@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
    <div class="space-y-6">
        <section class="card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-brand-ink">
                        مرحبًا، {{ auth()->user()?->name }}
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                        هذه نظرة سريعة على الطلبات والعملاء والمنتجات وقيم المبيعات. كل مؤشر محسوب من بيانات الطلبات المحفوظة،
                        وحالة الدفع منفصلة عن حالة التسليم.
                    </p>
                </div>

                <div class="shrink-0 rounded-xl bg-surface-muted px-3 py-2 text-xs text-slate-600">
                    العملة الحالية:
                    <span class="font-extrabold text-brand-ink" dir="ltr">{{ $currentCurrency }}</span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">عرض الطلبات</a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline">إدارة المنتجات</a>
                <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline">إدارة المحافظ</a>
            </div>
        </section>

        <section aria-labelledby="metrics-heading">
            <div class="mb-4">
                <h2 id="metrics-heading" class="text-lg font-extrabold text-brand-ink">المؤشرات الرئيسية</h2>
                <p class="mt-1 text-sm leading-7 text-slate-600">
                    تُعرض المبالغ حسب عملة كل طلب. لا يتم جمع عملات مختلفة معًا ولا يتم تطبيق أي تحويل عملات.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @include('admin._metric-card', [
                    'label' => 'إجمالي الطلبات',
                    'count' => $metrics['orders'],
                    'description' => 'كل الطلبات المسجلة، بما فيها الملغاة. لا توجد طلبات محذوفة في النظام.',
                    'tone' => 'teal',
                ])

                @include('admin._metric-card', [
                    'label' => 'العملاء المسجلون',
                    'count' => $metrics['customers'],
                    'description' => 'حسابات العملاء فقط، دون حسابات الإدارة.',
                    'tone' => 'info',
                ])

                @include('admin._metric-card', [
                    'label' => 'المنتجات',
                    'count' => $metrics['products'],
                    'description' => 'المنتجات غير المحذوفة، بجميع حالاتها: متاح، غير متوفر، وغير مفعّل.',
                    'tone' => 'success',
                ])

                @include('admin._metric-card', [
                    'label' => 'قيمة الطلبات التشغيلية',
                    'amounts' => $metrics['operational_value'],
                    'description' => 'مجموع إجمالي الطلبات غير الملغاة، بغض النظر عن حالة الدفع.',
                    'tone' => 'orange',
                ])

                @include('admin._metric-card', [
                    'label' => 'المبيعات المؤكدة',
                    'amounts' => $metrics['confirmed_sales'],
                    'description' => 'مجموع الطلبات بحالات: مؤكد، قيد التجهيز، قيد التوصيل، أو مكتمل.',
                    'tone' => 'teal',
                ])

                @include('admin._metric-card', [
                    'label' => 'المبيعات المدفوعة',
                    'amounts' => $metrics['paid_sales'],
                    'description' => 'مجموع الطلبات غير الملغاة التي حالتها المدفوعة «مدفوع» فقط. لا تُحتسب المبالغ غير المدفوعة أو المستردة.',
                    'tone' => 'success',
                ])

                @include('admin._metric-card', [
                    'label' => 'قيمة الطلبات المكتملة',
                    'amounts' => $metrics['completed_value'],
                    'description' => 'مجموع الطلبات المكتملة، بصرف النظر عن حالة الدفع. هذه ليست قيمة المبيعات المدفوعة.',
                    'tone' => 'warning',
                ])
            </div>
        </section>

        <section class="card p-6" aria-labelledby="metric-definitions-heading">
            <h2 id="metric-definitions-heading" class="text-lg font-extrabold text-brand-ink">تعريفات المؤشرات</h2>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-bold text-brand-ink">إجمالي الطلبات</dt>
                    <dd class="mt-1 leading-7 text-slate-600">عدد كل الطلبات المسجلة غير المحذوفة، بما فيها الطلبات الملغاة.</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">العملاء المسجلون</dt>
                    <dd class="mt-1 leading-7 text-slate-600">عدد الحسابات التي تحمل دور عميل، دون حسابات الإدارة.</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">المنتجات</dt>
                    <dd class="mt-1 leading-7 text-slate-600">عدد المنتجات غير المحذوفة، بما فيها غير المفعّلة وغير المتوفرة.</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">قيمة الطلبات التشغيلية</dt>
                    <dd class="mt-1 leading-7 text-slate-600">مجموع إجمالي كل الطلبات التي ليست بحالة «ملغي».</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">المبيعات المؤكدة</dt>
                    <dd class="mt-1 leading-7 text-slate-600">مجموع الطلبات بحالة مؤكد أو قيد التجهيز أو قيد التوصيل أو مكتمل.</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">المبيعات المدفوعة</dt>
                    <dd class="mt-1 leading-7 text-slate-600">مجموع الطلبات غير الملغاة التي حالة دفعها «مدفوع» فقط.</dd>
                </div>
                <div>
                    <dt class="font-bold text-brand-ink">قيمة الطلبات المكتملة</dt>
                    <dd class="mt-1 leading-7 text-slate-600">مجموع الطلبات بحالة «مكتمل»، بصرف النظر عن حالة الدفع.</dd>
                </div>
            </dl>

            <p class="mt-5 rounded-xl bg-surface-muted px-4 py-3 text-xs leading-6 text-slate-600">
                المبالغ مجمعة حسب العملة المحفوظة في كل طلب. عند وجود أكثر من عملة تظهر قيمة منفصلة لكل عملة،
                ولا تُجمع العملات أو تُحوّل تلقائيًا.
            </p>
        </section>

        <section class="card border-brand-orange/40 bg-brand-orange/10 p-6">
            <h2 class="text-base font-extrabold text-brand-ink">ملاحظة أمنية</h2>
            <p class="mt-2 text-sm leading-7 text-ink/80">
                إخفاء الروابط لا يُعد تفويضًا. كل مسار إداري محمي بوسيط <code class="font-mono text-xs">admin</code>
                ويعيد <span class="font-bold">403</span> لأي مستخدم غير مدير، حتى لو عرف الرابط مباشرة.
            </p>
        </section>
    </div>
@endsection

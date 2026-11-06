@php
    $brandName = config('app.name', 'Matrix Wave');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ $brandName }}</title>
    <style>
        :root {
            color-scheme: light;
            --brand-ink: #0B3038;
            --brand-teal: #61D7C8;
            --brand-orange: #F9A51A;
            --surface: #FFFFFF;
            --surface-muted: #F4F8F8;
            --text: #18343A;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: var(--brand-ink);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            line-height: 1.8;
        }

        .card {
            width: min(100%, 42rem);
            padding: clamp(1.5rem, 5vw, 3rem);
            border-radius: 1.25rem;
            background: var(--surface);
            box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, 0.25);
            text-align: center;
        }

        .brand {
            color: var(--brand-ink);
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .code {
            margin: 1rem 0 0;
            color: var(--brand-teal);
            font-size: clamp(3rem, 12vw, 5.5rem);
            font-weight: 800;
            line-height: 1;
        }

        h1 {
            margin: 0.75rem 0 0.5rem;
            color: var(--brand-ink);
            font-size: clamp(1.25rem, 4vw, 1.75rem);
        }

        p {
            margin: 0 auto;
            max-width: 32rem;
            color: #3d5a60;
        }

        a {
            display: inline-block;
            margin-top: 1.75rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            background: var(--brand-orange);
            color: var(--brand-ink);
            font-weight: 700;
            text-decoration: none;
        }

        a:focus-visible {
            outline: 3px solid var(--brand-teal);
            outline-offset: 3px;
        }

        @media (max-width: 480px) {
            .card {
                border-radius: 1rem;
            }
        }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="brand">{{ $brandName }}</div>
        <div class="code" aria-hidden="true">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <a href="{{ route('home') }}">العودة إلى الصفحة الرئيسية</a>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - 勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>

    <header class="guest-header">
        <div class="header-content">
            <a href="{{ route('login') }}" class="app-logo">
                <img src="{{ asset('img/logo.svg') }}" alt="COACHTECH Logo" class="header-logo-img">
            </a>
        </div>
    </header>

    <main class="guest-main">
        @yield('content')
    </main>

</body>

</html>

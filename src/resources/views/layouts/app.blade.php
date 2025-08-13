<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - 勤怠管理アプリ </title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>

<body>
    
    <header class="app-header">
        <div class="header-content">
            <a href="{{ route('attendance.index') }}" class="app-logo">
                <img src="{{ asset('img/logo.svg') }}" alt="COACHTECH Logo" class="header-logo-img">
            </a>
            <nav>
                <ul>
                    <li><a href="{{ route('attendance.index') }}" class="header-link">ホーム</a></li>
                    <li><a href="{{ route('attendance.list') }}" class="header-link">勤怠一覧</a></li>
                    <li><a href="{{ route('user_requests.list') }}" class="header-link">申請一覧</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="app-main">
        @yield('content')
    </main>
</body>

</html>

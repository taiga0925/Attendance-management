<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - 勤怠管理アプリ </title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>

    <header class="admin-header">
        <div class="header-content">
            <a href="{{ route('admin.attendances.index') }}" class="app-logo">
                <img src="{{ asset('img/logo.svg') }}" alt="COACHTECH Logo" class="header-logo-img">
            </a>
            <nav>
                <ul>
                    <li><a href="{{ route('admin.attendances.index') }}" class="header-link">勤怠一覧</a></li>
                    <li><a href="{{ route('admin.users.index') }}" class="header-link">スタッフ一覧</a></li>
                    <li><a href="{{ route('admin.requests.list') }}" class="header-link">申請一覧</a></li>
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

    <main class="admin-main">
        @yield('content')
    </main>

</body>

</html>

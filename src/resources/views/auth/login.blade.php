@extends('layouts.guest')

@section('title', 'ログイン')

@section('content')
    <div class="auth-container">
        <h2>ログイン</h2>
        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            {{-- ログイン成功/失敗時のメッセージ表示 --}}
            @if (session('status'))
                <div class="success-message">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="error-message">
                    {{ session('error') }}
                </div>
            @endif

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="auth-button">ログインする</button>
            </div>

            <div class="auth-links">
                <a href="{{ route('register') }}">会員登録はこちら</a>
            </div>
        </form>
    </div>
@endsection

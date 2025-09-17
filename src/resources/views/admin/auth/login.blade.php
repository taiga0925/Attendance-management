@extends('layouts.guest')

@section('title', '管理者ログイン')

@section('content')
    <div class="auth-container">
        <h2>管理者ログイン</h2>

        {{-- ★★★ こちらが修正箇所です ★★★ --}}
        <form method="POST" action="{{ route('admin.login.post') }}" class="auth-form">
            @csrf

            {{-- ログイン成功/失敗時のメッセージ表示 --}}
            @if (session('status'))
                <div class="success-message">
                    {{ session('status') }}
                </div>
            @endif

            {{-- バリデーションエラーや認証エラーの表示 --}}
            @error('email')
                <div class="error-message">{{ $message }}</div>
            @enderror
            @error('password')
                <div class="error-message">{{ $message }}</div>
            @enderror


            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <button type="submit" class="auth-button">管理者ログインする</button>
            </div>
        </form>
    </div>
@endsection

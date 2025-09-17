@extends('layouts.guest') {{-- ログイン前の共通レイアウトを継承 --}}

@section('title', 'メールアドレス認証')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}"> {{-- 既存の認証画面CSSを再利用 --}}
@endsection

@section('content')
<div class="auth-container">
    <h2>メールアドレスの認証</h2>

    <div class="auth-message">
        ご登録ありがとうございます！<br>
        ご利用を開始する前に、ご登録いただいたメールアドレスに送信されたリンクをクリックして、認証を完了してください。
    </div>

    <div class="auth-message">
        もし認証メールが届いていない場合は、以下のボタンをクリックして再送信してください。
    </div>

    {{-- メール再送信が成功した場合のメッセージ --}}
    @if (session('status') == 'verification-link-sent')
        <div class="success-message" style="margin-bottom: 20px;">
            新しい認証リンクが、登録されたメールアドレスに送信されました。
        </div>
    @endif

    <div class="form-group-row">
        {{-- メール再送信フォーム --}}
        <form method="POST" action="{{ route('verification.send') }}" class="auth-form-inline">
            @csrf
            <button type="submit" class="auth-button">
                認証メールを再送信
            </button>
        </form>

        {{-- ログアウトフォーム --}}
        <form method="POST" action="{{ route('logout') }}" class="auth-form-inline">
            @csrf
            <button type="submit" class="logout-link-button">
                ログアウト
            </button>
        </form>
    </div>
</div>
@endsection

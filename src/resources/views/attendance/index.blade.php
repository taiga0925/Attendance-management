@extends('layouts.app')

@section('title', '勤怠打刻')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
    <div class="attendance-container">

        <div class="status-badge">
            {{ $status }}
        </div>

        <div class="date-display">
            {{ $current_date_time->isoFormat('YYYY年M月D日(ddd)') }}
        </div>

        {{-- 現在時刻のリアルタイム表示 --}}
        <div class="current-time-display">
            <span id="current-time"></span>
        </div>

        {{-- 成功メッセージ --}}
        @if (session('status_message'))
            <div class="alert alert-success">
                {{ session('status_message') }}
            </div>
        @endif

        {{-- エラーメッセージ --}}
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="attendance-buttons">

            {{-- ステータスに応じたボタン表示制御 --}}
            @if ($status === '勤務外')
                {{-- 出勤ボタン --}}
                <form action="{{ route('attendance.clockIn') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button primary" onclick="this.form.submit(); this.disabled=true;">出勤</button>
                </form>
            @elseif ($status === '出勤中')
                {{-- 休憩開始ボタン --}}
                <form action="{{ route('attendance.startBreak') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button secondary" onclick="this.form.submit(); this.disabled=true;">休憩入</button>
                </form>

                {{-- 退勤ボタン --}}
                <form action="{{ route('attendance.clockOut') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button primary" onclick="this.form.submit(); this.disabled=true;">退勤</button>
                </form>
            @elseif ($status === '休憩中')
                {{-- 休憩終了ボタン --}}
                <form action="{{ route('attendance.endBreak') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button secondary" onclick="this.form.submit(); this.disabled=true;">休憩戻</button>
                </form>
            @elseif ($status === '退勤済')
                {{-- 退勤済み --}}
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>

    <script>
        const timeElement = document.getElementById('current-time');

        function updateCurrentTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            timeElement.textContent = `${hours}:${minutes}:${seconds}`;
        }

        // ページロード時に一度実行
        updateCurrentTime();
        // 1秒ごとに更新
        setInterval(updateCurrentTime, 1000);
    </script>
@endsection

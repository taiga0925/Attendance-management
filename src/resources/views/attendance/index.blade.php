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
                    <button type="submit" class="attendance-button primary">出勤</button>
                </form>
            @elseif ($status === '出勤中')

                {{-- 休憩開始ボタン --}}
                <form action="{{ route('attendance.startBreak') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button secondary">休憩入</button>
                </form>

                {{-- 退勤ボタン --}}
                <form action="{{ route('attendance.clockOut') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button primary">退勤</button>
                </form>

            @elseif ($status === '休憩中')

                {{-- 休憩終了ボタン --}}
                <form action="{{ route('attendance.endBreak') }}" method="POST" class="attendance-form">
                    @csrf
                    <button type="submit" class="attendance-button secondary">休憩戻</button>
                </form>

            @elseif ($status === '退勤済')

                {{-- 退勤済み --}}
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>

    <script>
        /**
         * 現在時刻を1分単位で更新
         */
        function updateCurrentTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}`;
        }

        // ページロード時に一度実行
        updateCurrentTime();
        // 1分ごとに更新 (60 * 1000 ミリ秒)
        setInterval(updateCurrentTime, 60 * 1000);
    </script>
@endsection

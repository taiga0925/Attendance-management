@extends('layouts.admin') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '修正申請承認')

@section('css')
    {{-- 管理者用の勤怠詳細画面とレイアウトが似ているので、同じCSSを再利用します --}}
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
    <div class="detail-container">
        <h2 class="page-title">｜勤怠詳細</h2>

        <div class="detail-wrapper">
            {{-- 成功メッセージ表示 --}}
            @if (session('status_message'))
                <div class="alert alert-success" style="margin-bottom: 20px; text-align: center; color: green;">
                    {{ session('status_message') }}
                </div>
            @endif

            <table class="detail-table">
                <tr>
                    <th>名前</th>
                    <td>{{ $request->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>{{ $request->attendance->date->format('Y年') }}<span class="date-space"></span>{{ $request->attendance->date->isoFormat('M月D日') }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="time-inputs">
                            {{-- 申請された時刻を表示 --}}
                            <span class="time-display">{{ \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i') }}</span>
                            <span class="time-separator">~</span>
                            <span class="time-display">{{ \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i') }}</span>
                        </div>
                    </td>
                </tr>

                {{-- 申請された休憩時間を表示 --}}
                @if ($requestedBreaks)
                    @foreach ($requestedBreaks as $index => $break)
                        <tr>
                            <th>休憩{{ $index + 1 }}</th>
                            <td>
                                <div class="time-inputs">
                                    <span class="time-display">{{ $break['break_start'] }}</span>
                                    <span class="time-separator">~</span>
                                    <span class="time-display">{{ $break['break_end'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif

                <tr>
                    <th>備考</th>
                    <td>{{ $request->remarks }}</td>
                </tr>
            </table>
        </div>

        <div class="form-actions">
            {{-- ★★★ 申請ステータスに応じてボタンの表示を切り替えます ★★★ --}}
            @if ($request->status == 'pending')
                {{-- 承認ボタンのフォーム --}}
                <form action="{{ route('admin.requests.approve', ['request' => $request->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="submit-button">承認</button>
                </form>
            @else
                {{-- 承認済みの場合の表示 --}}
                <button type="button" class="submit-button approved" disabled>承認済み</button>
            @endif
        </div>
    </div>
@endsection

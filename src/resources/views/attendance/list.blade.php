@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection


@section('content')
    <div class="attendance-list-container">
        <h2 class="page-title">
            ｜勤怠一覧
        </h2>

        <div class="month-navigation">
            <a href="{{ route('attendance.list', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="nav-link">← 前月</a>
            <span class="current-month-display">
                <img src="{{ asset('img/calendar.svg') }}" alt="Calendar Icon" class="calendar-icon">
                <span class="current-month-text">{{ $currentMonth->isoFormat('YYYY/MM') }}</span>
            </span>
            <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="nav-link">翌月 →</a>
        </div>

        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 月の全日をループして表示 --}}
                    @for ($day = 1; $day <= $currentMonth->daysInMonth; $day++)
                        @php
                            // 修正点: コントローラーから渡された$currentMonthのインスタンスを利用して日付を生成
                            $date = $currentMonth->copy()->day($day);
                            $attendance = $attendancesData[$day]?? null;
                        @endphp
                        <tr>
                            <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                            <td>{{ $attendance['clock_in']?? '-' }}</td>
                            <td>{{ $attendance['clock_out']?? '-' }}</td>
                            <td>{{ $attendance?? '-' }}</td>
                            <td>{{ $attendance?? '-' }}</td>
                            <td>
                                @if ($attendance['id']?? false)
                                    <a href="{{ route('attendance.detail', ['id' => $attendance['id']]) }}" class="detail-link">詳細</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
@endsection

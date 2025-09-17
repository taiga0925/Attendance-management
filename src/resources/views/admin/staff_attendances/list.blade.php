@extends('layouts.admin')

@section('title', $user->name. 'さんの勤怠')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-staff-attendances.css') }}"> 
@endsection

@section('content')
    <div class="attendance-list-container">
        <h2 class="page-title">｜{{ $user->name }}さんの勤怠</h2>

        <div class="month-navigation">
            <a href="{{ route('admin.users.attendances', ['user' => $user->id, 'year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="nav-link">← 前月</a>
            <span class="current-month-display">
                <img src="{{ asset('img/calendar.svg') }}" alt="Calendar Icon" class="calendar-icon">
                <span class="current-month-text">{{ $currentMonth->isoFormat('YYYY/MM') }}</span>
            </span>
            <a href="{{ route('admin.users.attendances', ['user' => $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="nav-link">翌月 →</a>
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
                    @for ($day = 1; $day <= $currentMonth->daysInMonth; $day++)
                        @php
                            $date = $currentMonth->copy()->day($day);
                            $attendance = $attendancesData[$day]?? null;
                        @endphp
                        <tr>
                            <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                            <td>{{ $attendance? $attendance->clock_in->format('H:i') : '-' }}</td>
                            <td>{{ $attendance? $attendance->clock_out?->format('H:i') : '-' }}</td>
                            <td>{{ $attendance? $attendance->total_break_time : '-' }}</td>
                            <td>{{ $attendance? $attendance->total_work_time : '-' }}</td>
                            <td>
                                @if ($attendance)
                                    <a href="{{ route('admin.attendances.detail', ['attendance' => $attendance->id]) }}" class="detail-link">詳細</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- CSV出力ボタン --}}
        <div class="export-button-wrapper">
            <a href="{{ route('admin.users.attendances.export', ['user' => $user->id, 'year' => $currentMonth->year, 'month' => $currentMonth->month]) }}" class="export-button">CSV出力</a>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', '勤怠一覧')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endsection

@section('content')
    <div class="admin-container">
        <h2 class="page-title">
            {{ $date->isoFormat('YYYY年M月D日') }}の勤怠
        </h2>

        <div class="date-navigation">
            <a href="{{ route('admin.attendances.index',) }}" class="nav-link">← 前日</a>
            <span class="current-date">{{ $date->format('Y/m/d') }}</span>
            <a href="{{ route('admin.attendances.index',) }}" class="nav-link">翌日 →</a>
        </div>

        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->user->name }}</td>
                            <td>{{ $attendance->clock_in?->format('H:i:s') }}</td>
                            <td>{{ $attendance->clock_out?->format('H:i:s') }}</td>
                            <td>{{ $attendance->totalBreakTime }}</td>
                            <td>{{ $attendance->totalWorkTime }}</td>
                            <td>
                                <a href="#" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="no-data">この日の勤怠記録はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

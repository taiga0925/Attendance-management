@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
    <div class="detail-container">
        <h2 class="page-title">｜勤怠詳細</h2>

        @if ($isPending)
            {{-- 承認待ちの場合の表示 --}}
            <div class="detail-wrapper readonly">
                <table class="detail-table">
                    <tr>
                        <th>名前</th>
                        <td>{{ $attendance->user->name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>{{ $attendance->date->format('Y年') }}<span class="date-space"></span>{{ $attendance->date->isoFormat('M月D日') }}</td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <div class="time-inputs">
                                <span class="time-display">{{ $attendance->clock_in?->format('H:i') }}</span>
                                <span class="time-separator">~</span>
                                <span class="time-display">{{ $attendance->clock_out?->format('H:i') }}</span>
                            </div>
                        </td>
                    </tr>
                    @foreach ($attendance->userBreaks as $index => $break)
                        <tr>
                            <th>休憩{{ $index + 1 }}</th>
                            <td>
                                <div class="time-inputs">
                                    <span class="time-display">{{ $break->break_start?->format('H:i') }}</span>
                                    <span class="time-separator">~</span>
                                    <span class="time-display">{{ $break->break_end?->format('H:i') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>備考</th>
                        <td>{{ $attendance->stampCorrectionRequest->remarks }}</td>
                    </tr>
                </table>
            </div>
            <div class="pending-message">
                *承認待ちのため修正はできません。
            </div>
        @else
            {{-- 申請中でない場合の表示 --}}
            <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="detail-wrapper">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table class="detail-table">
                        <tr>
                            <th>名前</th>
                            <td>{{ $attendance->user->name }}</td>
                        </tr>
                        <tr>
                            <th>日付</th>
                            <td>{{ $attendance->date->format('Y年') }}<span class="date-space"></span>{{ $attendance->date->isoFormat('M月D日') }}</td>
                        </tr>
                        <tr>
                            <th>出勤・退勤</th>
                            <td>
                                <div class="time-inputs">
                                    <input type="time" name="clock_in" value="{{ old('clock_in', $attendance->clock_in?->format('H:i')) }}" step="60">
                                    <span class="time-separator">~</span>
                                    <input type="time" name="clock_out" value="{{ old('clock_out', $attendance->clock_out?->format('H:i')) }}" step="60">
                                </div>
                            </td>
                        </tr>
                        @foreach ($attendance->userBreaks as $index => $break)
                            <tr>
                                <th>休憩{{ $index + 1 }}</th>
                                <td>
                                    <div class="time-inputs">
                                        <input type="time" name="breaks[{{ $break->id }}][break_start]" value="{{ old('breaks.'.$break->id.'.break_start', $break->break_start?->format('H:i')) }}" step="60">
                                        <span class="time-separator">~</span>
                                        <input type="time" name="breaks[{{ $break->id }}][break_end]" value="{{ old('breaks.'.$break->id.'.break_end', $break->break_end?->format('H:i')) }}" step="60">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <th>休憩{{ count($attendance->userBreaks) + 1 }}</th>
                            <td>
                                <div class="time-inputs">
                                    <input type="time" name="new_breaks[break_start]" value="{{ old('new_breaks.break_start') }}" step="60">
                                    <span class="time-separator">~</span>
                                    <input type="time" name="new_breaks[break_end]" value="{{ old('new_breaks.break_end') }}" step="60">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>備考</th>
                            <td>
                                <textarea name="remarks" rows="4">{{ old('remarks') }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-button">修正</button>
                </div>
            </form>
        @endif
    </div>
@endsection

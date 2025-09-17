@extends('layouts.admin')

@section('title', '申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user-requests.css') }}">
@endsection

@section('content')
    <div class="requests-container">
        <h2 class="page-title">｜申請一覧</h2>

        {{-- タブナビゲーション --}}
        <div class="tabs">
            <a href="{{ route('admin.requests.list', ['tab' => 'pending']) }}"
               class="tab-link {{ $activeTab == 'pending'? 'active' : '' }}">
               承認待ち
            </a>
            <a href="{{ route('admin.requests.list', ['tab' => 'processed']) }}"
               class="tab-link {{ $activeTab == 'processed'? 'active' : '' }}">
               承認済み
            </a>
        </div>

        {{-- タブの中身 --}}
        <div class="tab-content">
            <div class="table-wrapper">
                @if ($activeTab == 'pending')
                    {{-- 承認待ちテーブル --}}
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日時</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRequests as $request)
                                <tr>
                                    <td>承認待ち</td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ $request->attendance->date->format('Y/m/d') }}</td>
                                    <td class="remarks-cell">{{ $request->remarks }}</td>
                                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                                    <td>
                                        {{-- 申請詳細・承認画面へのリンク --}}
                                        <a href="{{ route('admin.requests.show', ['request' => $request->id]) }}" class="detail-link">詳細</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">承認待ちの申請はありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    {{-- 承認済みテーブル --}}
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日時</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($processedRequests as $request)
                                <tr>
                                    <td>
                                        @if ($request->status == 'approved')
                                            承認済み
                                        @elseif ($request->status == 'rejected')
                                            却下
                                        @else
                                            管理者修正
                                        @endif
                                    </td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ $request->attendance->date->format('Y/m/d') }}</td>
                                    <td class="remarks-cell">{{ $request->remarks }}</td>
                                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                                    <td>
                                        {{-- 申請詳細・承認画面へのリンク --}}
                                        <a href="{{ route('admin.requests.show', ['request' => $request->id]) }}" class="detail-link">詳細</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">処理済みの申請はありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">
@endsection

@section('content')
    <div class="users-container">
        <h2 class="page-title">｜スタッフ一覧</h2>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                {{-- 各ユーザーの月次勤怠一覧に遷移する --}}
                                <a href="{{ route('admin.users.attendances', ['user' => $user->id]) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ページネーションリンクの表示 --}}
        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>
    </div>
@endsection

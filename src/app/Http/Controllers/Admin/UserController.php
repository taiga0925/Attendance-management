<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * @return view ビュー
     * スタッフ一覧画面
     */
    public function index()
    {

        // 'role'が'general'のユーザー（一般ユーザー）のみを取得
        $users = User::where('role', 'general')->paginate(10); // 1ページあたり10件表示

        return view('admin.users.list', compact('users'));
    }
}

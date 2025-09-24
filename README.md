怠管理アプリケーション (Attendance Management Application)
概要
このアプリケーションは、企業向けの勤怠管理システムです。一般ユーザーは日々の勤怠打刻や修正申請を行い、管理者ユーザーは全スタッフの勤怠状況の確認、勤怠データの直接修正、月次勤怠のCSV出力、修正申請の承認などを行うことができます。

主な機能
一般ユーザー機能
会員登録、ログイン・ログアウト機能

メール認証機能

出勤・退勤の打刻

休憩開始・終了の打刻

自身の月次勤怠一覧の確認

日ごとの勤怠詳細の確認と修正申請

自身の申請状況（承認待ち／承認済み）の確認

管理者機能
管理者専用のログイン・ログアウト機能

全スタッフの日次勤怠一覧の確認

スタッフ一覧の確認

スタッフ別の月次勤怠一覧の確認

月次勤怠データのCSV出力

日ごとの勤怠詳細の確認と直接修正

一般ユーザーからの修正申請一覧の確認と承認

技術スタック
バックエンド: PHP / Laravel

データベース: MySQL

Webサーバー: Nginx

開発環境: Docker

#使用技術(実行環境) PHP 8.4.4 Laravel Framework 8.83.8 mysql Ver 15.1 Distrib 10.3.39-MariaDB

環境構築手順
1. 前提条件
Gitがインストールされていること

DockerおよびDocker Composeがインストールされ、起動していること

2. セットアップ手順
以下のコマンドをターミナルで順番に実行してください。

Bash

# 1. このリポジトリをクローンします
git clone git@github.com:taiga0925/Attendance-management.git

# 2. プロジェクトディレクトリに移動します
cd Attendance-management

# 3. Dockerコンテナをビルドして、バックグラウンドで起動します
docker-compose up -d --build

# 4. PHPコンテナに入り、Composerで依存パッケージをインストールします
docker-compose exec php composer install

# 5. Laravelの環境設定ファイルを作成します
# (srcディレクトリ内で.env.example をコピーして.env を作成)
docker-compose exec php cp.env.example.env

# 6. アプリケーションキーを生成します
docker-compose exec php php artisan key:generate

# 7. データベースのテーブルを作成します
docker-compose exec php php artisan migrate

# 8. テスト用のダミーデータを作成します
管理者ユーザー

役割	メールアドレス	パスワード

管理者	admin@test.com	admintest1

管理者	master@test.com	admintest2

一般ユーザー

5名の一般ユーザーが作成されます。名前とメールアドレスはランダムに生成されますが、パスワードは全員共通です。

役割	パスワード

一般ユーザー	password

docker-compose exec php php artisan db:seed
3. .envファイルの設定について
上記の手順でsrc/.envファイルが作成されますが、データベース接続情報がご自身のdocker-compose.ymlの設定と一致しているかご確認ください。今回開発した環境では、以下の設定値が使用されています。

コード スニペット

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
アプリケーションへのアクセス
環境構築後、以下のURLにブラウザでアクセスしてください。

一般ユーザー ログイン画面: http://localhost/login

管理者 ログイン画面: http://localhost/admin/login

テストユーザー / ダミーデータ
php artisan db:seedコマンドを実行すると、ログインテスト用の以下の管理者ユーザーが作成されます 。   

役割	メールアドレス	パスワード
管理者	admin@test.com	admintest1
管理者	master@test.com	admintest2

Google スプレッドシートにエクスポート
メールテスト環境 (MailHog)
本アプリケーションは、メール認証機能のテストのためにMailHogを使用しています。
Dockerコンテナ起動後、以下のURLにアクセスすることで、開発環境から送信されたメール（新規登録時の認証メールなど）をすべて確認できます。

MailHog 受信トレイ: http://localhost:8025

データベース設計 (ER図)
本アプリケーションのデータベース設計は、リポジトリに含まれるER.drawio.pngファイルをご参照ください。

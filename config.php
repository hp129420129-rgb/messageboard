<?php
// ==========================================
//  config.php
//  データベースの設定をするファイル
// ==========================================

// データベースの接続情報
$db_host = "127.0.0.1";    // データベースのサーバー
$db_user = "root";         // ユーザー名
$db_pass = "";             // パスワード
$db_name = "messageboard"; // 使うデータベースの名前

// エラーを自分で表示したいので、mysqli の自動エラー表示はオフにする
mysqli_report(MYSQLI_REPORT_OFF);

// データベースに接続する
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// 接続できなかったときは、メッセージを出して終わる
if ($conn->connect_error) {
    die("データベースに接続できません: " . $conn->connect_error);
}

// 文字コードを UTF-8 にする（日本語が文字化けしないように）
$conn->set_charset("utf8mb4");

// ------------------------------------------
//  XSS を防ぐための関数
//  HTML の中で意味を持つ文字を変換して、
//  <script> などが実行されないようにする
// ------------------------------------------
function h($text)
{
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

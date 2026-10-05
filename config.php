<?php
// ==========================================
//  config.php
//  データベースの設定
// ==========================================

// 接続情報
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "messageboard";

// エラーは自分で表示する
mysqli_report(MYSQLI_REPORT_OFF);

// データベースに接続
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// 接続できなかったとき
if ($conn->connect_error) {
    die("データベースに接続できません: " . $conn->connect_error);
}

// 文字コードを UTF-8 にする
$conn->set_charset("utf8mb4");

// XSS 対策の関数
// HTML の文字を変換して、<script> などが実行されないようにする
function h($text)
{
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

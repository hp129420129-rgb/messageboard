<?php
// ==========================================
//  index.php
//  トップページ（フォームと投稿一覧）
// ==========================================

// データベースに接続
require_once "config.php";

// エラーメッセージ用
$errors = array();

// 入力した値を覚えておく（エラーのときに入れ直す）
$name = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // フォームの値を受け取る
    $name = trim($_POST["name"]);
    $message = trim($_POST["message"]);

    // 名前が空なら「匿名」
    if ($name == "") {
        $name = "匿名";
    }

    // 入力チェック
    if ($message == "") {
        $errors[] = "メッセージを入力してください。";
    }
    if (mb_strlen($name) > 20) {
        $errors[] = "名前は20文字までにしてください。";
    }
    if (mb_strlen($message) > 500) {
        $errors[] = "メッセージは500文字までにしてください。";
    }

    // 画像のアップロード
    $image_path = NULL;

    // ファイルが選ばれているか（4 は選んでいないという意味）
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] != 4) {

        $file_size = $_FILES["image"]["size"];
        $file_name = $_FILES["image"]["name"];

        // 拡張子を取り出す
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $ok_ext = array("jpg", "jpeg", "png", "gif");

        // アップロードでエラーがあったとき
        if ($_FILES["image"]["error"] != 0) {
            $errors[] = "画像をアップロードできませんでした。";
        }
        // 5MB より大きいとき
        else if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "画像は5MBまでです。";
        }
        // 画像の種類をチェック
        else if (!in_array($ext, $ok_ext)) {
            $errors[] = "jpg / png / gif の画像だけアップロードできます。";
        }
        // 問題なければ保存する
        else {
            // ファイル名がかぶらないように新しく作る
            $new_name = date("YmdHis") . "_" . rand(1000, 9999) . "." . $ext;
            $save_path = "uploads/" . $new_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $save_path)) {
                $image_path = $save_path;
            } else {
                $errors[] = "画像を保存できませんでした。";
            }
        }
    }

    // エラーがなければ保存
    if (count($errors) == 0) {

        // 投稿した時間
        $created_at = date("Y-m-d H:i:s");

        // SQL インジェクション対策で prepare を使う
        $sql = "INSERT INTO posts (name, message, image_path, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $message, $image_path, $created_at);
        $stmt->execute();
        $stmt->close();

        // 二重投稿を防ぐためにリダイレクト
        header("Location: index.php");
        exit;
    }
}

// 投稿を新しい順に取ってくる
$sql = "SELECT * FROM posts ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <!-- スマートフォン用 -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>メッセージボード</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>メッセージボード</h1>

<!-- 入力フォーム -->
<div class="form-box">
    <h2>メッセージを書く</h2>

    <?php if (count($errors) > 0): ?>
        <ul class="error">
            <?php foreach ($errors as $e): ?>
                <li><?php echo h($e); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="index.php" method="post" enctype="multipart/form-data">
        <p>
            <label>名前（空でもいいです）</label>
            <input type="text" name="name" value="<?php echo h($name); ?>" maxlength="20">
        </p>

        <p>
            <label>メッセージ</label>
            <textarea name="message" rows="4" maxlength="500"><?php echo h($message); ?></textarea>
        </p>

        <p>
            <label>画像（5MBまで・jpg / png / gif）</label>
            <input type="file" name="image">
        </p>

        <p>
            <input type="submit" value="投稿する" class="button">
        </p>
    </form>
</div>

<!-- 投稿の一覧 -->
<h2>投稿一覧</h2>

<?php if ($result->num_rows == 0): ?>
    <p>まだ投稿がありません。</p>
<?php endif; ?>

<?php while ($row = $result->fetch_assoc()): ?>
    <div class="post" id="post-<?php echo $row["id"]; ?>">

        <p class="post-head">
            <!-- 投稿番号（DBで自動でつく） -->
            <span class="no">No.<?php echo $row["id"]; ?></span>
            <!-- XSS 対策で h() を通す -->
            <span class="name"><?php echo h($row["name"]); ?></span>
            <span class="date"><?php echo h($row["created_at"]); ?></span>
        </p>

        <p class="post-message">
            <?php echo nl2br(h($row["message"])); ?>
        </p>

        <?php if ($row["image_path"] != NULL): ?>
            <p>
                <img src="<?php echo h($row["image_path"]); ?>" alt="投稿された画像">
            </p>
        <?php endif; ?>

        <p class="link">
            <a href="#post-<?php echo $row["id"]; ?>">この投稿へのリンク</a>
        </p>
    </div>
<?php endwhile; ?>

</body>
</html>

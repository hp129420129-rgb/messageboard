# メッセージボード（PHP + MySQL）

PHP と MySQL で作った、かんたんなメッセージボードです。
メッセージと画像を投稿できます。

## 1. できること

- 名前とメッセージを入力して投稿する
- 画像をアップロードする（5MB まで・jpg / png / gif）
- 投稿した時間と投稿番号を自動でつける
- 投稿を新しい順に表示する
- SQL インジェクション対策（prepare を使う）
- XSS 対策（htmlspecialchars を使う）
- スマートフォンでも見やすくする（CSS）

## 2. ファイルの説明

```
messageboard/
├── index.php     … メインページ（フォームと一覧）
├── config.php    … データベースの設定
├── style.css     … 見た目の設定
├── schema.sql    … データベースとテーブルを作る SQL
├── uploads/      … アップロードされた画像を入れるフォルダ
└── README.md     … このファイル
```

## 3. パソコンで動かす（XAMPP）

1. XAMPP をインストールして、Apache と MySQL をスタートする。
2. `messageboard` フォルダを `C:\xampp\htdocs\` の中にコピーする。
3. データベースを作る。

   `http://localhost/phpmyadmin` を開いて、「インポート」から `schema.sql` を実行する。

4. ブラウザで `http://localhost/messageboard/` を開く。

XAMPP の MySQL は、ユーザー名 `root`・パスワードなしなので、`config.php` はそのままで動きます。

## 4. AWS EC2 で公開する

Amazon Linux 2023 を使います。`<IP>` は自分のサーバーの IP アドレスです。

### 4-1. EC2 を起動する

1. AWS の EC2 で「インスタンスを起動」を押す。
2. AMI は **Amazon Linux 2023**、インスタンスタイプは `t2.micro` を選ぶ。
3. キーペアを新しく作って、`.pem` ファイルをダウンロードする。
4. セキュリティグループで、次のポートを開ける。

| 種類 | ポート | どこから |
| --- | --- | --- |
| SSH | 22 | 自分の IP |
| HTTP | 80 | 0.0.0.0/0 |

### 4-2. SSH で接続する

```bash
ssh -i "ダウンロードしたキー.pem" ec2-user@<IP>
```

### 4-3. ソフトをインストールする

```bash
sudo dnf update -y
sudo dnf install -y nginx php8.2 php8.2-fpm php8.2-mysqlnd php8.2-mbstring mariadb105-server

sudo systemctl enable --now nginx
sudo systemctl enable --now php-fpm
sudo systemctl enable --now mariadb
```

### 4-4. データベースを作る

```bash
sudo mysql
```

MySQL の中で、次の命令を実行します。

```sql
CREATE DATABASE messageboard DEFAULT CHARACTER SET utf8mb4;
CREATE USER 'mbuser'@'localhost' IDENTIFIED BY 'ここにパスワード';
GRANT ALL PRIVILEGES ON messageboard.* TO 'mbuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4-5. コードをサーバーに置く

```bash
cd /var/www
sudo git clone https://github.com/<ユーザー名>/messageboard.git
```

### 4-6. config.php を直す

```bash
sudo nano /var/www/messageboard/config.php
```

`$db_user` と `$db_pass` を、4-4 で作ったユーザー名とパスワードに変えます。

```php
$db_user = "mbuser";
$db_pass = "ここにパスワード";
```

保存は `Ctrl + O`、`Enter`、終了は `Ctrl + X` です。

### 4-7. テーブルを作る

```bash
sudo mysql -u root messageboard < /var/www/messageboard/schema.sql
```

### 4-8. Nginx の設定をする

```bash
sudo nano /etc/nginx/conf.d/messageboard.conf
```

次を書いて保存します。

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/messageboard;
    index index.php;

    client_max_body_size 10m;

    location / {
        try_files $uri $uri/ /index.php;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

設定を確認して、Nginx を再起動します。

```bash
sudo nginx -t
sudo systemctl restart nginx
```

**ここで注意**：Amazon Linux の php-fpm は、最初は TCP ではなく **Unix ソケット**を使っています。
そのまま nginx を動かすと **502 Bad Gateway** になるので、php-fpm を TCP 9000 に変えます。

```bash
sudo sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' /etc/php-fpm.d/www.conf
sudo systemctl restart php-fpm

# 9000 番が LISTEN しているか確認する
sudo ss -tlnp | grep 9000
```

### 4-9. 権限を設定する

```bash
sudo chown -R nginx:nginx /var/www/messageboard
sudo chmod -R 755 /var/www/messageboard

# uploads フォルダは PHP が書きこむので、所有者を apache にする
# Amazon Linux の php-fpm は apache ユーザーで動いているため
sudo chown -R apache:nginx /var/www/messageboard/uploads
sudo chmod -R 775 /var/www/messageboard/uploads

# SELinux の設定（これをしないと 403 エラーになります）
sudo chcon -R -t httpd_sys_content_t /var/www/messageboard
sudo chcon -R -t httpd_sys_rw_content_t /var/www/messageboard/uploads
sudo setsebool -P httpd_can_network_connect 1
```

### 4-10. PHP の設定を変える

Amazon Linux では、最初は 2MB より大きいファイルをアップロードできません。

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 8M/' /etc/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 10M/' /etc/php.ini
sudo systemctl restart php-fpm
```

### 4-11. 確認する

ブラウザで `http://<IP>/` を開きます。

次のことを確認します。

- フォームが出て、メッセージを投稿できる
- 投稿番号と時間が出る
- 5MB より小さい画像をアップロードできる
- `<script>alert(1)</script>` と書いても、文字として表示される

## 5. GitHub にアップロードする

1. GitHub で **New repository** を押す。
2. 名前を `messageboard` にして、**Public** を選ぶ。
3. フォルダの中で、次の命令を実行する。

```bash
git init
git add .
git commit -m "メッセージボードを作成"
git branch -M main
git remote add origin https://github.com/<ユーザー名>/messageboard.git
git push -u origin main
```

## 6. よくあるエラー

| エラー | 原因 | 直し方 |
| --- | --- | --- |
| 502 Bad Gateway | php-fpm が止まっている、または Unix ソケットのままになっている | 4-8 の php-fpm 設定を確認して `sudo systemctl restart php-fpm` |
| 403 Forbidden | 権限か SELinux の設定が足りない | 4-9 をもう一度やる |
| 500 エラー | データベースのパスワードが違う | `config.php` を直す |
| 画像がアップロードできない | `uploads` の所有者が `nginx` になっている（php-fpm は `apache` で動く） | 4-9 の `chown apache:nginx` をもう一度やる |
| 文字化けする | 文字コードが utf8mb4 ではない | 4-4 と 4-7 をもう一度やる |


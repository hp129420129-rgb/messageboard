-- ==========================================
--  メッセージボードのデータベース
--  このファイルを MySQL で実行すると、テーブルが作られます
-- ==========================================

-- 1. データベースを作る
CREATE DATABASE IF NOT EXISTS messageboard DEFAULT CHARACTER SET utf8mb4;

USE messageboard;

-- 2. 投稿を入れるテーブルを作る
CREATE TABLE posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,  -- 投稿番号（1から自動で増える）
    name       VARCHAR(50) NOT NULL,            -- 名前
    message    TEXT NOT NULL,                   -- メッセージ
    image_path VARCHAR(255),                    -- 画像のファイルの場所
    created_at DATETIME NOT NULL                -- 投稿した時間
) DEFAULT CHARSET = utf8mb4;

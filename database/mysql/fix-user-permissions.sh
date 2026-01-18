#!/usr/bin/env bash

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    -- ユーザーが存在しない場合は作成
    CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
    
    -- すべてのホスト（%）からの接続を許可
    GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
    
    -- グローバル権限も付与（phpMyAdminなどのツールで使用する場合）
    GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_USER}'@'%' WITH GRANT OPTION;
    
    -- 権限を反映
    FLUSH PRIVILEGES;
EOSQL

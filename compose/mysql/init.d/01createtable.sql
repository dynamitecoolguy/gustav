-- EXAMPLE:

-- ユーザ識別
create table identification (
    user_id int unsigned not null auto_increment,              -- ユーザID
    open_id bigint unsigned not null,                          -- 公開ID(10桁)
    campaign_code varchar(16) not null,                        -- 登録時のキャンペーンコード
    created_at timestamp default current_timestamp not null,
    primary key(user_id),
    unique index (open_id)
);

-- データ移管
create table transfer_code (
    user_id int unsigned not null,                             -- ユーザID
    transfer_code char(8) not null,                            -- 移管コード
    password_hash varchar(256) not null,                       -- パスワードのハッシュ
    created_at timestamp default current_timestamp not null,
    primary key(user_id),
    unique index(transfer_code),
    index(password_hash(16)),
    foreign key(user_id) references identification (user_id)
);

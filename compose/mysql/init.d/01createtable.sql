-- EXAMPLE:

-- ユーザ識別
create table identification (
    user_id int unsigned not null auto_increment,              -- ユーザID
    open_id binary(10) null default null,                      -- 公開ID(10桁)
    transfer_code binary(8) null default null,                 -- 移管コード(8桁)
    note varchar(256) not null,                                -- 登録時のnote
    created_at timestamp default current_timestamp not null,
    primary key(user_id),
    unique index (open_id),
    unique index(transfer_code)
);

-- ユーザ鍵
create table key_pair (
    user_id int unsigned not null,                             -- ユーザID
    private_key varbinary(2048) not null,                      -- RSA秘密鍵
    public_key varbinary(1024) not null,                       -- RSA公開鍵
    created_at timestamp default current_timestamp not null,
    primary key(user_id),
    foreign key(user_id) references identification (user_id)
);

-- データ移管
create table transfer_code (
    user_id int unsigned not null,                             -- ユーザID
    password_hash varbinary(256) not null,                     -- パスワードのハッシュ
    created_at timestamp default current_timestamp not null,
    primary key(user_id),
    index(password_hash(16)),
    foreign key(user_id) references identification (user_id)
);

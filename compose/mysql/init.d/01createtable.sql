-- EXAMPLE:

create table registration (
    user_id int unsigned not null auto_increment,
    open_id bigint unsigned not null,
    campaign_code varchar(16) not null,
    created_at timestamp default current_timestamp not null,
    constraint registration_pk primary key (user_id)
);

create table delicious_tags (
	id int primary key,
	tag varchar(255)
);

create table bookmarks (
	id int primary key,
	md5 varchar(255),
	principalUrl varchar(255)
);
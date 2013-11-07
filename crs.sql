create table article(
	id int auto_increment primary key,
	title varchar(255),
	description text,
	url varchar(255),
	source varchar(50),
	update_time bigint
) default charset=utf8;

create table articles_recommended(	
	user_name varchar(50),
	article_id int
) default charset=utf8;

create table article_tag(	
	tag varchar(50),
	article_id int
) default charset=utf8;

create table user_interest(	
	username varchar(50),
	interest_tag varchar(100)
) default charset=utf8;

CREATE TABLE recommended_read (
`user_name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`interest_tag` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`recommended_num` INT NOT NULL ,
`read_num` INT NOT NULL
);

CREATE TABLE article_interest(
`article_id` INT NOT NULL ,
`user_name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`interest_tag` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
);
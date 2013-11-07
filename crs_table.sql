-- 文章
create table article(
	id int auto_increment primary key,
	title varchar(255),
	description text,
	url varchar(255), 
	source varchar(50),
	update_time bigint 
) default charset=utf8;

-- 文章的关键字
create table article_tag(	
	tag varchar(255),
	article_id int,
	weight double
) default charset=utf8;

-- 用户的兴趣标签
create table user_interest(	
	username varchar(50),
	interest_tag varchar(100),
	weight double
) default charset=utf8;

-- 推荐的文章
CREATE TABLE recommend_articles (
	user_name VARCHAR( 50 ) ,
	article_id int,
	related_tag varchar(100),
	relevance double 
);

-- 用户
CREATE TABLE users(
	user_name VARCHAR( 50 ) 
) default charset=utf8;

-- 用户点击行为
CREATE TABLE click_feedback(
article_id INT NOT NULL ,
user_name VARCHAR( 50 ) 
) default charset=utf8;
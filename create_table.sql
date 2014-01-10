CREATE TABLE `users` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`oauth_provider` varchar(10),
`oauth_uid` text,
`oauth_token` text,
`oauth_secret` text,
`username` text,
`avatar` text,
`full_name` text,
PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARACTER SET utf8;

CREATE TABLE `tokens` (
`user_id` INT(10) NOT NULL,
`token` INT(20) NOT NULL,
`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
KEY `idx_user_id_token` (`user_id`,`token`)
)
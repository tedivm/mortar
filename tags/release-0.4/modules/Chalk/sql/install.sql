/******************** Add Table: chalkBlog ************************/

/* Build Table Structure */
CREATE TABLE chalkBlog
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title TINYTEXT NULL,
	subtitle TINYTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;
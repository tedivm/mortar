SET foreign_key_checks = 0;

/******************** Add Table: chalkBlog ************************/

/* Build Table Structure */
CREATE TABLE chalkBlog
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	subtitle TINYTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Chalk', NOW(), 0, 1, 0);

SET foreign_key_checks = 1;
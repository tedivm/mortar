SET foreign_key_checks = 0;

/********************** Add Table: tesseraForums ***************************/

/* Build Table Structure */
CREATE TABLE tesseraForums
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	description MEDIUMTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/********************** Add Table: tesseraThreads **************************/

/* Build Table Structure */
CREATE TABLE tesseraThreads
(
        id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	open TINYTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/********************** Add Table: tesseraMessages *************************/

/* Build Table Structure */
CREATE TABLE tesseraMessages
(
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	author VARCHAR(40) NULL,
	email VARCHAR(255) NULL,
	anonymous CHAR(1) NOT NULL DEFAULT '0',
	replyTo INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/******************** Add Table: tesseraModelStatus ************************/

/* Build Table Structure */

CREATE TABLE tesseraModelStatus
(
        modelId INTEGER UNSIGNED NOT NULL PRIMARY KEY,
        commentSetting INTEGER UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;



/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Tessera', NOW(), 0, 1, 0);

SET foreign_key_checks = 1;
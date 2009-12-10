/********************** Add Table: tesseraForums ***************************/

/* Build Table Structure */
CREATE TABLE tesseraForums
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title TINYTEXT NULL,
	description MEDIUMTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/********************** Add Table: tesseraThreads **************************/

/* Build Table Structure */
CREATE TABLE tesseraThreads
(
        id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title TINYTEXT NULL,
	open TINYTEXT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/********************** Add Table: tesseraMessages *************************/

/* Build Table Structure */
CREATE TABLE tesseraMessages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	replyTo INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Tessera', NOW(), 0, 1, 0);
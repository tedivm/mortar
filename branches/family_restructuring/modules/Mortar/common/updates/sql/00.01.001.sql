/******************** Add Table: schemaVersion ************************/

/* Build Table Structure */
CREATE TABLE schemaVersion
(
	package VARCHAR(65) NOT NULL PRIMARY KEY,
	lastupdated TIMESTAMP NULL,
	majorVersion INTEGER UNSIGNED NULL DEFAULT 0,
	minorVersion INTEGER UNSIGNED NULL DEFAULT 0,
	microVersion INTEGER UNSIGNED NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: modules */

/* Add Indexes for: modules */
CREATE INDEX majorVersion_minorVersion_microVersion_Idx ON schemaVersion (majorVersion, minorVersion, microVersion);


/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Core', NOW(), 0, 1, 1);
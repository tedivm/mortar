/******************** Add Table: repositories ************************/

/* Build Table Structure */
CREATE TABLE repositories
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	priority TINYINT SIGNED NOT NULL DEFAULT 0,
	activeForInstalls TINYINT NOT NULL DEFAULT 0,
	published TINYINT NOT NULL DEFAULT 0,
	lastupdate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: repositories */
CREATE INDEX repositories_priority_activeForInstalls_id_Idx ON repositories (priority, activeForInstalls, id);


/******************** Add Table: repositoriesInformation ************************/

/* Build Table Structure */
CREATE TABLE repositoriesInformation
(
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	website TEXT NOT NULL,
	description TEXT NOT NULL,
	url	TEXT NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;



/******************** Add Table: packages ************************/

/* Build Table Structure */
CREATE TABLE packages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/******************** Add Table: packageInformation ************************/

/* Build Table Structure */
CREATE TABLE packageInformation
(
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY,

) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;



/******************** Add Table: packageConflicts ************************/

/* Build Table Structure */
CREATE TABLE packageConflicts
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/******************** Add Table: packageDependencies ************************/

/* Build Table Structure */
CREATE TABLE packageDependencies
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
	package VARCHAR(65) NOT NULL,
	majorVersion INTEGER UNSIGNED NULL,
	minorVersion INTEGER UNSIGNED NULL,
	microVersion INTEGER UNSIGNED NULL,
	type VARCHAR(20) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/* Add Indexes for: repositories */
CREATE INDEX repositories_priority_activeForInstalls_id_Idx ON repositories (id, type, package);



/******************** Add Table: repoHasPackages ************************/

/* Build Table Structure */
CREATE TABLE repoHasPackages
(
	repoId INTEGER UNSIGNED NOT NULL,
	packageId INTEGER UNSIGNED NOT NULL,
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

ALTER TABLE repoHasPackages ADD CONSTRAINT pkrepoHasPackages
	PRIMARY KEY (repoId, packageId);
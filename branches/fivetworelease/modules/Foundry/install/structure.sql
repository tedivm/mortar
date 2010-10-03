/******************** Add Table: foundryRepositories ************************/

/* Build Table Structure */
CREATE TABLE foundryRepositories
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	priority TINYINT SIGNED NOT NULL DEFAULT 0,
	activeForInstalls TINYINT NOT NULL DEFAULT 0,
	published TINYINT NOT NULL DEFAULT 0,
	lastupdated DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: repositories */
CREATE INDEX foundryRepositories_priority_activeForInstalls_id_Idx
		ON foundryRepositories (priority, activeForInstalls, id);


/******************** Add Table: foundryRepositoriesInformation ************************/

/* Build Table Structure */
CREATE TABLE foundryRepositoriesInformation
(
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	name VARCHAR(65) NOT NULL,
	website TEXT NOT NULL,
	description TEXT NOT NULL,
	serverSoftware VARCHAR(65),
	url	TEXT NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;



/******************** Add Table: foundryPackages ************************/

/* Build Table Structure */
CREATE TABLE foundryPackages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/******************** Add Table: foundryPackageInformation ************************/

/* Build Table Structure */
CREATE TABLE foundryPackageInformation
(
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY

) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;



/******************** Add Table: foundryPackageConflicts ************************/

/* Build Table Structure */
CREATE TABLE foundryPackageConflicts
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/******************** Add Table: foundryPackageDependencies ************************/

/* Build Table Structure */
CREATE TABLE foundryPackageDependencies
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	package VARCHAR(65) NOT NULL,
	majorVersion INTEGER UNSIGNED,
	minorVersion INTEGER UNSIGNED,
	microVersion INTEGER UNSIGNED,
	dependencyType VARCHAR(20) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;


/* Add Indexes for: repositories */
CREATE INDEX foundryPackageDependencies_priority_activeForInstalls_id_Idx ON foundryPackageDependencies (id, dependencyType, package);



/******************** Add Table: foundryRepoHasPackages ************************/

/* Build Table Structure */
CREATE TABLE foundryRepoHasPackages
(
	repoId INTEGER UNSIGNED NOT NULL,
	packageId INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

ALTER TABLE foundryRepoHasPackages ADD CONSTRAINT pkrepoHasPackages
	PRIMARY KEY (repoId, packageId);
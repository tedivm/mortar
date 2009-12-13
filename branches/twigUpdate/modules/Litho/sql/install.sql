/******************** Add Table: cmsContent ************************/

/* Build Table Structure */
CREATE TABLE lithoContent
(
	pageId INTEGER UNSIGNED NOT NULL,
	revisionId INTEGER UNSIGNED NOT NULL,
	rawContent MEDIUMTEXT NOT NULL,
	filteredContent MEDIUMTEXT NOT NULL,
	updateTime DATETIME NOT NULL,
	note TINYTEXT NULL,
	title TINYTEXT NULL,
	author INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsContent */
ALTER TABLE lithoContent ADD CONSTRAINT pklithoContent
	PRIMARY KEY (pageId, revisionId);

/******************** Add Table: cmsPages ************************/

/* Build Table Structure */
CREATE TABLE lithoPages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	activeRevision INTEGER UNSIGNED NULL,
	status VARCHAR(25) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsPages */
CREATE INDEX lithoPages_id_activeRevision_status_Idx ON lithoPages (id, activeRevision);


/************ Foreign Key: fk_lithoContent_lithoPages ***************/
ALTER TABLE lithoContent ADD CONSTRAINT fk_lithoContent_lithoPages
	FOREIGN KEY (pageId) REFERENCES lithoPages (id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_lithoContent_users ***************/
ALTER TABLE lithoContent ADD CONSTRAINT fk_lithoContent_users
	FOREIGN KEY (author) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_lithoPages_lithoContent ***************/
ALTER TABLE lithoPages ADD CONSTRAINT fk_lithoPages_lithoContent
	FOREIGN KEY (id, activeRevision) REFERENCES lithoContent (pageId, revisionId)
		ON UPDATE NO ACTION ON DELETE NO ACTION;


/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Litho', NOW(), 0, 1, 0);
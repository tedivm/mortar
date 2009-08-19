/******************** Add Table: cmsContent ************************/

/* Build Table Structure */
CREATE TABLE Litho_Content
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
ALTER TABLE Litho_Content ADD CONSTRAINT pkLitho_Content
	PRIMARY KEY (pageId, revisionId);

/******************** Add Table: cmsPages ************************/

/* Build Table Structure */
CREATE TABLE Litho_Pages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	activeRevision INTEGER UNSIGNED NULL,
	status VARCHAR(25) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsPages */
CREATE INDEX Litho_Pages_id_activeRevision_status_Idx ON Litho_Pages (id, activeRevision);


/************ Foreign Key: fk_Litho_Content_Litho_Pages ***************/
ALTER TABLE Litho_Content ADD CONSTRAINT fk_Litho_Content_Litho_Pages
	FOREIGN KEY (pageId) REFERENCES Litho_Pages (id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_Litho_Content_users ***************/
ALTER TABLE Litho_Content ADD CONSTRAINT fk_Litho_Content_users
	FOREIGN KEY (author) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_Litho_Pages_Litho_Content ***************/
ALTER TABLE Litho_Pages ADD CONSTRAINT fk_Litho_Pages_Litho_Content
	FOREIGN KEY (id, activeRevision) REFERENCES Litho_Content (pageId, revisionId)
		ON UPDATE NO ACTION ON DELETE NO ACTION;
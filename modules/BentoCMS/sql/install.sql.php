/******************** Add Table: cmsContent ************************/

/* Build Table Structure */
CREATE TABLE BentoCMS_Content
(
	pageId INTEGER UNSIGNED NOT NULL,
	revisionId INTEGER UNSIGNED NOT NULL,
	rawContent MEDIUMTEXT NOT NULL,
	filteredContent MEDIUMTEXT NOT NULL,
	updateTime DATETIME NOT NULL,
	title TINYTEXT NULL,
	author INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsContent */
ALTER TABLE BentoCMS_Content ADD CONSTRAINT pkBentoCMS_Content
	PRIMARY KEY (pageId, revisionId);

/******************** Add Table: cmsPages ************************/

/* Build Table Structure */
CREATE TABLE BentoCMS_Pages
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	activeRevision INTEGER UNSIGNED NULL,
	status VARCHAR(25) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsPages */
CREATE INDEX BentoCMS_Pages_id_activeRevision_status_Idx ON BentoCMS_Pages (id, activeRevision);


/************ Foreign Key: fk_BentoCMS_Content_BentoCMS_Pages ***************/
ALTER TABLE BentoCMS_Content ADD CONSTRAINT fk_BentoCMS_Content_BentoCMS_Pages
	FOREIGN KEY (pageId) REFERENCES BentoCMS_Pages (id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_BentoCMS_Content_users ***************/
ALTER TABLE BentoCMS_Content ADD CONSTRAINT fk_BentoCMS_Content_users
	FOREIGN KEY (author) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_BentoCMS_Pages_BentoCMS_Content ***************/
ALTER TABLE BentoCMS_Pages ADD CONSTRAINT fk_BentoCMS_Pages_BentoCMS_Content
	FOREIGN KEY (id, activeRevision) REFERENCES BentoCMS_Content (pageId, revisionId)
		ON UPDATE NO ACTION ON DELETE NO ACTION;
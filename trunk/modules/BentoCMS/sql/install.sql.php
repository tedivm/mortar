/******************** Add Table: cmsContent ************************/

/* Build Table Structure */
CREATE TABLE cmsContent
(
	pageId INTEGER UNSIGNED NOT NULL,
	contentVersion INTEGER UNSIGNED NOT NULL,
	contentAuthor INTEGER UNSIGNED NOT NULL,
	updateTime DATETIME NOT NULL,
	title TINYTEXT NULL,
	content MEDIUMTEXT NOT NULL,
	rawContent MEDIUMTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: cmsContent */
ALTER TABLE cmsContent ADD CONSTRAINT pkcmsContent
	PRIMARY KEY (pageId, contentVersion);

/******************** Add Table: cmsPages ************************/

/* Build Table Structure */
CREATE TABLE cmsPages
(
	pageId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	mod_id INTEGER UNSIGNED NOT NULL,
	pageName TINYTEXT NULL,
	pageCurrentVersion INTEGER UNSIGNED NULL,
	pageStatus VARCHAR(25) NOT NULL,
	pageKeywords TINYTEXT NULL,
	pageDescription TINYTEXT NULL,
	creationDate DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/************ Foreign Key: fk_cmsContent_cmsPages ***************/
ALTER TABLE cmsContent ADD CONSTRAINT fk_cmsContent_cmsPages
	FOREIGN KEY (pageId) REFERENCES cmsPages (pageId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_cmsContent_users ***************/
ALTER TABLE cmsContent ADD CONSTRAINT fk_cmsContent_users
	FOREIGN KEY (contentAuthor) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_cmsPages_modules ***************/
ALTER TABLE cmsPages ADD CONSTRAINT fk_cmsPages_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;
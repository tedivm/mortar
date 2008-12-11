/******************** Add Table: cmsContent ************************/

/* Build Table Structure */
CREATE TABLE cmsContent
(
	location_id BIGINT UNSIGNED NOT NULL,
	contentVersion INTEGER UNSIGNED NOT NULL,
	rawContent MEDIUMTEXT NOT NULL,
	updateTime DATETIME NOT NULL,
	title TINYTEXT NULL,
	content MEDIUMTEXT NOT NULL,
	contentAuthor INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: cmsContent */
ALTER TABLE cmsContent ADD CONSTRAINT pkcmsContent
	PRIMARY KEY (location_id, contentVersion);

/******************** Add Table: cmsPages ************************/

/* Build Table Structure */
CREATE TABLE cmsPages
(
	location_id BIGINT UNSIGNED NOT NULL,
	pageCurrentVersion INTEGER UNSIGNED NULL,
	pageKeywords TINYTEXT NULL,
	pageDescription TINYTEXT NULL,
	pageStatus VARCHAR(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: cmsPages */
ALTER TABLE cmsPages ADD CONSTRAINT pkcmsPages
	PRIMARY KEY (location_id);


/************ Foreign Key: fk_cmsContent_locations ***************/
ALTER TABLE cmsContent ADD CONSTRAINT fk_cmsContent_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION

/************ Foreign Key: fk_cmsContent_users ***************/
ALTER TABLE cmsContent ADD CONSTRAINT fk_cmsContent_users
	FOREIGN KEY (contentAuthor) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION

/************ Foreign Key: fk_cmsPages_locations ***************/
ALTER TABLE cmsPages ADD CONSTRAINT fk_cmsPages_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION
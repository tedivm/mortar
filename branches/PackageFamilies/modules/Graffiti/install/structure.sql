SET foreign_key_checks = 0;



/******************** Add Table: graffitiCategories **********************/
CREATE TABLE graffitiCategories
(
	categoryId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(45) NOT NULL,
	parent INTEGER UNSIGNED NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/******************** Add Table: graffitiLocationCategories ************************/

/* Build Table Structure */
CREATE TABLE graffitiLocationCategories
(
	locationId INTEGER UNSIGNED NOT NULL,
	categoryId INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: locationCategories */
ALTER TABLE graffitiLocationCategories ADD CONSTRAINT pkgraffitiLocationCategories
	PRIMARY KEY (locationId, categoryId);

/******************** Add Table: graffitiTags ************************/

/* Build Table Structure */
CREATE TABLE graffitiTags
(
	tagId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	tag VARCHAR(50) NOT NULL,
	stem VARCHAR(50) NOT NULL

) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsContent */
CREATE INDEX graffitiTags_tag_tagId_Idx ON graffitiTags (tag, tagId);
CREATE INDEX graffitiTags_stem_tag_Idx ON graffitiTags (stem, tag);

/******************** Add Table: graffitiLocationHasTags ************************/

/* Build Table Structure */
CREATE TABLE graffitiLocationHasTags
(
	tagId INTEGER UNSIGNED NOT NULL,
	locationId INTEGER UNSIGNED NOT NULL,
	userId INTEGER UNSIGNED NOT NULL,
	weight INTEGER UNSIGNED NOT NULL DEFAULT 1,
	createdOn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cmsContent */
ALTER TABLE graffitiLocationHasTags ADD CONSTRAINT pkgraffitiLocationHasTags
	PRIMARY KEY (locationId, tagId, userId);

CREATE INDEX graffitiLocationHasTags_weight_Idx ON graffitiLocationHasTags (locationId, tagId, weight);
CREATE INDEX graffitiLocationHasTags_createdOn_Idx ON graffitiLocationHasTags (createdOn, tagId);

/******************** Add Table: graffitiModelStatus ************************/

/* Build Table Structure */

CREATE TABLE graffitiModelStatus
(
	modelId INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	tagSetting INTEGER UNSIGNED NOT NULL DEFAULT '0',
	categorySetting INTEGER UNSIGNED NOT NULL  DEFAULT '0'
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/************ Add Foreign Keys to Database ***************/

/************ Foreign Key: fk_graffitiLocationHasTags_locations ***************/
ALTER TABLE graffitiLocationHasTags ADD CONSTRAINT fk_graffitiLocationHasTags_locations
	FOREIGN KEY (locationId) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_graffitiLocationHasTags_graffitiTags ***************/
ALTER TABLE graffitiLocationHasTags ADD CONSTRAINT fk_graffitiLocationHasWeightedTags_graffitiTags
	FOREIGN KEY (tagId) REFERENCES graffitiTags (tagId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_graffitiLocationHasTags_users ***************/
ALTER TABLE graffitiLocationHasTags ADD CONSTRAINT fk_graffitiLocationHasTags_users
	FOREIGN KEY (userId) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_graffitiModelStatus_modelsRegistered ***************/
ALTER TABLE graffitiModelStatus ADD CONSTRAINT fk_graffitiModelStatus_modelsRegistered
	FOREIGN KEY (modelId) REFERENCES modelsRegistered (modelId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_graffitiLocationCategories_locations ***************/
ALTER TABLE graffitiLocationCategories ADD CONSTRAINT fk_graffitiLocationCategories_locations
	FOREIGN KEY (locationId) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locationCategories_categories ***************/
ALTER TABLE graffitiLocationCategories ADD CONSTRAINT fk_graffitiLocationCategories_categories
	FOREIGN KEY (categoryId) REFERENCES categories (categoryId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_categories_categories ***************/
ALTER TABLE graffitiCategories ADD CONSTRAINT fk_graffitiCategories_graffitiCategories
	FOREIGN KEY (parent) REFERENCES graffitiCategories (categoryId) ON UPDATE NO ACTION ON DELETE NO ACTION;


/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Graffiti', NOW(), 0, 1, 0);

SET foreign_key_checks = 1;
SET foreign_key_checks = 0;

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

/******************** Add Table: graffitiTagsModels ************************/

/* Build Table Structure */

CREATE TABLE graffitiTagsModels
(
	modelId INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	modelSetting INTEGER UNSIGNED NOT NULL
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

/************ Foreign Key: fk_graffitiTagsModels_modelsRegistered ***************/
ALTER TABLE graffitiTagsModels ADD CONSTRAINT fk_graffitiTagsModels_modelsRegistered
	FOREIGN KEY (modelId) REFERENCES modelsRegistered (modelId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Graffiti', NOW(), 0, 1, 0);

SET foreign_key_checks = 1;
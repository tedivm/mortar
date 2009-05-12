/************ Update: Tables ***************/

/******************** Add Table: actions ************************/

/* Build Table Structure */
CREATE TABLE actions
(
	action_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	action_name VARCHAR(30) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: actions */

/* Add Indexes for: actions */
CREATE UNIQUE INDEX actions_action_name_Idx ON actions (action_name);

/******************** Add Table: aliases ************************/

/* Build Table Structure */
CREATE TABLE aliases
(
	location_id INTEGER UNSIGNED NOT NULL,
	aliasType VARCHAR(15) NOT NULL DEFAULT 'other',
	aliasLocation INTEGER UNSIGNED NULL,
	aliasOther VARCHAR(60) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/******************** Add Table: directories ************************/

/* Build Table Structure */
CREATE TABLE directories
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	allowIndex CHAR(1) NOT NULL DEFAULT 0,
	defaultChild INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: sites */
CREATE INDEX directories_id_defaultChild_Idx ON directories (id, defaultChild);

/******************** Add Table: groupPermissions ************************/

/* Build Table Structure */
CREATE TABLE groupPermissions
(
	memgroup_id INTEGER UNSIGNED NOT NULL,
	location_id INTEGER UNSIGNED NOT NULL,
	action_id INTEGER UNSIGNED NOT NULL,
	permission VARCHAR(4) NOT NULL DEFAULT 'i',
	resource VARCHAR(16) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: groupPermissions */
ALTER TABLE groupPermissions ADD CONSTRAINT pkgroupPermissions
	PRIMARY KEY (memgroup_id, location_id, action_id, resource);

/******************** Add Table: location_meta ************************/

/* Build Table Structure */
CREATE TABLE location_meta
(
	location_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(45) NOT NULL,
	value VARCHAR(45) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: location_meta */
ALTER TABLE location_meta ADD CONSTRAINT pklocation_meta
	PRIMARY KEY (location_id, name);

/******************** Add Table: locations ************************/

/* Build Table Structure */
CREATE TABLE locations
(
	location_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	parent INTEGER UNSIGNED NULL,
	name VARCHAR(65) NOT NULL,
	resourceType VARCHAR(16) NOT NULL,
	resourceId INTEGER UNSIGNED NOT NULL,
	creationDate DATETIME NOT NULL,
	owner INTEGER UNSIGNED,
	groupOwner INTEGER UNSIGNED,
	inherits CHAR(1) NOT NULL DEFAULT 1,
	lastModified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP /* This will update on each save */
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: locations */

/* Add Indexes for: locations */

CREATE UNIQUE INDEX locations_parent_name_Idx ON locations (parent, name);
CREATE INDEX locations_parent_name_id__Idx ON locations (parent, name, location_id);
CREATE INDEX locations_parent_resourceType_Idx ON locations (parent, resourceType, location_id);
CREATE INDEX locations_resourceType_resourceId ON locations (resourceType, resourceId, location_id);
CREATE INDEX locations_creationDate ON locations (creationDate, location_id);
CREATE INDEX locations_lastModified ON locations (lastModified, location_id);

/******************** Add Table: member_group ************************/

/* Build Table Structure */
CREATE TABLE member_group
(
	memgroup_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	memgroup_name VARCHAR(35) NOT NULL,
	is_system CHAR(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: member_group */

/* Add Indexes for: member_group */
CREATE INDEX member_group_memgroup_name_Idx ON member_group (memgroup_name, memgroup_id, is_system);

/******************** Add Table: mod_config ************************/

/* Build Table Structure */
CREATE TABLE mod_config
(
	config_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	mod_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(125) NOT NULL,
	value VARCHAR(125) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: mod_config */

/* Add Indexes for: mod_config */
CREATE INDEX mod_config_mod_id_Idx ON mod_config (mod_id);
CREATE UNIQUE INDEX mod_config_mod_id_name_Idx ON mod_config (mod_id, name);

/******************** Add Table: modules ************************/

/* Build Table Structure */
CREATE TABLE modules
(
	mod_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	package VARCHAR(45) NOT NULL,
	lastupdated TIMESTAMP NULL,
	majorVersion INTEGER UNSIGNED NULL DEFAULT 0,
	minorVersion INTEGER UNSIGNED NULL DEFAULT 0,
	microVersion INTEGER UNSIGNED NULL DEFAULT 0,
	releaseType VARCHAR(12) NULL,
	releaseVersion INTEGER UNSIGNED NULL,
	status VARCHAR(12) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: modules */

/* Add Indexes for: modules */
CREATE INDEX modules_package_Idx ON modules (package, mod_id, status);

/******************** Add Table: modelsRegistered ************************/

/* Build Table Structure */
CREATE TABLE modelsRegistered
(
	name VARCHAR(65) NOT NULL PRIMARY KEY,
	resource VARCHAR(16) NOT NULL,
	mod_id INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: modelsRegistered */

/* Add Indexes for: modelsRegistered */
CREATE INDEX modelsRegistered_resource_Idx ON modelsRegistered (resource);

/******************** Add Table: plugins ************************/

/* Build Table Structure */
CREATE TABLE plugins
(
	realm VARCHAR(80) NOT NULL,
	category VARCHAR(80) NOT NULL,
	name VARCHAR(65) NOT NULL,
	module INTEGER UNSIGNED NOT NULL,
	plugin  VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: location_meta */
ALTER TABLE plugins ADD CONSTRAINT pkplugins
	PRIMARY KEY (realm, category, name, module, plugin);

/******************** Add Table: sites ************************/

/* Build Table Structure */
CREATE TABLE sites
(
	site_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	primaryUrl VARCHAR(255),
	allowIndex CHAR(1) NOT NULL DEFAULT 0,
	defaultChild INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: sites */
CREATE INDEX sites_primaryUrl_Idx ON sites (primaryUrl);

/******************** Add Table: urls ************************/

/* Build Table Structure */
CREATE TABLE urls
(
	path VARCHAR(255) NOT NULL PRIMARY KEY,
	site_id INTEGER UNSIGNED NOT NULL,
	sslEnabled TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: urls */

/* Add Indexes for: urls */
CREATE INDEX urls_site_id_sslEnabled_Idx ON urls (site_id, sslEnabled);

/******************** Add Table: userInMemberGroup ************************/

/* Build Table Structure */
CREATE TABLE userInMemberGroup
(
	user_id INTEGER UNSIGNED NOT NULL,
	memgroup_id INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: userInMemberGroup */
ALTER TABLE userInMemberGroup ADD CONSTRAINT pkuserInMemberGroup
	PRIMARY KEY (user_id, memgroup_id);

/******************** Add Table: userPermissions ************************/

/* Build Table Structure */
CREATE TABLE userPermissions
(
	user_id INTEGER UNSIGNED NOT NULL,
	location_id INTEGER UNSIGNED NOT NULL,
	action_id INTEGER UNSIGNED NOT NULL,
	permission VARCHAR(4) NOT NULL DEFAULT 'i',
	resource VARCHAR(16) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: userPermissions */
ALTER TABLE userPermissions ADD CONSTRAINT pkuserPermissions
	PRIMARY KEY (user_id, location_id, action_id, resource);

/******************** Add Table: users ************************/

/* Build Table Structure */
CREATE TABLE users
(
	user_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	user_name VARCHAR(16) NOT NULL,
	user_password VARCHAR(255) NULL,
	user_email VARCHAR(255) NULL,
	user_allowlogin CHAR(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: users */

/* Add Indexes for: users */
CREATE INDEX user_name ON users (user_name, user_password);
CREATE UNIQUE INDEX user_name_2 ON users (user_name);




/************ Add Foreign Keys to Database ***************/

/************ Foreign Key: fk_aliases_locations ***************/
ALTER TABLE aliases ADD CONSTRAINT fk_aliases_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_aliases_locations_result ***************/
ALTER TABLE aliases ADD CONSTRAINT fk_aliases_locations_result
	FOREIGN KEY (aliasLocation) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_aliases_locations_target ***************/
ALTER TABLE aliases ADD CONSTRAINT fk_aliases_locations_target
	FOREIGN KEY (aliasLocation) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_directories_locations ***************/
ALTER TABLE directories ADD CONSTRAINT fk_directories_locations
	FOREIGN KEY (id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_locations ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_actions ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_actions
	FOREIGN KEY (action_id) REFERENCES actions (action_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_member_group ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_member_group
	FOREIGN KEY (memgroup_id) REFERENCES member_group (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_location_meta_locations ***************/
ALTER TABLE location_meta ADD CONSTRAINT fk_location_meta_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_locations ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_locations
	FOREIGN KEY (parent) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_users ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_users
	FOREIGN KEY (owner) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_member_group ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_member_group
	FOREIGN KEY (groupOwner) REFERENCES member_group (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_mod_config_modules ***************/
ALTER TABLE mod_config ADD CONSTRAINT fk_mod_config_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_modelsRegistered_modules ***************/
ALTER TABLE modelsRegistered ADD CONSTRAINT fk_modelsRegistered_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugins_modules ***************/
ALTER TABLE plugins ADD CONSTRAINT fk_plugins_modules
	FOREIGN KEY (module) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_sites_locations ***************/
ALTER TABLE sites ADD CONSTRAINT fk_sites_locations
	FOREIGN KEY (site_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_sites_urls ***************/
ALTER TABLE sites ADD CONSTRAINT fk_sites_urls
	FOREIGN KEY (primaryUrl) REFERENCES urls (path) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_domains_sites ***************/
ALTER TABLE urls ADD CONSTRAINT fk_domains_sites
	FOREIGN KEY (site_id) REFERENCES sites (site_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userInMemberGroup_member_group ***************/
ALTER TABLE userInMemberGroup ADD CONSTRAINT fk_userInMemberGroup_member_group
	FOREIGN KEY (memgroup_id) REFERENCES member_group (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userInMemberGroup_users ***************/
ALTER TABLE userInMemberGroup ADD CONSTRAINT fk_userInMemberGroup_users
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userPermissions_users ***************/
ALTER TABLE userPermissions ADD CONSTRAINT fk_userPermissions_users
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userPermissions_locations ***************/
ALTER TABLE userPermissions ADD CONSTRAINT fk_userPermissions_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userPermissions_actions ***************/
ALTER TABLE userPermissions ADD CONSTRAINT fk_userPermissions_actions
	FOREIGN KEY (action_id) REFERENCES actions (action_id) ON UPDATE NO ACTION ON DELETE NO ACTION;
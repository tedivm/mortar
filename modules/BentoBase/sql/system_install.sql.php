/************ Update: Tables ***************/

/******************** Add Table: actions ************************/

/* Build Table Structure */
CREATE TABLE actions
(
	action_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	action_name VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: actions */

/* Add Indexes for: actions */
CREATE UNIQUE INDEX actions_action_name_Idx ON actions (action_name);

/******************** Add Table: aliases ************************/

/* Build Table Structure */
CREATE TABLE aliases
(
	location_id BIGINT UNSIGNED NOT NULL,
	aliasType VARCHAR(15) NOT NULL DEFAULT 'other',
	aliasLocation BIGINT UNSIGNED NULL,
	aliasOther VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/******************** Add Table: group_permissions ************************/

/* Build Table Structure */
CREATE TABLE group_permissions
(
	memgroup_id INTEGER UNSIGNED NOT NULL,
	perprofile_id INTEGER UNSIGNED NOT NULL,
	location_id BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: group_permissions */
ALTER TABLE group_permissions ADD CONSTRAINT pkgroup_permissions
	PRIMARY KEY (memgroup_id, location_id, perprofile_id);

/******************** Add Table: location_meta ************************/

/* Build Table Structure */
CREATE TABLE location_meta
(
	location_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(45) NOT NULL,
	value VARCHAR(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: location_meta */
ALTER TABLE location_meta ADD CONSTRAINT pklocation_meta
	PRIMARY KEY (location_id, name);

/******************** Add Table: locations ************************/

/* Build Table Structure */
CREATE TABLE locations
(
	location_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	location_parent BIGINT UNSIGNED NULL,
	location_resource VARCHAR(16) NULL DEFAULT 'data',
	location_name VARCHAR(65) NULL,
	inherit TINYINT UNSIGNED NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: locations */

/* Add Indexes for: locations */
CREATE INDEX locations_location_parent_Idx ON locations (location_parent);
CREATE UNIQUE INDEX locations_location_parent_location_name_Idx ON locations (location_parent, location_name);
CREATE INDEX locations_location_resource_location_parent_Idx ON locations (location_resource, location_parent);

/******************** Add Table: member_group ************************/

/* Build Table Structure */
CREATE TABLE member_group
(
	memgroup_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	memgroup_name VARCHAR(35) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: member_group */

/* Add Indexes for: member_group */
CREATE INDEX member_group_memgroup_name_Idx ON member_group (memgroup_name);

/******************** Add Table: mod_config ************************/

/* Build Table Structure */
CREATE TABLE mod_config
(
	config_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	mod_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(125) NOT NULL,
	value VARCHAR(125) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: mod_config */

/* Add Indexes for: mod_config */
CREATE INDEX mod_config_mod_id_Idx ON mod_config (mod_id);
CREATE UNIQUE INDEX mod_config_mod_id_name_Idx ON mod_config (mod_id, name);

/******************** Add Table: modules ************************/

/* Build Table Structure */
CREATE TABLE modules
(
	mod_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	location_id BIGINT UNSIGNED NOT NULL,
	mod_name VARCHAR(45) NOT NULL,
	mod_package VARCHAR(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: modules */

/* Add Indexes for: modules */
CREATE INDEX mod_name ON modules (mod_name);

/******************** Add Table: package_installed ************************/

/* Build Table Structure */
CREATE TABLE package_installed
(
	name VARCHAR(60) NOT NULL,
	lastupdated TIMESTAMP NULL,
	majorVersion INTEGER UNSIGNED NOT NULL,
	minorVersion INTEGER UNSIGNED NOT NULL DEFAULT 0,
	microVersion INTEGER UNSIGNED NOT NULL DEFAULT 0,
	prereleaseType VARCHAR(12) NULL,
	prereleaseVersion INTEGER UNSIGNED NULL,
	status VARCHAR(12) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: package_installed */
ALTER TABLE package_installed ADD CONSTRAINT pkpackage_installed
	PRIMARY KEY (name);

/******************** Add Table: permission_profiles ************************/

/* Build Table Structure */
CREATE TABLE permission_profiles
(
	perprofile_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	perprofile_name VARCHAR(25) NOT NULL,
	perprofile_description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: permission_profiles */

/* Add Indexes for: permission_profiles */
CREATE UNIQUE INDEX permission_profiles_perprofile_name_Idx ON permission_profiles (perprofile_name);

/******************** Add Table: permissionsprofile_has_actions ************************/

/* Build Table Structure */
CREATE TABLE permissionsprofile_has_actions
(
	action_id INTEGER UNSIGNED NOT NULL,
	perprofile_id INTEGER UNSIGNED NOT NULL,
	permission_status CHAR(1) NOT NULL DEFAULT 'n' COMMENT 'boolean'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: permissionsprofile_has_actions */
ALTER TABLE permissionsprofile_has_actions ADD CONSTRAINT pkpermissionsprofile_has_actions
	PRIMARY KEY (action_id, perprofile_id);

/******************** Add Table: plugin_lookup_engine ************************/

/* Build Table Structure */
CREATE TABLE plugin_lookup_engine
(
	mod_id INTEGER UNSIGNED NOT NULL,
	plugin_engine VARCHAR(50) NOT NULL,
	hook VARCHAR(72) NOT NULL,
	name VARCHAR(40) NULL,
	package_name VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: plugin_lookup_engine */

/* Add Indexes for: plugin_lookup_engine */
CREATE INDEX plugin_lookup_engine_plugin_engine_hook_Idx ON plugin_lookup_engine (plugin_engine, hook);

/******************** Add Table: plugin_lookup_location ************************/

/* Build Table Structure */
CREATE TABLE plugin_lookup_location
(
	mod_id INTEGER UNSIGNED NOT NULL,
	location_id BIGINT UNSIGNED NOT NULL,
	hook VARCHAR(72) NOT NULL,
	name VARCHAR(40) NULL,
	package_name VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: plugin_lookup_location */

/* Add Indexes for: plugin_lookup_location */
CREATE INDEX plugin_lookup_location_location_id_hook_Idx ON plugin_lookup_location (location_id, hook);

/******************** Add Table: plugin_lookup_module ************************/

/* Build Table Structure */
CREATE TABLE plugin_lookup_module
(
	mod_id INTEGER UNSIGNED NOT NULL,
	module_package BIGINT NOT NULL,
	hook VARCHAR(72) NOT NULL,
	name VARCHAR(40) NOT NULL,
	package_name VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: plugin_lookup_module */

/* Add Indexes for: plugin_lookup_module */
CREATE INDEX plugin_lookup_module_hook_name_Idx ON plugin_lookup_module (hook, name);

/******************** Add Table: site_meta ************************/

/* Build Table Structure */
CREATE TABLE site_meta
(
	site_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(40) NOT NULL,
	value VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/******************** Add Table: sites ************************/

/* Build Table Structure */
CREATE TABLE sites
(
	site_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	location_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/******************** Add Table: urls ************************/

/* Build Table Structure */
CREATE TABLE urls
(
	site_id INTEGER UNSIGNED NOT NULL,
	urlPath VARCHAR(255) NOT NULL,
	urlSSL TINYINT UNSIGNED NULL,
	urlAlias TINYINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: urls */

/* Add Indexes for: urls */
CREATE UNIQUE INDEX urls_urlPath_Idx ON urls (urlPath);

/******************** Add Table: user_in_member_group ************************/

/* Build Table Structure */
CREATE TABLE user_in_member_group
(
	user_id INTEGER UNSIGNED NOT NULL,
	memgroup_id INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: user_in_member_group */
ALTER TABLE user_in_member_group ADD CONSTRAINT pkuser_in_member_group
	PRIMARY KEY (user_id, memgroup_id);

/******************** Add Table: user_permissions ************************/

/* Build Table Structure */
CREATE TABLE user_permissions
(
	user_id INTEGER UNSIGNED NOT NULL,
	perprofile_id INTEGER UNSIGNED NOT NULL,
	location_id BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: user_permissions */
ALTER TABLE user_permissions ADD CONSTRAINT pkuser_permissions
	PRIMARY KEY (user_id, location_id, perprofile_id);

/******************** Add Table: users ************************/

/* Build Table Structure */
CREATE TABLE users
(
	user_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	user_name VARCHAR(16) NOT NULL,
	user_password VARCHAR(128) NULL,
	user_email VARCHAR(255) NULL,
	user_allowlogin CHAR(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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

/************ Foreign Key: fk_group_permissions_locations ***************/
ALTER TABLE group_permissions ADD CONSTRAINT fk_group_permissions_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_group_permissions_member_group ***************/
ALTER TABLE group_permissions ADD CONSTRAINT fk_group_permissions_member_group
	FOREIGN KEY (memgroup_id) REFERENCES member_group (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_group_permissions_permission_profiles ***************/
ALTER TABLE group_permissions ADD CONSTRAINT fk_group_permissions_permission_profiles
	FOREIGN KEY (perprofile_id) REFERENCES permission_profiles (perprofile_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_location_meta_locations ***************/
ALTER TABLE location_meta ADD CONSTRAINT fk_location_meta_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_locations ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_locations
	FOREIGN KEY (location_parent) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_mod_config_modules ***************/
ALTER TABLE mod_config ADD CONSTRAINT fk_mod_config_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_modules_locations ***************/
ALTER TABLE modules ADD CONSTRAINT fk_modules_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_permissionsprofile_has_actions_actions ***************/
ALTER TABLE permissionsprofile_has_actions ADD CONSTRAINT fk_permissionsprofile_has_actions_actions
	FOREIGN KEY (action_id) REFERENCES actions (action_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_permissionsprofile_has_actions_permission_profiles ***************/
ALTER TABLE permissionsprofile_has_actions ADD CONSTRAINT fk_permissionsprofile_has_actions_permission_profiles
	FOREIGN KEY (perprofile_id) REFERENCES permission_profiles (perprofile_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_engine_modules ***************/
ALTER TABLE plugin_lookup_engine ADD CONSTRAINT fk_plugin_lookup_engine_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_engine_package_installed ***************/
ALTER TABLE plugin_lookup_engine ADD CONSTRAINT fk_plugin_lookup_engine_package_installed
	FOREIGN KEY (package_name) REFERENCES package_installed (name) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_location_locations ***************/
ALTER TABLE plugin_lookup_location ADD CONSTRAINT fk_plugin_lookup_location_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_location_modules ***************/
ALTER TABLE plugin_lookup_location ADD CONSTRAINT fk_plugin_lookup_location_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_location_package_installed ***************/
ALTER TABLE plugin_lookup_location ADD CONSTRAINT fk_plugin_lookup_location_package_installed
	FOREIGN KEY (package_name) REFERENCES package_installed (name) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_module_modules ***************/
ALTER TABLE plugin_lookup_module ADD CONSTRAINT fk_plugin_lookup_module_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugin_lookup_module_package_installed ***************/
ALTER TABLE plugin_lookup_module ADD CONSTRAINT fk_plugin_lookup_module_package_installed
	FOREIGN KEY (package_name) REFERENCES package_installed (name) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_site_settings_sites ***************/
ALTER TABLE site_meta ADD CONSTRAINT fk_site_settings_sites
	FOREIGN KEY (site_id) REFERENCES sites (site_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_sites_locations ***************/
ALTER TABLE sites ADD CONSTRAINT fk_sites_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_domains_sites ***************/
ALTER TABLE urls ADD CONSTRAINT fk_domains_sites
	FOREIGN KEY (site_id) REFERENCES sites (site_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_user_in_member_group_member_group ***************/
ALTER TABLE user_in_member_group ADD CONSTRAINT fk_user_in_member_group_member_group
	FOREIGN KEY (memgroup_id) REFERENCES member_group (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_user_in_member_group_users ***************/
ALTER TABLE user_in_member_group ADD CONSTRAINT fk_user_in_member_group_users
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_user_permissions_locations ***************/
ALTER TABLE user_permissions ADD CONSTRAINT fk_user_permissions_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_user_permissions_permission_profiles ***************/
ALTER TABLE user_permissions ADD CONSTRAINT fk_user_permissions_permission_profiles
	FOREIGN KEY (perprofile_id) REFERENCES permission_profiles (perprofile_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_user_permissions_users ***************/
ALTER TABLE user_permissions ADD CONSTRAINT fk_user_permissions_users
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;
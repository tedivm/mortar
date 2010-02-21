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

/******************** Add Table: controls ************************/

/* Build Table Structure */
CREATE TABLE controls
(
	controlId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	controlFormat VARCHAR(15) NOT NULL,
	controlName VARCHAR(65) NOT NULL,
	moduleId INTEGER UNSIGNED NOT NULL,
	controlClass  VARCHAR(65) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

CREATE UNIQUE INDEX controls_controlFormat_controlName_Idx ON controls (controlFormat, controlName);

/******************** Add Table: cronJobs ************************/

/* Build Table Structure */
CREATE TABLE cronJobs
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	moduleId INTEGER UNSIGNED NULL,
	locationId INTEGER UNSIGNED NULL,
	actionName VARCHAR(65) NOT NULL,
	jobPid INTEGER UNSIGNED NOT NULL DEFAULT 0,
	lastRun DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	minutesBetweenRequests INTEGER UNSIGNED NOT NULL DEFAULT 5,
	restrictTimeStart TIME NULL,
	restrictTimeEnd TIME NULL,
	restrictTimeDayOfWeek VARCHAR(12) NULL,
	restrictTimeDayOfMonth VARCHAR(90) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: cronJobs */

/* Add Indexes for: cronJobs */
CREATE UNIQUE INDEX cronJobs_moduleId_locationId_actionName_Idx ON cronJobs (moduleId, locationId, actionName);
CREATE UNIQUE INDEX cronJobs_locationId_actionName_Idx ON cronJobs (locationId, actionName);
CREATE UNIQUE INDEX cronJobs_moduleId_actionName_Idx ON cronJobs (moduleId, actionName);
CREATE INDEX cronJobs_jobPid_lastRun_Idx ON cronJobs (jobPid, lastRun);

/******************** Add Table: dashboardControls ***************/

/* Build Table Structure */
CREATE TABLE dashboardControls
(
	instanceId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	userId INTEGER UNSIGNED NOT NULL,
	sequence INTEGER UNSIGNED NOT NULL,
	controlId INTEGER UNSIGNED NOT NULL,
	locationId INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

CREATE UNIQUE INDEX dashboardControls_userId_sequence_Idx ON dashboardControls (userId, sequence);

/******************** Add Table: dashboardControlSettings ***********/

/* Build Table Structure */
CREATE TABLE dashboardControlSettings
(
	instanceId INTEGER UNSIGNED NOT NULL,
	settingName VARCHAR(45) NOT NULL,
	settingKey VARCHAR(45) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

ALTER TABLE dashboardControlSettings ADD CONSTRAINT pkdashboardControlSettings
	PRIMARY KEY (instanceId, settingName);

/******************** Add Table: directories ************************/

/* Build Table Structure */
CREATE TABLE directories
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	allowIndex CHAR(1) NOT NULL DEFAULT 0,
	defaultChild INTEGER UNSIGNED
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: directories */
CREATE INDEX directories_id_defaultChild_Idx ON directories (id, defaultChild);

/******************** Add Table: errorLog ************************/

/* Build Table Structure */
CREATE TABLE errorLog
(
	errorType VARCHAR(45) NOT NULL,
	severity INTEGER UNSIGNED NOT NULL,
	message VARCHAR(255),
	url TEXT,
	file TEXT,
	line INTEGER UNSIGNED,
	trace TEXT,
	accessTime DATETIME NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: errorLog */
CREATE INDEX errorLog_errorType_Idx ON errorLog (errorType);
CREATE INDEX errorLog_severity_Idx ON errorLog (severity);
CREATE INDEX errorLog_url_Idx ON errorLog (url(65));
CREATE INDEX errorLog_file_Idx ON errorLog (file(255));
CREATE INDEX errorLog_line_Idx ON errorLog (line);
CREATE INDEX errorLog_accessTime_Idx ON errorLog (accessTime);

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

/******************** Add Table: locationMeta ************************/

/* Build Table Structure */
CREATE TABLE locationMeta
(
	location_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(45) NOT NULL,
	value VARCHAR(45) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: locationMeta */
ALTER TABLE locationMeta ADD CONSTRAINT pklocationMeta
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
	resourceStatus VARCHAR(30) NOT NULL DEFAULT 'Active',
	owner INTEGER UNSIGNED,
	groupOwner INTEGER UNSIGNED,
	inherits CHAR(1) NOT NULL DEFAULT 1,
	creationDate DATETIME NOT NULL,
	lastModified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, /* This will update on each save */
	publishDate DATETIME NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: locations */

/* Add Indexes for: locations */

CREATE UNIQUE INDEX locations_parent_name_Idx ON locations (parent, name);
CREATE INDEX locations_parent_name_id__Idx ON locations (parent, name, location_id);
CREATE INDEX locations_parent_resourceType_Idx ON locations (parent, resourceType, location_id);
CREATE INDEX locations_resourceType_resourceId ON locations (resourceType, resourceId, location_id);
CREATE INDEX locations_creationDate ON locations (creationDate, location_id);
CREATE INDEX locations_lastModified ON locations (lastModified, location_id);

/******************** Add Table: memberGroup ************************/

/* Build Table Structure */
CREATE TABLE memberGroup
(
	memgroup_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	memgroup_name VARCHAR(35) NOT NULL,
	is_system CHAR(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: memberGroup */

/* Add Indexes for: memberGroup */
CREATE INDEX memberGroup_memgroup_name_Idx ON memberGroup (memgroup_name, memgroup_id, is_system);

/******************** Add Table: modConfig ************************/

/* Build Table Structure */
CREATE TABLE modConfig
(
	config_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	mod_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(125) NOT NULL,
	value VARCHAR(125) NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: modConfig */

/* Add Indexes for: modConfig */
CREATE INDEX modConfig_mod_id_Idx ON modConfig (mod_id);
CREATE UNIQUE INDEX modConfig_mod_id_name_Idx ON modConfig (mod_id, name);

/******************** Add Table: modules ************************/

/* Build Table Structure */
CREATE TABLE modules
(
	mod_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	package VARCHAR(65) NOT NULL,
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
CREATE INDEX modules_package_Idx ON modules (package, mod_id);
CREATE UNIQUE INDEX package_Idx ON modules (package);
CREATE INDEX majorVersion_minorVersion_microVersion_Idx ON modules (majorVersion, minorVersion, microVersion);

/******************** Add Table: modelsRegistered ************************/

/* Build Table Structure */
CREATE TABLE modelsRegistered
(
	modelId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	handlerName VARCHAR(65) NOT NULL,
	resource VARCHAR(16) NOT NULL,
	mod_id INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: modelsRegistered */

/* Add Indexes for: modelsRegistered */
CREATE INDEX modelsRegistered_resource_Idx ON modelsRegistered (resource);
CREATE UNIQUE INDEX modelsRegistered_handlerName_Idx ON modelsRegistered (handlerName);

/******************** Add Table: plugins ************************/

/* Build Table Structure */
CREATE TABLE plugins
(
	realm VARCHAR(80) NOT NULL,
	category VARCHAR(80) NOT NULL,
	hook VARCHAR(65) NOT NULL,
	modId INTEGER UNSIGNED NOT NULL,
	plugin  VARCHAR(65) NOT NULL,
	isRecursive CHAR(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: locationMeta */
ALTER TABLE plugins ADD CONSTRAINT pkplugins
	PRIMARY KEY (realm, category, hook, modId, plugin);

/******************** Add Table: requestLog ************************/

/* Build Table Structure */
CREATE TABLE requestLog
(
	userId INTEGER UNSIGNED,
	siteId INTEGER UNSIGNED,
	location INTEGER UNSIGNED,
	module VARCHAR(65),
	action VARCHAR(65),
	ioHandler VARCHAR(65),
	format VARCHAR(65),
	accessTime DATETIME
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: sites */
CREATE INDEX requestLog_userId_Idx ON requestLog (userId);
CREATE INDEX requestLog_siteId_Idx ON requestLog (siteId);
CREATE INDEX requestLog_location_Idx ON requestLog (location);
CREATE INDEX requestLog_module_Idx ON requestLog (module);
CREATE INDEX requestLog_action_Idx ON requestLog (action);
CREATE INDEX requestLog_ioHandler_Idx ON requestLog (ioHandler);
CREATE INDEX requestLog_format_Idx ON requestLog (format);
CREATE INDEX requestLog_accessTime_Idx ON requestLog (accessTime);


/******************** Add Table: schemaVersion ************************/

/* Build Table Structure */
CREATE TABLE schemaVersion
(
	package VARCHAR(65) NOT NULL PRIMARY KEY,
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
CREATE INDEX majorVersion_minorVersion_microVersion_Idx ON schemaVersion (majorVersion, minorVersion, microVersion);

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

/******************** Add Table: trash ************************/

/* Build Table Structure */
CREATE TABLE trash
(
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	originLocation INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Add Indexes for: trash */
CREATE INDEX trash_id_location_id_Idx ON trash (id, originLocation);

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
	name VARCHAR(16) NOT NULL,
	password VARCHAR(255) NULL,
	email VARCHAR(255) NULL,
	allowlogin CHAR(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB CHARACTER SET utf8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci DEFAULT COLLATE utf8_general_ci;

/* Table Items: users */

/* Add Indexes for: users */
CREATE INDEX user_name_password_Idx ON users (name, password);
CREATE UNIQUE INDEX user_name_Idx ON users (name);




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

/************ Foreign Key: fk_controls_modules ***************/
ALTER TABLE controls ADD CONSTRAINT fk_controls_modules
	FOREIGN KEY (moduleId) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_cronJobs_modules ***************/
ALTER TABLE cronJobs ADD CONSTRAINT fk_cronJobs_modules
	FOREIGN KEY (moduleId) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_cronJobs_locations ***************/
ALTER TABLE cronJobs ADD CONSTRAINT fk_cronJobs_locations
	FOREIGN KEY (locationId) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_dashboardControls_controls ***************/
ALTER TABLE dashboardControls ADD CONSTRAINT fk_dashboardControls_controls
	FOREIGN KEY (controlId) REFERENCES controls (controlId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_dashboardControls_users ***************/
ALTER TABLE dashboardControls ADD CONSTRAINT fk_dashboardControls_users
	FOREIGN KEY (userId) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_dashboardControls_locations ***************/
ALTER TABLE dashboardControls ADD CONSTRAINT fk_dashboardControls_locations
	FOREIGN KEY (locationId) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_dashboardControlSettings_dashboardControls ***************/
ALTER TABLE dashboardControlSettings ADD CONSTRAINT fk_dashboardControlSettings_dashboardControls
	FOREIGN KEY (instanceId) REFERENCES dashboardControls (instanceId) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_directories_locations ***************/
ALTER TABLE directories ADD CONSTRAINT fk_directories_locations
	FOREIGN KEY (id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_locations ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_actions ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_actions
	FOREIGN KEY (action_id) REFERENCES actions (action_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_groupPermissions_memberGroup ***************/
ALTER TABLE groupPermissions ADD CONSTRAINT fk_groupPermissions_memberGroup
	FOREIGN KEY (memgroup_id) REFERENCES memberGroup (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locationMeta_locations ***************/
ALTER TABLE locationMeta ADD CONSTRAINT fk_locationMeta_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_locations ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_locations
	FOREIGN KEY (parent) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_users ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_users
	FOREIGN KEY (owner) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_locations_memberGroup ***************/
ALTER TABLE locations ADD CONSTRAINT fk_locations_memberGroup
	FOREIGN KEY (groupOwner) REFERENCES memberGroup (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_modConfig_modules ***************/
ALTER TABLE modConfig ADD CONSTRAINT fk_modConfig_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_modelsRegistered_modules ***************/
ALTER TABLE modelsRegistered ADD CONSTRAINT fk_modelsRegistered_modules
	FOREIGN KEY (mod_id) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_plugins_modules ***************/
ALTER TABLE plugins ADD CONSTRAINT fk_plugins_modules
	FOREIGN KEY (modId) REFERENCES modules (mod_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_sites_locations ***************/
ALTER TABLE sites ADD CONSTRAINT fk_sites_locations
	FOREIGN KEY (site_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_sites_urls ***************/
ALTER TABLE sites ADD CONSTRAINT fk_sites_urls
	FOREIGN KEY (primaryUrl) REFERENCES urls (path) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_trash_locations ***************/
ALTER TABLE trash ADD CONSTRAINT fk_trash_locations
	FOREIGN KEY (originLocation) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_domains_sites ***************/
ALTER TABLE urls ADD CONSTRAINT fk_domains_sites
	FOREIGN KEY (site_id) REFERENCES sites (site_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_userInMemberGroup_memberGroup ***************/
ALTER TABLE userInMemberGroup ADD CONSTRAINT fk_userInMemberGroup_memberGroup
	FOREIGN KEY (memgroup_id) REFERENCES memberGroup (memgroup_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

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



/**** update version ****/
REPLACE INTO schemaVersion (package, lastupdated, majorVersion, minorVersion, microVersion)
						VALUES ( 'Core', NOW(), 0, 1, 1);
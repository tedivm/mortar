SET foreign_key_checks = 0;
DROP TABLE IF EXISTS `actions`, `aliases`, `controls`, `cronJobs`, `dashboardControls`,	`dashboardControlSettings`, 
				`directories`, `errorLog`, `groupPermissions`, `locations`, `locationMeta`, 
				`memberGroup`, `modelsRegistered`, `modules`, `modConfig`, `plugins`, `requestLog`, 
				`schemaVersion`, `sites`, `trash`, `urls`, `userPermissions`, `users`, 
				`userInMemberGroup`, `lithoContent`, `lithoPages`;
SET foreign_key_checks = 1;
SET foreign_key_checks = 0;
DROP TABLE IF EXISTS `actions`, `aliases`, `changeLog`, `changeTypes`, `controls`, `cronJobs`, `dashboardControls`,
				`dashboardControlSettings`, `directories`, `errorLog`, `groupPermissions`, `locations`,
				`locationMeta`, `markup`, `markupPost`, `memberGroup`, `modelsRegistered`, `modules`, 
				`modConfig`, `plugins`, `requestLog`, `schemaVersion`, `sites`, `trash`, `urls`, 
				`userPermissions`, `users`, `userInMemberGroup`, `lithoContent`, `lithoPages`;
SET foreign_key_checks = 1;
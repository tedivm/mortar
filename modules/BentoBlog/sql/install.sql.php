/******************** Add Table: BentoBlog_postDetail ************************/

/* Build Table Structure */
CREATE TABLE BentoBlog_postDetail
(
	location_id BIGINT UNSIGNED NOT NULL,
	user_id INTEGER UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: BentoBlog_postDetail */
ALTER TABLE BentoBlog_postDetail ADD CONSTRAINT pkBentoBlog_postDetail
	PRIMARY KEY (location_id);

/******************** Add Table: BentoBlog_BlogHasTags ************************/

/* Build Table Structure */
CREATE TABLE BentoBlog_BlogHasTags
(
	location_id BIGINT UNSIGNED NOT NULL,
	tag VARCHAR(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Table Items: cmsPages */
ALTER TABLE BentoBlog_BlogHasTags ADD CONSTRAINT pkBentoBlog_BlogHasTags
	PRIMARY KEY (location_id);


/************ Foreign Key: fk_BentoBlog_postDetail_locations ***************/
ALTER TABLE BentoBlog_postDetail ADD CONSTRAINT fk_BentoBlog_postDetail_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_BentoBlog_postDetail_users ***************/
ALTER TABLE BentoBlog_postDetail ADD CONSTRAINT fk_BentoBlog_postDetail_users
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

/************ Foreign Key: fk_BentoBlog_BlogHasTags_locations ***************/
ALTER TABLE BentoBlog_BlogHasTags ADD CONSTRAINT fk_BentoBlog_BlogHasTags_locations
	FOREIGN KEY (location_id) REFERENCES locations (location_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

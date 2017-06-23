

#
# Table structure for table 'tx_kesearch_allplan_url_ids'
#
CREATE TABLE tx_kesearch_allplan_faq_ids (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	notes_id varchar(60) ,

	PRIMARY KEY (uid),
	KEY notes (notes_id)
) ENGINE=MyISAM;


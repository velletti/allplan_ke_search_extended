-- Table structure for table 'tx_kesearch_allplan_url_ids'
-- (Used for Allplan faq)
-- Todo: TYPO3 conform table name, new column names, if we have the new version of Allplan faq
CREATE TABLE tx_kesearch_allplan_url_ids (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    notes_id varchar(60) DEFAULT NULL,
    PRIMARY KEY (uid),
    KEY notes (notes_id)
) ENGINE=MyISAM;

-- Todo: remove columns 'servername' and 'top10', if version 11.* of this extension is online on Www AND connect
-- Todo: Afterwards search in source code for these columns and adapt code
CREATE TABLE tx_kesearch_index (
    servername VARCHAR(40) DEFAULT '',
    top10  VARCHAR(11) DEFAULT '',
    tx_allplan_ke_search_extended_server_name ENUM('','www','connect') NOT NULL,
    tx_allplan_ke_search_extended_top_10 VARCHAR(11) DEFAULT '',
    INDEX directory (directory(200)) USING BTREE,
    INDEX tstamp (tstamp),
    FULLTEXT INDEX titlecontentdirectory (title,content,directory),
);

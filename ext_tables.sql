#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_ajaxlogin_forgotHash  varchar(80) DEFAULT '' NOT NULL,
	tx_ajaxlogin_verificationHash  varchar(80) DEFAULT '' NOT NULL,
	tx_ajaxlogin_forgotHashValid int(11) DEFAULT '0' NOT NULL,
	tx_ajaxlogin_newUser int(11) DEFAULT '0' NOT NULL,
);
#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_ajaxlogin_sectionreload tinyint(4) DEFAULT '0' NOT NULL
);



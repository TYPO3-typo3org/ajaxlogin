<?php
if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

$tempColumns = array (
	'tx_ajaxlogin_forgotHash' => array (
		'label' => 'LLL:EXT:ajaxlogin/Resources/Private/Language/locallang_db.xml:fe_users.tx_ajaxlogin_forgotHash',
		'displayCond' => 'HIDE_FOR_NON_ADMINS',
		'exclude' => true,
		'config' => array (
			'type' => 'input',
			'size' => 20,
			'max' => 32,
			'eval' => 'md5'
		)
	),
	'tx_ajaxlogin_verificationHash' => array (
		'label' => 'LLL:EXT:ajaxlogin/Resources/Private/Language/locallang_db.xml:fe_users.tx_ajaxlogin_verificationHash',
		'displayCond' => 'HIDE_FOR_NON_ADMINS',
		'exclude' => true,
		'config' => array (
			'type' => 'input',
			'size' => 32,
			'readOnly' => true,
		)
	),
	'tx_ajaxlogin_forgotHashValid' => array (
		'label' => 'LLL:EXT:ajaxlogin/Resources/Private/Language/locallang_db.xml:fe_users.tx_ajaxlogin_forgotHashValid',
		'displayCond' => 'HIDE_FOR_NON_ADMINS',
		'exclude' => true,
		'config' => array (
			'type' => 'input',
			'size' => '8',
			'eval' => 'datetime'
		)
	)
);

t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users", $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('fe_users', '--div--;Ajaxlogin,tx_ajaxlogin_verificationHash,tx_ajaxlogin_forgotHash,tx_ajaxlogin_forgotHashValid');

$pagesTempColumns = array (
	'tx_ajaxlogin_sectionreload' => array (
		'label' => 'After signing in or out',
		'config' => array (
			'type' => 'check',
			'items' => array(
				array('Reload the pages in this section', 1)
			)
		)
	)
);
include_once(t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Tca/FlexForm.php');
$GLOBALS['TCA']['fe_users']['columns']['country']['config'] = array(
	'type' => 'select',
	'itemsProcFunc' => 'Tx_Ajaxlogin_Tca_FlexForm->country',
	'foreign_table' => 'static_countries',
	'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',
	'foreign_table_uid_field' => 'cn_short_en',
	'size' => 1,
	'minitems' => 0,
	'maxitems' => 1,
	'allowNonIdValues' => true,
);

t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages", $pagesTempColumns, 1);
t3lib_extMgm::addFieldsToPalette('pages', 'miscellaneous', 'tx_ajaxlogin_sectionreload');

Tx_Extbase_Utility_Extension::registerPlugin(
	$_EXTKEY,
	'Profile',
	'Ajaxlogin'
);

$extensionName = strtolower(t3lib_div::underscoredToUpperCamelCase($_EXTKEY));

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$extensionName . '_profile'] = 'pages,recursive,layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$extensionName . '_profile'] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($extensionName . '_profile', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Profile.xml');

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'AJAX login');


Tx_Extbase_Utility_Extension::registerModule(
	$_EXTKEY,
	'web',
	'web_feadmin',
	'',
	array(
		'User' => 'adminModule, approveUser, declineUser'
	),
	array(
		'access' => 'user,group',
		'icon' => 'EXT:t3skin/icons/module_tools_user.gif',
		'labels' => 'LLL:EXT:ajaxlogin/Resources/Private/Language/locallang_mod.xml',
	)
);

?>
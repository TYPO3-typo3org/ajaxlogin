<?php

if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Profile',
	array(
		'User' => 'show,edit,update,editPassword,updatePassword,closeAccount,disable,activateAccount,changePassword,doChangePassword'
	),
	array(
		'User' => 'show,edit,update,editPassword,updatePassword,closeAccount,disable,activateAccount,changePassword,doChangePassword'
	)
);

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Widget',
	array(
		'User' => 'info,login,authenticate,logout,new,create,forgotPassword,resetPassword'
	),
	array(
		'User' => 'info,login,authenticate,logout,new,create,forgotPassword,resetPassword'
	)
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Ajaxlogin_Tasks_RegistrationCleanupTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:scheduler_tx_ajaxlogin_tasks_RegistrationCleanupTask_title',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:scheduler_tx_ajaxlogin_tasks_RegistrationCleanupTask_description',
);

$TYPO3_CONF_VARS['FE']['addRootLineFields'] .= ',tx_ajaxlogin_sectionreload';

$TYPO3_CONF_VARS['EXTCONF']['ajaxlogin']['redirectUrl_postProcess'] = array();

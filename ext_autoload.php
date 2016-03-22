<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id: $
 */
return array(
	'tx_ajaxlogin_controller_passwordcontroller' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Controller/PasswordController.php'),
	'tx_ajaxlogin_controller_usercontroller' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Controller/UserController.php'),
	'tx_ajaxlogin_domain_model_user' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Domain/Model/User.php'),
	'tx_ajaxlogin_domain_model_usergroup' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Domain/Model/UserGroup.php'),
	'tx_ajaxlogin_domain_repository_userrepository' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Domain/Repository/UserRepository.php'),
	'tx_ajaxlogin_domain_repository_usergrouprepository' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Domain/Repository/UserGroupRepository.php'),
	'tx_ajaxlogin_domain_validator_customregularexpressionvalidator' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Domain/Validator/CustomRegularExpressionValidator.php'),
	'tx_ajaxlogin_utility_frontenduser' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Utility/FrontendUser.php'),
	'tx_ajaxlogin_utility_notifymail' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Utility/NotifyMail.php'),
	'tx_ajaxlogin_utility_password' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Utility/Password.php'),
	'tx_ajaxlogin_utility_redirecturl' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Utility/RedirectUrl.php'),
	'tx_ajaxlogin_utility_typoscript' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Utility/TypoScript.php'),
	'tx_ajaxlogin_viewhelpers_formerrorviewhelper' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/ViewHelpers/FormErrorViewHelper.php'),
	'tx_ajaxlogin_pagenotfoundhandler' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/PageNotFoundHandler.php'),
	'tx_ajaxlogin_tasks_registrationcleanuptask' => t3lib_extMgm::extPath('ajaxlogin', 'Classes/Tasks/RegistrationCleanupTask.php')
);
?>

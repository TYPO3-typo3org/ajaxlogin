<?php

/**
 * Class Tx_Ajaxlogin_Tasks_RegistrationCleanupTask
 *
 * A task that should be run regularly to cleanup old not confirmed registrations
 */
class Tx_Ajaxlogin_Tasks_RegistrationCleanupTask extends tx_scheduler_Task {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * init class
	 */
	protected function init() {
		$this->settings = $this->loadTypoScriptForBEModule();
	}

	/**
	 * @param string $extKey
	 *
	 * @return mixed
	 */
	protected function loadTypoScriptForBEModule($extKey = 'ajaxlogin') {
		require_once(PATH_t3lib . 'class.t3lib_page.php');
		require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib . 'class.t3lib_tsparser_ext.php');
		$pageUid = 2;
		$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();
		return $TSObj->setup['plugin.']['tx_' . $extKey . '.']['settings.'];
	}

	/**
	 * @param string $title
	 * @param string $text
	 * @param string $color
	 */
	protected function sendSlackBotMessage($title, $text, $color = 'notice')
	{
		$url = $this->settings['webhook.']['url'];
		$content = json_encode(array(
			'securityToken' => $this->settings['webhook.']['securityToken'],
			'color' => $color,
			'title' => $title,
			'text' => $text
		));
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

		curl_exec($curl);
		curl_close($curl);
	}

	/**
	 * Executes the System Status Update task, determing the highest severity of
	 * status reports and saving that to the registry to be displayed at login
	 * if necessary.
	 *
	 * @see typo3/sysext/scheduler/tx_scheduler_Task::execute()
	 */
	public function execute() {
		$this->init();

		$compareTimestampStart = time() - (4*24*60*60);
		$compareTimestampEnd = time() - (3*24*60*60);
		$oldUsers = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'username',
			'fe_users',
			'deleted = 0 AND tx_ajaxlogin_verificationHash = \'\' AND tx_ajaxlogin_newUser = 1 AND crdate BETWEEN ' . $compareTimestampStart . ' AND ' . $compareTimestampEnd
		);
		if (!empty($oldUsers)) {
			foreach ($oldUsers as $user) {
				$this->sendSlackBotMessage(
					'REMINDER: User wait for approval',
					sprintf(
						'The user *%s* is waiting for approval since some days.',
						$user['username']
					),
					'info'
				);
			}
		}
		return true;
	}
}

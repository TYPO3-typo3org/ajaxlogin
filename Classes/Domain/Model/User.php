<?php

class Tx_Ajaxlogin_Domain_Model_User extends Tx_Extbase_Domain_Model_FrontendUser {
	
	/**
	 * @var string
	 * @validate Tx_Ajaxlogin_Domain_Validator_CustomRegularExpressionValidator(object = User, property = name)
	 */
	protected $name;
	
	/**
	 * @var boolean
	 */
	protected $disable;

	/**
	 * @var string
	 * @validate EmailAddress
	 */
	protected $email;
	
	/**
	 * @var string
	 * @validate Tx_Ajaxlogin_Domain_Validator_CustomRegularExpressionValidator(object = User, property = username)
	 */
	protected $username;
	
	/**
	 * @var string
	 * @validate Tx_Ajaxlogin_Domain_Validator_CustomRegularExpressionValidator(object = User, property = password)
	 */
	protected $password;
	
	/**
	 * @var string
	 */
	protected $forgotHash;
	
	/**
	 * @var string
	 */
	protected $verificationHash;
	
	/**
	 * @var DateTime
	 */
	protected $forgotHashValid;

	/**
	 * @var int
	 */
	protected $newUser;

	/**
	 * @var int
	 */
	protected $crdate;

	public function __construct($username = '', $password = ''){
		$this->forgotHashValid = new DateTime();
		
		parent::__construct($username, $password);
	}
	
	/**
	 * @return string
	 */
	public function getForgotHash() {
		return $this->forgotHash;
	}
	
	/**
	 * @return string
	 */
	public function getVerificationHash() {
		return $this->verificationHash;
	}
	
	/**
	 * @return string
	 */
	public function getForgotHashValid() {
		return $this->forgotHashValid;
	}
	
	/**
	 * @param string
	 */
	public function setVerificationHash($verificationHash) {
		$this->verificationHash = $verificationHash;
	}
	
	/**
	 * @param string
	 */
	public function setForgotHash($forgotHash) {
		$this->forgotHash = $forgotHash;
	}
	
	/**
	 * @param DateTime
	 */
	public function setForgotHashValid($forgotHashValid) {
		$this->forgotHashValid = $forgotHashValid;
	}

	/**
	 * @param boolean $disable
	 */
	public function setDisable($disable) {
		$this->disable = $disable;
	}

	/**
	 * @return boolean
	 */
	public function getDisable() {
		return $this->disable;
	}

	/**
	 * @return int
	 */
	public function getCrdate()
	{
		return $this->crdate;
	}

	/**
	 * @return int
	 */
	public function getNewUser() {
		return $this->newUser;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * @param int $newUser
	 */
	public function setNewUser($newUser) {
		$this->newUser = $newUser;
	}
}

?>

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

	/**
	 * @var boolean
	 */
	protected $acceptedTermsAndConditions = FALSE;

	/**
	 * @var string
	 */
	protected $tacVersion = '';

	/**
	 * @var \DateTime;
	 */
	protected $tacDateOfAcceptance = NULL;

	public function __construct($username = '', $password = ''){
		$this->forgotHashValid = new DateTime();
		$this->tacDateOfAcceptance = new \DateTime();

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

	/**
	 * @return boolean
	 */
	public function getAcceptedTermsAndConditions() {
		return $this->acceptedTermsAndConditions;
	}

	/**
	 * @param boolean $acceptedTermsAndConditions
	 * @return void
	 */
	public function setAcceptedTermsAndConditions($acceptedTermsAndConditions) {
		$this->acceptedTermsAndConditions = (boolean)$acceptedTermsAndConditions;
	}

	/**
	 * @return string
	 */
	public function getTacVersion() {
		return $this->tacVersion;
	}

	/**
	 * @param string $tacVersion
	 * @return void
	 */
	public function setTacVersion($tacVersion) {
		$this->tacVersion = (string)$tacVersion;
	}

	/**
	 * @return \DateTime
	 */
	public function getTacDateOfAcceptance() {
		return $this->tacDateOfAcceptance;
	}

	/**
	 * @param \DateTime $tacDateOfAcceptance
	 * @return void
	 */
	public function setTacDateOfAcceptance(\DateTime $tacDateOfAcceptance) {
		$this->tacDateOfAcceptance = $tacDateOfAcceptance;
}

	/**
	 * @return comma separated string of country and city
	 */
	public function getLocation()
	{
		$location = array();

		if ($this->country) {
			$location[] = $this->country;
		}

		if ($this->city) {
			$location[] = $this->city;
		}

		return implode(',' , $location);
	}

	/**
	 * @return string of the email domain
	 */
	public function getEmailDomain() {
		$emailDomain = '';
		if ($this->email && strrchr($this->email, "@")) {
			$emailDomain = substr(strrchr($this->email, "@"), 1);
		}
		return $emailDomain;
	}

}

?>

<?php

class Tx_Ajaxlogin_Domain_Repository_UserRepository extends Tx_Extbase_Domain_Repository_FrontendUserRepository {
	
	/**
	 * Find an Object using the UID of the current fe_user
	 * @return Tx_Ajaxlogin_Domain_Model_User the current fe_user or null if none
	 */
	public function findCurrent() {
		$fe_user = $GLOBALS['TSFE']->fe_user->user;
		
		if(!empty($fe_user)) {
			$query = $this->createQuery();
			$query->matching($query->equals('uid', intval($fe_user['uid'])));
			
			return $query->execute()->getFirst();
		}
		
		return null;
	}
	
	/**
	 * Find an Object using the UID of the current fe_user
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findOneByForgotHashAndEmail($forgotHash, $email) {
		$query = $this->createQuery();
		
		$constraints = array(
			$query->equals('forgotHash', $forgotHash),
			$query->equals('email', $email),
			// check for validity is done in controller to have better error messages
//			$query->greaterThan('forgotHashValid', time())
		);
		
		$query->matching($query->logicalAnd($constraints));
			
		return $query->execute()->getFirst();
	}
	
	/**
	 * Find an Object using the UID of the current fe_user
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findOneByVerificationHashAndEmail($verificationHash, $email) {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
		
		$constraints = array(
			$query->equals('deleted', 0),
			$query->equals('verificationHash', $verificationHash),
			$query->equals('email', $email)
		);
		
		$query->matching($query->logicalAnd($constraints));
			
		return $query->execute()->getFirst();
	}
	
	/**
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findOneByEmail($email) {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
	
		$constraints = array(
				$query->equals('deleted', 0),
				$query->equals('email', $email)
		);
	
		$query->matching($query->logicalAnd($constraints));
			
		return $query->execute()->getFirst();
	}
	
	/**
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findOneByUsername($username) {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
	
		$constraints = array(
				$query->equals('deleted', 0),
				$query->equals('username', $username)
		);
	
		$query->matching($query->logicalAnd($constraints));
			
		return $query->execute()->getFirst();
	}	
	
	
	/**
	 * Find an Object using the UID of the current fe_user
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findOneByEnableHash($enableHash) {
		$query = $this->createQuery();

		$constraints = array(
			$query->equals('enableHash', $enableHash),
			$query->equals('disable', 0)
		);

		$query->matching($query->logicalAnd($constraints));

		return $query->execute()->getFirst();
	}

	/**
	 * Find all users which not approved yet.
	 *
	 * @return array|Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findAllToApprove() {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);

		$constraints = array(
			$query->equals('deleted', 0),
			$query->equals('disable', 1),
			$query->equals('verificationHash', '')
		);

		$query->matching($query->logicalAnd($constraints));

		return $query->execute();
	}

	/**
	 * @param $uid
	 *
	 * @return Tx_Ajaxlogin_Domain_Model_User
	 */
	public function findUserByUid($uid) {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);

		$query->matching($query->equals('uid', $uid));
		return $query->execute()->getFirst();
	}

	public function _persistAll() {
		$this->persistenceManager->persistAll();
	}
}

?>
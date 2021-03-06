<?php

class Tx_Ajaxlogin_Controller_UserController extends Tx_Extbase_MVC_Controller_ActionController
{

    /**
     * @var Tx_T3oLdap_Connectors_Ldap
     */
    private $ldap;

    /**
     * @var bool
     */
    private $isT3oLdapAvailable = false;

    /**
     * @var Tx_Ajaxlogin_Domain_Repository_UserRepository
     */
    protected $userRepository;

    /**
     * @var Tx_Ajaxlogin_Domain_Repository_UserGroupRepository
     */
    protected $userGroupRepository;

    /**
     * @var Tx_Ajaxlogin_Domain_Repository_CountryRepository
     */
    protected $countryRepository;

    /**
     * Tx_Ajaxlogin_Controller_UserController constructor.
     */
    public function __construct()
    {
        if (class_exists('Tx_T3oLdap_Connectors_Ldap')) {
            $this->ldap = t3lib_div::makeInstance('Tx_T3oLdap_Connectors_Ldap');
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['t3o_ldap']);
            $enableLdapPasswordUpdates = intval($extensionConfiguration['enableLdapPasswordUpdates']);
            if (intval($enableLdapPasswordUpdates) === 1) {
                $this->isT3oLdapAvailable = true;
            }
        }
    }

    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->userRepository = t3lib_div::makeInstance('Tx_Ajaxlogin_Domain_Repository_UserRepository');
        $this->userGroupRepository = t3lib_div::makeInstance('Tx_Ajaxlogin_Domain_Repository_UserGroupRepository');
        $this->response->setHeader('X-Ajaxlogin-view', substr($this->actionMethodName, 0, -6));

        $this->countryRepository = t3lib_div::makeInstance('Tx_Ajaxlogin_Domain_Repository_CountryRepository');
        // TODO: remove this function when using TYPO3 version > 4.5
        $querySettings = $this->objectManager->create('Tx_Extbase_Persistence_Typo3QuerySettings');
        $querySettings->setRespectStoragePage(false);
        $this->countryRepository->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param $user
     * @param $exchange
     * @return boolean
     */
    protected function notifyExchange(Tx_Ajaxlogin_Domain_Model_User $user, $exchange)
    {
        $objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
        /** @var Tx_Amqp_Service_ProducerService $producerService */
        $producerService = $objectManager->get('Tx_Amqp_Service_ProducerService');

        $data = $this->convertUserObjectForMessageQueue($user);

        return $producerService->sendToExchange($data, $exchange);
    }

    /**
     * @param string $title
     * @param string $text
     * @param string $color
     */
    protected function sendSlackBotMessage($title, $text, $color = 'notice')
    {
        $url = $this->settings['webhook']['url'];
        $content = json_encode(array(
            'securityToken' => $this->settings['webhook']['securityToken'],
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
     * converts a user object into a sanitized presentation to push into a message queue
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     * @return array
     */
    protected function convertUserObjectForMessageQueue(Tx_Ajaxlogin_Domain_Model_User $user)
    {
        return array(
            'uid' => $user->getUid(),
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        );
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param Tx_Extbase_View_ViewInterface $view The view to be initialized
     *
     * @return void
     */
    protected function initializeView(Tx_Extbase_MVC_View_ViewInterface $view)
    {
        parent::initializeView($view);
        $view->assign('layout', ($GLOBALS['TSFE']->type > 0) ? 'Widget' : 'Profile');
    }

    /**
     * A template method for displaying custom error flash messages, or to
     * display no flash message at all on errors. Override this to customize
     * the flash message in your action controller.
     *
     * @return string|boolean The flash message or FALSE if no flash message should be set
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     *
     */
    public function adminModuleAction()
    {
        $this->view->assign('users', $this->userRepository->findAllToApprove());
    }

    /**
     *
     * @param int $user
     */
    public function approveUserAction($user)
    {
        $userUid = $user;
        $user = $this->doApprovement($user);

        if ($user) {
            $this->sendSlackBotMessage(
                'User approved',
                sprintf(
                    'the user *%s* with email *%s* has been approved by *%s*',
                    $user->getUsername(),
                    $user->getEmail(),
                    $GLOBALS['BE_USER']->user['username']
                ),
                'ok'
            );
        } else {
            $this->flashMessageContainer->add('No user was found for the given uid: ' . $userUid,
                'User Approvement failed', t3lib_FlashMessage::ERROR);
        }

        $this->forward('adminModule');
    }


    /**
     * @param int $user
     *
     * @throws Tx_Extbase_MVC_Exception_StopAction
     * @throws Tx_Extbase_Persistence_Exception_IllegalObjectType
     */
    public function declineUserAction($user)
    {
        $user = $this->userRepository->findUserByUid($user);
        $username = $user->getUsername();
        $this->userRepository->remove($user);
        $this->userRepository->_persistAll();

        // TODO: Does this have negative effects on other systems?
        if ($this->isT3oLdapAvailable === true) {
            $this->ldap->deleteUser($user->getUsername());
        }

        $this->sendSlackBotMessage(
            'User declined',
            sprintf(
                'the user *%s* with email *%s* has been declined by *%s*',
                $username,
                $user->getEmail(),
                $GLOBALS['BE_USER']->user['username']
            ),
            'danger'
        );

        $this->forward('adminModule');
    }

    /**
     * Displays the logged-in user's info
     * or forwards to the login form if a user is not logged in
     *
     * @return void
     */
    public function infoAction()
    {
        $user = $this->userRepository->findCurrent();

        if (!is_null($user)) {
            $this->view->assign('user', $user);
        } else {
            // needed in order to trigger the JS AJAX error callback
            $this->response->setStatus(401);
            $this->forward('login');
        }
    }

    /**
     * Displays the login form
     * @param string $redirectedFrom
     * @return void
     */
    public function loginAction($redirectedFrom = '')
    {
        $token = $this->getFormToken();
        $this->view->assign('formToken', $token);
        $this->view->assign('redirectedFrom', $redirectedFrom);

        /* pass hidden field from e.g. rsaauth to the view */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])) {
            $_params = array();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
                list($onSub, $hid) = t3lib_div::callUserFunction($funcRef, $_params, $this);
                $onSubmitAr[] = $onSub;
                $extraHiddenAr[] = $hid;
            }
        }
        $this->view->assign('additionalHiddenFields', implode("\n", $extraHiddenAr));
        $this->view->assign('onSubmitCode', implode(' ', $onSubmitAr));


        $this->response->setHeader('X-Ajaxlogin-formToken', $token);

        // Implement #43791 - Preserve username in login form on login failure
        $username = trim(t3lib_div::removeXSS(t3lib_div::_GP('user')));
        $this->view->assign('username', $username);
    }

    /**
     * Gets called by the JS hijaxing the login form (see tx_ajaxlogin.api.User.authenticate in JS generated by the TS)
     * 1. forwards to user info if user is logged in
     * 2. forwards to the login form if a user didn't manage to log in
     *
     * @return void
     */
    public function authenticateAction()
    {
        $user = $this->userRepository->findCurrent();

        if (!is_null($user)) {
            $message = Tx_Extbase_Utility_Localization::translate('login_successful', 'ajaxlogin');
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);

            // create LDAP entry if user does not exist yet
            if ($this->isT3oLdapAvailable && !$this->ldap->userExists($user->getUsername())) {
                $this->ldap->createUser($user->getUid(), array(), t3lib_div::_GP('pass'));
            }

            $referer = t3lib_div::_GP('referer');
            $redirectUrl = t3lib_div::_GP('redirectUrl');
            $redirect_url = Tx_Ajaxlogin_Utility_RedirectUrl::findRedirectUrl($referer, $redirectUrl);
            if (!empty($redirect_url)) {
                $this->response->setHeader('X-Ajaxlogin-redirectUrl', $redirect_url);
            }
            $this->forward('info');
        } else {
            // needed in order to trigger the JS AJAX error callback
            $this->response->setStatus(401);
            $message = Tx_Extbase_Utility_Localization::translate('authentication_failed', 'ajaxlogin');
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::ERROR);
            $this->forward('login');
        }
    }

    /**
     * Displays a form for creating a new user
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $newUser A fresh user object taken as a basis for the rendering
     * @dontvalidate $user
     *
     * @return void
     */
    public function newAction(Tx_Ajaxlogin_Domain_Model_User $user = null)
    {
        if ($user) {
            // needed in order to trigger the JS AJAX error callback
            $this->response->setStatus(409);
        }
        if ($user && $user->getUid()) {
            // somehow the cHash got hacked, user should not have an uid
            $user = null;
        }

        $token = $this->getFormToken();
        $this->view->assign('formToken', $token);
        $this->response->setHeader('X-Ajaxlogin-formToken', $token);

        if ($user === null) {
            $user = $this->objectManager->get('Tx_Ajaxlogin_Domain_Model_User');
        }

        // unset checkbox for terms and conditions
        $user->setAcceptedTermsAndConditions(FALSE);

        $this->view->assign('user', $user);
    }

    /**
     * Creates a new user
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user A fresh User object which has not yet been added to the repository
     * @param string $password_check
     *
     * @return void
     */
    public function createAction(Tx_Ajaxlogin_Domain_Model_User $user, $password_check)
    {
        if ($user && $user->getUid()) {
            // somehow the cHash got hacked
            $this->forward('new');
        }
        if (t3lib_div::_GP('additionalInfo')) {
            // honeypot field was filled
            $this->forward('new');
        }
        // TODO: clean this up and move it to the proper validators!!!
        // this much of validation shouldn't have found its way into the controller

        // START of MOVE TO VALIDATOR task
        $objectError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'user');
        $emailError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'email');
        $usernameError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'username');
        $passwordError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'password');
        $termsAndConditionsError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'acceptedTermsAndConditions');

        $checkEmail = $this->userRepository->findOneByEmail($user->getEmail());
        $checkUsername = $this->userRepository->findOneByUsername($user->getUsername());

        if (!is_null($checkEmail)) {
            $emailError->addErrors(array(
                t3lib_div::makeInstance('Tx_Extbase_Error_Error', 'Duplicate email address', 1320783534)
            ));
        }

        if (!is_null($checkUsername)) {
            $usernameError->addErrors(array(
                t3lib_div::makeInstance('Tx_Extbase_Error_Error', 'Duplicate username', 1320703758)
            ));
        }

        if (strcmp($user->getPassword(), $password_check) != 0) {
            $passwordError->addErrors(array(
                t3lib_div::makeInstance('Tx_Extbase_Error_Error', 'Password does not match', 1320703779)
            ));
        }

        if ($user->getAcceptedTermsAndConditions() === FALSE) {
            $termsAndConditionsError->addErrors(array(
                t3lib_div::makeInstance('Tx_Extbase_Error_Error', 'You did not accept our terms and conditions', 1481809833739)
            ));
        }

        if (count($emailError->getErrors())) {
            $objectError->addErrors(array(
                $emailError
            ));
        }

        if (count($usernameError->getErrors())) {
            $objectError->addErrors(array(
                $usernameError
            ));
        }

        if (count($passwordError->getErrors())) {
            $objectError->addErrors(array(
                $passwordError
            ));
        }

        if (count($termsAndConditionsError->getErrors())) {
            $objectError->addErrors(array(
                $termsAndConditionsError
            ));
        }

        if (count($objectError->getErrors())) {
            $requestErrors = $this->request->getErrors();

            $requestErrors[] = $objectError;

            $this->request->setErrors($requestErrors);

            // needed in order to trigger the JS AJAX error callback
            $this->response->setStatus(409);
            $this->forward('new');
        }
        // END of MOVE TO VALIDATOR task

        $userGroups = $this->userGroupRepository->findByUidArray(t3lib_div::intExplode(',',
            $this->settings['defaultUserGroups']));

        $cleartextPassword = $user->getPassword();

        $password = Tx_Ajaxlogin_Utility_Password::salt($cleartextPassword);

        foreach ($userGroups as $userGroup) {
            $user->getUsergroup()->attach($userGroup);
        }

        $user->setPassword($password);

        // add a hash to verify the account by sending an e-mail
        $user->setVerificationHash(md5(t3lib_div::generateRandomBytes(64)));
        $user->setDisable(true);
        // set new user flag for BE confirmation module
        $user->setNewUser(1);

        // set terms and condition date of acceptance and version
        $now = new \DateTime('now');
        $user->setTacDateOfAcceptance($now);
        $user->setTacVersion($this->settings['currentTermsAndConditionVersion']);

        $this->userRepository->add($user);
        $this->userRepository->_persistAll();

        // Create the user account
        if ($this->isT3oLdapAvailable === true) {
            $this->ldap->createUser($user->getUid(), array(), $cleartextPassword);
        }

        $message = Tx_Extbase_Utility_Localization::translate('signup_successful', 'ajaxlogin');
        $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);

        $this->view->assign('user', $user);

        $emailSubject = Tx_Extbase_Utility_Localization::translate('signup_notification_subject', 'ajaxlogin', array(
            t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
        ));

        $emailBodyContent = $this->view->render();

        $mail = t3lib_div::makeInstance('t3lib_mail_Message');
        $mail->setFrom(array($this->settings['notificationMail']['emailAddress'] => $this->settings['notificationMail']['sender']));
        $mail->setTo(array($user->getEmail() => $user->getName()));
        $mail->setSubject($emailSubject);
        $mail->setBody($emailBodyContent);
        $mail->send();

        $referer = t3lib_div::_GP('referer');
        $redirectUrl = t3lib_div::_GP('redirectUrl');
        $redirect_url = Tx_Ajaxlogin_Utility_RedirectUrl::findRedirectUrl($referer, $redirectUrl);
        if (!empty($redirect_url)) {
            $this->response->setHeader('X-Ajaxlogin-redirectUrl', $redirect_url);
        }

        $this->forward('info');
    }

    /**
     * Perfoms the user log out and redirects to the login form
     *
     * @return void
     */
    public function logoutAction()
    {
        $message = Tx_Extbase_Utility_Localization::translate('logout_successful', 'ajaxlogin');
        $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::NOTICE);

        $GLOBALS['TSFE']->fe_user->logoff();

        $this->forward('login', null, null, array('redirectedFrom' => 'logout'));
    }

    /**
     * Shows the logged-in user details
     *
     * @return void
     */
    public function showAction()
    {
        $user = $this->userRepository->findCurrent();

        $this->view->assign('user', $user);
    }

    /**
     * Shows the user edit details form
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     * @dontvalidate $user
     *
     * @return void
     */
    public function editAction(Tx_Ajaxlogin_Domain_Model_User $user = null)
    {
        // double check if the passed user is indeed currently logged in user
        $currentUser = $this->userRepository->findCurrent();

        if (!$user || $user->getUid() != $currentUser->getUid()) {
            $user = $currentUser;
        }

        $this->view->assign('user', $user);
        $countries = $this->countryRepository->findAll();
        // TODO: if FLUID supports it, add empty option in FLUID
        $countries = $countries->toArray();
        $countries = array_merge(array(null => ''), $countries);
        $this->view->assign('countries', $countries);
    }


    /**
     * replaces the validator of the standard user model
     * with the validator of the modified user model (without username validation
     */
    public function initializeUpdateAction()
    {
        if ($this->arguments->hasArgument('user')) {
            /** @var Tx_Extbase_Validation_ValidatorResolver $validatorResolver */
            $validatorResolver = $this->objectManager->get('Tx_Extbase_Validation_ValidatorResolver');
            $userForEditingValidator = $validatorResolver->getBaseValidatorConjunction('Tx_Ajaxlogin_Domain_Model_UserForEditing');

            // set validator of modified user model as standard user model
            $this->arguments->getArgument('user')->setValidator($userForEditingValidator);
        }
    }

    /**
     * Updates an existing user
     *
     * @param Tx_Ajaxlogin_Domain_Model_User
     *
     * @return void
     */
    public function updateAction(Tx_Ajaxlogin_Domain_Model_User $user)
    {
        // double check if the passed user is indeed currently logged in user
        $currentUser = $this->userRepository->findCurrent();

        if ($user->getUid() != $currentUser->getUid()) {
            // no way...
            $this->forward('edit');
        }

        // TODO: clean this up and move it to the proper validators!!!
        // this much of validation shouldn't have found its way into the controller
        // START of MOVE TO VALIDATOR task
        $objectError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'user');
        $emailError = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', 'email');

        $checkEmail = $this->userRepository->findOneByEmail($user->getEmail());

        if (!is_null($checkEmail) && $checkEmail->getUid() != $user->getUid()) {
            $emailError->addErrors(array(
                t3lib_div::makeInstance('Tx_Extbase_Error_Error', 'Duplicate email address', 1320783534)
            ));
        }

        if (count($emailError->getErrors())) {
            $objectError->addErrors(array(
                $emailError
            ));
        }

        if (count($objectError->getErrors())) {
            $requestErrors = $this->request->getErrors();

            $requestErrors[] = $objectError;

            $this->request->setErrors($requestErrors);
            $this->forward('edit');
        }
        // END of MOVE TO VALIDATOR task

        // check submitted country
        if ($country) {
            $country = $this->countryRepository->findByCnShortEn($user->getCountry());
            if (!$country->count()) {
                $user->setCountry('');
            }
        }

        if ($this->isT3oLdapAvailable) {
            $userArray = array(
                'username' => $user->getUsername(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'name' => $user->getName(),
                'address' => $user->getAddress(),
                'zip' => $user->getZip(),
                'city' => $user->getCity(),
                'country' => $user->getCountry(),
                'email' => $user->getEmail(),
                'telephone' => $user->getTelephone(),
                'fax' => $user->getFax(),
                'www' => $user->getWww(),
                'accepted_terms_and_conditions' => $user->getAcceptedTermsAndConditions(),
                'tac_version' => $user->getTacVersion(),
                'tac_date_of_acceptance' => ($user->getTacDateOfAcceptance() ? $user->getTacDateOfAcceptance()->getTimestamp() : 0),
                // you need to send the password to update LDAP record
                'password' => $currentUser->getPassword()
            );
            $this->ldap->updateUser($userArray);
        }

        $this->userRepository->update($user);
        $this->flashMessageContainer->add('User updated');
        $this->forward('show');
    }

    /**
     * Activates an account based on the link in the activation mail
     *
     * @param string $verificationHash
     * @param string $email
     *
     * @return void
     */
    public function activateAccountAction($verificationHash = '', $email = '')
    {
        if (!empty($verificationHash) && !empty($email)) {
            $user = $this->userRepository->findOneByVerificationHashAndEmail($verificationHash, $email);
        }

        if (!is_null($user)) {
            if ($this->confirmAccount($user)) {
                $message = Tx_Extbase_Utility_Localization::translate('account_activated', 'ajaxlogin');
                $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);

            } else {
                $message = Tx_Extbase_Utility_Localization::translate('account_confirmed', 'ajaxlogin');
                $this->flashMessageContainer->add($message, Tx_Extbase_Utility_Localization::translate('account_confirmation_needed', 'ajaxlogin'), t3lib_FlashMessage::WARNING);
            }

            $this->userRepository->update($user);
            $this->userRepository->_persistAll();

            // Enable LDAP Account
            if ($this->isT3oLdapAvailable === true) {
                $this->ldap->enableUser($user->getUsername());
            }

            $this->notifyExchange($user, 'org.typo3.user.register');

            //$this->redirectToURI('/');
        } else {
            $message = Tx_Extbase_Utility_Localization::translate('invalid_activation_link', 'ajaxlogin');
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::ERROR);
            //$this->response->setStatus(409);
        }
    }

    /**
     * activates a user account
     * (does not persist it)
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     */
    protected function activateAccount($user)
    {
        $userGroups = $this->userGroupRepository->findByUidArray(t3lib_div::intExplode(',',
            $this->settings['defaultUserGroupsAfterVerification']));

        foreach ($userGroups as $userGroup) {
            $user->getUsergroup()->attach($userGroup);
        }
        $user->setCity('');
        $user->setCountry('');
        $user->setNewUser(0);

        $user->setDisable(false);

        // Enable LDAP Account
        if ($this->isT3oLdapAvailable === true) {
            $this->ldap->enableUser($user->getUsername());
        }

    }

    /**
     * confirms a user account
     * (does not persist it)
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     * @return bool true on autoactivation, false if not autoactivated
     */
    protected function confirmAccount($user)
    {
        $userGroups = $this->userGroupRepository->findByUidArray(t3lib_div::intExplode(',',
            $this->settings['defaultUserGroupsAfterConfirmation']));

        foreach ($userGroups as $userGroup) {
            $user->getUsergroup()->attach($userGroup);
        }
        $mapsLink = '';
        $user->setVerificationHash(null);

        $locationData = $this->getLocationDataByIp();
        if (!empty($locationData)) {
            if (isset($locationData['error'])) {
                $mapsLink = 'Location could not retrieved.';
            } else {

                if (isset($locationData['country']['name'])) {
                    $mapsLink = $locationData['country']['name'];
                    $user->setCountry($locationData['country']['name']);
                }

                if (isset($locationData['city']) && $locationData['city']) {
                    $mapsLink .= ', ' . $locationData['city'];
                    $user->setCity($locationData['city']);
                }
            }
        }
        if ($this->approveUserAutomatically($user)) {
            $this->sendSlackBotMessage(
                'New user auto approvement',
                sprintf(
                    'new user registered on typo3.org with username: *%s* email: *%s* name: *%s* and IP located in: %s was auto approved',
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getName(),
                    $mapsLink
                ),
                'info'
            );
            return true;

        } else {
            $this->sendConfirmationMessage($user);
            $this->sendSlackBotMessage(
                'New User Registration',
                sprintf(
                    'new user registered on typo3.org with username: *%s* email: *%s* name: *%s* and IP located in: %s',
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getName(),
                    $mapsLink
                ),
                'notice'
            );
            return false;
        }
    }

    /**
     * @return array
     */
    protected function getLocationDataByIp()
    {
        $data = file_get_contents('http://geoip.nekudo.com/api/' . $_SERVER['REMOTE_ADDR']);
        if ($data !== false) {
            $data = json_decode($data, true);

            return $data;
        }

        return array();
    }

    /**
     * send a welcome message to the user.
     * This method is called after an admin has approved the account.
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     */
    protected function sendConfirmationMessage($user)
    {
        $this->view->assign('user', $user);

        $emailSubject = Tx_Extbase_Utility_Localization::translate('confirm_notification_subject', 'ajaxlogin', array(
            t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
        ));

        $emailBodyContent = Tx_Extbase_Utility_Localization::translate('confirm_notification_sent', 'ajaxlogin', array(
            t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
        ));

        $emailBodyContent .= $user->getEmail() . ', ' . $user->getName();
        /** @var t3lib_mail_Message $mail */
        $mail = t3lib_div::makeInstance('t3lib_mail_Message');
        $mail->setFrom(array($this->settings['confirmationMail']['emailAddress'] => $this->settings['confirmationMail']['sender']));
        $mail->setTo(array($user->getEmail() => $user->getName()));
        $mail->setSubject($emailSubject);
        $mail->setBody($emailBodyContent);
        $mail->send();
    }

    /**
     * send a welcome message to the user.
     * This method is called after an admin has approved the account.
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     */
    protected function sendWelcomeMessage($user)
    {
        $this->view->assign('user', $user);

        $emailSubject = Tx_Extbase_Utility_Localization::translate('approve_notification_subject', 'ajaxlogin', array(
            t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
        ));

        $emailBodyContent = Tx_Extbase_Utility_Localization::translate('approve_notification_sent', 'ajaxlogin', array(
            t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
        ));

        /** @var t3lib_mail_Message $mail */
        $mail = t3lib_div::makeInstance('t3lib_mail_Message');
        $mail->setFrom(array($this->settings['notificationMail']['emailAddress'] => $this->settings['notificationMail']['sender']));
        $mail->setTo(array($user->getEmail() => $user->getName()));
        $mail->setSubject($emailSubject);
        $mail->setBody($emailBodyContent);
        $mail->send();
    }

    /**
     * Shows the user/email form
     *
     * @return void
     */
    public function forgotPasswordAction()
    {
        $token = $this->getFormToken();
        $this->view->assign('formToken', $token);
        $this->response->setHeader('X-Ajaxlogin-formToken', $token);
    }

    /**
     * Tries to find a user by the username or email
     * 1. If found, resets the user's forgot password hash, sends an email with the reset link, and forwards to the login form
     * 2. If not found, displays the error message and forwards to the forgot password form again
     *
     * @param string $usernameOrEmail
     *
     * @return void
     */
    public function resetPasswordAction($usernameOrEmail = '')
    {
        $user = null;
        $usernameOrEmail = filter_var($usernameOrEmail, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!empty($usernameOrEmail) && t3lib_div::validEmail($usernameOrEmail)) {
            $user = $this->userRepository->findOneByEmail($usernameOrEmail);
        } else {
            if (!empty($usernameOrEmail)) {
                $user = $this->userRepository->findOneByUsername($usernameOrEmail);
            }
        }

        if (!is_null($user)) {
            $user->setForgotHash(md5(t3lib_div::generateRandomBytes(64)));
            $user->setForgotHashValid((time() + (24 * 3600)));
            $this->view->assign('user', $user);

            $emailSubject = Tx_Extbase_Utility_Localization::translate('resetpassword_notification_subject',
                'ajaxlogin', array(
                    t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')
                ));

            $emailBodyContent = $this->view->render();

            $mail = t3lib_div::makeInstance('t3lib_mail_Message');
            $mail->setFrom(array($this->settings['notificationMail']['emailAddress'] => $this->settings['notificationMail']['sender']));
            $mail->setTo(array($user->getEmail() => $user->getName()));
            $mail->setSubject($emailSubject);
            $mail->setBody($emailBodyContent);
            $mail->send();

            $message = Tx_Extbase_Utility_Localization::translate('resetpassword_notification_sent', 'ajaxlogin');
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);

            $this->forward('info');
        } else {
            //$this->response->setStatus(409);
            $message = Tx_Extbase_Utility_Localization::translate('user_notfound', 'ajaxlogin',
                array($usernameOrEmail));
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::ERROR);
            $this->forward('forgotPassword');
        }
    }

    /**
     * @param string $forgotHash
     * @param string $email
     *
     * @return void
     */
    public function editPasswordAction($forgotHash = '', $email = '')
    {
        $user = $this->getUserByForgotHashAndEmail($forgotHash, $email);

        if ($user) {
            $this->view->assign('user', $user);
            $this->view->assign('forgotHash', $forgotHash);
            $this->view->assign('notExpired', true);
        }
    }

    /**
     * @param array $password
     * @validate $password Tx_Ajaxlogin_Domain_Validator_PasswordsValidator
     * @param string $forgotHash
     * @param string $email
     *
     * @return void
     */
    public function updatePasswordAction($password, $forgotHash = '', $email = '')
    {
        $user = $this->getUserByForgotHashAndEmail($forgotHash, $email);

        if (!$user) {
            $this->forward('editPassword');
        } else {
            $saltedPW = Tx_Ajaxlogin_Utility_Password::salt($password['new']);
            $user->setPassword($saltedPW);
            $user->setForgotHash('');
            $user->setForgotHashValid(0);

            if ($this->isT3oLdapAvailable === true) {
                /**
                 * Update LDAP Passwords for given user. Will create the account in LDAP
                 * if no exists. Otherwise, only password will be updated.
                 *
                 * @var Tx_T3oLdap_Utility_PasswordUpdate $ldapPasswordUtility
                 */
                if ($this->ldap->userExists($user->getUsername()) === true) {
                    $ldapPasswordUtility = t3lib_div::makeInstance('Tx_T3oLdap_Utility_PasswordUpdate');
                    $ldapPasswordUtility->updatePassword($user->getUsername(), $password['new']);
                } else {
                    // Create the user record in LDAP
                    $this->ldap->createUser(
                        $user->getUid(),
                        array(),
                        $password['new']
                    );
                }
            }
        }
    }

    /**
     * finds a user object based on the given hash and email
     *
     * if no matching user is found a flash message is set and null returned.
     *
     * @param string $forgotHash
     * @param string $email
     * @return Tx_Ajaxlogin_Domain_Model_User| null
     */
    protected function getUserByForgotHashAndEmail($forgotHash, $email)
    {
        $forgotHash = trim($forgotHash);
        if (empty($forgotHash)) {
            return $this->addForgetHashFlashMessage('forgotHash_required');
        }
        $email = trim($email);
        if (empty($email)) {
            return $this->addForgetHashFlashMessage('email_required');
        }
        if (!t3lib_div::validEmail($email)) {
            return $this->addForgetHashFlashMessage('email_invalid');
        }

        $user = $this->userRepository->findOneByEmail($email);
        if (!$user) {
            return $this->addForgetHashFlashMessage('user_notFound');
        }

        if ($user->getForgotHash() == '') {
            return $this->addForgetHashFlashMessage('password_already_changed');
        }

        if ($user->getForgotHash() !== $forgotHash) {
            return $this->addForgetHashFlashMessage('user_notFound');
        }

        if ($user->getForgotHashValid()->format('U') <= time()) {
            // if hash is no longer valid
            return $this->addForgetHashFlashMessage('link_outdated');
        }

        return $user;
    }

    /**
     * adds a flash message if something happens in the forgetHash function
     *
     * This method exists to follow the DRY principle and to prevent duplicate messages on redirects
     *
     * @param $key
     * @param int $severity
     * @return null
     */
    protected function addForgetHashFlashMessage($key, $severity = t3lib_FlashMessage::WARNING)
    {
        $message = Tx_Extbase_Utility_Localization::translate($key, 'ajaxlogin');

        // check if the flash messages was already assigned
        // this is needed to prevent duplicate messages on the forward() in updatePasswordAction
        foreach ($this->flashMessageContainer->getAllMessages() as $flashMessage) {
            if ($flashMessage->getMessage() == $message && $flashMessage->getSeverity() == $severity) {
                return null;
            }
        }

        $this->flashMessageContainer->add($message, '', $severity);

        return null;
    }

    /**
     * Shows the close account confirmation page
     *
     * @return void
     */
    public function closeAccountAction()
    {
        $user = $this->userRepository->findCurrent();

        $this->view->assign('user', $user);
    }

    /**
     * Disable currently logged in user and logout afterwards
     * @param Tx_Ajaxlogin_Domain_Model_User
     *
     * @return void
     */
    public function disableAction(Tx_Ajaxlogin_Domain_Model_User $user)
    {
        // double check if the passed user is indeed currently logged in user
        $currentUser = $this->userRepository->findCurrent();

        if ($user->getUid() != $currentUser->getUid()) {
            // no way...
            $this->forward('close');
        } else {
            $this->userRepository->update($user);
            $GLOBALS['TSFE']->fe_user->logoff();

            $message = Tx_Extbase_Utility_Localization::translate('account_disabled', 'ajaxlogin');
            $this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);
            //$this->redirectToURI('/');
        }
    }

    /**
     * Generates password-change form.
     *
     * @param $errors array     Custom validation errors.
     */
    public function changePasswordAction(array $errors = null)
    {
        $this->view->assignMultiple(array(
                'user' => $this->userRepository->findCurrent(),
                'errors' => $errors
            )
        );
    }

    /**
     * @param array $password Associate array with the following keys.
     *                              cur   - Current password
     *                              new   - New password
     *                              check - Confirmed new password
     * @validate $password Tx_Ajaxlogin_Domain_Validator_PasswordsValidator
     * @return string
     */
    public function doChangePasswordAction(array $password)
    {
        $errors = array();
        $currentUser = $this->userRepository->findCurrent();

        if (isset($password['cur']) && isset($password['new']) && isset($password['check'])) {
            $plainTextPassword = $password['cur'];
            $encryptedPassword = $currentUser->getPassword();

            if (Tx_Ajaxlogin_Utility_Password::validate($plainTextPassword, $encryptedPassword)) {

                if ($this->isT3oLdapAvailable === true) {
                    /**
                     * Update LDAP Passwords for given user. Will create the account in LDAP
                     * if no exists. Otherwise, only password will be updated.
                     *
                     * @var Tx_T3oLdap_Utility_PasswordUpdate $ldapPasswordUtility
                     */
                    if ($this->ldap->userExists($currentUser->getUsername()) === true) {
                        $ldapPasswordUtility = t3lib_div::makeInstance('Tx_T3oLdap_Utility_PasswordUpdate');
                        $ldapPasswordUtility->updatePassword($currentUser->getUsername(), $password['new']);
                    } else {
                        // Create the user record in LDAP
                        $this->ldap->createUser(
                            $currentUser->getUid(),
                            array(),
                            $password['new']
                        );
                    }
                }

                $saltedPassword = Tx_Ajaxlogin_Utility_Password::salt($password['new']);
                $currentUser->setPassword($saltedPassword);

                // redirect (if configured) or show static success text
                $redirectPageId = intval($this->settings['page']['passwordChangeSuccess']);
                if ($redirectPageId > 0) {
                    $this->redirectToPage($redirectPageId);
                } else {
                    return Tx_Extbase_Utility_Localization::translate('password_updated', 'ajaxlogin');
                }
            } else {
                $errors['current_password'] = Tx_Extbase_Utility_Localization::translate('password_invalid',
                    'ajaxlogin');
            }
        }

        $this->forward('changePassword', null, null, array('errors' => $errors));
    }

    /**
     * Redirects user to the page identified by the given page-id.
     *
     * @param int $pageId ID of the page to redirect to.
     */
    private function redirectToPage($pageId)
    {
        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pageId)
            ->build();
        $this->redirectToURI($uri);
    }

    /**
     * @return string
     */
    protected function getFormToken()
    {
        return 'tx-ajaxlogin-form' . md5(microtime());
    }

    /**
     *  checks if the user belongs to a whitlisted domain
     *
     * @param Tx_Ajaxlogin_Domain_Model_User $user
     *
     * @return bool true, if domain is whitelisted
     */
    protected function approveUserAutomatically($user)
    {

        // get configuration
        $whitelistDomainsAllowed = t3lib_div::trimExplode(',',
            $this->settings['autoApprovement']['whitelistDomains']['allowTopLevelDomains']);

        $whitelistDomainsExceptions = t3lib_div::trimExplode(',',
            $this->settings['autoApprovement']['whitelistDomains']['exceptions']);

        // could be used for automatically denyments
        #$blacklistDomains = t3lib_div::trimExplode(',', $this->settings['autoApprovement']['blacklistDomains']);

        // get domain from user

        $host_names = explode(".", $user->getEmailDomain());

        $userTopLevelDomain = '.' . end($host_names);

        // check if user domain is allowed
        if (in_array($userTopLevelDomain, $whitelistDomainsAllowed)) {
            // check if the domain has a restriction

            if (in_array($user->getEmailDomain(), $whitelistDomainsExceptions)) {
                return false;
            }
            if ($this->doApprovement($user->getUid())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $user
     * @return mixed
     */
    protected function doApprovement($user)
    {
        $user = $this->userRepository->findUserByUid($user);

        if ($user) {
            $this->activateAccount($user);
            $this->sendWelcomeMessage($user);
            $this->userRepository->_persistAll();

            // Enable LDAP Account
            if ($this->isT3oLdapAvailable === true) {
                $this->ldap->enableUser($user->getUsername());
            }

            return $user;
        }

        return false;

    }
}

<?php


class UltimateMemberNewUserAdminNotification extends SMSNotification
{
	public static $instance;

	function __construct()
	{
		parent::__construct();
		$this->title 			= 'New Account';
		$this->page 			= 'um_new_customer_admin_notif';
		$this->isEnabled 		= FALSE;
		$this->tooltipHeader 	= 'NEW_UM_CUSTOMER_NOTIF_HEADER';
		$this->tooltipBody 		= 'NEW_UM_CUSTOMER_ADMIN_NOTIF_BODY';
		$this->recipient 		=  UltimateMemberSMSNotificationUtility::getAdminPhoneNumber();;
		$this->smsBody 			=  UltimateMemberSMSNotificationMessages::showMessage('NEW_UM_CUSTOMER_ADMIN_SMS');
		$this->defaultSmsBody	=  UltimateMemberSMSNotificationMessages::showMessage('NEW_UM_CUSTOMER_ADMIN_SMS');
		$this->availableTags 	= '{site-name},{username},{accountpage-url},{email},{firtname},{lastname}';
		$this->pageHeader 		= mo_("NEW ACCOUNT ADMIN NOTIFICATION SETTINGS");
		$this->pageDescription 	= mo_("SMS notifications settings for New Account creation SMS sent to the admins");
		self::$instance 		= $this;
	}


	
	public static function getInstance()
	{
		return self::$instance === null ? new self() : self::$instance;
	}


	
	function sendSMS(array $args)
	{
		if(!$this->isEnabled) return;
		$phoneNumbers 	= maybe_unserialize($this->recipient);
		$username 		= um_user( 'user_login' );
		$profileUrl     = um_user_profile_url();
		$firstName      = um_user( 'first_name' );
		$lastName       = um_user( 'last_name' );
		$email          = um_user( 'user_email' );
		$smsBody 		= MoUtility::replaceString([
			'site-name' => get_bloginfo() , 'username' => $username, 'accountpage-url'  => $profileUrl,
			'firstname' => $firstName, 'lastname' => $lastName, 'email' => $email,
		],
			$this->smsBody
		);

		if(MoUtility::isBlank($phoneNumbers)) return;
		foreach ($phoneNumbers as $phoneNumber) {
			MoUtility::send_phone_notif($phoneNumber, $smsBody);
		}
	}
}
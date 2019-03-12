<?php

if(! defined( 'ABSPATH' )) exit;


class PolyLangStrings 
{
	use Instance;

	private function __construct()
	{		
		define("MO_POLY_STRINGS", serialize( array(	

			BaseMessage::OTP_SENT_PHONE 		=> MoMessages::showMessage('OTP_SENT_PHONE'),
			BaseMessage::OTP_SENT_EMAIL 		=> MoMessages::showMessage('OTP_SENT_EMAIL'),
			BaseMessage::ERROR_OTP_EMAIL 		=> MoMessages::showMessage('ERROR_OTP_EMAIL'),
			BaseMessage::ERROR_OTP_PHONE 		=> MoMessages::showMessage('ERROR_OTP_PHONE'),
			BaseMessage::ERROR_PHONE_FORMAT 	=> MoMessages::showMessage('ERROR_PHONE_FORMAT'),
			BaseMessage::CHOOSE_METHOD 		    => MoMessages::showMessage('CHOOSE_METHOD'),
			BaseMessage::PLEASE_VALIDATE 		=> MoMessages::showMessage('PLEASE_VALIDATE'),
			BaseMessage::ERROR_PHONE_BLOCKED 	=> MoMessages::showMessage('ERROR_PHONE_BLOCKED'),
			BaseMessage::ERROR_EMAIL_BLOCKED 	=> MoMessages::showMessage('ERROR_EMAIL_BLOCKED'),
			BaseMessage::INVALID_OTP 			=> MoMessages::showMessage('INVALID_OTP'),
			BaseMessage::EMAIL_MISMATCH 		=> MoMessages::showMessage('EMAIL_MISMATCH'),
			BaseMessage::PHONE_MISMATCH 		=> MoMessages::showMessage('PHONE_MISMATCH'),
			BaseMessage::ENTER_PHONE 			=> MoMessages::showMessage('ENTER_PHONE'),
			BaseMessage::ENTER_EMAIL 			=> MoMessages::showMessage('ENTER_EMAIL'),
			BaseMessage::ENTER_PHONE_CODE 		=> MoMessages::showMessage('ENTER_PHONE_CODE'),
			BaseMessage::ENTER_EMAIL_CODE 		=> MoMessages::showMessage('ENTER_EMAIL_CODE'),
			BaseMessage::ENTER_VERIFY_CODE 	    => MoMessages::showMessage('ENTER_VERIFY_CODE'),
			BaseMessage::PHONE_VALIDATION_MSG 	=> MoMessages::showMessage('PHONE_VALIDATION_MSG'),
			BaseMessage::MO_REG_ENTER_PHONE 	=> MoMessages::showMessage('MO_REG_ENTER_PHONE'),
			BaseMessage::UNKNOWN_ERROR 		    => MoMessages::showMessage('UNKNOWN_ERROR'),
			BaseMessage::PHONE_NOT_FOUND 		=> MoMessages::showMessage('PHONE_NOT_FOUND'),
			BaseMessage::REGISTER_PHONE_LOGIN 	=> MoMessages::showMessage('REGISTER_PHONE_LOGIN'),
			BaseMessage::DEFAULT_SMS_TEMPLATE	=> MoMessages::showMessage('DEFAULT_SMS_TEMPLATE'),
			BaseMessage::EMAIL_SUBJECT			=> MoMessages::showMessage('EMAIL_SUBJECT'),
			BaseMessage::DEFAULT_EMAIL_TEMPLATE=> MoMessages::showMessage('DEFAULT_EMAIL_TEMPLATE'),
			BaseMessage::DEFAULT_BOX_HEADER 	=> 'Validate OTP (One Time Passcode)',
			BaseMessage::GO_BACK 				=> '&larr; Go Back',
			BaseMessage::RESEND_OTP 			=> 'Resend OTP',
			BaseMessage::VALIDATE_OTP 			=> 'Validate OTP',
			BaseMessage::VERIFY_CODE 			=> 'Verify Code',
			BaseMessage::SEND_OTP 				=> 'Send OTP',
			BaseMessage::VALIDATE_PHONE_NUMBER  => 'Validate your Phone Number',
			BaseMessage::VERIFY_CODE_DESC 		=> 'Enter Verification Code',
			BaseMessage::WC_BUTTON_TEXT		    => "Verify Your Purchase",
			BaseMessage::WC_POPUP_BUTTON_TEXT 	=> "Place Order",
			BaseMessage::WC_LINK_TEXT 			=> "[ Click here to verify your Purchase ]",
			BaseMessage::WC_EMAIL_TTLE 		    => "Please Enter an Email Address to enable this.",
			BaseMessage::WC_PHONE_TTLE 		    => "Please Enter a Phone Number to enable this.",
		)));
	}
}
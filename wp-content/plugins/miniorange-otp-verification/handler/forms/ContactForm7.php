<?php

	
	class ContactForm7 extends FormHandler implements IFormHandler
	{
        use Instance;

        
		private $_formFinalEmailVer;

        
		private $_formFinalPhoneVer;

        
		private $_formSessionTagName;

		protected function __construct()
		{
            $this->_isLoginOrSocialForm = FALSE;
            $this->_isAjaxForm = TRUE;
			$this->_formSessionVar 	= FormSessionVars::CF7_FORMS;
			$this->_formEmailVer = FormSessionVars::CF7_EMAIL_VER;
			$this->_formPhoneVer = FormSessionVars::CF7_PHONE_VER;
			$this->_formFinalEmailVer = FormSessionVars::CF7_EMAIL_SUB;
			$this->_formFinalPhoneVer = FormSessionVars::CF7_PHONE_SUB;
			$this->_typePhoneTag = 'mo_cf7_contact_phone_enable';
			$this->_typeEmailTag = 'mo_cf7_contact_email_enable';
			$this->_formKey = 'CF7_FORM';
			$this->_formName = mo_('Contact Form 7 - Contact Form');
			$this->_isFormEnabled = get_mo_option('cf7_contact_enable');
			$this->_generateOTPAction = "miniorange-cf7-contact";
			parent::__construct();
		}

		
		function handleForm()
		{
			$this->_otpType = get_mo_option('cf7_contact_type');
			$this->_emailKey = get_mo_option('cf7_email_key');
			$this->_phoneKey = 'mo_phone';
			$this->_phoneFormId = [
			    '.class_'.$this->_phoneKey,
                'input[name='.$this->_phoneKey.']'
            ];

			add_filter( 'wpcf7_validate_text*'	, array($this,'validateFormPost'), 1 , 2 );
			add_filter( 'wpcf7_validate_email*'	, array($this,'validateFormPost'), 1 , 2 );
			add_filter( 'wpcf7_validate_email'	, array($this,'validateFormPost'), 1 , 2 );
			add_filter( 'wpcf7_validate_tel*'	, array($this,'validateFormPost'), 1 , 2 );

			add_shortcode('mo_verify_email', array($this,'_cf7_email_shortcode') );
			add_shortcode('mo_verify_phone', array($this,'_cf7_phone_shortcode') );

            add_action("wp_ajax_nopriv_{$this->_generateOTPAction}"  ,[$this,'_handle_cf7_contact_form']);
            add_action("wp_ajax_{$this->_generateOTPAction}"         ,[$this,'_handle_cf7_contact_form']);
		}


        
		function _handle_cf7_contact_form()
		{
		    $data = $_POST;
		    $this->validateAjaxRequest();
			MoUtility::checkSession();
			MoUtility::initialize_transaction($this->_formSessionVar);

			if(MoUtility::sanitizeCheck('user_email',$data))
			{
				$_SESSION[$this->_formEmailVer] = $data['user_email'];
				$this->sendChallenge('test',$data['user_email'],null,$data['user_email'],"email");
			}
			else if(MoUtility::sanitizeCheck('user_phone',$data))
			{
				$_SESSION[$this->_formPhoneVer] = trim($data['user_phone']);
				$this->sendChallenge('test','',null, trim($data['user_phone']),"phone");
			}
			else
			{
				if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0)
					wp_send_json( MoUtility::_create_json_response(
					    MoMessages::showMessage('ENTER_PHONE'),
                        MoConstants::ERROR_JSON_TYPE
                    ));
				else
					wp_send_json( MoUtility::_create_json_response(
					    MoMessages::showMessage('ENTER_EMAIL'),
                        MoConstants::ERROR_JSON_TYPE
                    ));
			}
		}


        
		function validateFormPost($result, $tag)
		{
			MoUtility::checkSession();
			$tag = new WPCF7_FormTag( $tag );
			$name = $tag->name;
			$value = isset( $_POST[$name] ) ? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) ) : '';

			if ( 'email' == $tag->basetype && $name==$this->_emailKey) $_SESSION[$this->_formFinalEmailVer] = $value;

			if ( 'tel' == $tag->basetype && $name==$this->_phoneKey) $_SESSION[$this->_formFinalPhoneVer]  = $value;

			if ( 'text' == $tag->basetype && $name=='email_verify' || 'text' == $tag->basetype && $name=='phone_verify')
			{
				$_SESSION[$this->_formSessionTagName] = $name;
								if($this->checkIfVerificationCodeNotEntered($name)) $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
								if($this->checkIfVerificationNotStarted()) $result->invalidate( $tag, mo_(MoMessages::showMessage('PLEASE_VALIDATE')) );
								if($this->processEmail()) $result->invalidate( $tag, mo_(MoMessages::showMessage('EMAIL_MISMATCH')) );
								if($this->processPhoneNumber()) $result->invalidate( $tag, mo_(MoMessages::showMessage('PHONE_MISMATCH')) );
								if(empty($result->get_invalid_fields())) {
                    if(!$this->processOTPEntered()) {
                        $result->invalidate($tag, MoUtility::_get_invalid_otp_method());
                    } else {
                        $this->unsetOTPSessionVariables();
                    }
                }
			}
			return $result;
		}


		
		function handle_failed_verification($user_login,$user_email,$phone_number)
		{
			MoUtility::checkSession();
			if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)) return;
			$_SESSION[$this->_formSessionVar] = 'verification_failed';
		}


	    
		function handle_post_verification($redirect_to,$user_login,$user_email,$password,$phone_number,$extra_data)
		{
			MoUtility::checkSession();
			if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)) return;
			$_SESSION[$this->_formSessionVar] = 'validated';
		}


		
		function processOTPEntered()
		{
			$this->validateChallenge($_SESSION[$this->_formSessionTagName],NULL);
			return strcasecmp($_SESSION[$this->_formSessionVar],'validated')!=0 ? FALSE : TRUE;
		}


		
		function processEmail()
		{
			return array_key_exists($this->_formEmailVer, $_SESSION)
				&& strcasecmp($_SESSION[$this->_formEmailVer], $_SESSION[$this->_formFinalEmailVer])!=0;
		}


		
		function processPhoneNumber()
		{
			return array_key_exists($this->_formPhoneVer, $_SESSION)
				&& strcasecmp($_SESSION[$this->_formPhoneVer], $_SESSION[$this->_formFinalPhoneVer])!=0;
		}


		
		function checkIfVerificationNotStarted()
		{
		    error_log(print_r($_SESSION,true));
			return !array_key_exists($this->_formSessionVar,$_SESSION);
		}


        
		function checkIfVerificationCodeNotEntered($name)
		{
			return !MoUtility::sanitizeCheck($name,$_REQUEST);
		}


        
		function _cf7_email_shortcode($attrs)
		{
		    $emailKey = MoUtility::sanitizeCheck("key",$attrs);
		    $buttonId = MoUtility::sanitizeCheck("buttonid",$attrs);
            $messagediv = MoUtility::sanitizeCheck("messagediv",$attrs);
		    $emailKey = $emailKey ? "#".$emailKey : "input[name='".$this->_emailKey."']";
		    $buttonId = $buttonId ? $buttonId : "miniorange_otp_token_submit";
		    $messagediv = $messagediv ? $messagediv : "mo_message";

		    			$img   = "<div style='display:table;text-align:center;'>".
                        "<img src='".MOV_URL. "includes/images/loader.gif'>".
                      "</div>";
						$html  = '<script>'.
                        'jQuery(document).ready(function(){'.
                            '$mo=jQuery;'.
                            '$mo("#'.$buttonId.'").click(function(o){ '.
                                'var e=$mo("'.$emailKey.'").val();'.
                                '$mo("#'.$messagediv.'").empty(),'.
                                '$mo("#'.$messagediv.'").append("'.$img.'"),'.
                                '$mo("#'.$messagediv.'").show(),'.
                                '$mo.ajax({url:"'.wp_ajax_url().'",'.
                                    'type:"POST",'.
                                    'data:{'.
                                        'user_email:e,'.
                                        'action:"'.$this->_generateOTPAction.'",'.
                                        $this->_nonceKey.':"'.wp_create_nonce($this->_nonce).'"'.
                                    '},'.
                                    'crossDomain:!0,'.
                                    'dataType:"json",'.
                                    'success:function(o){ '.
                                        'if(o.result=="success"){ '.
                                            '$mo("#'.$messagediv.'").empty(),'.
                                            '$mo("#'.$messagediv.'").append(o.message),'.
                                            '$mo("#'.$messagediv.'").css("border-top","3px solid green"),'.
                                            '$mo("input[name=email_verify]").focus()'.
                                        '}else{'.
                                            '$mo("#'.$messagediv.'").empty(),'.
                                            '$mo("#'.$messagediv.'").append(o.message),'.
                                            '$mo("#'.$messagediv.'").css("border-top","3px solid red")'.
                                        '}'.
                                    '},'.
                                    'error:function(o,e,n){}'.
                                '})'.
                            '});'.
                        '});'.
                     '</script>';
			return $html;
		}


        
		function _cf7_phone_shortcode($attrs)
		{
            $phonekey = MoUtility::sanitizeCheck("key",$attrs);
            $buttonId = MoUtility::sanitizeCheck("buttonid",$attrs);
            $messagediv = MoUtility::sanitizeCheck("messagediv",$attrs);
            $phonekey = $phonekey ? "#".$phonekey : "input[name='".$this->_phoneKey."']";
            $buttonId = $buttonId ? $buttonId : "miniorange_otp_token_submit";
            $messagediv = $messagediv ? $messagediv : "mo_message";

            			$img   = "<div style='display:table;text-align:center;'>".
                        "<img src='".MOV_URL. "includes/images/loader.gif'>".
                      "</div>";
            			$html  = '<script>'.
                        'jQuery(document).ready(function(){'.
                            '$mo=jQuery;$mo("#'.$buttonId.'").click(function(o){'.
                                'var e=$mo("'.$phonekey.'").val();'.
                                '$mo("#'.$messagediv.'").empty(),'.
                                '$mo("#'.$messagediv.'").append("'.$img.'"),'.
                                '$mo("#'.$messagediv.'").show(),'.
                                '$mo.ajax({'.
                                    'url:"'.wp_ajax_url().'",'.
                                    'type:"POST",'.
                                    'data:{'.
                                        'user_phone:e,'.
                                        'action:"'.$this->_generateOTPAction.'",'.
                                        $this->_nonceKey.':"'.wp_create_nonce($this->_nonce).'"'.
                                    '},'.
                                    'crossDomain:!0,'.
                                    'dataType:"json",'.
                                    'success:function(o){ '.
                                        'if(o.result=="success"){'.
                                            '$mo("#'.$messagediv.'").empty(),'.
                                            '$mo("#'.$messagediv.'").append(o.message),'.
                                            '$mo("#'.$messagediv.'").css("border-top","3px solid green"),'.
                                            '$mo("input[name=phone_verify]").focus()'.
                                        '}else{'.
                                            '$mo("#'.$messagediv.'").empty(),'.
                                            '$mo("#'.$messagediv.'").append(o.message),'.
                                            '$mo("#'.$messagediv.'").css("border-top","3px sol id red")'.
                                        '}'.
                                    '},'.
                                    'error:function(o,e,n){}'.
                                '})'.
                            '});'.
                        '});'.
                     '</script>';
			return $html;
		}


		
		public function unsetOTPSessionVariables()
		{
			unset($_SESSION[$this->_txSessionId]);
			unset($_SESSION[$this->_formSessionVar]);
			unset($_SESSION[$this->_formEmailVer]);
			unset($_SESSION[$this->_formPhoneVer]);
			unset($_SESSION[$this->_formFinalEmailVer]);
			unset($_SESSION[$this->_formFinalPhoneVer]);
			unset($_SESSION[$this->_formSessionTagName]);
		}


        
		public function getPhoneNumberSelector($selector)
		{
			MoUtility::checkSession();
			if($this->_isFormEnabled && ($this->_otpType == $this->_typePhoneTag)) {
                $selector = array_merge($selector,$this->_phoneFormId);
            }
            return $selector;
		}


        
		private function emailKeyValidationCheck(){
            if($this->_otpType === $this->_typeEmailTag && MoUtility::isBlank($this->_emailKey)){
                do_action(
                    'mo_registration_show_message',
                    MoMessages::showMessage(BaseMessage::CF7_PROVIDE_EMAIL_KEY),
                    MoConstants::ERROR
                );
                return false;
            }
            return true;
        }


		
		function handleFormOptions()
		{
			if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

			$this->_isFormEnabled = $this->sanitizeFormPOST('cf7_contact_enable');
			$this->_otpType = $this->sanitizeFormPOST('cf7_contact_type');
			$this->_emailKey = $this->sanitizeFormPOST('cf7_email_field_key');

						if($this->basicValidationCheck(BaseMessage::CF7_CHOOSE)
                && $this->emailKeyValidationCheck()) {
                update_mo_option('cf7_contact_enable', $this->_isFormEnabled);
                update_mo_option('cf7_contact_type', $this->_otpType);
                update_mo_option('cf7_email_key', $this->_emailKey);
            }
		}
	}
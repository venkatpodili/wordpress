<?php

	
	class CalderaForms extends FormHandler implements IFormHandler
	{
        use Instance;

		protected function __construct()
		{
            $this->_isLoginOrSocialForm = FALSE;
            $this->_isAjaxForm = TRUE;
			$this->_formSessionVar = FormSessionVars:: CALDERA;
			$this->_formEmailVer = FormSessionVars:: CALDERA_EMAIL_VER;
			$this->_formPhoneVer = FormSessionVars:: CALDERA_PHONE_VER;
			$this->_typePhoneTag = 'mo_caldera_phone_enable';
			$this->_typeEmailTag = 'mo_caldera_email_enable';
			$this->_formKey = 'CALDERA';
			$this->_formName = mo_('Caldera Forms');
			$this->_isFormEnabled = get_mo_option('caldera_enable');
			$this->_buttonText = get_mo_option('caldera_button_text');
			$this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
			$this->_phoneFormId = array();
			parent::__construct();
		}

		
		function handleForm()
		{
			$this->_otpType = get_mo_option('caldera_enable_type');
            $this->_formDetails = maybe_unserialize(get_mo_option('caldera_forms'));
			if(empty($this->_formDetails)) return;
			
			foreach ($this->_formDetails as $key => $value) {
                array_push($this->_phoneFormId,'input[name='.$value["phonekey"]);
                add_filter( 'caldera_forms_validate_field_'.$value["phonekey"], array($this,'validateForm'),99,3);
                add_filter( 'caldera_forms_validate_field_'.$value["emailkey"], array($this,'validateForm'),99,3);
                add_filter( 'caldera_forms_validate_field_'.$value["verifyKey"], array($this,'validateForm'),99,3);
			}
            add_filter( 'caldera_forms_render_field_structure', array($this,'showVerificationButton'),99,2);
			$this->routeData();
		}


		private function routeData()
		{
			if(!array_key_exists('option', $_GET)) return;
			switch (trim($_GET['option']))
			{
				case "miniorange-calderaforms":
					$this->_send_otp($_POST);		break;
			}
		}


        
		function _send_otp($data)
		{
			MoUtility::checkSession();
			MoUtility::initialize_transaction($this->_formSessionVar);
			if($this->_otpType==$this->_typePhoneTag)
				$this->_processPhoneAndStartOTPVerificationProcess($data);
			else
				$this->_processEmailAndStartOTPVerificationProcess($data);
		}


		
		private function _processEmailAndStartOTPVerificationProcess($data)
		{
			if(!array_key_exists('user_email', $data) || !isset($data['user_email']))
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_EMAIL'),MoConstants::ERROR_JSON_TYPE) );
			else
				$this->setSessionAndStartOTPVerification($data['user_email'],$data['user_email'],NULL,"email");
		}


		
		private function _processPhoneAndStartOTPVerificationProcess($data)
		{
			if(!array_key_exists('user_phone', $data) || !isset($data['user_phone']))
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_PHONE'),MoConstants::ERROR_JSON_TYPE) );
			else
				$this->setSessionAndStartOTPVerification(trim($data['user_phone']),NULL,trim($data['user_phone']),"phone");
		}


		
		private function setSessionAndStartOTPVerification($sessionvalue,$useremail,$phoneNumber,$_otpType)
		{
			$_SESSION[ strcasecmp($this->_otpType,$this->_typePhoneTag)==0 ? $this->_formPhoneVer : $this->_formEmailVer ] = $sessionvalue;
            $this->sendChallenge('testUser',$useremail,NULL,$phoneNumber,$_otpType);
		}


        
		public function showVerificationButton($field_structure,$form)
		{
			$formId = $form['ID'];
			if(!array_key_exists($formId,$this->_formDetails)) return $field_structure;
            $formData = $this->_formDetails[$formId];
			if($this->_otpType==$this->_typePhoneTag && strcasecmp($field_structure['field']['ID'],$formData['phonekey'])==0) {
				$field_structure['field_after'] = $this->getButtonAndScriptCode('phone',$formData);
			}
			elseif($this->_otpType==$this->_typeEmailTag && strcasecmp($field_structure['field']['ID'],$formData['emailkey'])==0) {
				$field_structure['field_after'] = $this->getButtonAndScriptCode('email',$formData);
            }
            return $field_structure;
		}


        
		private function getButtonAndScriptCode($mo_type,$formData)
		{
		    $uq = $formData[$mo_type."key"];             $button_title = $mo_type == "phone"
                            ? mo_("Please Enter your phone details to enable this.")
                            :  mo_("Please Enter your email to enable this.");
			$img = "<div style='display:table;text-align:center;'><img src='".MOV_LOADER_URL."'></div>";

			$field_content = '</div><div style="margin-top: 2%;"><div class=""><button type="button" style="width:100%;"';
			$field_content .= 'class="btn btn-default" id="miniorange_otp_token_submit'.$uq.'" title="'.$button_title.'">';
			$field_content .= mo_($this->_buttonText).'</button></div></div><div style="margin-top:2%">';
			$field_content .= '<div id="mo_message'.$uq.'" hidden="" style="background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;"></div></div>';
			$field_content .= '<script>jQuery(document).ready(function(){$mo=jQuery;';
			$field_content .= '$mo("#miniorange_otp_token_submit'.$uq.'").click(function(o){';
			$field_content .= 'var e=$mo("input[name='.$uq.']").val();';
			$field_content .= '$mo("#mo_message'.$uq.'").empty(),$mo("#mo_message'.$uq.'").append("'.$img.'")';
			$field_content .= ',$mo("#mo_message'.$uq.'").show(),$mo.ajax({';
			$field_content .= 'url:"'.site_url().'/?option=miniorange-calderaforms",type:"POST",data:{user_';
			$field_content .= $mo_type.':e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result=="success"){';
			$field_content .= '$mo("#mo_message'.$uq.'").empty()';
			$field_content .= ',$mo("#mo_message'.$uq.'").append(o.message),';
			$field_content .= '$mo("#mo_message'.$uq.'").css("border-top","3px solid green"),$mo("';
			$field_content .= 'input[name='.$uq.'").focus()}else{$mo("#mo_message'.$uq.'").empty(),';
			$field_content .= '$mo("#mo_message'.$uq.'").append(o.message),';
			$field_content .= '$mo("#mo_message'.$uq.'").css("border-top","3px solid red"),';
			$field_content .= '$mo("input[name='.$formData["verifyKey"].'").focus()} ;},';
			$field_content .= 'error:function(o,e,n){}})});});</script>';
			return $field_content;
		}


        
		public function validateForm($entry, $field, $form)
		{
			if(is_wp_error( $entry ) ) return $entry;
			$id = $form['ID'];
			if(!array_key_exists($id,$this->_formDetails)) return;
			$formData = $this->_formDetails[$id];
			MoUtility::checkSession();
            $entry = $this->checkIfOtpVerificationStarted($entry);
             
			if(is_wp_error($entry)) return $entry;
			if(strcasecmp($this->_otpType,$this->_typeEmailTag)==0 && strcasecmp($field['ID'],$formData['emailkey'])==0)
				$entry= $this->processEmail($entry);
			if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0 && strcasecmp($field['ID'],$formData['phonekey'])==0)
				$entry = $this->processPhone($entry);
            
			if(empty($errors) && strcasecmp($field['ID'],$formData['verifyKey'])==0)
				$entry = $this->processOTPEntered($entry);
			return $entry;
		}


        
		function processOTPEntered($entry)
		{
			$this->validateChallenge(NULL,$entry);
			if(strcasecmp($_SESSION[$this->_formSessionVar],'validated')!=0)
				$entry = new WP_Error('INVALID_OTP',MoUtility::_get_invalid_otp_method());
			else
				$this->unsetOTPSessionVariables();
			return $entry;
		}


        
		function checkIfOtpVerificationStarted($entry)
		{
			if(array_key_exists($this->_formSessionVar, $_SESSION)) return $entry;
			if(strcasecmp($this->_otpType,$this->_typeEmailTag)==0)
				return new WP_Error('ENTER_VERIFY_CODE',MoMessages::showMessage('ENTER_VERIFY_CODE'));
			else
                return new WP_Error('ENTER_VERIFY_CODE',MoMessages::showMessage('ENTER_VERIFY_CODE'));
			return $entry;
		}


        
		function processEmail($entry)
		{
			return $_SESSION[$this->_formEmailVer]!=$entry ? new WP_Error('EMAIL_MISMATCH',MoMessages::showMessage('EMAIL_MISMATCH')) : $entry;
		}


        
		function processPhone($entry)
		{
			return $_SESSION[$this->_formPhoneVer]!=$entry ? new WP_Error('PHONE_MISMATCH',MoMessages::showMessage('PHONE_MISMATCH')) : $entry;
		}


		
		function handle_failed_verification($user_login,$user_email,$phone_number)
		{
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])) return;
			$_SESSION[$this->_formSessionVar] = 'verification_failed';
		}


	    
		function handle_post_verification($redirect_to,$user_login,$user_email,$password,$phone_number,$extra_data)
		{
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])) return;
			$_SESSION[$this->_formSessionVar] = 'validated';
		}


		
		public function unsetOTPSessionVariables()
		{
			unset($_SESSION[$this->_txSessionId]);
			unset($_SESSION[$this->_formSessionVar]);
			unset($_SESSION[$this->_formPhoneVer]);
			unset($_SESSION[$this->_formEmailVer]);
		}


        
		public function getPhoneNumberSelector($selector)
		{
			MoUtility::checkSession();
			if($this->isFormEnabled() && $this->_otpType==$this->_typePhoneTag)
				$selector = array_merge($selector, $this->_phoneFormId);
			return $selector;
		}


		
		function handleFormOptions()
	    {
			if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

			$this->_isFormEnabled = $this->sanitizeFormPOST('caldera_enable');
			$this->_otpType = $this->sanitizeFormPOST('caldera_enable_type');
			$this->_buttonText = $this->sanitizeFormPOST('caldera_button_text');

			$form = $this->parseFormDetails();

			$this->_formDetails = !empty($form) ? $form : "";

			update_mo_option('caldera_enable',$this->_isFormEnabled);
			update_mo_option('caldera_enable_type',$this->_otpType);
            update_mo_option('caldera_button_text',$this->_buttonText);
			update_mo_option('caldera_forms',maybe_serialize($this->_formDetails));
		}


		
		function parseFormDetails()
		{
			
			if(!array_key_exists('caldera_form',$_POST) || !$this->_isFormEnabled) return array();
			foreach (array_filter($_POST['caldera_form']['form']) as $key => $value)
			{
				$form[$value]= array(
					'emailkey'=> $_POST['caldera_form']['emailkey'][$key],
					'phonekey'=> $_POST['caldera_form']['phonekey'][$key],
					'verifyKey'=> $_POST['caldera_form']['verifyKey'][$key],
                    'phone_show'=>$_POST['caldera_form']['phonekey'][$key],
                    'email_show'=>$_POST['caldera_form']['emailkey'][$key],
                    'verify_show'=>$_POST['caldera_form']['verifyKey'][$key]
				);
			}
			return $form;
		}

	}
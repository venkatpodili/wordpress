<?php

	
	class GravityForm extends FormHandler implements IFormHandler
	{
        use Instance;

		protected function __construct()
		{
            $this->_isLoginOrSocialForm = FALSE;
            $this->_isAjaxForm = TRUE;
			$this->_formSessionVar = FormSessionVars::GF_FORMS;
			$this->_formEmailVer = FormSessionVars::GF_EMAIL_VER;
			$this->_formPhoneVer = FormSessionVars::GF_PHONE_VER;
			$this->_typePhoneTag = 'mo_gf_contact_phone_enable';
			$this->_typeEmailTag = 'mo_gf_contact_email_enable';
			$this->_formKey = 'GRAVITY_FORM';
			$this->_formName = mo_('Gravity Form');
			$this->_isFormEnabled = get_mo_option('gf_contact_enable');
			$this->_phoneFormId =  ".ginput_container_phone";
            $this->_buttonText = get_mo_option("gf_button_text");
            $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
			parent::__construct();
		}

		
		function handleForm()
		{
			$this->_otpType = get_mo_option('gf_contact_type');
			$this->_formDetails = maybe_unserialize(get_mo_option('gf_otp_enabled'));
			if(empty($this->_formDetails)) return;
			add_filter('gform_field_content',array($this,'_add_scripts'),1,5);
			add_filter('gform_field_validation',array($this,'validate_form_submit'),1,5);

			$this->routeData();
		}


        
        function routeData()
		{
			if(!array_key_exists('option', $_GET)) return;

			switch (trim($_GET['option']))
			{
				case "miniorange-gf-contact":
					$this->_handle_gf_form($_POST);		break;
			}
		}


        
		function _handle_gf_form($getdata)
		{
			MoUtility::checkSession();
			MoUtility::initialize_transaction($this->_formSessionVar);

			$this->processEmailAndStartOTPVerificationProcess($getdata);
			$this->processPhoneAndStartOTPVerificationProcess($getdata);
			$this->sendErrorMessageIfOTPVerificationNotStarted();
		}


		
		function processEmailAndStartOTPVerificationProcess($getdata)
		{
			if(!array_key_exists('user_email', $getdata) || !isset($getdata['user_email'])) return;

			if(MoUtility::isBlank($getdata['user_email']))
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_EMAIL'),MoConstants::ERROR_JSON_TYPE) );

			$_SESSION[$this->_formEmailVer] = $getdata['user_email'];
			$this->sendChallenge('testuser',$getdata['user_email'],null,$getdata['user_email'],"email");
		}


		
		function processPhoneAndStartOTPVerificationProcess($getdata)
		{
			if(!array_key_exists('user_phone', $getdata) || !isset($getdata['user_phone'])) return;

			if(MoUtility::isBlank($getdata['user_phone']))
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_PHONE'),MoConstants::ERROR_JSON_TYPE) );

			$_SESSION[$this->_formPhoneVer] = trim($getdata['user_phone']);
			$this->sendChallenge('testuser','',null, trim($getdata['user_phone']),"phone");
		}


		
		function sendErrorMessageIfOTPVerificationNotStarted()
		{
			if(strcasecmp($this->_otpType,$this->_typePhoneTag)===0)
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_PHONE'),MoConstants::ERROR_JSON_TYPE) );
			else
				wp_send_json( MoUtility::_create_json_response(MoMessages::showMessage('ENTER_EMAIL'),MoConstants::ERROR_JSON_TYPE) );
		}


        
		function _add_scripts($field_content, $field, $value, $zero, $form_id)
		{
            $formData = $this->_formDetails[$form_id];
            if(!MoUtility::isBlank($formData)) {
                if (strcasecmp($this->_otpType, $this->_typeEmailTag) === 0
                    && get_class($field) === "GF_Field_Email"
                    && $field["id"] == $formData["emailkey"]) {
                    $field_content = $this->_add_shortcode_to_form('email', $field_content, $field, $form_id);
                }
                if (strcasecmp($this->_otpType, $this->_typePhoneTag) === 0
                    && get_class($field) === "GF_Field_Phone"
                    && $field["id"]== $formData["phonekey"]) {
                    $field_content = $this->_add_shortcode_to_form('phone', $field_content, $field, $form_id);
                }
            }
			return $field_content;
		}


        
		function _add_shortcode_to_form($mo_type, $field_content,$field,$form_id)
		{
			$img = "<div style='display:table;text-align:center;'><img src='".MOV_URL. "includes/images/loader.gif'></div>";
			$field_content .= "<div style='margin-top: 2%;'><input type='button' class='gform_button button medium' ";
			$field_content .= "id='miniorange_otp_token_submit' title='Please Enter an " . $mo_type . " to enable this' ";
			$field_content .= "value= '".mo_($this->_buttonText)."'><div style='margin-top:2%'>";
			$field_content .= "<div id='mo_message' hidden='' style='background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;'></div></div></div>";
			$field_content .= '<style>@media only screen and (min-width: 641px) { #mo_message { width: calc(50% - 8px);}}</style>';
			$field_content .= '<script>jQuery(document).ready(function(){$mo=jQuery;$mo("#gform_'.$form_id.' #miniorange_otp_token_submit").click(function(o){';
			$field_content .= 'var e=$mo("#input_'.$form_id.'_'.$field->id.'").val(); $mo("#gform_'.$form_id.' #mo_message").empty(),$mo("#gform_'.$form_id.' #mo_message").append("'.$img.'")';
			$field_content .= ',$mo("#gform_'.$form_id.' #mo_message").show(),$mo.ajax({url:"'.site_url().'/?option=miniorange-gf-contact",type:"POST",data:{user_';
			$field_content .= $mo_type.':e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result==="success"){$mo("#gform_'.$form_id.' #mo_message").empty()';
			$field_content .= ',$mo("#gform_'.$form_id.' #mo_message").append(o.message),$mo("#gform_'.$form_id.' #mo_message").css("border-top","3px solid green"),$mo("';
			$field_content .= '#gform_'.$form_id.' input[name=email_verify]").focus()}else{$mo("#gform_'.$form_id.' #mo_message").empty(),$mo("#gform_'.$form_id.' #mo_message").append(o.message),';
			$field_content .= '$mo("#gform_'.$form_id.' #mo_message").css("border-top","3px solid red"),$mo("#gform_'.$form_id.' input[name=phone_verify]").focus()} ;},';
			$field_content .= 'error:function(o,e,n){}})});});</script>';
			return $field_content;
		}


        
		function validate_form_submit( $error, $value, $form, $field )
		{
            MoUtility::checkSession();
            $formDetails = MoUtility::sanitizeCheck($field->formId,$this->_formDetails);

            if($formDetails && $error['is_valid']==1)
            {
                if(strpos($field->label, $formDetails['verifyKey']) !== false
                    && $this->transactionIdSet())
                {
                    $error = $this->validate_otp($error, $value);
                }
                else if($this->isEmailOrPhoneField($field,$formDetails))
                {
                    if($this->transactionIdSet()) {
                        $error = $this->validate_submitted_email_or_phone($error['is_valid'], $value, $error);
                    } else {
                        $error = [
                            'is_valid' => null,
                            'message' => MoMessages::showMessage(MoMessages::PLEASE_VALIDATE),
                        ];
                    }
                }
            }
            return $error;
		}


        
		function validate_otp($error,$value)
		{
            if(MoUtility::isBlank($value))
            {
                $error = [ 'is_valid'=>null,'message'=> MoUtility::_get_invalid_otp_method() ] ;
            }
            else
            {
                $this->validateChallenge(NULL,$value);
                if (strcasecmp($_SESSION[$this->_formSessionVar],'validated')!=0) {
                    $error = [ 'is_valid'=>null,'message'=> MoUtility::_get_invalid_otp_method() ] ;
                } else {
                    $this->unsetOTPSessionVariables();
                }
            }
            return $error;
		}


        
		function validate_submitted_email_or_phone($isValid,$value,$error)
		{
			if($isValid)
			{
				if(array_key_exists($this->_formEmailVer, $_SESSION)
					&& strcasecmp($_SESSION[$this->_formEmailVer], $value)!=0)
					return array('is_valid'=>null,'message'=>MoMessages::showMessage('EMAIL_MISMATCH'));
				if(array_key_exists($this->_formPhoneVer, $_SESSION)
					&& strcasecmp($_SESSION[$this->_formPhoneVer], $value)!=0)
					return array('is_valid'=>null,'message'=>MoMessages::showMessage('PHONE_MISMATCH'));
			}
			return $error;
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
			unset($_SESSION[$this->_formEmailVer]);
			unset($_SESSION[$this->_formPhoneVer]);
		}


        
		public function getPhoneNumberSelector($selector)
		{
			MoUtility::checkSession();
			if($this->isFormEnabled() && $this->_otpType===$this->_typePhoneTag) {
			    foreach ($this->_formDetails as $key => $formDetail) {
			        $phoneField = sprintf("%s_%d_%d","input",$key,$formDetail["phonekey"]);
                    array_push($selector, sprintf("%s #%s",$this->_phoneFormId,$phoneField));
                }
            }
			return $selector;
		}


		
		function handleFormOptions()
		{
			if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

			$this->_isFormEnabled = $this->sanitizeFormPOST('gf_contact_enable');
			$this->_otpType = $this->sanitizeFormPOST('gf_contact_type');
            $this->_buttonText = $this->sanitizeFormPOST('gf_button_text');
            $forms = $this->parseFormDetails();

            $this->_formDetails = is_array($forms) ? maybe_serialize($forms) : '';

			update_mo_option('gf_otp_enabled', $this->_formDetails);
			update_mo_option('gf_contact_enable', $this->_isFormEnabled);
			update_mo_option('gf_contact_type', $this->_otpType);
            update_mo_option('gf_button_text',$this->_buttonText);
		}

        
        private function parseFormDetails()
        {
            $getFieldKey = function($fieldDetails,$fieldLabel,$type) {
                foreach ($fieldDetails as $field) {
                    if(get_class($field) === $type
                        && $field['label']==$fieldLabel) {
                        return $field['id'];
                    }
                }
                return null;
            };

            $form = NULL;
            if(!array_key_exists('gravity_form',$_POST) || !$this->_isFormEnabled) return array();
            foreach (array_filter($_POST['gravity_form']['form']) as $key => $value)
            {
                $formdata= GFAPI::get_form($value);
                $emailKey = $_POST['gravity_form']['emailkey'][$key];
                $phoneKey = $_POST['gravity_form']['phonekey'][$key];
                $forms[$value]= array(
                    'emailkey'=> $getFieldKey($formdata["fields"],$emailKey,"GF_Field_Email"),
                    'phonekey'=> $getFieldKey($formdata["fields"],$phoneKey,"GF_Field_Phone"),
                    'verifyKey'=> $_POST['gravity_form']['verifyKey'][$key],
                    'phone_show'=>$_POST['gravity_form']['phonekey'][$key],
                    'email_show'=>$_POST['gravity_form']['emailkey'][$key],
                    'verify_show'=>$_POST['gravity_form']['verifyKey'][$key]
                );
            }
            return $forms;




        }

        
        private function transactionIdSet()
        {
            return array_key_exists($this->_formSessionVar, $_SESSION);
        }

        
        private function isEmailOrPhoneField($field,$f)
        {
            return  ($this->_otpType===$this->_typePhoneTag && $field->id === $f['phonekey'])
                || ($this->_otpType===$this->_typeEmailTag && $field->id === $f['emailkey']);
        }
	}
<?php

	
	class WooCommerceRegistrationForm extends FormHandler implements IFormHandler
	{
        use Instance;

        
		private $_redirectToPage;

		protected function __construct()
		{
            $this->_isLoginOrSocialForm = FALSE;
			$this->_formSessionVar = FormSessionVars::WC_DEFAULT_REG;
			$this->_typePhoneTag = 'mo_wc_phone_enable';
			$this->_typeEmailTag = 'mo_wc_email_enable';
			$this->_typeBothTag = 'mo_wc_both_enable';
			$this->_phoneFormId = '#reg_billing_phone';
            $this->_formPhoneVer = FormSessionVars::WC_REG_AJAX_PHONE_VER;
            $this->_formEmailVer = FormSessionVars::WC_REG_AJAX_EMAIL_VER;
			$this->_formKey = 'WC_REG_FORM';
			$this->_formName = mo_("Woocommerce Registration Form");
			$this->_isFormEnabled = get_mo_option('wc_default_enable');
			$this->_buttonText = get_mo_option("wc_button_text");
            $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
			parent::__construct();
		}

		
		function handleForm()
		{
            $this->_isAjaxForm = get_mo_option('wc_is_ajax_form');
			$this->_otpType = get_mo_option('wc_enable_type');
			$this->_redirectToPage = get_mo_option('wc_redirect');
			$this->_restrictDuplicates = get_mo_option('wc_restrict_duplicates');

			add_filter('woocommerce_process_registration_errors', array($this,'woocommerce_site_registration_errors'),99,4);
            add_action('woocommerce_created_customer', array( $this, 'register_woocommerce_user' ),1,3);
            add_filter('woocommerce_registration_redirect', array( $this,'custom_registration_redirect'), 99, 1);
			if($this->isPhoneVerificationEnabled()) {

                add_action('woocommerce_register_form', array($this, 'mo_add_phone_field'), 1);
                add_action('wcmp_vendor_register_form', array($this, 'mo_add_phone_field'), 1);
            }
            if($this->_isAjaxForm && $this->_otpType!=$this->_typeBothTag) {
                add_action('woocommerce_register_form', array($this, 'mo_add_verification_field'), 1);
                add_action('wcmp_vendor_register_form', array($this, 'mo_add_verification_field'), 1);
                add_action('wp_enqueue_scripts',array($this, 'miniorange_register_wc_script'));
                $this->routeData();
            }
        }


        
        private function routeData()
        {
            MoUtility::checkSession();
            if (!array_key_exists('option', $_GET)) return;
            switch (trim($_GET['option'])) {
                case "miniorange-wc-reg-verify":
                    $this->sendAjaxOTPRequest();    break;
            }
        }


        
        private function sendAjaxOTPRequest()
        {
            MoUtility::initialize_transaction($this->_formSessionVar);
            $this->validateAjaxRequest();
            $mobile_number = MoUtility::sanitizeCheck('user_phone',$_POST);
            $user_email = MoUtility::sanitizeCheck('user_email',$_POST);
            if($this->_otpType===$this->_typePhoneTag){
                $_SESSION[$this->_formPhoneVer] = MoUtility::processPhoneNumber($mobile_number);
            } else {
                $_SESSION[$this->_formEmailVer] = $user_email;
            }
            $error = $this->processFormFields(null,$user_email,new WP_Error(),null,$mobile_number);
            if($error->get_error_code()) {
                wp_send_json(MoUtility::_create_json_response($error->get_error_message(),MoConstants::ERROR_JSON_TYPE));
            }
        }


        
        function miniorange_register_wc_script()
        {
            wp_register_script( 'mowcreg', MOV_URL . 'includes/js/wcreg.min.js',array('jquery') );
            wp_localize_script( 'mowcreg', 'mowcreg', array(
                'siteURL' 		=> site_url(),
                'otpType'  		=> $this->_otpType,
                'nonce'         => wp_create_nonce($this->_nonce),
                'buttontext'    => mo_($this->_buttonText),
                'field'         => $this->_otpType === $this->_typePhoneTag ? "reg_billing_phone" : "reg_email",
                'imgURL'        => MOV_LOADER_URL,
            ));
            wp_enqueue_script( 'mowcreg' );
        }


        
        function custom_registration_redirect($var) {
		    return  MoUtility::isBlank($var) ? get_permalink( get_page_by_title($this->_redirectToPage)->ID) : $var;
        }


		
		function isPhoneVerificationEnabled()
		{
			return (strcasecmp($this->_otpType,$this->_typePhoneTag)===0
                || strcasecmp($this->_otpType,$this->_typeBothTag)===0);
		}


        
		function woocommerce_site_registration_errors(WP_Error $errors,$username,$password,$email)
		{
			MoUtility::checkSession();
			if(!MoUtility::isBlank(array_filter($errors->errors))) return $errors;
			if($this->_isAjaxForm){
                $this->assertOTPField($errors,$_POST);
                $this->checkIfOTPWasSent($errors);
                return $this->checkIntegrityAndValidateOTP($_POST,$errors);
            }	else {
			    return $this->processFormAndSendOTP($username,$password,$email,$errors);
            }
		}


        
        private function assertOTPField(&$errors,$form_items)
        {
            if(!MoUtility::sanitizeCheck("moverify",$form_items)){
                $errors = new WP_Error(
                    'registration-error-otp-needed',
                    MoMessages::showMessage('REQUIRED_OTP')
                );
            }
        }


        
        private function checkIfOTPWasSent(&$errors)
        {
            if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)){
                $errors= new WP_Error(
                    'registration-error-need-validation',
                    MoMessages::showMessage('PLEASE_VALIDATE')
                );
            }
        }


        
		private function checkIntegrityAndValidateOTP($data,WP_Error $errors)
        {
            if(!empty($errors->errors)) return $errors;
            $data['billing_phone'] = MoUtility::processPhoneNumber($data['billing_phone']);
            $errors = $this->checkIntegrity($data,$errors);
            if(!empty($errors->errors)) return $errors;

            $this->validateChallenge(NULL,$data['moverify']);

            if(MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION) !== 'validated') {
                return new WP_Error(
                    'registration-error-invalid-otp',
                    MoUtility::_get_invalid_otp_method()
                );
            } else {
                $this->unsetOTPSessionVariables();
            }
            return $errors;
        }


        
        private function checkIntegrity($data,WP_Error $errors)
        {
            if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0) {
                if($_SESSION[$this->_formPhoneVer]!== MoUtility::processPhoneNumber($data['billing_phone'])) {
                    return new WP_Error(
                        'billing_phone_error',
                        MoMessages::showMessage('PHONE_MISMATCH')
                    );
                }
            } else if(strcasecmp($this->_otpType,$this->_typeEmailTag)==0) {
                if($_SESSION[$this->_formEmailVer]!==$data['email']) {
                    return new WP_Error(
                        'registration-error-invalid-email',
                        mo_( MoMessages::showMessage('EMAIL_MISMATCH'), 'woocommerce' )
                    );
                }
            }
            return $errors;
        }


        
        private function processFormAndSendOTP($username,$password,$email,WP_Error $errors)
        {
            if(MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)==='validated') {
                $this->unsetOTPSessionVariables();
                return $errors;
            }
            MoUtility::initialize_transaction($this->_formSessionVar);

            try {
                $this->assertUserName($username);
                $this->assertPassword($password);
                $this->assertEmail($email);
            } catch (MoException $e) {
                return new WP_Error( $e->getMoCode(), $e->getMessage());
            }
            do_action( 'woocommerce_register_post', $username, $email, $errors );

            return $errors->get_error_code()
                    ? $errors
                    : $this->processFormFields($username,$email,$errors,$password,$_POST['billing_phone']);
        }



        
		private function assertPassword($password)
        {
            if( get_mo_option( 'woocommerce_registration_generate_password' , '')==='no' ) {
                if (  MoUtility::isBlank( $password ) ) {
                    throw new MoException(
                        'registration-error-invalid-password',
                        mo_('Please enter a valid account password.', 'woocommerce'),
                        204
                    );
                }
            }
        }

        
        private function assertEmail($email)
        {
            if ( MoUtility::isBlank( $email ) || ! is_email( $email ) ) {
                throw new MoException(
                    'registration-error-invalid-email',
                    mo_('Please enter a valid email address.', 'woocommerce'),
                    202
                );
            }
            if ( email_exists( $email ) ) {
                throw new MoException(
                    'registration-error-email-exists',
                    mo_('An account is already registered with your email address. Please login.', 'woocommerce'),
                    203
                );
            }
        }


        
		private function assertUserName($username)
        {
            if( get_mo_option( 'woocommerce_registration_generate_username' , '')==='no' ) {
                if (  MoUtility::isBlank( $username ) || ! validate_username( $username ) ) {
                    throw new MoException(
                        'registration-error-invalid-username',
                        mo_('Please enter a valid account username.', 'woocommerce'),
                        200
                    );
                }
                if ( username_exists( $username ) ) {
                    throw new MoException(
                        'registration-error-username-exists',
                        mo_('An account is already registered with that username. Please choose another.', 'woocommerce'),
                        201
                    );
                }
            }
        }


        
		function processFormFields($username,$email,$errors,$password,$phone)
		{
			global $phoneLogic;
			if(strcasecmp($this->_otpType,$this->_typePhoneTag)===0)
			{
				if ( !isset( $phone ) || !MoUtility::validatePhoneNumber($phone))
					return new WP_Error( 'billing_phone_error',
						str_replace("##phone##",$phone,$phoneLogic->_get_otp_invalid_format_message()) );
				elseif($this->_restrictDuplicates && $this->isPhoneNumberAlreadyInUse($phone,'billing_phone'))
					return new WP_Error( 'billing_phone_error', MoMessages::showMessage('PHONE_EXISTS'));
				$this->sendChallenge($username,$email,$errors,$phone,"phone",$password);
			}
			else if(strcasecmp($this->_otpType,$this->_typeEmailTag)===0)
			{
				$phone = isset($phone) ? $phone : "";
				$this->sendChallenge($username,$email,$errors,$phone,"email",$password);
			}
			else if(strcasecmp($this->_otpType,$this->_typeBothTag)===0)
			{
				if ( !isset( $phone ) || !MoUtility::validatePhoneNumber($phone))
					return new WP_Error( 'billing_phone_error',
						str_replace("##phone##",$_POST['billing_phone'],$phoneLogic->_get_otp_invalid_format_message()) );
				$this->sendChallenge($username,$email,$errors,$_POST['billing_phone'],"both",$password);
			}
			return $errors;
		}


		
		public function register_woocommerce_user($customer_id, $new_customer_data, $password_generated)
		{
			if(isset($_POST['billing_phone'])) {
                update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
            }
		}


		
		function mo_add_phone_field()
		{
            if(!did_action("woocommerce_register_form") || !did_action("wcmp_vendor_register_form")) {
                echo '<p class="form-row form-row-wide">
					<label for="reg_billing_phone">
					    ' . mo_('Phone') . '
					    <span class="required">*</span>
                    </label>
					<input type="text" class="input-text" 
					        name="billing_phone" id="reg_billing_phone" 
					        value="' . (!empty($_POST['billing_phone']) ? $_POST['billing_phone'] : "") . '" />
			  	  </p>';
            }
		}


        
		function mo_add_verification_field()
        {
            echo '<p class="form-row form-row-wide">
					<label for="reg_verification_phone">
					    '.mo_('Enter Code').'
					    <span class="required">*</span>
                    </label>
					<input type="text" class="input-text" name="moverify" 
					        id="reg_verification_field" 
					        value="" />
			  	  </p>';
        }


		
		function handle_failed_verification($user_login,$user_email,$phone_number)
		{
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])) return;
			if($this->_isAjaxForm) {
                $_SESSION[$this->_formSessionVar] = 'verification_failed';
            } else {
                $otpVerType = strcasecmp($this->_otpType,$this->_typePhoneTag)===0 ? "phone"
                    : (strcasecmp($this->_otpType,"mo_wc_both_enable")===0 ? "both" : "email" );
                $fromBoth = strcasecmp($otpVerType,"both")===0 ? TRUE : FALSE;
                miniorange_site_otp_validation_form($user_login,$user_email,$phone_number,MoUtility::_get_invalid_otp_method(),$otpVerType,$fromBoth);
            }
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
			if($this->isFormEnabled() && $this->isPhoneVerificationEnabled()) {
                array_push($selector, $this->_phoneFormId);
            }
            return $selector;
		}


        
		function isPhoneNumberAlreadyInUse($phone,$key)
		{
			global $wpdb;
			$phone = MoUtility::processPhoneNumber($phone);
			$results = $wpdb->get_row("SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '$key' AND `meta_value` =  '$phone'");
			return !MoUtility::isBlank($results);
		}


		
		function handleFormOptions()
		{
			if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

			$this->_isFormEnabled = $this->sanitizeFormPOST('wc_default_enable');
			$this->_otpType = $this->sanitizeFormPOST('wc_enable_type');
			$this->_restrictDuplicates = $this->sanitizeFormPOST('wc_restrict_duplicates');
			$this->_redirectToPage = isset( $_POST['page_id']) ? get_the_title($_POST['page_id']) : 'My Account';
			$this->_isAjaxForm = $this->sanitizeFormPOST('wc_is_ajax_form');
			$this->_buttonText = $this->sanitizeFormPOST('wc_button_text');

			update_mo_option('wc_default_enable',$this->_isFormEnabled);
			update_mo_option('wc_enable_type',$this->_otpType);
			update_mo_option('wc_restrict_duplicates',$this->_restrictDuplicates);
			update_mo_option('wc_redirect',$this->_redirectToPage);
			update_mo_option('wc_is_ajax_form',$this->_isAjaxForm);
			update_mo_option('wc_button_text',$this->_buttonText);
		}

		
		
		

		public function redirectToPage(){ return $this->_redirectToPage; }
	}


	class MoException extends \Exception
    {
        private $moCode;

        public function __construct($moCode,$message,$code)
        {
            $this->moCode = $moCode;
            parent::__construct($message, $code, NULL);
        }

        
        public function getMoCode(){ return $this->moCode; }
    }
<?php

	
	class WPLoginForm extends FormHandler implements IFormHandler
	{
        use Instance;

        
		private $_formSessionVar2;

        
		private $_savePhoneNumbers;

        
		private $_byPassAdmin;

        
		private $_allowLoginThroughPhone;

        
        private $_skipPasswordCheck;

        
		private $_userLabel;

        
		private $_delayOtp;

        
		private $_delayOtpInterval;

        
		private $_skipPassFallback;

        
		private $_createUserAction;

        
		private $_timeStampMetaKey = 'mov_last_verified_dttm';

		protected function __construct()
		{
            $this->_isLoginOrSocialForm = TRUE;
            $this->_isAjaxForm = TRUE;
			$this->_formSessionVar = FormSessionVars::WP_LOGIN_REG_PHONE;
			$this->_formSessionVar2 = FormSessionVars::WP_DEFAULT_LOGIN;
			$this->_phoneFormId = '#mo_phone_number';
            $this->_typePhoneTag = 'mo_wp_login_phone_enable';
            $this->_typeEmailTag = 'mo_wp_login_email_enable';
			$this->_formKey = 'WP_DEFAULT_LOGIN';
			$this->_formName = mo_("WordPress Login Form");
			$this->_isFormEnabled = get_mo_option('wp_login_enable');
            $this->_userLabel = get_mo_option('wp_username_label_text');
            $this->_userLabel = $this->_userLabel ? mo_($this->_userLabel) :mo_("Username, E-mail or Phone No.");
            $this->_skipPasswordCheck = get_mo_option('wp_login_skip_password');
			$this->_allowLoginThroughPhone = get_mo_option('wp_login_allow_phone_login');
			$this->_skipPassFallback = get_mo_option('wp_login_skip_password_fallback');
			$this->_delayOtp = get_mo_option('wp_login_delay_otp');
            $this->_delayOtpInterval = get_mo_option('wp_login_delay_otp_interval');
            $this->_delayOtpInterval = $this->_delayOtpInterval ? $this->_delayOtpInterval : 43800;

            if($this->_skipPasswordCheck || $this->_allowLoginThroughPhone) {
				add_action('login_enqueue_scripts',array($this, 'miniorange_register_login_script'));
				add_action('wp_enqueue_scripts'   ,array($this, 'miniorange_register_login_script'));
            }
			parent::__construct();
		}

		
		function handleForm()
		{
            $this->_otpType = get_mo_option('wp_login_enable_type');
			$this->_phoneKey = get_mo_option('wp_login_key');
			$this->_savePhoneNumbers = get_mo_option('wp_login_register_phone');
			$this->_byPassAdmin = get_mo_option('wp_login_bypass_admin');
			$this->_restrictDuplicates = get_mo_option('wp_login_restrict_duplicates');
			add_filter( 'authenticate', array($this,'_handle_mo_wp_login'), 99, 3 );
            if(class_exists("UM")) {
                add_filter('wp_authenticate_user', array($this, '_get_and_return_user'), 99, 2);
            }
            $this->routeData();
		}

        function routeData()
        {
            if(!array_key_exists('option', $_REQUEST)) return;
            switch (trim($_REQUEST['option']))
            {
                case "miniorange-ajax-otp-generate":
                    $this->_handle_wp_login_ajax_send_otp();				break;
                case "miniorange-ajax-otp-validate":
                    $this->_handle_wp_login_ajax_form_validate_action();	break;
                case "mo_ajax_form_validate":
                    $this->_handle_wp_login_create_user_action();			break;
            }
        }


        function miniorange_register_login_script()
        {
            wp_register_script( 'mologin', MOV_URL . 'includes/js/loginform.min.js',array('jquery') );
            wp_localize_script( 'mologin', 'movarlogin', array(
                'userLabel'         =>  $this->_allowLoginThroughPhone ? $this->_userLabel : null,
				'skipPwdCheck'      =>  $this->_skipPasswordCheck,
				'skipPwdFallback'	=>	$this->_skipPassFallback,
                'buttontext'        =>  mo_("Login with OTP"),
                            ));
            wp_enqueue_script( 'mologin' );
        }

        
        function _get_and_return_user($username,$password)
        {
            if(is_object($username)) return $username;
            $user = $this->getUser($username,$password);
            if(is_wp_error($user)) return $user;
            UM()->login()->auth_id = $user->data->ID;
            UM()->form()->errors = null;
            return $user;
        }


        
		function byPassLogin($user,$skipOTPProcess)
		{
            $user_meta 	= get_userdata($user->data->ID);
            $user_role 	= $user_meta->roles;
			return (in_array('administrator',$user_role) && $this->_byPassAdmin)                     || $skipOTPProcess                                                                     || $this->delayOTPProcess($user->data->ID);
		}


		
		function _handle_wp_login_create_user_action()
		{
            
            $getUserFromPost = function($postdata) {
                $username = MoUtility::sanitizeCheck("log",$postdata);
                if(!$username) {
                    $array = array_filter($postdata, function($key) {
                        return strpos($key, 'username') === 0;
                    },ARRAY_FILTER_USE_KEY);
                    $username = !empty($array) ? array_shift($array) : $username;
                }
                return is_email( $username ) ? get_user_by("email",$username) : get_user_by("login",$username);
            };

		    $postdata = $_POST;
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])
				|| $_SESSION[$this->_formSessionVar]!='validated') 	return;

			$user = $getUserFromPost($postdata);
			update_user_meta($user->data->ID, $this->_phoneKey ,$postdata['mo_phone_number']);
			$this->login_wp_user($user->data->user_login);
		}


		
		function login_wp_user($user_log,$extra_data=null)
		{
			$user = is_email( $user_log ) ? get_user_by("email",$user_log)  					: ( $this->allowLoginThroughPhone() && MoUtility::validatePhoneNumber($user_log)    						? $this->getUserFromPhoneNumber($user_log) : get_user_by("login",$user_log) ); 			wp_set_auth_cookie($user->data->ID);
			update_user_meta($user->data->ID,$this->_timeStampMetaKey,time());
			$this->unsetOTPSessionVariables();
			do_action( 'wp_login', $user->user_login, $user );
			$redirect = MoUtility::isBlank($extra_data) ? site_url() : $extra_data;
			wp_redirect($redirect);
			exit;
		}


        
		function _handle_mo_wp_login($user,$username,$password)
		{
		    if(!MoUtility::isBlank($username)) {
		        $skipOTPProcess = $this->skipOTPProcess($password);
                $user = $this->getUser($username,$password);                    				if(is_wp_error($user)) return $user;                                            MoUtility::checkSession();
                if($this->byPassLogin($user,$skipOTPProcess)) return $user;                     $this->startOTPVerificationProcess($user,$username,$password);
		    }
            return $user;
		}


		
        function startOTPVerificationProcess($user,$username,$password)
        {
            if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0){
                $phone_number = get_user_meta($user->data->ID, $this->_phoneKey);
                $phone_number = isset($phone_number[0]) ? MoUtility::processPhoneNumber($phone_number[0]) : '';
                $this->askPhoneAndStartVerification($user,$this->_phoneKey,$username,$phone_number);
                $this->fetchPhoneAndStartVerification($user,$this->_phoneKey,$username,$password,$phone_number);
            }
            else if(strcasecmp($this->_otpType,$this->_typeEmailTag)==0){
                $email= $user->data->user_email;
                $this->startEmailVerification($user,$this->_phoneKey,$username,$password,$email);
            }
        }


        
		function getUser($username, $password = null)
		{
            $user = is_email( $username ) ? get_user_by("email",$username) : get_user_by("login",$username);
			if($this->_allowLoginThroughPhone && MoUtility::validatePhoneNumber($username)){
			    $user = $this->getUserFromPhoneNumber($username);
			}
			if($user && !$this->isLoginWithOTP()){
			    $user = wp_authenticate_username_password(NULL,$user->data->user_login,$password);
            }
			return $user ? $user : new WP_Error( 'INVALID_USERNAME' , mo_(" <b>ERROR:</b> Invalid UserName. ") );
		}


        
		function getUserFromPhoneNumber($username)
		{
			global $wpdb;
			$results = $wpdb->get_row("SELECT `user_id` FROM `{$wpdb->prefix}usermeta`"
                                        ."WHERE `meta_key` = '$this->_phoneKey' AND `meta_value` =  '$username'");
			return !MoUtility::isBlank($results) ? get_userdata($results->user_id) : false;
		}


        
		function askPhoneAndStartVerification($user,$key,$username,$phone_number)
		{
			if(!MoUtility::isBlank($phone_number)) return;

			if( !$this->savePhoneNumbers() ) {
                miniorange_site_otp_validation_form(null, null, null,
                    MoMessages::showMessage('PHONE_NOT_FOUND'), null, null);
            } else {
				MoUtility::initialize_transaction($this->_formSessionVar);
				$this->sendChallenge(NULL,$user->data->user_login,NULL,NULL,
                        'external',NULL, array('data'=>array('user_login'=>$username),
                        'message'=>MoMessages::showMessage('REGISTER_PHONE_LOGIN'),
                        'form'=>$key,'curl'=>MoUtility::currentPageUrl()));
			}
		}


        
		function fetchPhoneAndStartVerification($user,$key,$username,$password,$phone_number)
		{
			if((array_key_exists($this->_formSessionVar,$_SESSION)
                    && strcasecmp($_SESSION[$this->_formSessionVar],'validated')==0)
				|| (array_key_exists($this->_formSessionVar2,$_SESSION)
                    && strcasecmp($_SESSION[$this->_formSessionVar2],'validated')==0)) return;

			MoUtility::initialize_transaction($this->_formSessionVar2);
			$redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : MoUtility::currentPageUrl();
			$this->sendChallenge($username,null,null,$phone_number,"phone",
                                $password,$redirect_to,false);
		}


        
        function startEmailVerification($user,$key,$username,$password,$email)
        {
            if((array_key_exists($this->_formSessionVar,$_SESSION)
                    && strcasecmp($_SESSION[$this->_formSessionVar],'validated')==0)
                || (array_key_exists($this->_formSessionVar2,$_SESSION)
                    && strcasecmp($_SESSION[$this->_formSessionVar2],'validated')==0)) return;
            MoUtility::initialize_transaction($this->_formSessionVar2);
            $this->sendChallenge($username,$email,null,null,"email");
        }


		
		function _handle_wp_login_ajax_send_otp()
		{
            $data = $_POST;
			MoUtility::checkSession();
			if($this->restrictDuplicates()
				&& !MoUtility::isBlank($this->getUserFromPhoneNumber($data['user_phone']))) {
                wp_send_json(MoUtility::_create_json_response(
                    MoMessages::showMessage('PHONE_EXISTS'),
                    MoConstants::ERROR_JSON_TYPE)
                );
            }elseif(isset($_SESSION[$this->_formSessionVar])) {
                $this->sendChallenge('ajax_phone', '', null, trim($data['user_phone']),
                    "phone", null, $data);
            }
		}


		
		function _handle_wp_login_ajax_form_validate_action()
        {
            $data = $_POST;
            MoUtility::checkSession();
            if (!isset($_SESSION[$this->_formSessionVar])) return;

            if (strcmp($_SESSION['phone_number_mo'], MoUtility::processPhoneNumber($data['user_phone']))) {
                wp_send_json(MoUtility::_create_json_response(
                    MoMessages::showMessage('PHONE_MISMATCH'),
                    MoConstants::ERROR_JSON_TYPE)
                );
            }else {
                $this->validateChallenge();
            }
		}


		
		function handle_failed_verification($user_login,$user_email,$phone_number)
		{
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])
				&& !isset($_SESSION[$this->_formSessionVar2]) ) return;

			if(isset($_SESSION[$this->_formSessionVar])){
				$_SESSION[$this->_formSessionVar] = 'verification_failed';
				wp_send_json( MoUtility::_create_json_response(
				    MoUtility::_get_invalid_otp_method(),
                    MoConstants::ERROR_JSON_TYPE)
                );
			}

			if(isset($_SESSION[$this->_formSessionVar2]))
				miniorange_site_otp_validation_form($user_login,$user_email,$phone_number,
                    MoUtility::_get_invalid_otp_method(),"phone",FALSE);
		}


	    
		function handle_post_verification($redirect_to,$user_login,$user_email,$password,$phone_number,$extra_data)
		{
			MoUtility::checkSession();
			if(!isset($_SESSION[$this->_formSessionVar])
				 && !isset($_SESSION[$this->_formSessionVar2])) return;

			if(isset($_SESSION[$this->_formSessionVar])) {
				$_SESSION[$this->_formSessionVar] = 'validated';
				wp_send_json( MoUtility::_create_json_response('',MoConstants::SUCCESS_JSON_TYPE) );
			}


			if(isset($_SESSION[$this->_formSessionVar2])) {
			    $username = MoUtility::isBlank($user_login) ? MoUtility::sanitizeCheck('log',$_POST) : $user_login;
                $username = MoUtility::isBlank($username) ? MoUtility::sanitizeCheck('username',$_POST) : $username;
                $this->login_wp_user($username,$extra_data);
            }
		}


		
		public function unsetOTPSessionVariables()
		{
			unset($_SESSION[$this->_txSessionId]);
			unset($_SESSION[$this->_formSessionVar]);
			unset($_SESSION[$this->_formSessionVar2]);
		}


        
		public function getPhoneNumberSelector($selector)
		{
			MoUtility::checkSession();
			if($this->isFormEnabled()) array_push($selector, $this->_phoneFormId);
			return $selector;
		}


        
		private function isLoginWithOTP()
        {
            $loginWithOTPText = mo_("Login with OTP");
            return MoUtility::sanitizeCheck('wp-submit',$_POST)== $loginWithOTPText                     || MoUtility::sanitizeCheck('login',$_POST)== $loginWithOTPText                         || MoUtility::sanitizeCheck('logintype',$_POST)== $loginWithOTPText;            }

        
        private function skipOTPProcess($password)
        {
            return $this->_skipPasswordCheck                    && $this->_skipPassFallback                     && isset($password)                             && !$this->isLoginWithOTP();            }


        
        private function delayOTPProcess($user_id)
        {
            $lastVerifiedDTTM = get_user_meta($user_id,$this->_timeStampMetaKey);
            if(MoUtility::isBlank($lastVerifiedDTTM)) return FALSE;
            $lastVerifiedDTTM = is_array($lastVerifiedDTTM) ? $lastVerifiedDTTM[0] : $lastVerifiedDTTM;
            $timeDiff = time() - $lastVerifiedDTTM;
            return $this->_delayOtp && $timeDiff < ($this->_delayOtpInterval * 60);
        }


		
		function handleFormOptions()
	    {
			if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

			$this->_isFormEnabled = $this->sanitizeFormPOST('wp_login_enable');
			$this->_savePhoneNumbers = $this->sanitizeFormPOST('wp_login_register_phone');
			$this->_byPassAdmin = $this->sanitizeFormPOST('wp_login_bypass_admin');
			$this->_phoneKey = $this->sanitizeFormPOST('wp_login_phone_field_key');
			$this->_allowLoginThroughPhone = $this->sanitizeFormPOST('wp_login_allow_phone_login');
			$this->_restrictDuplicates = $this->sanitizeFormPOST('wp_login_restrict_duplicates');
            $this->_otpType = $this->sanitizeFormPOST('wp_login_enable_type');
            $this->_skipPasswordCheck = $this->sanitizeFormPOST('wp_login_skip_password');
			$this->_userLabel = $this->sanitizeFormPOST('wp_username_label_text');
			$this->_skipPassFallback = $this->sanitizeFormPOST('wp_login_skip_password_fallback');
			$this->_delayOtp = $this->sanitizeFormPOST('wp_login_delay_otp');
			$this->_delayOtpInterval = $this->sanitizeFormPOST('wp_login_delay_otp_interval');
            
            update_mo_option('wp_login_enable_type', $this->_otpType);
			update_mo_option('wp_login_enable', $this->_isFormEnabled);
			update_mo_option('wp_login_register_phone', $this->_savePhoneNumbers);
			update_mo_option('wp_login_bypass_admin', $this->_byPassAdmin);
			update_mo_option('wp_login_key', $this->_phoneKey);
			update_mo_option('wp_login_allow_phone_login', $this->_allowLoginThroughPhone);
			update_mo_option('wp_login_restrict_duplicates', $this->_restrictDuplicates);
			update_mo_option('wp_login_skip_password', $this->_skipPasswordCheck);
			update_mo_option('wp_login_skip_password_fallback', $this->_skipPassFallback);
            update_mo_option('wp_username_label_text', $this->_userLabel);
            update_mo_option('wp_login_delay_otp', $this->_delayOtp);
            update_mo_option('wp_login_delay_otp_interval', $this->_delayOtpInterval);
		}


		

        
		public function savePhoneNumbers() { return $this->_savePhoneNumbers; }

        
		function byPassCheckForAdmins() { return $this->_byPassAdmin; }

        
		function allowLoginThroughPhone() { return $this->_allowLoginThroughPhone; }

        
        public function getSkipPasswordCheck() { return $this->_skipPasswordCheck; }

        
        public function getUserLabel() { return mo_($this->_userLabel); }

        
        public function getSkipPasswordCheckFallback() { return $this->_skipPassFallback; }

        
        public function isDelayOtp(){ return $this->_delayOtp; }

        
        public function getDelayOtpInterval(){ return $this->_delayOtpInterval; }
	}
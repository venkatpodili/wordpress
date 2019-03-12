<?php



class UMPasswordResetHandler extends FormHandler implements IFormHandler
{
    use Instance;

    
    private $_sendOTPAction;
    
    private $_formUserSessionVar;
    
    private $_fieldKey;

    protected function __construct()
    {
        $this->_isAjaxForm = TRUE;
        $this->_formOption = "um_password_reset_handler";
        $this->_formSessionVar = FormSessionVars::UM_DEFAULT_PASS;
        $this->_formUserSessionVar = FormSessionVars::UM_PASS_VER;
        $this->_typePhoneTag = 'mo_um_phone_enable';
        $this->_typeEmailTag = 'mo_um_email_enable';
        $this->_phoneFormId = "username_b";
        $this->_fieldKey = "username_b";
        $this->_formKey = 'ULTIMATE_PASS_RESET';
        $this->_formName = mo_("Ultimate Member Password Reset using OTP");
        $this->_isFormEnabled = get_umpr_option('pass_enable') ? TRUE : FALSE ;
        $this->_sendOTPAction = 'mo_umpr_send_otp';
        $this->_buttonText = get_umpr_option("pass_button_text");
        $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Reset Password");
        $this->_phoneKey =  get_umpr_option('pass_phoneKey');
        $this->_phoneKey = $this->_phoneKey ? $this->_phoneKey : "mobile_number";
        parent::__construct();
    }

    
    public function handleForm()
    {
        $this->_otpType = get_umpr_option('enabled_type');
        add_action("wp_ajax_nopriv_".$this->_sendOTPAction,array($this,'sendAjaxOTPRequest'));
        add_action("wp_ajax_".$this->_sendOTPAction,array($this,'sendAjaxOTPRequest'));
        add_action('wp_enqueue_scripts',array($this, 'miniorange_register_um_script'));
        add_action('um_reset_password_errors_hook', array($this,'um_reset_password_errors_hook'),99);
        add_action('um_reset_password_process_hook', array($this,'um_reset_password_process_hook'),1);
    }


    
    public function sendAjaxOTPRequest()
    {
        MoUtility::initialize_transaction($this->_formSessionVar);
        $this->validateAjaxRequest();
        $username = MoUtility::sanitizeCheck('username',$_POST);
        $_SESSION[$this->_formUserSessionVar] = $username;
        $user = $this->getUser($username);
        $phone = get_user_meta($user->ID,$this->_phoneKey);
        $this->startOtpTransaction(null,$user->user_email,null,$phone,null,null);
    }

    
    public function um_reset_password_process_hook()
    {
        $user = MoUtility::sanitizeCheck("username_b",$_POST);
        $user = $this->getUser(trim($user));
        $pwdObj = $this->getUmPwdObj();
                        um_fetch_user( $user->ID );
        $this->getUmUserObj()->password_reset();
        wp_redirect($pwdObj->reset_url());
        exit();
    }

    
    public function um_reset_password_errors_hook()
    {
        $form = $this->getUmFormObj();
        $user = MoUtility::sanitizeCheck($this->_fieldKey,$_POST);

        if(isset($form->errors)) {
            if( strcasecmp($this->_otpType,$this->_typePhoneTag)==0
                && MoUtility::validatePhoneNumber($user)) {
                $user_id = $this->getUserFromPhoneNumber($user);
                if(!$user_id) {
                    $form->add_error($this->_fieldKey,UMPasswordResetMessages::showMessage("USERNAME_NOT_EXIST"));
                } else {
                    if(!isset($form->errors)) {
                        $this->check_reset_password_limit($form,$user_id);
                    }
                }
            }
        }
        if(!isset($form->errors)) {
            $this->checkIntegrityAndValidateOTP($form,MoUtility::sanitizeCheck('verify_field',$_POST),$_POST);
        }
    }


    
    private function checkIntegrityAndValidateOTP(&$form,$value,array $args)
    {
        MoUtility::checkSession();
        $this->checkIntegrity($form,$args);
        $this->validateChallenge(NULL,$value);
        if(MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION) !== 'validated') {
            $form->add_error($this->_fieldKey,UMPasswordResetMessages::showMessage('INVALID_OTP'));
        }
    }


    
    private function checkIntegrity($umForm,array $args)
    {
        $sessionVar = MoUtility::sanitizeCheck($this->_formUserSessionVar,$_SESSION);
        if($sessionVar!==$args[$this->_fieldKey]) {
            $umForm->add_error($this->_fieldKey, UMPasswordResetMessages::showMessage('USERNAME_MISMATCH'));
        }
    }


    
    public function getUserId($user)
    {
        $user = $this->getUser($user);
        return $user ? $user->ID : false;
    }


    
    public function getUser($username)
    {
        if( strcasecmp($this->_otpType,$this->_typePhoneTag)==0
            && MoUtility::validatePhoneNumber($username)) {
            $username = MoUtility::processPhoneNumber($username);
            $user = $this->getUserFromPhoneNumber($username);
        } else if(is_email($username)) {
            $user = get_user_by("email",$username);
        } else {
            $user = get_user_by("login",$username);
        }
        return $user;
    }


    
    function getUserFromPhoneNumber($username)
    {
        global $wpdb;
        $results = $wpdb->get_row("SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '$this->_phoneKey' AND `meta_value` =  '$username'");
        return !MoUtility::isBlank($results) ? get_userdata($results->user_id) : false;
    }


    
    public function check_reset_password_limit(\UM\Core\Form &$form,$user_id)
    {
        $attempts = (int)get_user_meta( $user_id, 'password_rst_attempts', true );
        $is_admin = user_can( intval( $user_id ),'manage_options' );

        if ( $this->getUmOptions()->get( 'enable_reset_password_limit' ) ) { 
            if ( $this->getUmOptions()->get( 'disable_admin_reset_password_limit' ) &&  $is_admin ) {
                            } else {
                $limit = $this->getUmOptions()->get( 'reset_password_limit_number' );
                if ( $attempts >= $limit ) {
                    $form->add_error($this->_fieldKey, __('You have reached the limit for requesting password ".
                    "change for this user already. Contact support if you cannot open the email','ultimate-member') );
                } else {
                    update_user_meta( $user_id, 'password_rst_attempts', $attempts + 1 );
                }
            }

        }
    }


    
    private function getUmFormObj()
    {
        if($this->isUltimateMemberV2Installed()) {
            return UM()->form();
        }else{
            global $ultimatemember;
            return $ultimatemember->form;
        }
    }


    
    private function getUmUserObj()
    {
        if($this->isUltimateMemberV2Installed()) {
            return UM()->user();
        }else{
            global $ultimatemember;
            return $ultimatemember->user;
        }
    }


    
    private function getUmPwdObj()
    {
        if($this->isUltimateMemberV2Installed()) {
            return UM()->password();
        }else{
            global $ultimatemember;
            return $ultimatemember->password;
        }
    }


    
    private function getUmOptions()
    {
        if($this->isUltimateMemberV2Installed()) {
            return UM()->options();
        }else{
            global $ultimatemember;
            return $ultimatemember->options;
        }
    }


    
    function isUltimateMemberV2Installed()
    {
        if( !function_exists('is_plugin_active') ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return is_plugin_active( 'ultimate-member/ultimate-member.php' );
    }


    
    private function startOtpTransaction($username,$email,$errors,$phone_number,$password,$extra_data)
    {
        if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0)
            $this->sendChallenge($username,$email,$errors,$phone_number,"phone",$password,$extra_data);
        else
            $this->sendChallenge($username,$email,$errors,$phone_number,"email",$password,$extra_data);
    }


    
    public function miniorange_register_um_script()
    {
        wp_register_script( 'moumpr', UMPR_URL . 'includes/js/moumpr.min.js',array('jquery') );
        wp_localize_script( 'moumpr', 'moumprvar', array(
            'siteURL' 		=> wp_ajax_url(),
            'nonce'         => wp_create_nonce($this->_nonce),
            'buttontext'    => mo_($this->_buttonText),
            'imgURL'        => MOV_LOADER_URL,
            'action'        => [ 'send' => $this->_sendOTPAction ],
            'fieldKey'      => $this->_fieldKey,
        ));
        wp_enqueue_script( 'moumpr' );
    }


    
    public function unsetOTPSessionVariables()
    {
        unset($_SESSION[$this->_txSessionId]);
        unset($_SESSION[$this->_formSessionVar]);
        unset($_SESSION[$this->_formUserSessionVar]);
    }


    
    public function handle_post_verification($redirect_to, $user_login, $user_email, $password, $phone_number, $extra_data)
    {
        MoUtility::checkSession();
        if(!isset($_SESSION[$this->_formSessionVar])) return;
        $_SESSION[$this->_formSessionVar] = 'validated';
    }


    
    public function handle_failed_verification($user_login, $user_email, $phone_number)
    {
        MoUtility::checkSession();
        if(!isset($_SESSION[$this->_formSessionVar])) return;
        $_SESSION[$this->_formSessionVar] = 'invalid';
    }



    public function handleFormOptions()
    {
        if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

        $this->_isFormEnabled = $this->sanitizeFormPOST("um_pr_enable");
        $this->_buttonText = $this->sanitizeFormPOST("um_pr_button_text");
        $this->_buttonText = $this->_buttonText ? $this->_buttonText : "Reset Password";
        $this->_otpType = $this->sanitizeFormPOST("um_pr_enable_type");
        $this->_phoneKey = $this->sanitizeFormPOST("um_pr_phone_field_key");

        update_umpr_option('pass_enable',$this->_isFormEnabled);
        update_umpr_option("pass_button_text",$this->_buttonText);
        update_umpr_option('enabled_type',$this->_otpType);
        update_umpr_option('pass_phoneKey',$this->_phoneKey);
    }


    
    public function getPhoneNumberSelector($selector)
    {
        MoUtility::checkSession();
        if($this->isFormEnabled() && strcasecmp($this->_otpType,$this->_typePhoneTag)==0) {
            array_push($selector, $this->_phoneFormId);
        }
        return $selector;
    }
}
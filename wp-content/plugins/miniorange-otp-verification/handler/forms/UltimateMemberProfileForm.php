<?php


class UltimateMemberProfileForm extends FormHandler implements IFormHandler
{
    use Instance;
    private $_verifyFieldKey;

    protected function __construct()
    {
        $this->_isLoginOrSocialForm = TRUE;
        $this->_isAjaxForm = TRUE;
        $this->_formSessionVar = FormSessionVars::UM_PROFILE_UPDATE;
        $this->_typePhoneTag = 'mo_um_profile_phone_enable';
        $this->_typeEmailTag = 'mo_um_profile_email_enable';
        $this->_formPhoneVer = FormSessionVars::UM_PROFILE_PHONE_VER;
        $this->_formEmailVer = FormSessionVars::UM_PROFILE_EMAIL_VER;
        $this->_typeBothTag = 'mo_um_profile_both_enable';
        $this->_formKey = 'ULTIMATE_PROFILE_FORM';
        $this->_verifyFieldKey = 'verify_field';
        $this->_formName = mo_("Ultimate Member Profile/Account Form");
        $this->_isFormEnabled = get_mo_option('um_profile_enable');
        $this->_restrictDuplicates = get_mo_option('um_profile_restrict_duplicates');
        $this->_buttonText = get_mo_option("um_profile_button_text");
        $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
        $this->_emailKey = "user_email";
        $this->_phoneKey =  get_mo_option('um_profile_phone_key');
        $this->_phoneKey = $this->_phoneKey ? $this->_phoneKey : "mobile_number";
        $this->_phoneFormId= "input[name^='$this->_phoneKey']";
        parent::__construct();
    }


    public function handleForm()
    {
        $this->_otpType = get_mo_option('um_profile_enable_type');
        add_action( 'wp_enqueue_scripts',array($this, 'miniorange_register_um_script'));
        add_action( 'um_submit_account_errors_hook', array($this, 'miniorange_um_validation'), 99, 1);
        add_action( 'um_add_error_on_form_submit_validation', array($this, 'miniorange_um_profile_validation'), 1, 3 );
        $this->routeData();
    }


    
    private function isAccountVerificationEnabled()
    {
        return strcasecmp($this->_otpType,$this->_typeEmailTag)==0
            || strcasecmp($this->_otpType,$this->_typeBothTag)==0;
    }


    
    private function isProfileVerificationEnabled()
    {
        return strcasecmp($this->_otpType,$this->_typePhoneTag)==0
            || strcasecmp($this->_otpType,$this->_typeBothTag)==0;
    }


    
    private function routeData()
    {
        if(!array_key_exists('option', $_GET)) return;
        switch (trim($_GET['option']))
        {   case "miniorange-um-acc-ajax-verify":
            $this->sendAjaxOTPRequest();            break;
        }
    }


    
    private function sendAjaxOTPRequest()
    {
        MoUtility::initialize_transaction($this->_formSessionVar);
        $this->validateAjaxRequest();
        $mobile_number = MoUtility::sanitizeCheck('user_phone',$_POST);
        $user_email = MoUtility::sanitizeCheck('user_email',$_POST);
        $otpRequestType = MoUtility::sanitizeCheck('otp_request_type',$_POST);
        $this->startOtpTransaction($user_email,$mobile_number,$otpRequestType);
    }


    
    private function startOtpTransaction($email,$phone_number,$otpRequestType)
    {
        if(strcasecmp($otpRequestType,$this->_typePhoneTag)==0) {
            $this->checkDuplicates($phone_number, $this->_phoneKey);
            $_SESSION[$this->_formPhoneVer] = $phone_number;
            $this->sendChallenge(null, $email, null, $phone_number,
                "phone", null, null);
        } else {
            $_SESSION[$this->_formEmailVer] = $email;
            $this->sendChallenge(null, $email, null, $phone_number,
                "email", null, null);
        }
    }


    
    private function checkDuplicates($value,$key)
    {
        if($this->_restrictDuplicates && $this->isPhoneNumberAlreadyInUse($value,$key)) {
            $message = MoMessages::showMessage('PHONE_EXISTS');
            wp_send_json(MoUtility::_create_json_response($message,MoConstants::ERROR_JSON_TYPE));
        }
    }


    
    private function getUserData($key)
    {
        $current_user = wp_get_current_user();
        if($key===$this->_phoneKey) {
            global $wpdb;
            $q = "SELECT meta_value FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '$key' AND `user_id` = $current_user->ID";
            $results = $wpdb->get_row($q);
            return (isset($results)) ? $results->meta_value : '';
        }else {
            return $current_user->user_email;
        }

    }


    
    private function checkFormSession(UM\Core\Form $form)
    {
        if(MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION) === 'validated') {
            $this->unsetOTPSessionVariables();
        }else {
            $form->add_error($this->_emailKey,MoUtility::_get_invalid_otp_method());
            $form->add_error($this->_phoneKey,MoUtility::_get_invalid_otp_method());
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


    
    function isUltimateMemberV2Installed()
    {
        if( !function_exists('is_plugin_active') ) include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active( 'ultimate-member/ultimate-member.php' );
    }


    
    function isPhoneNumberAlreadyInUse($phone,$key)
    {
        global $wpdb;
        MoUtility::processPhoneNumber($phone);
        $q = "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '$key' AND `meta_value` =  '$phone'";
        $results = $wpdb->get_row($q);
        return !MoUtility::isBlank($results);
    }


    
    public function miniorange_register_um_script()
    {
        MoUtility::checkSession();
        wp_register_script( 'movumprofile', MOV_URL . 'includes/js/moumprofile.min.js',array('jquery') );
        wp_localize_script( 'movumprofile', 'moumacvar', array(
            'siteURL' 		=> site_url(),
            'otpType'       => $this->_otpType,
            'emailOtpType'  => $this->_typeEmailTag,
            'phoneOtpType'  => $this->_typePhoneTag,
            'bothOTPType'   => $this->_typeBothTag,
            'nonce'         => wp_create_nonce($this->_nonce),
            'buttonText'    => mo_($this->_buttonText),
            'imgURL'        => MOV_LOADER_URL,
            'formKey'       => $this->_verifyFieldKey,
            'emailValue'    => $this->getUserData($this->_emailKey),
            'phoneValue'    => $this->getUserData($this->_phoneKey),
            'phoneKey'      => $this->_phoneKey,
        ));
        wp_enqueue_script( 'movumprofile' );
    }


    
    private function userHasNotChangeData($type, $args)
    {
        $data = $this->getUserData($type);
        if($type===$this->_phoneKey && strcasecmp($data,$args[$type])==0){
            return TRUE;
        }else if($type===$this->_emailKey && strcasecmp($data,$args['user_email'])==0){
            return TRUE;
        }
        return FALSE;
    }


    
    public function miniorange_um_validation($args, $type='user_email')
    {
        MoUtility::checkSession();
        $mode = MoUtility::sanitizeCheck('mode',$args);

        if(!$this->userHasNotChangeData($type,$args) && $mode!= "register") {

            $form = $this->getUmFormObj();
            if ($this->isValidationRequired($type) && !MoUtility::sanitizeCheck($this->_formSessionVar, $_SESSION)) {
                $key = $this->isProfileVerificationEnabled() && $mode =="profile"  ? $this->_phoneKey : $this->_emailKey;
                $form->add_error($key, MoMessages::showMessage('PLEASE_VALIDATE'));
            } else {
                foreach ($args as $key => $value) {
                    if ($key === $this->_verifyFieldKey) {
                        $this->checkIntegrityAndValidateOTP($form, $value, $args);
                    } elseif ($key === $this->_phoneKey) {
                        $this->processPhoneNumbers($value, $form);
                    }
                }
            }
        }
    }


    
    private function isValidationRequired($type)
    {
        return $this->isAccountVerificationEnabled() && $type==='user_email'
            || $this->isProfileVerificationEnabled() && $type===$this->_phoneKey;
    }


    
    public function miniorange_um_profile_validation($form,$key,$args)
    {
        if($key===$this->_phoneKey) {
            $this->miniorange_um_validation($args, $this->_phoneKey);
        }
    }


    
    private function processPhoneNumbers($value,$form)
    {
        global $phoneLogic;
        if (!MoUtility::validatePhoneNumber($value)) {
            $message = str_replace("##phone##", $value, $phoneLogic->_get_otp_invalid_format_message());
            $form->add_error($this->_phoneKey, $message);
        }
        $this->checkDuplicates($value,$this->_phoneKey);
    }


    
    private function checkIntegrityAndValidateOTP($form,$value,array $args)
    {
        $this->checkIntegrity($form,$args);
        if($form->count_errors()>0) return;
        $this->validateChallenge(NULL,$value);
        $this->checkFormSession($form);
    }


    
    private function checkIntegrity($umForm,array $args)
    {
        if($this->isProfileVerificationEnabled()) {
            if(MoUtility::sanitizeCheck($this->_formPhoneVer,$_SESSION)
                !== MoUtility::sanitizeCheck($this->_phoneKey,$args)) {
                $umForm->add_error($this->_phoneKey, MoMessages::showMessage('PHONE_MISMATCH'));
            }
        }
        if($this->isAccountVerificationEnabled()) {
            if(MoUtility::sanitizeCheck($this->_formEmailVer,$_SESSION)
                !== MoUtility::sanitizeCheck($this->_emailKey,$args)) {
                $umForm->add_error($this->_emailKey, MoMessages::showMessage('EMAIL_MISMATCH'));
            }
        }
    }


    
    public function unsetOTPSessionVariables()
    {
        unset($_SESSION[$this->_txSessionId]);
        unset($_SESSION[$this->_formSessionVar]);
        unset($_SESSION[$this->_formEmailVer]);
        unset($_SESSION[$this->_formPhoneVer]);
    }


    
    public function handle_post_verification($redirect_to, $user_login, $user_email, $password, $phone_number,
                                             $extra_data)
    {
        MoUtility::checkSession();
        if(!isset($_SESSION[$this->_formSessionVar])) return;
        $_SESSION[$this->_formSessionVar] = 'validated';
    }


    
    public function handle_failed_verification($user_login, $user_email, $phone_number)
    {
        MoUtility::checkSession();
        if(!isset($_SESSION[$this->_formSessionVar])) return;
        $_SESSION[$this->_formSessionVar] = 'verification_failed';
    }


    
    public function getPhoneNumberSelector($selector)
    {
        MoUtility::checkSession();
        if($this->isFormEnabled() && $this->isProfileVerificationEnabled()) {
            array_push($selector, $this->_phoneFormId);
        }
        return $selector;
    }


    
    public function handleFormOptions()
    {
        if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

        $this->_isFormEnabled = $this->sanitizeFormPOST('um_profile_enable');
        $this->_otpType =  $this->sanitizeFormPOST('um_profile_enable_type');
        $this->_buttonText = $this->sanitizeFormPOST('um_profile_button_text');
        $this->_restrictDuplicates = $this->sanitizeFormPOST('um_profile_restrict_duplicates');
        $this->_phoneKey =  $this->sanitizeFormPOST('um_profile_phone_key');

        update_mo_option('um_profile_enable',$this->_isFormEnabled);
        update_mo_option('um_profile_enable_type',$this->_otpType);
        update_mo_option('um_profile_button_text',$this->_buttonText);
        update_mo_option('um_profile_restrict_duplicates',$this->_restrictDuplicates);
        update_mo_option('um_profile_phone_key',$this->_phoneKey);
    }
}
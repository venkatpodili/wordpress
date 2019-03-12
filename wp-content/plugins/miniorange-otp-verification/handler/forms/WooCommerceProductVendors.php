<?php


class WooCommerceProductVendors extends FormHandler implements IFormHandler
{
    use Instance;

    protected function __construct()
    {
        $this->_isLoginOrSocialForm = FALSE;
        $this->_formSessionVar = FormSessionVars::WC_PRODUCT_VENDOR;
        $this->_isAjaxForm = TRUE;
        $this->_typePhoneTag = 'mo_wc_pv_phone_enable';
        $this->_typeEmailTag = 'mo_wc_pv_email_enable';
        $this->_phoneFormId = '#reg_billing_phone';
        $this->_formPhoneVer = FormSessionVars::WC_VENDOR_PHONE_VER;
        $this->_formEmailVer = FormSessionVars::WC_VENDOR_EMAIL_VER;
        $this->_formKey = 'WC_PV_REG_FORM';
        $this->_formName = mo_("Woocommerce Product Vendor Registration Form");
        $this->_isFormEnabled = get_mo_option('wc_pv_default_enable');
        $this->_buttonText = get_mo_option("wc_pv_button_text");
        $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
        parent::__construct();
    }


    
    public function handleForm()
    {
        $this->_otpType = get_mo_option('wc_pv_enable_type');
        $this->_restrictDuplicates = get_mo_option('wc_pv_restrict_duplicates');
        add_action('wcpv_registration_form', array($this, 'mo_add_phone_field'), 1);
        add_action('wcpv_registration_form', array($this, 'mo_add_verification_field'), 1);
        add_action('wp_ajax_nopriv_miniorange_wc_vp_reg_verify',array($this, 'sendAjaxOTPRequest'));
        add_filter('wcpv_shortcode_registration_form_validation_errors', array($this, 'reg_fields_errors'), 1, 2 );
        add_action('wp_enqueue_scripts',array($this, 'miniorange_register_wc_script'));
    }

    
    public function sendAjaxOTPRequest()
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


    
    public function reg_fields_errors($errors, $form_items)
    {
        if(!empty($errors)) return $errors;
        MoUtility::checkSession();
        $this->assertOTPField($errors,$form_items);
        $this->checkIfOTPWasSent($errors);
        return $this->checkIntegrityAndValidateOTP($form_items,$errors);
    }


    
    private function assertOTPField(&$errors,$form_items)
    {
        if(!MoUtility::sanitizeCheck("moverify",$form_items)){
            $errors[] = MoMessages::showMessage('REQUIRED_OTP');
        }
    }


    
    private function checkIfOTPWasSent(&$errors)
    {
        if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)){
            $errors[] = MoMessages::showMessage('PLEASE_VALIDATE');
        }
    }


    
    private function checkIntegrityAndValidateOTP($data,array $errors)
    {
        if(!empty($errors)) return $errors;
        $data['billing_phone'] = MoUtility::processPhoneNumber($data['billing_phone']);
        $errors = $this->checkIntegrity($data,$errors);
        if(!empty($errors->errors)) return $errors;
        $this->validateChallenge(NULL,$data['moverify']);
        if(MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION) !== 'validated') {
            $errors[] = MoUtility::_get_invalid_otp_method();
        } else {
            $this->unsetOTPSessionVariables();
        }
        return $errors;
    }


    
    private function checkIntegrity($data,array $errors)
    {
        if(strcasecmp($this->_otpType,$this->_typePhoneTag)==0) {
            if($_SESSION[$this->_formPhoneVer]!== MoUtility::processPhoneNumber($data['billing_phone'])) {
                $errors[] = MoMessages::showMessage('PHONE_MISMATCH');
            }
        } else if(strcasecmp($this->_otpType,$this->_typeEmailTag)==0) {
            if($_SESSION[$this->_formEmailVer]!==$data['email']) {
                $errors[] = MoMessages::showMessage('EMAIL_MISMATCH');
            }
        }
        return $errors;
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
        return $errors;
    }


    
    function isPhoneNumberAlreadyInUse($phone,$key)
    {
        global $wpdb;
        $phone = MoUtility::processPhoneNumber($phone);
        $results = $wpdb->get_row("SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '$key' AND `meta_value` =  '$phone'");
        return !MoUtility::isBlank($results);
    }


    
    function miniorange_register_wc_script()
    {
        wp_register_script( 'mowcpvreg', MOV_URL . 'includes/js/wcpvreg.min.js',array('jquery') );
        wp_localize_script( 'mowcpvreg', 'mowcpvreg', array(
            'siteURL' 		=> wp_ajax_url(),
            'otpType'  		=> $this->_otpType,
            'nonce'         => wp_create_nonce($this->_nonce),
            'buttontext'    => mo_($this->_buttonText),
            'field'         => $this->_otpType === $this->_typePhoneTag ? "reg_vp_billing_phone" : "wcpv-email",
            'imgURL'        => MOV_LOADER_URL,
        ));
        wp_enqueue_script( 'mowcpvreg' );
    }


    
    public function mo_add_phone_field()
    {
        echo '<p class="form-row form-row-wide">
					<label for="reg_vp_billing_phone">
					    ' . mo_('Phone') . '
					    <span class="required">*</span>
                    </label>
					<input type="text" class="input-text" 
					        name="billing_phone" id="reg_vp_billing_phone" 
					        value="' . (!empty($_POST['billing_phone']) ? $_POST['billing_phone'] : "") . '" />
			  	  </p>';
    }


    
    function mo_add_verification_field()
    {
        echo '<p class="form-row form-row-wide">
					<label for="reg_vp_verification_phone">
					    '.mo_('Enter Code').'
					    <span class="required">*</span>
                    </label>
					<input type="text" class="input-text" name="moverify" 
					        id="reg_vp_verification_phone" 
					        value="" />
			  	  </p>';
    }


    
    public function unsetOTPSessionVariables()
    {
        unset($_SESSION[$this->_txSessionId]);
        unset($_SESSION[$this->_formSessionVar]);
        unset($_SESSION[$this->_formPhoneVer]);
        unset($_SESSION[$this->_formEmailVer]);
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
        $_SESSION[$this->_formSessionVar] = 'verification_failed';
    }


    
    public function getPhoneNumberSelector($selector)
    {
        MoUtility::checkSession();
        if($this->isFormEnabled()) array_push($selector, $this->_phoneFormId);
        return $selector;
    }


    public function handleFormOptions()
    {
        if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

        $this->_isFormEnabled = $this->sanitizeFormPOST('wc_pv_default_enable');
        $this->_otpType = $this->sanitizeFormPOST('wc_pv_enable_type');
        $this->_restrictDuplicates = $this->sanitizeFormPOST('wc_pv_restrict_duplicates');
        $this->_buttonText = $this->sanitizeFormPOST('wc_pv_button_text');

        update_mo_option('wc_pv_default_enable',$this->_isFormEnabled);
        update_mo_option('wc_pv_enable_type',$this->_otpType);
        update_mo_option('wc_pv_restrict_duplicates',$this->_restrictDuplicates);
        update_mo_option('wc_pv_button_text',$this->_buttonText);
    }
}
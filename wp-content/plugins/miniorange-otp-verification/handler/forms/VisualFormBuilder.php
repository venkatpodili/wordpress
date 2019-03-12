<?php


class VisualFormBuilder extends FormHandler implements IFormHandler
{
    use Instance;

    protected function __construct()
    {
        $this->_isLoginOrSocialForm = FALSE;
        $this->_isAjaxForm = TRUE;
        $this->_formSessionVar = FormSessionVars::VISUAL_FORM;
        $this->_formEmailVer = FormSessionVars::VISUAL_FORM_EMAIL_VER;
        $this->_formPhoneVer = FormSessionVars::VISUAL_FORM_PHONE_VER;
        $this->_typePhoneTag = 'mo_visual_form_phone_enable';
        $this->_typeEmailTag = 'mo_visual_form_email_enable';
        $this->_typeBothTag = 'mo_visual_form_both_enable';
        $this->_formKey = 'VISUAL_FORM';
        $this->_formName = mo_('Visual Form Builder');
        $this->_phoneFormId = [];
        $this->_isFormEnabled = get_mo_option('visual_form_enable');
        $this->_buttonText = get_mo_option("visual_form_button_text");
        $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
        $this->_generateOTPAction = "miniorange-vf-send-otp";
        $this->_validateOTPAction = "miniorange-vf-verify-code";

        parent::__construct();
    }

    
    function handleForm()
    {
        $this->_otpType = get_mo_option('visual_form_enable_type');
        $this->_formDetails = maybe_unserialize(get_mo_option('visual_form_otp_enabled'));
        if(empty($this->_formDetails) || !$this->_isFormEnabled) return;
        foreach($this->_formDetails as $key => $value) {
            array_push($this->_phoneFormId, '#' . $value['phonekey']);
        }
        add_action('wp_enqueue_scripts', array($this, 'mo_enqueue_vf'));
        add_action("wp_ajax_{$this->_generateOTPAction}", [$this,'_send_otp_vf_ajax']);
        add_action("wp_ajax_nopriv_{$this->_generateOTPAction}", [$this,'_send_otp_vf_ajax']);
        add_action("wp_ajax_{$this->_validateOTPAction}", [$this,'processFormAndValidateOTP']);
        add_action("wp_ajax_nopriv_{$this->_validateOTPAction}", [$this,'processFormAndValidateOTP']);
    }

    
    function mo_enqueue_vf()
    {
        wp_register_script( 'vfscript', MOV_URL . 'includes/js/vfscript.min.js',array('jquery') );
        wp_localize_script( 'vfscript', 'movfvar', array(
            'siteURL' 		=> 	wp_ajax_url(),
            'otpType'       =>  strcasecmp($this->_otpType,$this->_typePhoneTag),
            'formDetails'   =>  $this->_formDetails,
            'buttontext'    =>  $this->_buttonText,
            'imgURL'        =>  MOV_LOADER_URL,
            'fieldText'     =>  mo_('Enter OTP here'),
            'gnonce'        =>  wp_create_nonce($this->_nonce),
            'nonceKey'      =>  wp_create_nonce($this->_nonceKey),
            'vnonce'        =>  wp_create_nonce($this->_nonce),
            'gaction'       =>  $this->_generateOTPAction,
            'vaction'       =>  $this->_validateOTPAction
        ) );
        wp_enqueue_script( 'vfscript' );
    }

    
    function _send_otp_vf_ajax()
    {
        MoUtility::checkSession();
        $this->validateAjaxRequest();
        if ( $this->_otpType == $this->_typePhoneTag)
            $this->_send_vf_otp_to_phone($_POST);
        else
            $this->_send_vf_otp_to_email($_POST);
    }

    
    function _send_vf_otp_to_phone($data)
    {
        if(!MoUtility::sanitizeCheck('user_phone',$data)) {
            wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('ENTER_PHONE'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        } else {
            $this->startOTPVerification(trim($data['user_phone']), NULL, trim($data['user_phone']), "phone");
        }
    }

    
    function _send_vf_otp_to_email($data)
    {
        if(!MoUtility::sanitizeCheck('user_email',$data)) {
            wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('ENTER_EMAIL'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        } else {
            $this->startOTPVerification($data['user_email'], $data['user_email'], NULL, "email");
        }
    }

    
    private function startOTPVerification($sessionValue, $userEmail, $phoneNumber, $otpType)
    {
        
        $verificationSessionVar = function () {
            return ($this->_otpType === $this->_typeEmailTag) ? $this->_formEmailVer : $this->_formPhoneVer;
        };

        MoUtility::initialize_transaction($this->_formSessionVar);
        $_SESSION[ $verificationSessionVar() ] = $sessionValue;
        $this->sendChallenge('testUser',$userEmail,NULL,$phoneNumber,$otpType);
    }

    
    function processFormAndValidateOTP()
    {
        MoUtility::checkSession();
        $this->validateAjaxRequest();
        $this->checkIfVerificationNotStarted();
        $this->checkIntegrityAndValidateOTP($_POST);
    }

    
    function checkIfVerificationNotStarted()
    {
        if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)){
            wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('ENTER_VERIFY_CODE'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        }
    }

    

    private function checkIntegrityAndValidateOTP($post)
    {
        MoUtility::checkSession();
        $this->checkIntegrity($post);
        $this->validateChallenge(NULL,$post['otp_token']);
    }

    
    private function checkIntegrity($post)
    {
        if($this->isPhoneVerificationEnabled()) {
            if($_SESSION[$this->_formPhoneVer]!==$post['sub_field']) {
                                wp_send_json(
                    MoUtility::_create_json_response(
                        MoMessages::showMessage('PHONE_MISMATCH'),
                        MoConstants::ERROR_JSON_TYPE
                    )
                );
            }
        } else if($_SESSION[$this->_formEmailVer]!==$post['sub_field']) {
                        wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('EMAIL_MISMATCH'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        }
    }

    
    function handle_failed_verification($user_login,$user_email,$phone_number)
    {
        MoUtility::checkSession();
        if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)) return;
        wp_send_json(
            MoUtility::_create_json_response(
                MoUtility::_get_invalid_otp_method(),
                MoConstants::ERROR_JSON_TYPE
            )
        );
    }

    
    function handle_post_verification($redirect_to,$user_login,$user_email,$password,$phone_number,$extra_data)
    {
        MoUtility::checkSession();
        if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)) return;
        $this->unsetOTPSessionVariables();
        wp_send_json(
            MoUtility::_create_json_response(
                $_SESSION[$this->_formSessionVar],
                MoConstants::SUCCESS_JSON_TYPE
            )
        );
    }

    
    function unsetOTPSessionVariables()
    {
        unset($_SESSION[$this->_txSessionId]);
        unset($_SESSION[$this->_formSessionVar]);
        unset($_SESSION[$this->_formEmailVer]);
        unset($_SESSION[$this->_formPhoneVer]);
    }


    
    public function getPhoneNumberSelector($selector)
    {
        if($this->_isFormEnabled && $this->isPhoneVerificationEnabled()) {
            $selector = array_merge($selector, $this->_phoneFormId);
        }
        return $selector;
    }

    
    function isPhoneVerificationEnabled()
    {
        return (strcasecmp($this->_otpType,$this->_typePhoneTag)===0
            || strcasecmp($this->_otpType,$this->_typeBothTag)===0);
    }


    
    function handleFormOptions()
    {
        if(!MoUtility::areFormOptionsBeingSaved($this->getFormOption())) return;

        $form = $this->parseFormDetails();

        $this->_isFormEnabled = $this->sanitizeFormPOST('visual_form_enable');
        $this->_otpType = $this->sanitizeFormPOST('visual_form_enable_type');
        $this->_formDetails = !empty($form) ? maybe_serialize($form) : "";
        $this->_buttonText = $this->sanitizeFormPOST('visual_form_button_text');

        if($this->basicValidationCheck(BaseMessage::VISUAL_FORM_CHOOSE)) {
            update_mo_option('visual_form_button_text', $this->_buttonText);
            update_mo_option('visual_form_enable', $this->_isFormEnabled);
            update_mo_option('visual_form_enable_type', $this->_otpType);
            update_mo_option('visual_form_otp_enabled', $this->_formDetails);
        }
    }



    
    function parseFormDetails()
    {
        $form = array();
        if(!array_key_exists('visual_form',$_POST)) return array();

        foreach (array_filter($_POST['visual_form']['form']) as $key => $value)
        {
            $form[$value]= array(
                'emailkey'=> $this->getFieldID($_POST['visual_form']['emailkey'][$key],$value),
                'phonekey'=> $this->getFieldID($_POST['visual_form']['phonekey'][$key],$value),
                'phone_show'=>$_POST['visual_form']['phonekey'][$key],
                'email_show'=>$_POST['visual_form']['emailkey'][$key],
            );
        }
        return $form;
    }

    
    private function getFieldID($key, $formId)
    {
        global $wpdb;
        $query = "SELECT * FROM ".VFB_WP_FIELDS_TABLE_NAME." where field_name ='".$key."'and form_id = '".$formId."'";
        $result = $wpdb->get_row($query);
        return !MoUtility::isBlank($result) ? 'vfb-'.$result->field_id : '';
    }

}
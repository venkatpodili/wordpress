<?php


class FormidableForm extends FormHandler implements IFormHandler
{
    use Instance;

    protected function __construct()
    {
        $this->_isLoginOrSocialForm = FALSE;
        $this->_isAjaxForm = TRUE;
        $this->_formSessionVar = FormSessionVars::FORMIDABLE_FORM;
        $this->_formEmailVer = FormSessionVars::FORMIDABLE_FORM_EMAIL_VER;
        $this->_formPhoneVer = FormSessionVars::FORMIDABLE_FORM_PHONE_VER;
        $this->_typePhoneTag = 'mo_frm_form_phone_enable';
        $this->_typeEmailTag = 'mo_frm_form_email_enable';
        $this->_typeBothTag = 'mo_frm_form_both_enable';
        $this->_formKey = 'FORMIDABLE_FORM';
        $this->_formName = mo_('Formidable Forms');
        $this->_isFormEnabled = get_mo_option('frm_form_enable');
        $this->_buttonText = get_mo_option("frm_button_text");
        $this->_buttonText = !MoUtility::isBlank($this->_buttonText) ? $this->_buttonText : mo_("Click Here to send OTP");
        $this->_generateOTPAction = 'miniorange_frm_generate_otp';

        parent::__construct();
    }

    
    function handleForm()
    {
        $this->_otpType = get_mo_option('frm_form_enable_type');
        $this->_formDetails = maybe_unserialize(get_mo_option('frm_form_otp_enabled'));
        $this->_phoneFormId = array();
        if(empty($this->_formDetails) || !$this->_isFormEnabled) return;
        foreach($this->_formDetails as $key => $value) {
            array_push($this->_phoneFormId, '#' . $value['phonekey'] . ' input');
        }
        add_action('frm_entry_form', [$this,'mo_add_form_specific_scripts']);
        add_filter('frm_validate_field_entry', [$this,'miniorange_otp_validation'], 11, 4 );
        add_action("wp_ajax_{$this->_generateOTPAction}", [$this,'_send_otp_frm_ajax']);
        add_action("wp_ajax_nopriv_{$this->_generateOTPAction}", [$this,'_send_otp_frm_ajax']);
    }

    
    function mo_add_form_specific_scripts($form)
    {
        foreach($this->_formDetails as $formId => $fields)
        {
            if ("{$formId}" == $form->id) {
                echo $this->_addScripts($formId, $fields);
            }
        }
    }

    
    function _addScripts($formid, $fields)
    {
        $fieldID = strcasecmp($this->_otpType,$this->_typePhoneTag)==0 ? $fields['phonekey'] : $fields['emailkey'];

        $img = "<img src='".MOV_LOADER_URL."'>";

                $html="var messageBox = '<div  class=\"frm_top_container frm_full\" hidden '+
                                       'id=\"mo_message{$fieldID}\" '+
                                       'style=\"width:100%; background-color: #f7f6f7;padding: 1em 2em 1em 3.5em; '+
                                       'text-align: center;margin-top:3px;\">'+
                                '</div>';";

                $html.="var button = '<div class=\"frm_submit\" >'+
                                '<input type= \"button\" '+
                                        'id=\"mobutton$fieldID\" '+
                                        'class=\"frm_button_submit\"  '+
                                        'value= \"$this->_buttonText\">'+
                                messageBox+
                             '</div>';";

                $html .= '$mo(button).insertAfter("#'.$fieldID.'");';
        $html .= '$mo("#mobutton'.$fieldID.'").click( function(){
                        var e = $mo("#'.$fieldID.' input").val();
                        $mo("#mo_message'.$fieldID.'").empty(),
                        $mo("#mo_message'.$fieldID.'").append("'.$img.'"),
                        $mo("#mo_message'.$fieldID.'").show();'.
                                                '$mo.ajax( {
                            url: "'.wp_ajax_url().'",
                            type:"POST",
                            data:{
                                user_email:e,
                                user_phone:e,
                                action:"'.$this->_generateOTPAction.'",
                                '.$this->_nonceKey.':"'.wp_create_nonce($this->_nonce).'"
                            },
                            crossDomain:!0,
                            dataType:"json",
                            success:function(o){
                                if(o.result=="success"){
                                    $mo("#mo_message'.$fieldID.'").empty(),
                                    $mo("#mo_message'.$fieldID.'").append(o.message),
                                    $mo("#mo_message'.$fieldID.'").css("border-top","3px solid green")
                                }else{
                                    $mo("#mo_message'.$fieldID.'").empty(),
                                    $mo("#mo_message'.$fieldID.'").append(o.message),
                                    $mo("#mo_message'.$fieldID.'").css("border-top","3px solid red");
                                }
                            },
                            error:function(o){}
                        });
                   });';

        $html = '<script>jQuery(document).ready(function(){ $mo=jQuery;'.$html.'});</script>';

        return $html;
    }

    
    function _send_otp_frm_ajax()
    {
        MoUtility::checkSession();
        $this->validateAjaxRequest();
        if ( $this->_otpType == $this->_typePhoneTag)
            $this->_send_frm_otp_to_phone($_POST);
        else
            $this->_send_frm_otp_to_email($_POST);
    }

    
    function _send_frm_otp_to_phone($data)
    {
        if(!MoUtility::sanitizeCheck('user_phone',$data)) {
            wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('ENTER_PHONE'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        } else {
            $this->sendOTP(trim($data['user_phone']), NULL, trim($data['user_phone']), "phone");
        }
    }

    
    function _send_frm_otp_to_email($data)
    {
        if(!MoUtility::sanitizeCheck('user_email',$data)) {
            wp_send_json(
                MoUtility::_create_json_response(
                    MoMessages::showMessage('ENTER_EMAIL'),
                    MoConstants::ERROR_JSON_TYPE
                )
            );
        } else {
            $this->sendOTP($data['user_email'], $data['user_email'], NULL, "email");
        }
    }

    
    private function sendOTP($sessionValue, $userEmail, $phoneNumber, $otpType)
    {
        MoUtility::initialize_transaction($this->_formSessionVar);
        $_SESSION[ $this->verificationSessionVar() ] = $sessionValue;
        $this->sendChallenge('testUser',$userEmail,NULL,$phoneNumber,$otpType);
    }


    
    function miniorange_otp_validation( $errors, $field, $value, $args )
    {
        MoUtility::checkSession();

                if( $this->getFieldId('verify_show',$field) !== $field->id) return $errors;
                if(!MoUtility::isBlank($errors)) return $errors;
        if(!$this->hasOTPBeenSent($errors,$field)) return $errors;
        if($this->isMisMatchEmailOrPhone($errors,$field)) return $errors;
        if(!$this->isValidOTP($value,$field,$errors)) return $errors;
        return $errors;
    }


    
    private function hasOTPBeenSent(&$errors,$field)
    {
        if(!MoUtility::sanitizeCheck($this->_formSessionVar,$_SESSION)) {
            $message = MoMessages::showMessage(BaseMessage::ENTER_VERIFY_CODE);
            if( $this->isPhoneVerificationEnabled() )
                $errors['field'.$this->getFieldId('phone_show',$field)] = $message;
            else
                $errors['field'.$this->getFieldId('email_show',$field)] = $message;
            return false;
        }
        return true;
    }


    
    private function isMisMatchEmailOrPhone(&$errors,$field)
    {
        $fieldId = $this->getFieldId(($this->isPhoneVerificationEnabled() ? 'phone_show' : 'email_show'),$field);
        $fieldValue = $_POST['item_meta'][$fieldId];
        if (  $_SESSION[$this->verificationSessionVar()] !== $fieldValue ) {
            if( $this->isPhoneVerificationEnabled() )
                $errors['field'.$this->getFieldId('phone_show',$field)]
                    = MoMessages::showMessage(BaseMessage::PHONE_MISMATCH);
            else
                $errors['field'.$this->getFieldId('email_show',$field)]
                    = MoMessages::showMessage(BaseMessage::EMAIL_MISMATCH);
            return true;
        }
        return false;
    }

    
    private function isValidOTP($value,$field,&$errors)
    {
        $this->validateChallenge(NULL,$value);
        if(strcasecmp($_SESSION[$this->_formSessionVar],'validated')==0) {
                        $this->unsetOTPSessionVariables();
            return true;
        }else if(strcasecmp($_SESSION[$this->_formSessionVar],'verification_failed')==0) {
                        $errors['field'.$this->getFieldId('verify_show',$field)] = MoUtility::_get_invalid_otp_method();
            return false;
        }
        return false;
    }


    
    private function verificationSessionVar()
    {
        return $this->isPhoneVerificationEnabled() ? $this->_formPhoneVer : $this->_formEmailVer;
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

    
    function unsetOTPSessionVariables()
    {
        unset($_SESSION[$this->_txSessionId]);
        unset($_SESSION[$this->_formSessionVar]);
        unset($_SESSION[$this->_formEmailVer]);
        unset($_SESSION[$this->_formPhoneVer]);
    }


    
    public function getPhoneNumberSelector($selector)
    {
        MoUtility::checkSession();
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

        $this->_isFormEnabled = $this->sanitizeFormPOST('frm_form_enable');
        $this->_otpType = $this->sanitizeFormPOST('frm_form_enable_type');
        $this->_formDetails = !empty($form) ? maybe_serialize($form) : "";
        $this->_buttonText = $this->sanitizeFormPOST('frm_button_text');

        if($this->basicValidationCheck(BaseMessage::FORMIDABLE_CHOOSE)) {
            update_mo_option('frm_button_text', $this->_buttonText);
            update_mo_option('frm_form_enable', $this->_isFormEnabled);
            update_mo_option('frm_form_enable_type', $this->_otpType);
            update_mo_option('frm_form_otp_enabled', $this->_formDetails);
        }
    }

    
    function parseFormDetails()
    {
        $form = array();
        if(!array_key_exists('frm_form',$_POST)) return array();

        foreach (array_filter($_POST['frm_form']['form']) as $key => $value)
        {
            $form[$value]= array(
                'emailkey'=> 'frm_field_'.$_POST['frm_form']['emailkey'][$key].'_container',
                'phonekey'=> 'frm_field_'. $_POST['frm_form']['phonekey'][$key].'_container',
                'verifyKey'=> 'frm_field_'. $_POST['frm_form']['verifyKey'][$key].'_container',
                'phone_show'=> $_POST['frm_form']['phonekey'][$key],
                'email_show'=> $_POST['frm_form']['emailkey'][$key],
                'verify_show'=> $_POST['frm_form']['verifyKey'][$key],
            );
        }
        return $form;
    }

    
    function getFieldId($key,$field) { return $this->_formDetails[$field->form_id][$key]; }
}
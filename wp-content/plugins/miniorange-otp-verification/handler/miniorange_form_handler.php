<?php

class FormActionHandler extends BaseActionHandler
{
    use Instance;

    function __construct()
    {
        $this->_nonce = 'mo_form_actions';
        add_action('init', array($this, 'handleFormActions'), 1);
        add_action('mo_validate_otp', array($this,'validateOTP'), 1, 2);
        add_action('mo_generate_otp', array($this,'challenge'), 2, 8);
        add_filter('mo_filter_phone_before_api_call', array($this, 'filterPhone'), 1, 1);
    }

    
    public function challenge($user_login, $user_email, $errors, $phone_number = null,
                              $otp_type="email", $password = "", $extra_data = null, $from_both = false)
    {
        MoUtility::checkSession();
        $phone_number = MoUtility::processPhoneNumber($phone_number);
        $_SESSION['current_url'] = MoUtility::currentPageUrl();
        $_SESSION['user_email'] = $user_email;
        $_SESSION['user_login'] = $user_login;
        $_SESSION['user_password'] = $password;
        $_SESSION['phone_number_mo'] = $phone_number;
        $_SESSION['extra_data'] = $extra_data;
        $this->handleOTPAction($user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data);
    }


    
    private function handleResendOTP($otp_type, $from_both)
    {
        MoUtility::checkSession();
        $user_email = $_SESSION['user_email'];
        $user_login = $_SESSION['user_login'];
        $password = $_SESSION['user_password'];
        $phone_number = $_SESSION['phone_number_mo'];
        $extra_data = $_SESSION['extra_data'];
        $this->handleOTPAction($user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data);
    }


    
    function handleOTPAction($user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data)
    {
        
        
        global $phoneLogic, $emailLogic;
        switch ($otp_type)
        {
            case 'phone':
                $phoneLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both);     break;
            case 'email':
                $emailLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both);     break;
            case 'both':
                miniorange_verification_user_choice($user_login, $user_email, $phone_number,
                    MoMessages::showMessage('CHOOSE_METHOD'), $otp_type);                           break;
            case 'external':
                mo_external_phone_validation_form($extra_data['curl'], $user_email,
                    $extra_data['message'], $extra_data['form'], $extra_data['data']);                          break;
        }
    }


    
    function handleGoBackAction()
    {
        MoUtility::checkSession();
        $url = isset($_SESSION['current_url']) ? $_SESSION['current_url'] : '';
        do_action('unset_session_variable');
        header("location:" . $url);
    }


    
    function validateOTP($requestVariable = 'mo_customer_validation_otp_token', $otp_token = NULL)
    {
        MoUtility::checkSession();
        $user_login = MoUtility::sanitizeCheck('user_login',$_SESSION);
        $user_email = MoUtility::sanitizeCheck('user_email',$_SESSION);
        $phone_number = MoUtility::sanitizeCheck('phone_number_mo',$_SESSION);
        $password = MoUtility::sanitizeCheck('user_password',$_SESSION);
        $extra_data = MoUtility::sanitizeCheck('extra_data',$_SESSION);
        $txID = MoUtility::sanitizeCheck(FormSessionVars::TX_SESSION_ID,$_SESSION);
        $otp_token = !is_null($requestVariable) && array_key_exists($requestVariable, $_REQUEST)
                     && !MoUtility::isBlank($_REQUEST[$requestVariable]) ? $_REQUEST[$requestVariable] : $otp_token;

        if (!is_null($txID)) {
            $content = MO_TEST_MODE
                        ? ( MO_FAIL_MODE ? ['status' => ''] : ['status' => 'SUCCESS'] )
                        : json_decode(MocURLOTP::validate_otp_token($txID, $otp_token), true);
            switch ($content['status'])
            {
                case 'SUCCESS':
                    $this->onValidationSuccess($user_login, $user_email, $password, $phone_number, $extra_data); break;
                default:
                    $this->onValidationFailed($user_login, $user_email, $phone_number); break;
            }
        }
    }


    
    private function onValidationSuccess($user_login, $user_email, $password, $phone_number, $extra_data)
    {
        $redirect_to = array_key_exists('redirect_to', $_POST) ? $_POST['redirect_to'] : '';
        do_action('otp_verification_successful', $redirect_to, $user_login, $user_email, $password, $phone_number, $extra_data);
    }


    
    private function onValidationFailed($user_login, $user_email, $phone_number)
    {
        do_action('otp_verification_failed', $user_login, $user_email, $phone_number);
    }


    
    private function handleOTPChoice($postdata)
    {
        MoUtility::checkSession();
        if (strcasecmp($postdata['mo_customer_validation_otp_choice'], 'user_email_verification') == 0)
            $this->challenge($_SESSION['user_login'], $_SESSION['user_email'], null, $_SESSION['phone_number_mo'],
                "email", $_SESSION['user_password'], $_SESSION['extra_data'], true);
        else
            $this->challenge($_SESSION['user_login'], $_SESSION['user_email'], null, $_SESSION['phone_number_mo'],
                "phone", $_SESSION['user_password'], $_SESSION['extra_data'], true);
    }


    
    function filterPhone($phone)
    {
        return str_replace("+", "", $phone);
    }


    
    function handleFormActions()
    {
        if (array_key_exists('option', $_REQUEST) && MoUtility::micr()) {

            $from_both = MoUtility::sanitizeCheck('from_both',$_POST);

            switch (trim($_REQUEST['option'])) {
                case "validation_goBack":
                    $this->handleGoBackAction();                             break;
                case "miniorange-validate-otp-form":
                    $this->validateOTP();                                    break;
                case "verification_resend_otp_phone":
                    $this->handleResendOTP("phone", $from_both);    break;
                case "verification_resend_otp_email":
                    $this->handleResendOTP("email", $from_both);    break;
                case "verification_resend_otp_both":
                    $this->handleResendOTP("both", $from_both);     break;
                case "miniorange-validate-otp-choice-form":
                    $this->handleOTPChoice($_POST);                          break;
            }
        }
    }
}
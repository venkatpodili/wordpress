<?php


class MoOTPActionHandlerHandler extends BaseActionHandler
{
    use Instance;
	function __construct()
	{
		$this->_nonce = 'mo_admin_actions';
		add_action( 'admin_init', array( $this,'_handle_admin_actions' ),1);
		add_action( 'admin_init', array( $this,'moScheduleTransactionSync'),1);
        add_action( 'admin_init', array( $this,'checkIfPopupTemplateAreSet'),1);
		add_filter( 'dashboard_glance_items', array( $this,'otp_transactions_glance_counter'),10,1);
		add_action( 'admin_post_miniorange_get_form_details', array($this,'showFormHTMLData'));
	}


	
	function _handle_admin_actions()
	{
		if(!isset($_POST['option'])) return;
		switch($_POST['option'])
		{
			case "mo_customer_validation_settings":
				$this->_save_settings($_POST);																	 break;
			case "mo_customer_validation_messages":
				$this->_handle_custom_messages_form_submit($_POST);												 break;
			case "mo_validation_contact_us_query_option":
				$this->_mo_validation_support_query($_POST['query_email'],$_POST['query'],$_POST['query_phone']);break;
			case "mo_otp_extra_settings":
				$this->_save_extra_settings($_POST); 															 break;
			case "mo_otp_feedback_option":
			    $this->_mo_validation_feedback_query();	                                                         break;
            case "check_mo_ln":
                $this->_mo_check_l();											                                 break;
            case "mo_customer_validation_sms_configuration":
                $this->_mo_configure_sms_template($_POST);														 break;
            case "mo_customer_validation_email_configuration":
                $this->_mo_configure_email_template($_POST);													 break;
		}
	}


	
	function _handle_custom_messages_form_submit($post)
	{
		$this->isValidRequest();
		update_mo_option('success_email_message' ,MoUtility::sanitizeCheck('otp_success_email',$post)   ,"mo_otp_");
		update_mo_option('success_phone_message' ,MoUtility::sanitizeCheck('otp_success_phone',$post)   ,"mo_otp_");
		update_mo_option('error_phone_message'   ,MoUtility::sanitizeCheck('otp_error_phone',$post)     ,"mo_otp_");
		update_mo_option('error_email_message'   ,MoUtility::sanitizeCheck('otp_error_email',$post)     ,"mo_otp_");
		update_mo_option('invalid_phone_message' ,MoUtility::sanitizeCheck('otp_invalid_phone',$post)   ,"mo_otp_");
        update_mo_option('invalid_email_message' ,MoUtility::sanitizeCheck('otp_invalid_email',$post)   ,"mo_otp_");
		update_mo_option('invalid_message'       ,MoUtility::sanitizeCheck('invalid_otp',$post)         ,"mo_otp_");
		update_mo_option('blocked_email_message' ,MoUtility::sanitizeCheck('otp_blocked_email',$post)   ,"mo_otp_");
		update_mo_option('blocked_phone_message' ,MoUtility::sanitizeCheck('otp_blocked_phone',$post)   ,"mo_otp_");

		do_action('mo_registration_show_message', MoMessages::showMessage('MSG_TEMPLATE_SAVED'),'SUCCESS');
	}


	
	function _save_settings($posted)
	{
        $this->isValidRequest();
        if (MoUtility::sanitizeCheck("page",$_GET) !== "mosettings"
            && $posted['error_message']) {
            do_action(
                'mo_registration_show_message',
                MoMessages::showMessage($posted['error_message']),
                'ERROR'
            );
        }
	}


	
	function _save_extra_settings($posted)
	{
		$this->isValidRequest();
		delete_site_option('default_country_code');
		update_mo_option('default_country',isset($posted['default_country_code']) ? maybe_serialize(CountryList::$countries[$posted['default_country_code']]) : '');
		update_mo_option('blocked_domains', isset($posted['mo_otp_blocked_email_domains']) ? $posted['mo_otp_blocked_email_domains'] : '');
		update_mo_option('blocked_phone_numbers',isset($posted['mo_otp_blocked_phone_numbers']) ? $posted['mo_otp_blocked_phone_numbers'] : '');
		update_mo_option('show_remaining_trans',isset($posted['mo_show_remaining_trans']) ? $posted['mo_show_remaining_trans'] : '');
		update_mo_option('show_dropdown_on_form',isset($posted['show_dropdown_on_form']) ? $posted['show_dropdown_on_form'] : '');
		update_mo_option('otp_length', isset($posted['mo_otp_length']) ? $posted['mo_otp_length'] : '');
		update_mo_option('otp_validity', isset($posted['mo_otp_validity']) ? $posted['mo_otp_validity'] : '');
		do_action('mo_registration_show_message', MoMessages::showMessage('EXTRA_SETTINGS_SAVED'),'SUCCESS');
	}


	
	function _mo_validation_support_query($email,$query,$phone)
	{
		if( empty($email) || empty($query) )
		{
			do_action('mo_registration_show_message', MoMessages::showMessage('SUPPORT_FORM_VALUES'),'ERROR');
			return;
		}

		$query 	  = sanitize_text_field( $query );
		$email 	  = sanitize_text_field( $email );
		$phone 	  = sanitize_text_field( $phone );
		$submited = MocURLOTP::submit_contact_us( $email, $phone, $query );

		if(json_last_error() == JSON_ERROR_NONE && $submited)
		{
			do_action('mo_registration_show_message',MoMessages::showMessage('SUPPORT_FORM_SENT'),'SUCCESS');
			return;
		}

		do_action('mo_registration_show_message',MoMessages::showMessage('SUPPORT_FORM_ERROR'),'ERROR');
	}


	
	public function otp_transactions_glance_counter()
	{
		if(!MoUtility::micr() || MoUtility::mclv()) return;
		$email = get_mo_option('email_transactions_remaining');
		$phone = get_mo_option('phone_transactions_remaining');
		echo "<li class='mo-trans-count'><a href='" . admin_url() . "admin.php?page=mosettings'>"
				. MoMessages::showMessage('TRANS_LEFT_MSG',array('email'=>$email,'phone'=>$phone)). "</a></li>";
	}


	
	public function checkIfPopupTemplateAreSet()
	{
		$email_templates = maybe_unserialize(get_mo_option('custom_popups'));
		if(empty($email_templates)) {
			$templates = apply_filters( 'mo_template_defaults', array() );
			update_mo_option('custom_popups',maybe_serialize($templates));
		}
	}


	
	public function showFormHTMLData()
	{
		$this->isValidRequest();
		$formName = $_POST['form_name'];
		$controller = MOV_DIR . 'controllers/';
		$disabled = !MoUtility::micr() ? "disabled" : "";
		$page_list = admin_url().'edit.php?post_type=page';
		ob_start();
		include $controller . 'forms/'.$formName . '.php';
		$string = ob_get_clean();
		wp_send_json( MoUtility::_create_json_response($string,MoConstants::SUCCESS_JSON_TYPE));
	}


	
	function moScheduleTransactionSync()
	{
		if (! wp_next_scheduled('hourlySync') && MoUtility::micr()) wp_schedule_event(time(), 'daily', 'hourlySync');
	}


    
	function _mo_validation_feedback_query()
    {
        $this->isValidRequest();
        $submitType = $_POST['miniorange_feedback_submit'];

        if($submitType==="Skip & Deactivate"){
            deactivate_plugins( [MOV_PLUGIN_NAME]);
            return;
        }

        $deactivatingPlugin = strcasecmp($_POST['plugin_deactivated'],"true")==0;
        $type =  !$deactivatingPlugin ? mo_("[ Plugin Feedback ] : ") : mo_("[ Plugin Deactivated ]");
        $summary = $_POST['feedback_reason'];
        $feedback = sanitize_text_field($_POST['query_feedback']);
        $feedbackTemplate = file_get_contents(MOV_DIR . 'includes/html/feedback.min.html');
        $current_user = wp_get_current_user();
        $email = get_mo_option("admin_email");

        $feedbackTemplate = str_replace("{{FIRST_NAME}}",$current_user->first_name,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{LAST_NAME}}",$current_user->last_name,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{SERVER}}",$_SERVER['SERVER_NAME'],$feedbackTemplate);
        $feedbackTemplate = str_replace("{{EMAIL}}",$email,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{PLUGIN}}",MoConstants::AREA_OF_INTEREST,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{VERSION}}",MOV_VERSION,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{SUMMARY}}",$summary,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{TYPE}}",$type,$feedbackTemplate);
        $feedbackTemplate = str_replace("{{FEEDBACK}}",$feedback,$feedbackTemplate);

        
        $notif = MoUtility::send_email_notif(
            $email,
            "miniOrange",
            MoConstants::FEEDBACK_EMAIL,
            "WordPress OTP Verification Plugin Feedback",
            $feedbackTemplate
        );

        if($notif) {
            do_action('mo_registration_show_message',MoMessages::showMessage('FEEDBACK_SENT'),'SUCCESS');
        } else {
            do_action('mo_registration_show_message',MoMessages::showMessage('FEEDBACK_ERROR'),'ERROR');
        }

        
        if($deactivatingPlugin) deactivate_plugins( [MOV_PLUGIN_NAME]);
    }


    
    function _mo_check_l()
    {
        $this->isValidRequest();
        MoUtility::_handle_mo_check_ln(true,
            get_mo_option('admin_customer_key'),
            get_mo_option('admin_api_key')
        );
    }
}
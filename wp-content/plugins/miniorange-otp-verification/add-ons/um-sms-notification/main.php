<?php

if(! defined( 'ABSPATH' )) exit;

final class UltimateMemberSmsNotification extends BaseAddon
{
    use Instance;

    public function __construct()
	{
	    parent::__construct();
		add_action( 'admin_enqueue_scripts'					    , array( $this, 'um_sms_notif_settings_style'   ) );
        add_action( 'mo_otp_verification_delete_addon_options'	, array( $this, 'um_sms_notif_delete_options' 	) );
	}

	
	function um_sms_notif_settings_style()
	{
		wp_enqueue_style( 'um_sms_notif_admin_settings_style', UMSN_CSS_URL);
	}

    
    function initializeHandlers()
    {
        
        $list = AddOnList::instance();
        
        $handler = UltimateMemberSMSNotificationsHandler::instance();
        $list->add($handler->getAddOnKey(),$handler);
    }

    
    function initializeHelpers()
    {
	    UltimateMemberSMSNotificationMessages::instance();
	    UltimateMemberNotificationsList::instance();
    }


    
    function show_addon_settings_page()
    {
        include UMSN_DIR . '/controllers/main-controller.php';
    }

	
	function um_sms_notif_delete_options()
    {
        delete_site_option('mo_um_sms_notification_settings');
    }
}
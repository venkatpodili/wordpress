<?php


final class MenuItems
{
    use Instance;

    
    private $_callback;

    
    private $_menuSlug;

    
    private $_menuLogo;

    
    private function __construct()
    {
        $this->_callback  = [   MoOTP::instance(), 'mo_customer_validation_options' ];
        $this->_menuSlug  = 'mosettings';
        $this->_menuLogo  = MOV_URL . 'includes/images/miniorange_icon.png';

        $this->addMenuItem();
        $this->addFormsTab();
        $this->addOTPSettingsTab();
        $this->addAccountsTab();
        $this->addSMSEmailTab();
        $this->addMessagesTab();
        $this->addDesignTab();
        $this->addPricingTab();
        $this->addFaqTab();
        $this->addAddOnTab();
    }

    
    private function addMenuItem()
    {
        add_menu_page (
            'OTP Verification' ,
            'OTP Verification' ,
            'manage_options',
            $this->_menuSlug ,
            $this->_callback,
            $this->_menuLogo
        );
    }

    
    private function addFormsTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'Forms',
            'manage_options',
            $this->_menuSlug,
            $this->_callback
        );
    }

    
    private function addOTPSettingsTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'OTP Settings',
            'manage_options',
            'otpsettings',
            $this->_callback
        );
    }

    
    private function addAccountsTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'Account',
            'manage_options',
            'otpaccount',
            $this->_callback
        );
    }

    
    private function addSMSEmailTab()
    {
        add_submenu_page( $this->_menuSlug	,
            'OTP Verification'	,
            'SMS/Email',
            'manage_options',
            'config',
            $this->_callback
        );
    }

    
    private function addMessagesTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'Messages',
            'manage_options',
            'messages',
            $this->_callback
        );
    }

    
    private function addDesignTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'Design',
            'manage_options',
            'design',
            $this->_callback
        );
    }

    
    private function addPricingTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'Licensing Plans',
            'manage_options',
            'pricing',
            $this->_callback
        );
    }

    
    private function addFaqTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'FAQs',
            'manage_options',
            'help',
            $this->_callback
        );
    }

    
    private function addAddOnTab()
    {
        add_submenu_page(
            $this->_menuSlug	,
            'OTP Verification'	,
            'AddOns',
            'manage_options',
            'addon',
            $this->_callback
        );
    }
}
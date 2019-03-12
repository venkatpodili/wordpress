<?php

final class MiniOrangeCustomMessage extends BaseAddOn implements AddOnInterface
{
    use Instance;

    
    function initializeHandlers()
    {
        
        $list = AddOnList::instance();
        
        $handler = CustomMessages::instance();
        $list->add($handler->getAddOnKey(),$handler);
    }

    
    function initializeHelpers()
    {
        CustomMessagesShortcode::instance();
    }

    
    function show_addon_settings_page()
    {
        include MCM_DIR . 'controllers/main-controller.php';
    }
}
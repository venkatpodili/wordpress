<?php

    

    
    $defaultPopup = DefaultPopup::instance();
    
    $userChoicePopup = UserChoicePopup::instance();
    
    $externalPopup = ExternalPopup::instance();
    
    $errorPopup = ErrorPopup::instance();

    
    $nonce = $defaultPopup->getNonceValue();

    

    
    $default_template_type = $defaultPopup->getTemplateKey();
    $userchoice_template_type = $userChoicePopup->getTemplatekey();
    $external_template_type = $externalPopup->getTemplateKey();
    $error_template_type = $errorPopup->getTemplateKey();

    
    $email_templates = maybe_unserialize(get_mo_option('custom_popups'));
    $custom_default_popup = $email_templates[$defaultPopup->getTemplateKey()];
    $custom_external_popup = $email_templates[$externalPopup->getTemplateKey()];
    $custom_userchoice_popup = $email_templates[$userChoicePopup->getTemplatekey()];
    $error_popup = $email_templates[$errorPopup->getTemplateKey()];


    

    
    $common_template_settings = Template::$templateEditor;


    
    $editorId 		   = $defaultPopup->getTemplateEditorId();
    $templateSettings  = array_merge($common_template_settings,['textarea_name' => $editorId,'editor_height'=>400]);

    
    $editorId2         = $userChoicePopup->getTemplateEditorId();
    $templateSettings2 = array_merge($common_template_settings,['textarea_name' => $editorId2,'editor_height'=>400]);

    
    $editorId3         = $externalPopup->getTemplateEditorId();
    $templateSettings3 = array_merge($common_template_settings,['textarea_name' => $editorId3,'editor_height'=>400]);

    
    $editorId4         = $errorPopup->getTemplateEditorId();
    $templateSettings4 = array_merge($common_template_settings,['textarea_name' => $editorId4,'editor_height'=>400]);

    

    
    $loaderimgdiv = str_replace("{{CONTENT}}","<img src='".MOV_LOADER_URL."'>",Template::$paneContent);

    
    $previewpane = "<span style='font-size: 1.3em;'>".
                        "PREVIEW PANE<br/><br/>".
                   "</span>".
                   "<span>".
                        "Click on the Preview button above to check how your popup would look like.".
                    "</span>";
    $previewpane = str_replace("{{MESSAGE}}",$previewpane,Template::$messageDiv);
    $message = str_replace("{{CONTENT}}",$previewpane,Template::$paneContent);

    include MOV_DIR . 'views/design.php';
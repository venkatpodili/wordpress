<?php


$handler 				  = FormCraftBasicForm::instance();
$formcraft_enabled		  = $handler->isFormEnabled() ? "checked" : "";
$formcraft_hidden		  = $formcraft_enabled== "checked" ? "" : "hidden";
$formcraft_enabled_type   = $handler->getOtpTypeEnabled();
$formcraft_list 		  = admin_url().'admin.php?page=formcraft_basic_dashboard';
$formcraft_otp_enabled    = $handler->getFormDetails();
$formcarft_type_phone 	  = $handler->getPhoneHTMLTag();
$formcarft_type_email 	  = $handler->getEmailHTMLTag();
$form_name                = $handler->getFormName();

include MOV_DIR . 'views/forms/FormCraftBasicForm.php';
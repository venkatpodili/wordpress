<?php


$handler 				  = NinjaForm::instance();
$ninja_form_enabled		  = $handler->isFormEnabled() ? "checked" : "";
$ninja_form_hidden		  = $ninja_form_enabled== "checked" ? "" : "hidden";
$ninja_form_enabled_type  = $handler->getOtpTypeEnabled();
$ninja_form_list 		  = admin_url().'admin.php?page=ninja-forms';
$ninja_form_otp_enabled   = $handler->getFormDetails();
$ninja_form_type_phone 	  = $handler->getPhoneHTMLTag();
$ninja_form_type_email 	  = $handler->getEmailHTMLTag();
$ninja_form_type_both 	  = $handler->getBothHTMLTag();
$form_name                = $handler->getFormName();

include MOV_DIR . 'views/forms/NinjaForm.php';
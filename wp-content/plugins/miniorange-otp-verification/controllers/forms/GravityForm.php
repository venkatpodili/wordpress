<?php


$handler 			 = GravityForm::instance();
$gf_enabled 		 = $handler->isFormEnabled() ? "checked" : "";
$gf_hidden 			 = $gf_enabled== "checked" ? "" : "hidden";
$gf_enabled_type 	 = $handler->getOtpTypeEnabled();
$gf_field_list 		 = admin_url().'admin.php?page=gf_edit_forms';
$gf_otp_enabled 	 = $handler->getFormDetails();
$gf_type_email 		 = $handler->getEmailHTMLTag();
$gf_type_phone 		 = $handler->getPhoneHTMLTag();
$form_name           = $handler->getFormName();
$gf_button_text      = $handler->getButtonText();

include MOV_DIR . 'views/forms/GravityForm.php';
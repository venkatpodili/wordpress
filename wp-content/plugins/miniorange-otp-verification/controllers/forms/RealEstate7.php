<?php


$handler                = RealEstate7::instance();
$realestate_enabled     = $handler->isFormEnabled() ? "checked" : "";
$realestate_hidden 		= $realestate_enabled== "checked" ? "" : "hidden";
$realestate_enabled_type= $handler->getOtpTypeEnabled();
$realestate_type_phone  = $handler->getPhoneHTMLTag();
$realestate_type_email 	= $handler->getEmailHTMLTag();
$form_name              = $handler->getFormName();

include MOV_DIR . 'views/forms/RealEstate7.php';

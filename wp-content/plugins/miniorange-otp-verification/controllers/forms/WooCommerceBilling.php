<?php


$handler                        = WooCommerceBilling::instance();
$wc_billing_enable              = (Boolean) $handler->isFormEnabled() ? "checked" : "";
$wc_billing_hidden		        = $wc_billing_enable == "checked" ? "" : "hidden";
$wc_billing_type_enabled        = $handler->getOtpTypeEnabled();
$wc_billing_type_phone 	        = $handler->getPhoneHTMLTag();
$wc_billing_type_email 	        = $handler->getEmailHTMLTag();
$wc_restrict_duplicates         = (Boolean) $handler->restrictDuplicates() ? "checked" : "";
$button_text                    = $handler->getButtonText();
$form_name                      = $handler->getFormName();

include MOV_DIR . 'views/forms/WooCommerceBilling.php';
<?php

$current_user 	= wp_get_current_user();
$email 			= get_mo_option("admin_email");
$phone 			= get_mo_option("admin_phone");

include MOV_DIR . 'views/support.php';
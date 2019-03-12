<?php

	$page_list = admin_url().'edit.php?post_type=page';
    $plan_type = MoUtility::micv() ? 'wp_otp_verification_upgrade_plan' : 'wp_otp_verification_basic_plan';

	$nonce = $adminHandler->getNonceValue();
	$action = add_query_arg([
	   "page" => "mosettings#configured_forms",
    ]);

    include MOV_DIR . 'views/settings.php';
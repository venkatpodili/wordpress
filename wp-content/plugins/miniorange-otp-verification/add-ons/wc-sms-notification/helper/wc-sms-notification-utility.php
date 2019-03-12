<?php

	
	class MoWcAddOnUtiltiy
	{

		
		public static function getAdminPhoneNumber()
		{
			$user = new WP_User_Query( array(
			    'role' => 'Administrator',
                'search_columns' => array( 'ID', 'user_login' )
            ) );
			return ! empty( $user->results[0] ) ? array(get_user_meta( $user->results[0]->ID,
                'billing_phone', true)) : array();
		}


		
		public static function is_addon_activated()
		{
			$registration_url = add_query_arg(
			    array('page' => 'otpaccount'),
                remove_query_arg('addon',$_SERVER['REQUEST_URI'])
            );
			MoUtility::is_addon_activated($registration_url);
		}
	}
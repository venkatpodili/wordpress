<?php


class UMPasswordResetUtility
{
    
    public static function is_addon_activated()
    {
        $registration_url = add_query_arg(
            array('page' => 'otpaccount'),
            remove_query_arg('addon',$_SERVER['REQUEST_URI'])
        );
        MoUtility::is_addon_activated($registration_url);
    }
}
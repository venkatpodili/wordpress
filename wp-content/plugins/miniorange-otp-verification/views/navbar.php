<?php

echo'	<div class="wrap">
			<div><img style="float:left;" src="'.MOV_LOGO_URL.'"></div>
			<div class="otp-header">
				'.mo_("OTP Verification").'
				<a class="add-new-h2" href="'.$profile_url.'">'.mo_("Account").'</a>
				<a class="add-new-h2" href="'.$help_url.'">'.mo_("FAQs").'</a>
				<a class="otp-license add-new-h2" href="'.$license_url.'">'.mo_("Upgrade Plans").'</a>
				<a class="mo-otp-feedback add-new-h2" href="#">'.mo_("Feedback").'</a>
			    <div class="mo-otp-help-button static">
				    <button class="show_support_form button button-primary button-large" data-show="false" data-toggle="support_form">
				        '.mo_("Need Help").'<span class="dashicons dashicons-editor-help"></span>
	                </button>
	            </div>
            </div>
		</div>';

echo'	<div id="tab">
			<h2 class="nav-tab-wrapper">';
	
echo '			<a class="nav-tab '.($active_tab === 'mosettings'   ? 'nav-tab-active' : '').'" href="'.$settings.'">
                    '.mo_("Forms").'
                </a>
				<a class="nav-tab '.($active_tab === 'otpsettings'  ? 'nav-tab-active' : '').'" href="'.$otpsettings.'">
				    '.mo_("OTP Settings").'
				</a>
				<a class="nav-tab '.($active_tab === 'config'       ? 'nav-tab-active' : '').'" href="'.$config.'">
				    '.mo_("SMS/Email Templates").'
				</a>
				<a class="nav-tab '.($active_tab === 'messages' 	? 'nav-tab-active' : '').'" href="'.$messages.'">
				    '.mo_("Messages").'
				</a>
				<a class="nav-tab '.($active_tab === 'design' 	    ? 'nav-tab-active' : '').'" href="'.$design.'">
				    '.mo_("Pop-up Design").'
                </a>
                <a class="nav-tab '.($active_tab === 'addon' 	    ? 'nav-tab-active' : '').'" href="'.$addon.'">
                    '.mo_("AddOns").'
                </a>
			</h2>
		</div>';

        if(!$registerd) {
            echo '<div style="margin-top:1%;background-color:rgba(255,5,0,0.29);" class="notice notice-error"><h2>' .$registerMsg.'</h2></div>';
        }
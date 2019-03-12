<?php

echo'	<div class="mo_registration_divided_layout">
			<form name="f" method="post" action="'.$action.'" id="mo_otp_verification_settings">
			    <input type="hidden" id="error_message" name="error_message" value="">
				<input type="hidden" name="option" value="mo_customer_validation_settings" />';

					wp_nonce_field( $nonce );

echo'			<div class="mo_registration_table_layout" id="form_search">
					<table style="width:100%">
						<tr>
							<td colspan="2">
								<h2>
								    '.mo_("SELECT YOUR FORM FROM THE LIST BELOW").':';
                                    mo_draw_tooltip(
                                        MoMessages::showMessage('FORM_NOT_AVAIL_HEAD'),
                                        MoMessages::showMessage('FORM_NOT_AVAIL_BODY')
                                    );
 echo'							    
							        <span style="float:right;margin-top:-10px;">
							            <input  class="show_configured_forms button button-primary button-large" 
							                    value="'.mo_("Show All Enabled Forms").'" type="button">
                                        <span   class="dashicons dashicons-arrow-up toggle-div" 
                                                data-show="false" 
                                                data-toggle="modropdown"></span>
                                    </span>
                                </h2>                                    
							</td>
						</tr>
						<tr>
							<td colspan="2">';
                                get_otp_verification_form_dropdown();
echo'							
							</td>
						</tr>
					</table>
				</div>
				<div class="mo_registration_table_layout" id="selected_form_details" hidden>
					<table id="mo_forms" style="width: 100%;">
						<tr>
							<td>
								<h2>
									<i>'.mo_("FORM SETTINGS").'</i>
									<span style="float:right;margin-top:-10px;">
									    <input  class="show_configured_forms button button-primary button-large" 
									            value="'.mo_("Show All Enabled Forms").'" type="button">
									    <input  class="show_form_list button button-primary button-large" 
									            value="'.mo_("Show Forms List").'" type="button">
                                        <input  name="save" id="ov_settings_button" '.$disabled.' 
                                                class="button button-primary button-large" 
                                                value="'.mo_("Save Settings").'" type="submit">
                                        <span   class="dashicons dashicons-arrow-up toggle-div" 
                                                data-show="false" 
                                                data-toggle="new_form_settings"></span>
                                    </span>									
								</h2><hr>
							</td>
						</tr>
						<tr>
							<td>
								<div id="new_form_settings">
									<div class="mo_otp_note">
										<div id="text">'.mo_("Please select a form from the list above to see it's settings here.").'</div>
										<img id="loader" style="display:none" src="'.MOV_LOADER_URL.'">
									</div> 
									<div id="form_details"></div>
								</div>
							</td>
						</tr>
					</table>
				</div>
				<div class="mo_registration_table_layout" id="configured_forms" hidden>
					<table style="width:100%">
						<tr>
							<td>
								<h2>
									<i>'.mo_("CONFIGURED FORMS").'</i>
									<span style="float:right;margin-top:-10px;">
									    <input  class="show_form_list button button-primary button-large" 
									            value="'.mo_("Show Forms List").'" type="button">
                                        <input  name="save" id="ov_settings_button_config" 
                                                class="button button-primary button-large" '.$disabled.' 
                                                value="'.mo_("Save Settings").'" type="submit">
                                        <span   class="dashicons dashicons-arrow-up toggle-div" 
                                                data-show="false" 
                                                data-toggle="configured_mo_forms">                                                
                                        </span>
                                    </span>	
								</h2><hr>
							</td>
						</tr>
					</table>
					<div id="configured_mo_forms">';
						show_configured_form_details($controller,$disabled,$page_list);
echo'				</div>
				</div>
			</form>
		</div>';

        include MOV_DIR . 'views/instructions.php';
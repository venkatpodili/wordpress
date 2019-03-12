<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$gl_options = get_option('xoo-el-general-options');
$redirect 	= !empty( $gl_options['m-register-url'] ) ? esc_attr( $gl_options['m-register-url'] ) : $_SERVER['REQUEST_URI'];
$terms_url 	= !empty( $gl_options['m-terms-url'] ) ? esc_attr( $gl_options['m-terms-url'] ) : null;

?>

<div class="xoo-el-head">
	<span><?php _e('Sign Up','easy-login-woocommerce'); ?></span>
	<a class="xoo-el-login-tgr xoo-el-head-nav"><?php _e('Already Registered? Sign In','easy-login-woocommerce'); ?></a>
</div>


<div class="xoo-el-fields">
	<form class="xoo-el-action-form">
		<div class="xoo-el-notice"></div>

		<?php do_action('xoo_el_register_form_start'); ?>


		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
			<div class="xoo-el-group">
				<span class="xoo-el-input-icon xoo-el-icon-user-circle"></span>
				<input type="text" placeholder="<?php _e('Username','easy-login-woocommerce'); ?>" id="xoo-el-reg-username" name="xoo-el-username">
			</div>
		<?php endif; ?>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-envelope-o"></span>
			<input type="email" placeholder="<?php _e('Email','easy-login-woocommerce'); ?>" id="xoo-el-reg-email" name="xoo-el-email">
		</div>

		<div class="xoo-el-row">

			<div class="xoo-el-col-2 xoo-el-group">
				<span class="xoo-el-input-icon xoo-el-icon-user-circle"></span>
				<input type="text" placeholder="<?php _e('First Name','easy-login-woocommerce'); ?>" id="xoo-el-reg-fname" name="xoo-el-fname">
			</div>

			<div class="xoo-el-col-2 xoo-el-group">
				<span class="xoo-el-input-icon xoo-el-icon-user-circle"></span>
				<input type="text" placeholder="<?php _e('Last Name','easy-login-woocommerce'); ?>" id="xoo-el-reg-lname" name="xoo-el-lname">
			</div>

		</div>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-key"></span>
			<input type="password" placeholder="<?php _e('Password','easy-login-woocommerce'); ?>" id="xoo-el-reg-password" name="xoo-el-password">
		</div>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-key1"></span>		
			<input type="password" placeholder="<?php _e('Confirm Password','easy-login-woocommerce'); ?>" id="xoo-el-reg-confirm-password" name="xoo-el-confirm-password">
		</div>

		<div class="xoo-el-group">

			<label class="xoo-el-form-label" for="xoo-el-terms">
				<input type="checkbox" name="xoo-el-terms" id="xoo-el-terms" value="1" />
				<span>
					<?php printf( 
					    __( 'I accept the <a target="_blank" href="%s">Terms of Service and Privacy Policy</a>', 'easy-login-woocommerce' ), 
					    esc_url( $terms_url ) 
					); ?>	
				</span>
			</label>

		</div>


		<input type="hidden" name="_xoo_el_form" value="register">

		<button type="submit" class="button btn xoo-el-action-btn xoo-el-register-btn"><?php _e('Sign Up','easy-login-woocommerce'); ?></button>

		<input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

		<?php do_action('xoo_el_register_form_end'); ?>

	</form>
</div>
<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$gl_options = get_option('xoo-el-general-options');
$redirect 	= !empty( $gl_options['m-login-url'] ) ? esc_attr( $gl_options['m-login-url'] ) : $_SERVER['REQUEST_URI'];
$en_reg   	= $gl_options['m-en-reg'];

?>

<div class="xoo-el-head">
	<span><?php _e('Sign In','easy-login-woocommerce'); ?></span>
	<?php if($en_reg === "yes"): ?>
		<a class="xoo-el-reg-tgr xoo-el-head-nav"><?php _e('Not registered? Sign up','easy-login-woocommerce'); ?></a>
	<?php endif; ?>
</div>

<div class="xoo-el-fields">
	<form class="xoo-el-action-form">
		<div class="xoo-el-notice"></div>

		<?php do_action('xoo_el_login_form_start'); ?>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-user-circle"></span>
			<input type="text" placeholder="<?php _e('Username / Email','easy-login-woocommerce'); ?>" id="xoo-el-username" name="xoo-el-username">
		</div>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-key1"></span>
			<input type="password" placeholder="<?php _e('Password','easy-login-woocommerce'); ?>" id="xoo-el-password" name="xoo-el-password">
		</div>

		<div class="xoo-el-group">
			<label class="xoo-el-form-label" for="xoo-el-rememberme">
				<input type="checkbox" name="xoo-el-rememberme" id="xoo-el-rememberme" value="forever" />
				<span><?php _e( 'Remember me', 'easy-login-woocommerce' ); ?></span>
			</label>
			<a class="xoo-el-lostpw-tgr"><?php _e('Forgot Password?','easy-login-woocommerce'); ?></a>
		</div>

		<input type="hidden" name="_xoo_el_form" value="login">

		<button type="submit" class="button btn xoo-el-action-btn xoo-el-login-btn"><?php _e('Sign In','easy-login-woocommerce'); ?></button>

		<input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

		<?php do_action('xoo_el_login_form_end'); ?>

	</form>
</div>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$gl_options = get_option('xoo-el-general-options');
$en_reg   	= $gl_options['m-en-reg'];

?>

<div class="xoo-el-head">
	<span><?php _e('FORGOT PASSWORD ?','easy-login-woocommerce'); ?></span>
	<div class="xoo-el-head-action">
		<?php  if($en_reg === "yes"): ?>
			<a class="xoo-el-reg-tgr xoo-el-head-nav"><?php _e('Sign up','easy-login-woocommerce'); ?></a>
		<?php endif; ?>
		<a class="xoo-el-login-tgr xoo-el-head-nav"><?php _e('Sign In','easy-login-woocommerce'); ?></a>
	</div>
</div>

<div class="xoo-el-fields">
	<form class="xoo-el-action-form">
		<div class="xoo-el-notice"></div>

		<?php do_action('xoo_el_lostpassword_form_start'); ?>

		<span class="xoo-el-form-txt"><?php _e('Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.','easy-login-woocommerce'); ?></span>

		<div class="xoo-el-group">
			<span class="xoo-el-input-icon xoo-el-icon-envelope-o"></span>
			<input type="text" placeholder="<?php _e('Username / Email','easy-login-woocommerce'); ?>" id="xoo-el-lostpw-email" name="user_login">
		</div>

		<input type="hidden" name="_xoo_el_form" value="lostPassword">

		<button type="submit" class="button btn xoo-el-action-btn xoo-el-lostpw-btn"><?php _e('Email Reset Link','easy-login-woocommerce'); ?></button>

		<?php do_action('xoo_el_lostpassword_form_end'); ?>

	</form>
</div>
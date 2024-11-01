<?php
/*
Plugin Name: UpMenu - Online ordering for restaurants
Plugin URI: http://upmenu.com
Description: UpMenu is everything your restaurant needs for you to take your customers’ orders directly from your website or app while protecting your bottom line from third-party aggregators and enabling you to grow your revenue.
Author: UpMenu
Version: 3.1
*/

add_action('admin_menu', 'upmenu_add_menu');

function upmenu_add_menu() {

	$menu_icon = file_get_contents( plugin_dir_path( __FILE__ ) . '/assets/icon.svg' );

	if( get_option('upmenu_access_token') ) {
		add_menu_page('UpMenu', 'UpMenu', 'administrator', 'upmenu-connection', 'upmenu_connection_page', 'data:image/svg+xml;base64,' . base64_encode( $menu_icon ));
		add_submenu_page( 'upmenu-connection', 'Connection', 'Connection', 'administrator', 'upmenu-connection', 'upmenu_connection_page' );
		add_submenu_page( 'upmenu-connection', 'Widgets', 'Widgets', 'administrator', 'upmenu-widgets', 'upmenu_widgets_page' );
	} else {
		add_menu_page('UpMenu', 'UpMenu', 'administrator', 'upmenu-login', 'upmenu_login_page', 'data:image/svg+xml;base64,' . base64_encode( $menu_icon ));
	}

}

add_action( 'admin_enqueue_scripts', 'upmenu_admin_style' );

function upmenu_admin_style() {
	wp_register_style( 'upmenu_css', plugins_url( '/assets/css/style.min.css', __FILE__ ), false );
	wp_enqueue_style( 'upmenu_css' );
}

add_action( 'admin_init', 'upmenu_plugin_settings' );

function upmenu_plugin_settings() { 

	register_setting( 'upmenu_plugin-settings-group', 'upmenu_button_text' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_button_font' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_button_weight' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_font_size' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_text_color' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_background_color' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_padding' );
	register_setting( 'upmenu_plugin-settings-group', 'upmenu_border_radius' );

}

function getSiteDetails( $siteID, $token ) {

	$url = 'https://www.upmenu.com/restapi/admin/wp/details/'.$siteID.'/?access_token='.$token;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	$response = json_decode($response);

	curl_close($ch);

	return $response;
  	die();
}

function listSitesForUser( $token ) {

	$url = 'https://www.upmenu.com/restapi/admin/wp/sites/?access_token='.$token;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	$response = json_decode( $response );

	curl_close($ch);

	return $response;
  	die();
}

function getAuthorizationToken( $username, $password ) {

  	$url = 'https://www.upmenu.com/oauth/token';

  	$data = array(
        'grant_type' => 'password',
        'client_id' => 'Eovhbf3aYs',
        'client_secret' => 'WcvPNjqoC9',
		'username' => $username,
		'password' => $password
  	);

  	$data_string = http_build_query($data);

  	$ch = curl_init();
  	curl_setopt($ch, CURLOPT_URL, $url);
  	curl_setopt($ch, CURLOPT_POST, 1);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

  	$response = curl_exec( $ch );
  	$response = json_decode($response);

  	$token = $response->access_token;

	if( get_option('upmenu_access_token') || get_option('upmenu_access_token') == '' ) {
		update_option('upmenu_access_token', $response->access_token);
	} else {
		add_option('upmenu_access_token', $response->access_token);
	}

	if( get_option('upmenu_refresh_token') || get_option('upmenu_refresh_token') == '' ) {
		update_option('upmenu_refresh_token', $response->refresh_token);
	} else {
		add_option('upmenu_refresh_token', $response->refresh_token);
	}

	if( get_option('upmenu_connected_as') || get_option('upmenu_connected_as') == '' ) {
		update_option('upmenu_connected_as', $username);
	} else {
		add_option('upmenu_connected_as', $username);
	}

  	curl_close($ch);

  	return $token;
  	die();

}

function refreshToken() {

	$url = 'https://www.upmenu.com/oauth/token';

	$data = array(
	  'grant_type' => 'refresh_token',
	  'client_id' => 'Eovhbf3aYs',
	  'client_secret' => 'WcvPNjqoC9',
	  'refresh_token' => get_option('upmenu_refresh_token')
	);

	$data_string = http_build_query($data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

	$response = curl_exec( $ch );
	$response = json_decode($response);

	$token = $response->access_token;

	if( get_option('upmenu_access_token') || get_option('upmenu_access_token') == '' ) {
		update_option('upmenu_access_token', $response->access_token);
	} else {
		add_option('upmenu_access_token', $response->access_token);
	}

	if( get_option('upmenu_refresh_token') || get_option('upmenu_refresh_token') == '' ) {
		update_option('upmenu_refresh_token', $response->refresh_token);
	} else {
		add_option('upmenu_refresh_token', $response->refresh_token);
	}

	curl_close($ch);

	return $token;
	die();

}

function userLogout() {

	delete_option( 'upmenu_access_token' );
	delete_option( 'upmenu_refresh_token' );
	delete_option( 'upmenu_selected_account' );
	delete_option( 'upmenu_connected_as' );

	$loginPage = get_home_url().'/wp-admin/admin.php?page=upmenu-login'; ?>
	<script>
		window.location.replace("<?php echo $loginPage; ?>");
	</script> <?php 

}

add_action( 'wp_ajax_nopriv_ajaxUpdateOption', 'ajaxUpdateOption' );
add_action( 'wp_ajax_ajaxUpdateOption', 'ajaxUpdateOption' );
function ajaxUpdateOption() { 
	update_option('upmenu_selected_account', $_POST['account']);
}

function upmenu_login_page() { ?>

	<div class="wrap">
		<img src="<?php echo plugins_url( '/assets/logo.svg', __FILE__ ); ?>" width="140" height="35" alt="UpMenu" />
		<div class="upmenu-postbox small">
			<div class="upmenu-postbox-inside">
				<h1>
					<?php echo __("Log in", "upmenu"); ?>
				</h1>
				<p class="fs-16">
					<?php echo __("You do not have an account?", "upmenu"); ?>
					<a href="https://www.upmenu.com/admin/registration" target="_blank">
						<?php echo __("Register", "upmenu"); ?>
					</a>
				</p>
				<form method="post">
					<div class="upmenu-margin">
						<input type="email" name="email" placeholder="<?php echo __("Email", "upmenu"); ?>" />
					</div>
					<div class="upmenu-margin">
						<input type="password" name="password" placeholder="<?php echo __("Password", "upmenu"); ?>" />
					</div>
					<button type="submit" class="upmenu-button upmenu-button-block">
						<?php echo __("Log in", "upmenu"); ?>
					</button>
					<?php 
					if ($_SERVER["REQUEST_METHOD"] == "POST") {
						$token = getAuthorizationToken( $_POST['email'], $_POST['password'] );
						if( $token ) {
							$accounts = listSitesForUser( $token );
							if( get_option('upmenu_selected_account') ) {
								update_option('upmenu_selected_account', $accounts[0]->id);
							} else {
								add_option('upmenu_selected_account', $accounts[0]->id);
							}
							$connectionPage = get_home_url().'/wp-admin/admin.php?page=upmenu-connection'; ?>
							<script>
								window.location.replace("<?php echo $connectionPage; ?>");
							</script> <?php 
						} else {
							echo '<p class="error-alert">'.__("Wrong credentials", "upmenu").'</p>';
						}
					} ?>
				</form>
			</div>
		</div>
	</div>

<?php }

function upmenu_connection_page() { ?>

	<?php 
	$accounts = listSitesForUser( get_option('upmenu_access_token') );
	if( $accounts ) { 
		$newToken = refreshToken(); 
		$accounts = listSitesForUser( $newToken );
		if( isset($accounts->error) ) { 
			userLogout();
		}
	} 
	?>

	<div class="wrap">
		<div class="upmenu-postbox">
			<div class="upmenu-postbox-header">
				<h2>
					<span>
						<?php echo __("Connected as: ", "upmenu"); ?>
						
					</span>
					<?php echo get_option('upmenu_connected_as'); ?>
				</h2>
				<form method="post">
					<button type="submit" class="upmenu-link">
						<?php echo __("Disconnect", "upmenu"); ?>
					</button>
				</form>
			</div>
			<div class="upmenu-postbox-inside">
				<div class="upmenu-margin">
					<label>
						<?php echo __("Account", "upmenu"); ?>
					</label>
					<?php if( count($accounts) > 1 ) { ?>
						<select id="accounts-list">
							<?php 
							foreach( $accounts as $account ) {
								if( $account->id == get_option('upmenu_selected_account') ) {
									echo '<option value="'.$account->id.'" selected>'.$account->name.'</option>';
								} else {
									echo '<option value="'.$account->id.'">'.$account->name.'</option>';
								}
							} 
							?>
						</select>
					<?php } else { 
						foreach( $accounts as $account ) { ?>
							<p class="fs-16">
								<?php echo $account->name; ?>
							</p> <?php 
						} ?>
					<?php } ?>
				</div>
				<a href="https://www.upmenu.com/admin/dashboard/<?php echo get_option('upmenu_selected_account'); ?>" target="_blank" class="upmenu-button">
					<?php echo __("Open UpMenu dashboard", "upmenu"); ?>
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M12 8.66667V12.6667C12 13.0203 11.8595 13.3594 11.6095 13.6095C11.3594 13.8595 11.0203 14 10.6667 14H3.33333C2.97971 14 2.64057 13.8595 2.39052 13.6095C2.14048 13.3594 2 13.0203 2 12.6667V5.33333C2 4.97971 2.14048 4.64057 2.39052 4.39052C2.64057 4.14048 2.97971 4 3.33333 4H7.33333" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M10 2H14V6" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M6.66675 9.33333L14.0001 2" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>
		</div>
		<script>
			jQuery('#accounts-list').on( 'change', function() {
				jQuery.ajax({
					url: '<?php echo get_home_url(); ?>/wp-admin/admin-ajax.php',
					type: 'post',
					data: {
						action: 'ajaxUpdateOption',
						account: jQuery( this ).val()
					},
					success: function( result ) {
						
					}
				});
			});
		</script>
		<?php 
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				userLogout();
			}
		?>
	</div>

<?php }

function upmenu_widgets_page() { ?>

	<?php 
	$accountID = get_option('upmenu_selected_account');
	$details = getSiteDetails( $accountID, get_option('upmenu_access_token') ); 
	if( $details ) { 
		$newToken = refreshToken(); 
		$details = getSiteDetails( $accountID, $newToken );
		if( isset($details->error) ) { 
			userLogout();
		}
	} 
	$restaurantsList = $details->restaurants;
	$languagesList = $details->languages;
	$cmsEditorUrl = $details->cmsEditorUrl;
	?>

	<div class="wrap">
	
		<?php $default_button_text = __("Order now", "upmenu"); ?>
		<?php $default_button_font = 'initial'; ?>
		<?php $default_button_weight = 'normal'; ?>
		<?php $default_font_size = '14'; ?>
		<?php $default_text_color = '#ffffff'; ?>
		<?php $default_background_color = '#E4B355'; ?>
		<?php $default_padding = '12'; ?>
		<?php $default_border_radius = '4'; ?>

		<h2 class="upmenu-title">
			<?php echo __("Widgets", "upmenu"); ?>
			<a href="https://www.upmenu.com/admin/dashboard/<?php echo $accountID; ?>" target="_blank" class="upmenu-link">
				<?php echo __("Open UpMenu dashboard", "upmenu"); ?>
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9 6.5V9.5C9 9.76522 8.89464 10.0196 8.70711 10.2071C8.51957 10.3946 8.26522 10.5 8 10.5H2.5C2.23478 10.5 1.98043 10.3946 1.79289 10.2071C1.60536 10.0196 1.5 9.76522 1.5 9.5V4C1.5 3.73478 1.60536 3.48043 1.79289 3.29289C1.98043 3.10536 2.23478 3 2.5 3H5.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M7.5 1.5H10.5V4.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M5 7L10.5 1.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</h2>

		<div class="upmenu-postbox">
			<div class="upmenu-postbox-header">
				<h2>
					<?php echo __("Online ordering button shortcode", "upmenu"); ?>
				</h2>
				<select id="restaurants-list-button">
					<option value="0">
						<?php echo __("All restaurants", "upmenu"); ?>
					</option>
					<?php foreach( $restaurantsList as $restaurant ) {
						echo '<option value="'.$restaurant->id.'">'.$restaurant->name.'</option>';
					} ?>
				</select>
			</div>
			<div class="upmenu-postbox-inside">
				<div class="upmenu-flex">
					<div id="upmenu-button" style="
						color: <?php if( get_option('upmenu_text_color') ) { echo get_option('upmenu_text_color'); } else { echo $default_text_color; } ?>; 
						background: <?php if( get_option('upmenu_background_color') ) { echo get_option('upmenu_background_color'); } else { echo $default_background_color; } ?>; 
						border-radius: <?php if( get_option('upmenu_border_radius') ) { echo get_option('upmenu_border_radius'); } else { echo $default_border_radius; } ?>px; 
						font-size: <?php if( get_option('upmenu_font_size') ) { echo get_option('upmenu_font_size'); } else { echo $default_font_size; } ?>px; 
						padding: <?php if( get_option('upmenu_padding') ) { echo get_option('upmenu_padding'); } else { echo $default_padding; } ?>px;
						font-family: <?php if( get_option('upmenu_button_font') ) { echo get_option('upmenu_button_font'); } else { echo $default_button_font; } ?>;
						font-weight: <?php if( get_option('upmenu_button_weight') ) { echo get_option('upmenu_button_weight'); } else { echo $default_button_weight; } ?>;
						text-decoration: none;
						border: none;
						white-space: nowrap;
						display: inline-flex;
					">
						<?php if( get_option('upmenu_button_text') ) { echo get_option('upmenu_button_text'); } else { echo $default_button_text; } ?>
					</div>
					<input type="text" id="shortcode-button" value='[upmenu-button id="<?php echo $accountID; ?>"]' />
				</div>
			</div>
			<div class="upmenu-postbox-footer">
				<a href="#" id="toggle-customize">
					<?php echo __("Customize", "upmenu"); ?>
					<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M3 4.5L6 7.5L9 4.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
				<div class="upmenu-hidden">
					
					<form method="post" action="options.php">

						<?php settings_fields( 'upmenu_plugin-settings-group' ); ?>
		    			<?php do_settings_sections( 'upmenu_plugin-settings-group' ); ?>
						<div class="upmenu-margin">
							<label>
								<?php echo __("Button text", "upmenu"); ?>
							</label>
							<input type="text" name="upmenu_button_text" value="<?php if( get_option('upmenu_button_text') ) { echo get_option('upmenu_button_text'); } else { echo $default_button_text; } ?>" />
						</div>
						<div class="upmenu-row upmenu-margin">
							<div class="upmenu-col">
								<label><?php echo __("Font", "upmenu"); ?></label>
								<select name="upmenu_button_font">
									<option value="inherit"><?php echo __("Inherit from your template", "upmenu"); ?></option>
									<option value="Helvetica" <?php if( get_option('upmenu_button_font') == 'Helvetica' ) { echo 'selected'; } ?>>Helvetica</option>
									<option value="Futura" <?php if( get_option('upmenu_button_font') == 'Futura' ) { echo 'selected'; } ?>>Futura</option>
									<option value="Garamond" <?php if( get_option('upmenu_button_font') == 'Garamond' ) { echo 'selected'; } ?>>Garamond</option>
									<option value="Arial" <?php if( get_option('upmenu_button_font') == 'Arial' ) { echo 'selected'; } ?>>Arial</option>
									<option value="Verdana" <?php if( get_option('upmenu_button_font') == 'Verdana' ) { echo 'selected'; } ?>>Verdana</option>
								</select>
							</div>
							<div class="upmenu-col">
								<div class="upmenu-row">
									<div class="upmenu-col">
										<label><?php echo __("Font size", "upmenu"); ?></label>
										<input type="number" name="upmenu_font_size" value="<?php if( get_option('upmenu_font_size') ) { echo get_option('upmenu_font_size'); } else { echo $default_font_size; } ?>" />
									</div>
									<div class="upmenu-col">
										<label><?php echo __("Font weight", "upmenu"); ?></label>
										<select name="upmenu_button_weight">
											<option value="normal"><?php echo __("Regular", "upmenu"); ?></option>
											<option value="bold" <?php if( get_option('upmenu_button_weight') == 'bold' ) { echo 'selected'; } ?>><?php echo __("Bold", "upmenu"); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="upmenu-row upmenu-margin">
							<div class="upmenu-col">
								<label><?php echo __("Text color", "upmenu"); ?></label>
								<input type="color" name="upmenu_text_color" value="<?php if( get_option('upmenu_text_color') ) { echo get_option('upmenu_text_color'); } else { echo $default_text_color; } ?>" />
							</div>
							<div class="upmenu-col">
								<label><?php echo __("Background color", "upmenu"); ?></label>
								<input type="color" name="upmenu_background_color" value="<?php if( get_option('upmenu_background_color') ) { echo get_option('upmenu_background_color'); } else { echo $default_background_color; } ?>" />
							</div>
						</div>
						<div class="upmenu-row upmenu-margin">
							<div class="upmenu-col">
								<label><?php echo __("Padding", "upmenu"); ?></label>
								<input type="number" name="upmenu_padding" value="<?php if( get_option('upmenu_padding') ) { echo get_option('upmenu_padding'); } else { echo $default_padding; } ?>" />
							</div>
							<div class="upmenu-col">
								<label><?php echo __("Border radius", "upmenu"); ?></label>
								<input type="number" name="upmenu_border_radius" value="<?php if( get_option('upmenu_border_radius') ) { echo get_option('upmenu_border_radius'); } else { echo $default_border_radius; } ?>" />
							</div>
						</div>
						<input type="submit" id="submit" name="submit" class="upmenu-button" value="<?php echo __("Save", "upmenu"); ?>" />
					</form>
					<script>
						jQuery('[name="upmenu_button_text"]').on('input', function(e) {
							jQuery('#upmenu-button').text( jQuery(this).val() );
						});
						jQuery('[name="upmenu_button_font"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'font-family', jQuery(this).val() );
						});
						jQuery('[name="upmenu_font_size"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'font-size', jQuery(this).val()+'px' );
						});
						jQuery('[name="upmenu_button_weight"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'font-weight', jQuery(this).val() );
						});
						jQuery('[name="upmenu_text_color"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'color', jQuery(this).val() );
						});
						jQuery('[name="upmenu_background_color"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'background', jQuery(this).val() );
						});
						jQuery('[name="upmenu_padding"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'padding', jQuery(this).val()+'px' );
						});
						jQuery('[name="upmenu_border_radius"]').on('input', function(e) {
							jQuery('#upmenu-button').css( 'border-radius', jQuery(this).val()+'px' );
						});
					</script>
				</div>
			</div>
		</div>

		<div class="upmenu-postbox">
			<div class="upmenu-postbox-header">
				<h2>
					<?php echo __("Embedded online ordering shortcode", "upmenu"); ?>
				</h2>
				<select id="restaurants-list-ordering">
					<option value="0">
						<?php echo __("All restaurants", "upmenu"); ?>
					</option>
					<?php foreach( $restaurantsList as $restaurant ) {
						echo '<option value="'.$restaurant->id.'">'.$restaurant->name.'</option>';
					} ?>
				</select>
			</div>
			<div class="upmenu-postbox-inside">
				<input type="text" id="shortcode-ordering" value='[upmenu-menu id="<?php echo $accountID; ?>"]' />
				<div class="upmenu-row">
					<div class="upmenu-col">
						<div class="input-language">
							<label>
								<?php echo __("Language", "upmenu"); ?>
							</label>
							<select id="languages-list-ordering">
								<option value="0">
									<?php echo __("Default", "upmenu"); ?>
								</option>
								<?php foreach( $languagesList as $language ) {
									echo '<option value="'.$language.'">'.$language.'</option>';
								} ?>
							</select>
						</div>
					</div>
					<div class="upmenu-col">
						<div class="input-offset">
							<label>
								<?php echo __("Offset", "upmenu"); ?>
							</label>
							<input type="number" id="offset-ordering" />
						</div>
					</div>
				</div>
			</div>
			<div class="upmenu-postbox-footer">
				<a href="<?php echo $cmsEditorUrl; ?>" target="_blank">
					<?php echo __("Customize", "upmenu"); ?> 
					<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M9 6.5V9.5C9 9.76522 8.89464 10.0196 8.70711 10.2071C8.51957 10.3946 8.26522 10.5 8 10.5H2.5C2.23478 10.5 1.98043 10.3946 1.79289 10.2071C1.60536 10.0196 1.5 9.76522 1.5 9.5V4C1.5 3.73478 1.60536 3.48043 1.79289 3.29289C1.98043 3.10536 2.23478 3 2.5 3H5.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M7.5 1.5H10.5V4.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M5 7L10.5 1.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>
		</div>

		<?php /*
		<div class="upmenu-postbox">
			<div class="upmenu-postbox-header">
				<h2>
					<?php echo __("Embedded online reservation shortcode", "upmenu"); ?>
				</h2>
				<select id="restaurants-list-reservation">
					<option value="0">
						<?php echo __("All restaurants", "upmenu"); ?>
					</option>
					<?php foreach( $restaurantsList as $restaurant ) {
						echo '<option value="'.$restaurant->id.'">'.$restaurant->name.'</option>';
					} ?>
				</select>
			</div>
			<div class="upmenu-postbox-inside">
				<input type="text" id="shortcode-reservation" value='[upmenu-reservation id="<?php echo $accountID; ?>"]' />
				<div class="input-language">
					<label>
						<?php echo __("Language", "upmenu"); ?>
					</label>
					<select id="languages-list-reservation">
						<option value="0">
							<?php echo __("Default", "upmenu"); ?>
						</option>
						<?php foreach( $languagesList as $language ) {
							echo '<option value="'.$language.'">'.$language.'</option>';
						} ?>
					</select>
				</div>
			</div>
			<div class="upmenu-postbox-footer">
				<a href="https://www.upmenu.com/admin/dashboard/<?php echo $accountID; ?>" target="_blank">
					<?php echo __("Customize", "upmenu"); ?> 
					<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M9 6.5V9.5C9 9.76522 8.89464 10.0196 8.70711 10.2071C8.51957 10.3946 8.26522 10.5 8 10.5H2.5C2.23478 10.5 1.98043 10.3946 1.79289 10.2071C1.60536 10.0196 1.5 9.76522 1.5 9.5V4C1.5 3.73478 1.60536 3.48043 1.79289 3.29289C1.98043 3.10536 2.23478 3 2.5 3H5.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M7.5 1.5H10.5V4.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M5 7L10.5 1.5" stroke="#8B38CB" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>
		</div>
		*/ ?>

		<script>
			jQuery('#toggle-customize').click( function(e) {
				e.preventDefault();
				jQuery(this).toggleClass('active');
				jQuery('.upmenu-hidden').slideToggle();
			});
			jQuery('#restaurants-list-button').on( 'change', function() {
				if( jQuery(this).val() != 0 ) {
					jQuery('#shortcode-button').val( '[upmenu-button id="<?php echo $accountID; ?>" restaurant="'+jQuery(this).val()+'"]' );
				} else {
					jQuery('#shortcode-button').val( '[upmenu-button id="<?php echo $accountID; ?>"]' );
				}
			});
			jQuery('#restaurants-list-ordering, #languages-list-ordering, #offset-ordering').on( 'change', function() {
				refreshOrderingShortcode();
			});
			jQuery('#restaurants-list-reservation, #languages-list-reservation').on( 'change', function() {
				refreshReservationShortcode();
			});
			function refreshOrderingShortcode() {

				var restaurantsListOrdering = '';
				var languagesListOrdering = '';
				var offsetOrdering = '';

				if( jQuery('#restaurants-list-ordering').val() != 0 ) {
					restaurantsListOrdering = ' restaurant="'+jQuery('#restaurants-list-ordering').val()+'"';
				}
				if( jQuery('#languages-list-ordering').val() != 0 ) {
					languagesListOrdering = ' lang="'+jQuery('#languages-list-ordering').val()+'"';
				}
				if( jQuery('#offset-ordering').val() ) {
					offsetOrdering = ' offset="'+jQuery('#offset-ordering').val()+'"';
				}

				jQuery('#shortcode-ordering').val( '[upmenu-menu id="<?php echo $accountID; ?>"'+restaurantsListOrdering+languagesListOrdering+offsetOrdering+']' );
			}
			function refreshReservationShortcode() {

				var restaurantsListReservation = '';
				var languagesListReservation = '';

				if( jQuery('#restaurants-list-reservation').val() != 0 ) {
					restaurantsListOrdering = ' restaurant="'+jQuery('#restaurants-list-reservation').val()+'"';
				}
				if( jQuery('#languages-list-reservation').val() != 0 ) {
					languagesListOrdering = ' lang="'+jQuery('#languages-list-reservation').val()+'"';
				}

				jQuery('#shortcode-reservation').val( '[upmenu-reservation id="<?php echo $accountID; ?>"'+restaurantsListReservation+languagesListReservation+']' );
			}
		</script>

	</div> 

<?php }


// button shortcode
function upmenu_button_shortcode($atts) {

	$default = array(
        'id' => '',
		'restaurant' => '',
    );
	$a = shortcode_atts($default, $atts);

	$accountID = get_option('upmenu_selected_account');
	$details = getSiteDetails( $accountID, get_option('upmenu_access_token') ); 
	$siteUrl = $details->url;

	foreach ($details->restaurants as $arr) {
		if( $arr->id == $a['restaurant'] ) {
			$siteUrl = $arr->url;
		}
	}

	$default_button_text = 'Order now';
	$default_font_family = 'initial';
	$default_font_size = '14';
	$default_font_weight = 'normal';
	$default_text_color = '#ffffff';
	$default_background_color = '#E4B355';
	$default_padding = '12';
	$default_border_radius = '4';    

	if( get_option('upmenu_button_text') ) { $title = get_option('upmenu_button_text'); } else { $title = $default_button_text; }

	$style = 'text-decoration: none; border: none; white-space: nowrap;display: inline-flex;';

	if( get_option('upmenu_button_font') ) { $style .= 'font-family: '.get_option('upmenu_button_font').';'; } else { $style .= 'font-family: '.$default_font_family.';'; }
	if( get_option('upmenu_button_weight') ) { $style .= 'font-weight: '.get_option('upmenu_button_weight').';'; } else { $style .= 'font-weight: '.$default_font_weight.';'; }
	if( get_option('upmenu_text_color') ) { $style .= 'color: '.get_option('upmenu_text_color').';'; } else { $style .= 'color: '.$default_text_color.';'; }
	if( get_option('upmenu_background_color') ) { $style .= 'background: '.get_option('upmenu_background_color').';'; } else { $style .= 'background: '.$default_background_color.';'; }
	if( get_option('upmenu_border_radius') ) { $style .= 'border-radius: '.get_option('upmenu_border_radius').'px;'; } else { $style .= 'border-radius: '.$default_border_radius.'px;'; }
	if( get_option('upmenu_font_size') ) { $style .= 'font-size: '.get_option('upmenu_font_size').'px;'; } else { $style .= 'font-size: '.$default_font_size.'px;'; }
	if( get_option('upmenu_padding') ) { $style .= 'padding: '.get_option('upmenu_padding').'px;'; } else { $style .= 'padding: '.$default_padding.'px;'; }
    
	return '<a href="'.$siteUrl.'" id="upmenu-button" style="'.$style.'">'.$title.'</a>';
}
add_shortcode('upmenu-button', 'upmenu_button_shortcode');

// menu shortcode
function upmenu_menu_shortcode($atts) {
    $default = array(
        'id' => '',
		'restaurant' => '',
		'lang' => '',
		'offset' => ''
    );
    $a = shortcode_atts($default, $atts);
	$restaurant = '';
	$lang = '';
	$offset = '';
	if($a['restaurant']) {
		$restaurant = 'restaurant_id: "'.$a['restaurant'].'",';
	}
	if($a['lang']) {
		$lang = 'language: "'.$a['lang'].'",';
	}
	if( isset($a['offset']) ) {
		if( is_int($a['offset']) ) {
			$offset = ' data-fixed-offset-top="'.$a['offset'].'"';
		} else {
			$offset = ' data-fixed-offset-top="0"';
		}
	}
	return '
		<script>
			window.upmenuSettings = {
				id: "'.$a['id'].'",
				additional_source: "WORDPRESS_PLUGIN",
				'.$restaurant.'
				'.$lang.'
			};
		</script>
		<script src="https://cdn.upmenu.com/media/upmenu-widget.js"></script>
		<div id="upmenu-widget"'.$offset.'></div>
	';
}
add_shortcode('upmenu-menu', 'upmenu_menu_shortcode');

// reservation shortcode
function upmenu_reservation_shortcode($atts) {
    $default = array(
        'id' => '',
		'restaurant' => '',
		'lang' => ''
    );
    $a = shortcode_atts($default, $atts);
	$restaurant = '';
	$lang = '';
	if($a['restaurant']) {
		$restaurant = 'restaurant_id: "'.$a['restaurant'].'",';
	}
	if($a['lang']) {
		$lang = 'language: "'.$a['lang'].'",';
	}
	return '
		<script>
			window.upmenuSettings = {
				id: "'.$a['id'].'",
				additional_source: "WORDPRESS_PLUGIN",
				page_id: "booking",
				'.$restaurant.'
				'.$lang.'
			};
		</script>
		<script src="https://cdn.upmenu.com/media/upmenu-widget.js"></script>
		<div id="upmenu-widget"></div>
	';
}
add_shortcode('upmenu-reservation', 'upmenu_reservation_shortcode');

// support for the old version of the plugin
function upmenu_old_menu_shortcode() {
	return '
		<script>
			window.upmenuSettings = {
				id: "'.get_option('upmenu_code').'",
				additional_source: "WORDPRESS_PLUGIN"
			};
		</script>
		<script src="https://cdn.upmenu.com/media/upmenu-widget.js"></script>
		<div id="upmenu-widget"></div>
	';
}
add_shortcode('upmenu', 'upmenu_old_menu_shortcode');

?>
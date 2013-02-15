<?php
/*
Plugin Name: NinjaWPass
Plugin URI: http://NinjaFirewall.com/ninjawpass.html
Description: Secure WordPress log-in form against keyloggers, stolen passwords and brute-force attacks.
Version: 1.0.3
Author: The Ninja Technologies Network
Author URI: http://NinTechNet.com/
License: GPLv2 or later
*/

define('NINJAWP_VERSION', '1.0.3');


/* ================================================================ */

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) { die('Forbidden'); }

if (! session_id() ) { session_start(); }

/* ================================================================ */



/* Login form hook ================================================ */

add_filter( 'login_form', 'ninjawp_login_hook');

function ninjawp_login_hook () {

	// get the plugin options :
	$ninjawp_options = get_option( 'ninjawp_options' );

	// is that IP banned ?
	if ( check_login_attempt( $ninjawp_options['ban_time'] ,
		$ninjawp_options['failed_count'], $ninjawp_options['max_seconds'] ) ) {
		// we can break rules, too :
		wp_die('<p align=center><strong>ERROR :</strong> Too many failed attempts !<br />You are banned for a few minutes...</p>', '', array( 'response' => 403 ) );
	}

	// if password is not defined, warn about it :
	if (! $ninjawpass = base64_decode( $ninjawp_options['ninjawpass'] ) ) {
		echo '<div class="error settings-error"><p><strong>WARNING : your NinjaWPass password is not defined.<br />Please create a password or disable this plugin.</strong></p></div><br /><br />';
		return;
	}

	$n_rand_chars = random_num( $ninjawpass );
	ksort( $n_rand_chars );

	// some cosmetic work :
	$numb = array(
		1	=> 'st', 2	=> 'nd',
		3	=> 'rd',	21	=> 'st',
		22	=> 'nd',	23	=> 'rd'
	);
	$img = '<img src="data:image/png;base64,R0lGODlhCgAKAIABAMzMzP///yH5BAEKAAEALAAAAAAKAAoAAAIRjI8HC8msHgxy1mVuy9LNBBYAOw==" width="10" height="10" />&nbsp;';

	$count = 1;
	$n_char = array();
	$text = '';
	// insert our input fields and dot images into WP login form :
	foreach ( $n_rand_chars as $key => $value ) {
		$n_char[$count] = $key;
		if ( $count == 1 ) {
			if ( $key > 1 ) {
				for ( $i = 1; $i < $key; $i++ ) {
					$text .= $img;
				}
				@$numb[$key] ? $label_1 = $key . $numb[$key] : $label_1 = $key . 'th';
			} else {
				$label_1 = '1st';
			}
			$text .= '<input type="password" size="1" tabindex="20" maxlength=1 value="" class="input" name="nwp1" style="width:30px;text-align:center;">';
		} elseif ( $count == 2 ) {
			if ( ($key - 1) != $n_char[$count-1] ) {
				for ( $i=$n_char[1]+1; $i< $key; $i++ ) {
					$text .= $img;
				}
			}
			@$numb[$key] ? $label_2 = $key . $numb[$key] : $label_2 = $key . 'th';
			$text .= '<input type="password" size="1" tabindex="20" maxlength=1 value="" class="input" name="nwp2" style="width:30px;text-align:center;">';
		} elseif ($count == 3) {
			if ( ($key - 1) != $n_char[$count - 1] ) {
				for ( $i = $n_char[2] + 1; $i < $key; $i++ ) {
					$text .= $img;
				}
			}
			@$numb[$key] ? $label_3 = $key . $numb[$key] : $label_3 = $key . 'th';
			$text .= '<input type="password" size="1" tabindex="20" maxlength=1 value="" class="input" name="nwp3" style="width:30px;text-align:center;">';
		}
		$count++;
	}
	// save into session :
	$_SESSION['NINJAWPASS'] = $n_char[1] . ':' . $n_char[2] . ':' . $n_char[3];

	// display it all :
	echo '
	<p>
		<label>Enter the <b>' . $label_1 . '</b>, <b>' . $label_2 . '</b> and <b>' .
		$label_3 . '</b> characters of your NinjaWPass password&nbsp;:</label>
		<br><center>' . $text. '</center>';

}

// generate our 3 random characters :
function random_num( $ninjawpass ) {
	$count = 1;
	$ran_values = array();
	while ( $count < 4 ) {
		$tmp = mt_rand( 1, strlen($ninjawpass) );
		if (! $ran_values[$tmp] ) {
			$ran_values[$tmp] = 1;
			$count++;
		}
	}
	return $ran_values;
}

/* End login form hook ============================================ */




/* Check whether an IP is banned or not =========================== */

function check_login_attempt( $ban_time, $failed_count, $max_seconds ) {

	// is brute-force attack protection enabled ?
	if ( (! $ban_time ) || (! $failed_count ) || (! $max_seconds ) ||
	(! isset($_SESSION['login_attempt'])) ) {
		return 0;
	}

	list( $tmp_failed_count, $tmp_start_time ) = explode( ':', $_SESSION['login_attempt'] . ':' );

	// reset it, if the banning period has expired :
	if ( ( time() - $tmp_start_time ) >  $ban_time * 60) {
		unset( $_SESSION['login_attempt'] );
		return 0;
	}

	// banned ?
	if ( $tmp_failed_count > $failed_count ) {
		if ( $tmp_failed_count == $failed_count + 1 ) {
			// update ban period with last attempt time :
			$_SESSION['login_attempt'] = $tmp_failed_count + 1 . ':' . time();
		}
		unset( $_SESSION['NINJAWPASS'] );
		return 1;
	}
	// OK, let it go :
	return 0;
}

/* End check whether an IP is banned or not ======================= */




/* Authentication functions ======================================= */

// hook into the authenticate filter :
add_filter( 'authenticate', 'ninjawp_auth_hook', 10, 3 );

// check the password :
function ninjawp_auth_hook( $user, $username, $password ) {

	if ( ( $username ) && ( $password ) ) {
		// get our saved settings :
		$ninjawp_options = get_option( 'ninjawp_options' );
		if (! $ninjawpass = base64_decode( $ninjawp_options['ninjawpass'] ) ) {
			return false;
		}

		// is that IP banned ?
		if ( check_login_attempt( $ninjawp_options['ban_time'] ,
			$ninjawp_options['failed_count'], $ninjawp_options['max_seconds'] ) ) {
			// we can break rules, too :
			wp_die('<p align=center><strong>ERROR :</strong> Too many failed attempts !<br />You are banned for a few minutes...</p>', '', array( 'response' => 403 ) );
		}

		// fetch the 3 characters :
		$nwp1 = @$_POST['nwp1'];
		$nwp2 = @$_POST['nwp2'];
		$nwp3 = @$_POST['nwp3'];

		// get the requested characters from the SESSION :
		list( $first, $second, $third ) = @split (':', $_SESSION['NINJAWPASS'] . ':');

		// check if Okay :
		if ( ( $nwp1 !== $ninjawpass[$first-1] ) || ( $nwp2 !== $ninjawpass[$second-1] ) ||
			( $nwp3 !== $ninjawpass[$third-1] ) ) {
			$user = new WP_Error( 'denied', __('<strong>ERROR</strong>: Wrong NinjaWPass password&nbsp;!') );
			// stop authentication action :
			remove_action('authenticate', 'wp_authenticate_username_password', 20);
			// shake the box and output error message :
			add_filter('shake_error_codes', 'ninja_err_shake');

			// record the failed login attempt :
			if ( isset( $_SESSION['login_attempt'] ) ) {
				list( $tmp_failed_count, $tmp_start_time ) = explode( ':', $_SESSION['login_attempt'] . ':' );
				if ( ( time() - $tmp_start_time ) >  $ninjawp_options['max_seconds'] ) {
					// reset counter if the failed attempt wasn't within the time range :
					$_SESSION['login_attempt'] = '1:' . time();
				} else {
					// otherwise, increment it :
					$_SESSION['login_attempt'] = ( $tmp_failed_count + 1 ) . ':' . $tmp_start_time;
					}
			} else {
				$_SESSION['login_attempt'] = '1:' . time();
			}

			return $user;
		}
		// it looks like a good guy, we clear the failed login attempts counter :
		if ( isset( $_SESSION['login_attempt'] ) ) {
			unset( $_SESSION['login_attempt'] );
		}
	}
	// let it go :
	return null;
}

// shake the login box :
function ninja_err_shake( $shake_codes ) {
	$shake_codes[] = 'denied';
	return $shake_codes;
}

/* End authentication functions =================================== */




/* Login alert ==================================================== */

add_action('wp_login','ninjawp_login_alert');

function ninjawp_login_alert() {

	$ninjawp_options = get_option( 'ninjawp_options' );

	// send alert by email only if requested by the admin :
	if ( $ninjawp_options['login_alert'] ) {
		if ( $_SERVER['SERVER_PORT'] == 443 ) {
			$http = 'https';
		} else {
			$http = 'http';
		}
		$subject = '[NinjaWPass] WordPress admin console login';
		$message = 'Someone just logged in to your WordPress admin console:' . "\n\n".
					'- IP   : ' . $_SERVER['REMOTE_ADDR'] . "\n" .
					'- Date : ' . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n" .
					'- URL  : ' . $http . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "\n";
		wp_mail( $ninjawp_options['login_email'], $subject, $message );
	}
}

/* End login alert ================================================ */





/* Admin menu and settings ======================================== */

add_action('admin_menu', 'ninjawp_admin_menu');

function ninjawp_admin_menu() {
	add_submenu_page('options-general.php', 'NinjaWPass', 'NinjaWPass', 10, __FILE__, 'ninjawp_submenu');
}

function ninjawp_submenu() {

	$ninjawp_options = get_option( 'ninjawp_options' );
	$ninjawpass = base64_decode( $ninjawp_options['ninjawpass'] );

	if ( isset( $_POST['nwp_act'] ) ) {

		$nwp_pass0 = @$_POST['nwp_pass0'];
		$nwp_pass1 = @$_POST['nwp_pass1'];
		$nwp_pass2 = @$_POST['nwp_pass2'];
		$nwp_error = 0;

		// current password :
		if ( ( ! $nwp_pass0 ) && ( $ninjawpass ) ) {
			echo '<div class="error settings-error"><p><strong>Please enter your current NinjaWPass password if you want to change any option.</strong></p></div>';
			$nwp_error++;
		} elseif ( ( preg_match( '/^.{10,30}$/', $ninjawpass ) ) && ( $ninjawpass !== $nwp_pass0 ) ) {
			echo '<div class="error settings-error"><p><strong>Your current NinjaWPass password is invalid, please try again.</strong></p></div>';
			$nwp_error++;
		} else {
			// new password ?
			if ( ( $nwp_pass1 ) || ( $nwp_pass2 ) ) {
				// obviously, they should match :
				if ( $nwp_pass1 !== $nwp_pass2 ) {
					echo '<div class="error settings-error"><p><strong>The 2 new password fields do not match. Please try again.</strong></p></div>';
					$nwp_error++;
				} elseif (! preg_match( '/^.{10,30}$/', $nwp_pass1 ) ) {
					// min 10 char, max 30 char :
					echo '<div class="error settings-error"><p><strong>The new password must be from 10 to 30 characters. Please try again.</strong></p></div>';
					$nwp_error++;
				} else {
					echo '<div class="updated settings-error"><p><strong>Your NinjaWPass password was successfully ';
					if (! $ninjawp_options['ninjawpass'] ) {
						echo 'created.</strong></p></div>';
					}else {
						echo 'changed.</strong></p></div>';
					}
					// don't display the 'Options were changed' message :
					$nwp_error++;
					$ninjawp_options['ninjawpass'] = base64_encode( $nwp_pass1 );
				}
			}

			// check and validate email, if any :
			$nwp_login_email = @$_POST['nwp_login_email'];
			if ( $nwp_login_email ) {
				if ( $nwp_login_email != sanitize_email( $nwp_login_email ) ) {
					echo '<div class="error settings-error"><p><strong>Your email address does not seem to be valid.</strong></p></div>';
					$nwp_error++;
				} else {
					$ninjawp_options['login_email'] = $nwp_login_email;
				}
			} else {
				$ninjawp_options['login_email'] = '';
			}

			// if login alert is enabled, ensure we have an email address :
			$ninjawp_options['login_alert'] = 0;
			if ( @$_POST['nwp_login_alert'] == 1 ) {
				if (! $nwp_login_email ) {
					echo '<div class="error settings-error"><p><strong>Please enter an email address if you want to enable the login alert.</strong></p></div>';
					$nwp_error++;
				} else {
					$ninjawp_options['login_alert'] = 1;
				}
			}

			// IP banning :
			if ((! @preg_match( '/^(?:[1-9]|[1-9][0-9])$/', $_POST['ban_time']) ) ||
				 (! @preg_match( '/^(?:[1-9]|[1-9][0-9])$/', $_POST['failed_count']) ) ||
				 (! @preg_match( '/^(?:[1-9]|[1-9][0-9])$/', $_POST['max_seconds']) ) ) {
				$ninjawp_options['ban_time']     = 0;
				$ninjawp_options['failed_count'] = 0;
				$ninjawp_options['max_seconds']  = 0;
			} else {
				$ninjawp_options['ban_time']     = $_POST['ban_time'];
				$ninjawp_options['failed_count'] = $_POST['failed_count'];
				$ninjawp_options['max_seconds']  = $_POST['max_seconds'];
			}

		}
		if (! $nwp_error ) {
			echo '<div class="updated settings-error"><p><strong>Options were changed.</strong></p></div>';
		}
		update_option('ninjawp_options', $ninjawp_options);

	} else { // nwp_act == 1
		if (! $ninjawpass ) {
			echo '<div class="error settings-error"><p><strong>You do not have a NinjaWPass password yet. Please create one below.</strong></p></div>';
		}
	}
	// refresh options
	$ninjawp_options = get_option( 'ninjawp_options' );

?>
<div class="wrap">
	<div id="icon-ms-admin" class="icon32"></div>
	<h2>NinjaWPass options</h2>
	<br />

	<script type="text/javascript">
	function is_number(id) {
		var e = document.getElementById(id);
		if (! e.value ) { return }
		if (! /^[0-9]+$/.test(e.value) ) {
			alert("Please enter only number from 1 to 99 (0 or blank to disable).");
			e.value = e.value.substring(0, e.value.length-1);
		}
	}
	</script>

	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" >
	<table class="form-table">
		<?php
		// if there is already a pass, user must enter it too:
		if ( $ninjawp_options['ninjawpass'] ) {
		?>
		<tr valign="top">
			<th>NinjaWPass password :</th>
			<td style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
				<input type="password" name="nwp_pass0" size="20" maxlength="30" value="" autocomplete="off" /> <span class="description">Enter your current password if you want to change any option below.</span>
			</td>
		</tr>

		<tr valign="top"><td>&nbsp;</td></tr>

		<tr valign="top">
			<th>Change your password :</th>
		<?php
		} else {
		?>
		<tr valign="top">
			<th>Create a password :</th>
		<?php
		}
		?>
			<td>
				<input type="password" name="nwp_pass1" size="20" maxlength="30" value="" autocomplete="off" /> <span class="description">
			<?php
			if ( $ninjawp_options['ninjawpass'] ) {
				echo 'Enter your new password; it ';
			} else {
				echo 'Your password ';
			}
			?>
			 must be from 10 to 30 characters.</span>
			 <br />
			<input type="password" name="nwp_pass2" size="20" maxlength="30" value="" autocomplete="off" /> <span class="description">Type your new password again.</span>
			</td>
		</tr>

		<tr valign="top">
			<th>Admin login alert :</th>
			<td>
				<?php
				if (! $ninjawp_options['ninjawpass'] ) {
					// enable login alert by default if first run :
					$ninjawp_options['login_alert'] = 1;
				}
				echo '<label>Yes <input type="radio" value="1" name="nwp_login_alert"';
				if ( $ninjawp_options['login_alert'] ) {
					echo ' checked="checked"';
				}
				echo ' /></label>&nbsp;&nbsp;&nbsp;&nbsp;<label>No <input type="radio" value="0" name="nwp_login_alert"';
				if (! $ninjawp_options['login_alert'] ) {
					echo ' checked="checked"';
				}
				echo ' />';
				?>
				</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="description">Do you wish to receive an email whenever someone logs in to your WordPress admin console&nbsp;?</span>
				<br />
				<input type="text" name="nwp_login_email" size="20" maxlength="250" value="<?php
				if ( $ninjawp_options['login_email'] ) {
					echo $ninjawp_options['login_email'];
				} else {
					echo get_option('admin_email');
				}
				?>" />&nbsp;<span class="description">Type your email address where the login alert should be sent to.</span><br />
			</td>
		</tr>

		<tr valign="top">
			<th>Brute-force protection :</th>
			<td>
			Ban for <input type=text maxlength=2 class="small-text" value="<?php echo $ninjawp_options['ban_time'] ?>" name='ban_time' id='ban1' onkeyup="is_number('ban1')"> minutes any IP with more than <input type=text maxlength=2 class="small-text" value="<?php echo $ninjawp_options['failed_count'] ?>" name='failed_count' id='ban2' onkeyup="is_number('ban2')"> failed login attempts within <input type=text maxlength=2 class="small-text" value="<?php echo $ninjawp_options['max_seconds'] ?>" name='max_seconds' id='ban3' onkeyup="is_number('ban3')"> seconds.<br /><span class="description">Leave fields empty if you don't want to enable brute-force protection.</span>
			</td>
		</tr>
	</table>
	<br>
	<input class="button-primary" type="submit" name="Save" value="Save options" />
	<input type="hidden" name="nwp_act" value="1" />
	</form>
	<div style="margin:10px 0 10px; text-align:center;"><small>NinjaWPass v<?php echo NINJAWP_VERSION ?> by <a href="http://nintechnet.com/" target="_blank" title="The Ninja Technologies Network">NinTechNet</a> - The Ninja Technologies Network<br />[&nbsp;&nbsp;<a href="http://ninjafirewall.com/" target="_blank" title="Powerful firewall software for WordPress and all your PHP applications">NinjaFirewall</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="http://ninjarecovery.com/" target="_blank" title="Incident response, malware removal &amp; hacking recovery">NinjaRecovery</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="http://ninjamonitoring.com/" target="_blank" title="Monitor your website for suspicious activities">NinjaMonitoring</a>&nbsp;&nbsp;]</small></div>
</div>
<?php
}


// settings link
add_filter('plugin_action_links', 'ninjawp_settings_link', 10, 2);

function ninjawp_settings_link( $links, $file ) {
	static $this_plugin;
	if (! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page='. plugin_basename( __FILE__ ) .'">' . __('Settings', 'ninjawpass.php') . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

/* End admin menu and settings ==================================== */


// EOF //
?>
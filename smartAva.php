<?php
/**
 * Plugin Name: smartAva
 * Description: Protects users from tracking by obfuscating the e-mail hash used for gravatar.com avatars. Supports white-lists of domains and e-mail addresses you do not want obfuscated.
 * Plugin URI: http://wordpress.org/plugins/smartava/
 * Version: 0.4
 * Author: Alice Wonder
 * License: GPL2+

    This program is free software; you can redistribute it and/or modify
 */
 
 /*  Copyright 2013  Alice Wonder  (email : alicewonder@shastaherps.org)
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    ---
    
    The function get_avatar() is lifted from the pluggable.php file in the
    3.7.1 version of WordPress and is (c) it's respective authors. One
    minor modification was made to that function.
*/

function smartAvaIDN2Puny($domain) {
	if(function_exists('idn_to_ascii')) {
		$domain=idn_to_ascii($domain);
		}
	return($domain);
	}
	
function smartAvaPuny2IDN($domain) {
	if(function_exists('idn_to_utf8')) {
		$domain=idn_to_utf8($domain);
		}
	return($domain);
	}
	
function smartAvaEmail2Puny($email) {
	//warning - if argument does not have exactly 1 @ it will return argument.
	if(substr_count($email, '@') == 1) {
		$tmp=explode('@', $email);
		$email=$tmp[0] . '@' . smartAvaIDN2Puny($tmp[1]);
		}
	return($email);
	}
 
//for the blog footer
function smartAvaFooter() {
	echo('<div style="text-align: center;">' . __( 'Anonymity protected with' ) . ' <a href="http://wordpress.org/plugins/smartava/" target="_blank">smartAva</a></div>');
	return;
	}
	
//adds domains to white-list
function smartAvaAddDomains($input) {
	$error=array();
	if(! $domains=get_option('smartAvaDomains')) {
		$domains=array();
		}
	$n=sizeof($domains);
	$newDomains=explode(';', $input);
	$j=sizeof($newDomains);
	for($i=0;$i<$j;$i++) {
		$domain=trim(strtolower($newDomains[$i]));
		$domain=smartAvaIDN2Puny($domain);
		$test='user@' . $domain;
		if(filter_var($test, FILTER_VALIDATE_EMAIL)) {
			$domains[]=$domain;
			} else {
			$error[]='The domain <code>' . $domain . '</code> is not a valid domain name.';
			}
		}
	$domains=array_values(array_unique($domains));
	$m=sizeof($domains);
	if($m > $n) {
		update_option('smartAvaDomains', $domains);
		}
	return($error);
	}
	
//adds e-mail addresses to white-list
function smartAvaAddAddresses($input) {
	$error=array();
	if(! $addys=get_option('smartAvaAddys')) {
		$addys=array();
		}
	$n=sizeof($addys);
	$newAddys=explode(';', $input);
	$j=sizeof($newAddys);
	for($i=0;$i<$j;$i++) {
		$address=trim(strtolower($newAddys[$i]));
		$paddress=smartAvaEmail2Puny($address);
		if(filter_var($paddress, FILTER_VALIDATE_EMAIL)) {
			$addys[]=$paddress;
			} else {
			$error[]='The e-mail address <code>' . $address . '</code> is not a valid e-mail address.';
			}
		}
	$addys=array_values(array_unique($addys));
	$m=sizeof($addys);
	if($m > $n) {
		update_option('smartAvaAddys',$addys);
		}
	return($error);
	}
	
function smartAvaRemoveDomains($input) {
	if(! $domains=get_option('smartAvaDomains')) {
		$domains=array();
		}
	$n=sizeof($domains);
	$remove=array();
	$remList=explode(';',$input);
	$j=sizeof($remList);
	for($i=0;$i<$j;$i++) {
		$domain=trim(strtolower($remList[$i]));
		if(strlen($domain) > 0) {
			$remove[]=$domain;
			}
		}
	$domains=array_values(array_diff($domains,$remove));
	$m=sizeof($domains);
	if ($n > $m) {
		update_option('smartAvaDomains', $domains);
		}
	}
	
function smartAvaRemoveAddresses($input) {
	if(! $addys=get_option('smartAvaAddys')) {
		$addys=array();
		}
	$n=sizeof($addys);
	$remove=array();
	$remList=explode(';',$input);
	$j=sizeof($remList);
	for($i=0;$i<$j;$i++) {
		$addy=trim(strtolower($remList[$i]));
		if(strlen($addy) > 0) {
			$remove[]=$addy;
			}
		}
	$addys=array_values(array_diff($addys,$remove));
	$m=sizeof($addys);
	if ($n > $m) {
		update_option('smartAvaAddys', $addys);
		}
	}
 
//generates the salts used
function smartAvaSaltShaker() {
	$alphabet='abcdefghijklmnopqrstuvwxyz 0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZ ~!@#$%^&*(-_=+{}[]:;,./<>?|';
	$alphLength=strlen($alphabet);
	$j=rand(4,7);
	for($i=0;$i<$j;$i++) {
		$alphabet=str_shuffle($alphabet);
		}
	$salt='';
	$j=rand(65,127);
	$max=$alphLength - 1;
	for($i=0;$i<$j;$i++) {
		$pos=rand(0, $max);
		$salt=$salt . substr($alphabet,$pos,1);
		}
	return(trim($salt));
	}

function smartAvaHash($email) {
	if(! $salt=get_option('smartAvaSalt')) {
		$salt=array();
		$salt[]=smartAvaSaltShaker();
		$salt[]=smartAvaSaltShaker();
		update_option('smartAvaSalt',$salt);
		}
	if(! $domains=get_option('smartAvaDomains')) {
		$domains=array();
		}	
	if(! $addys=get_option('smartAvaAddys')) {
		$addys=array();
		}	
	$addys[]='unknown@gravatar.com'; //no need to obfuscate that one
	
	$email=trim(strtolower($email));
	$pemail=smartAvaEmail2Puny($email);
	//validate email
	if(!filter_var($pemail, FILTER_VALIDATE_EMAIL)) { //hopefully wordpress already has validated this but...
		$pemail='unknown@gravatar.com';
		}
	$foo=explode('@', $pemail);
	$domino=$foo[1]; //this is domain part of @domain
	$qq=0;
	//check for white-listed domain
	$j=sizeof($domains);
	for ($i=0;$i<$j;$i++) {
		$test=trim(strtolower($domains[$i]));
		$dummy='user@' . $test;
		if(filter_var($dummy, FILTER_VALIDATE_EMAIL)) {
			//check for exact match first
			if(strcasecmp($domino, $test) == 0) {
				$qq++;
				} else {
				$domino='.' . $domino; //for testing if $test is subdomain
				$qq = $qq + substr_count($domino, $test); //any matches and $qq is no longer 0
				}
			}
		}
	
	//check for white-listed address
	if ($qq == 0) {
		$j=sizeof($addys);
		for ($i=0;$i<$j;$i++) {
			$test=trim(strtolower($addys[$i]));
			if(strcasecmp($test, $pemail) == 0) {
				$qq++; //any match and $qq is no longer 0
				}
			}
		}
	
	if ($qq == 0) {
		$obf=hash('sha256', $salt[0] . $email);
		return(md5($salt[1] . $obf)); //obfuscate
		} else {
		return(md5($email)); //this means there was a white-list match, don't obfuscate
		}
	}
	
// admin interface functions

function smartAvaAdmDomainMenu() {
	echo("<div>\n");
	echo('<h2 style="font-variant: small-caps;">Domain White-List Management</h2>' . "\n");
	echo("<p>E-Mail addresses at domains in your white-list will not have their MD5 hash of their e-mail address obfuscated. If these users at white-listed domains have gr*vatar.com accounts, their custom avatars will be used with their comments. Whether or not they have gr*vatar.com accounts, the MD5 hash of their e-mail address will be public information. Please do not white-list domains you or the company you work for do not control.</p>\n");
	echo("<h3>Add Domain to White-List</h3>\n");
	echo("<p>If entering more than one domain, separate domains with a semi-colon ; character.</p>\n");
	
	echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
	echo('<th scope="row"><label for="addDomains">Domains to White-List</label></th>' . "\n");
	
	echo('<td><input type="text" id="addDomains" name="addDomains" size="64" title="Enter domains to white-list" autocomplete="off" /></td>' . "\n");
	echo('</tr></table>' . "\n");
	
	if(! $domains=get_option('smartAvaDomains')) {
		$domains=array();
		}	
	$j=sizeof($domains);
	if($j != 0) {
		echo("<h3>Remove Existing Domains</h3>\n");
		echo("<p>If you wish to remove an existing domain from the white-list, check the box next to the domain name.</p>\n");
		
		echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
		echo('<th scope="row">Domains to Remove</th>' . "\n");	
		echo('<td>' . "\n" . '<fieldset><legend class="screen-reader-text"><span>Domains to Remove</span></legend>');
		
		sort($domains);
		$search=array(); $replace=array();
		$search[]='/\./'; $replace[]='_DOT_';
		for($i=0;$i<$j;$i++) {
			$name='del_' . preg_replace($search, $replace, $domains[$i]);
			$label=smartAvaPuny2IDN($domains[$i]);
			echo('   <input type="checkbox" name="' . $name . '" value="T" id="' . $name . '" /><label for="' . $name . '" title="' . $domains[$i] . '"> ' . $label . "</label><br />\n");
			}
		echo("</fieldset>\n</td></tr></table>");
		}
	echo("</div>\n");
	return;
	}
	
function smartAvaAdmAddressMenu() {
	echo("<div>\n");
	echo('<h2 style="font-variant: small-caps;">E-Mail White-List Management</h2>' . "\n");
	echo("<p>E-Mail addresses in your white-list will not have their MD5 hash of their e-mail address obfuscated. If users with white-listed e-mail addresses have gr*vatar.com accounts, their custom avatars will be used with their comments. Whether or not they have gr*vatar.com accounts, the MD5 hash of their e-mail address will be public information. Please do not white-list e-mail addresses without consent of the user.</p>");
	echo("<h3>Add E-Mail Address to White-List</h3>\n");
	echo("<p>If entering more than one e-mail address, separate them with a semi-colon ; character.</p>\n");
	
	echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
	echo('<th scope="row"><label for="addEmails">New Addresses to White-List</label></th>' . "\n");
	
	echo('<td><input type="text" id="addEmails" name="addEmails" size="64" title="Enter e-mail addresses to white-list" autocomplete="off" /></td>' . "\n");
	echo('</tr></table>' . "\n");
	
	if(! $addys=get_option('smartAvaAddys')) {
		$addys=array();
		}
	$j=sizeof($addys);
	if($j != 0) {
		echo("<h3>Remove Existing E-Mail Addresses</h3>\n");
		echo("<p>If you wish to remove an e-mail address from the white-list, check the box next to the e-mail address.</p>\n");
		
		echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
		echo('<th scope="row">Addresses to Remove</th>' . "\n");	
		echo('<td>' . "\n" . '<fieldset><legend class="screen-reader-text"><span>Addresses to Remove</span></legend>');
		
		sort($addys);
		$search=array(); $replace=array();
		$search[]='/@/'; $replace[]='_AT_';
		$search[]='/\./'; $replace[]='_DOT_';
		for($i=0;$i<$j;$i++) {
			$name='del_' . preg_replace($search, $replace, $addys[$i]);
			$tmp=explode('@', $addys[$i]);
			$label=$tmp[0] . '@' . smartAvaPuny2IDN($tmp[1]);
			echo('   <input type="checkbox" name="' . $name . '" value="T" id="' . $name . '" /><label for="' . $name . '" title="' . $addys[$i] . '"> ' . $label . "</label><br />\n");
			}
		echo("</fieldset>\n</td></tr></table>");
		}
	echo("</div>\n");
	return;
	}
	
function smartAvaAdmSalty() {
	echo("<div>\n");
	echo('<h2 style="font-variant: small-caps;">Obfuscation Salts</h2>' . "\n");
	echo("<p>A salt is a randomized string of gibberish that is often used when obfuscating a hash to thwart <a href=\"https://en.wikipedia.org/wiki/Rainbow_table\" target=\"_blank\">Rainbow Table</a> attacks. If the attacker does not know the value of the salt, the attacker can not generate a table of hashes that will correspond to the hash you use. The smartAva plugin uses two salts in the obfuscation of e-mail address hashes.</p>\n");
	echo("<p>It is suggested you allow smartAva to generate the salts for you. If you run multiple blogs and you want your users to have the same obfuscated hash between blogs, then you can manually create the salts. If you do so, make sure they are at least 18 characters long and are made up using an arrangement of many different characters.</p>\n");
	echo('<p>The salts currently being used by your install of smartAva:</p>' . "\n" . '<div style="background-color: #cccccc; padding: 1em;">');
	echo('<ol style="font-family: monospace;">' . "\n");
	if(! $salt=get_option('smartAvaSalt')) {
		$salt=array();
		$salt[]=smartAvaSaltShaker();
		$salt[]=smartAvaSaltShaker();
		update_option('smartAvaSalt',$salt);
		}
	$search=array(); $replace=array();
	$search[]='/&/'; $replace[]='&amp;';
	$search[]='/</'; $replace[]='&lt;';
	$search[]='/>/'; $replace[]='&gt;';
	$aa=preg_replace($search, $replace, $salt[0]);
	$bb=preg_replace($search, $replace, $salt[1]);
	echo('<li>' . $aa . '</li>' . "\n" . '<li>' . $bb . '</li>' . "\n</ol>\n</div>");
	
	echo("<h3>Regenerate Salts</h3>\n");
	echo("<p>If for some reason you wish to regenerate the salts, check the box below:</p>\n");
	
	echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">Random Salts</th>' . "\n");	
	
	echo('<td><input type="checkbox" name="smartAvaRegenSalts" id="smartAvaRegenSalts" value="T" /><label for="smartAvaRegenSalts"> Regenerate Salts</label></td>' . "\n");
	echo("</tr>\n</table>");
	
	echo("<h3>Custom Salts</h3>\n");
	echo("<p>If you wish to manually create your own salts, place your salt strings in the two input fields below. They must be at least 18 characters in length.</p>\n");
	
	echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">Custom Salts</th>' . "\n");	
	echo('<td><fieldset><legend class="screen-reader-text"><span>Custom Salts</span></legend>');
	echo('<input type="text" id="smartAvaSaltOne" name="smartAvaSaltOne" size="64" title="Enter a salt string at least 18 characters long." autocomplete="off" /><br />' . "\n");
	echo('<label for="smartAvaSaltOne">First Custom Salt</label><br />&#160;<br />' . "\n");
	echo('<input type="text" id="smartAvaSaltTwo" name="smartAvaSaltTwo" size="64" title="Enter another salt string at least 18 characters long." autocomplete="off" /><br />' . "\n");
	echo('<label for="smartAvaSaltTwo">Second Custom Salt</label>' . "\n");
	echo("</fieldset>\n</td>\n</tr>\n</table>");
	
	echo("</div>\n");
	}
	
function smartAvaAdmNotify() {
	echo("<div>\n");
	echo('<h2 style="font-variant: small-caps;">User Notification</h2>' . "\n");
	echo("<p>You can notify users of your blog that you are using smartAva by checking the box below. I would greatly appreciate this, and it will also let your users know that you care about their privacy and that their e-mail hash will be obfuscated when they post a comment so that they can not be tracked.</p>\n");
	echo("<p>If you check the box, the following notice will appear in the footer of your pages:</p>");
	smartAvaFooter(); echo("\n");
	
	echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">User Notification</th>' . "\n");
	if($footerPermission=get_option('smartAvaFooter')) {
		$checked=' checked="checked"';
		} else {
		$checked='';
		}
	echo('<td><input type="checkbox" name="avaSmartNotify" value="T" id="avaSmartNotify"' . $checked . ' /><label for="avaSmartNotify"> Allow Notification to Users of smartAva usage</label></td>' . "\n");
	echo("</tr>\n</table>");
	
	echo("</div>\n");
	}
	
function smartAvaProcessForm() {
	$error=array();
	//remove white-list domains
	if(! $domains=get_option('smartAvaDomains')) {
		$domains=array();
		}	
	$j=sizeof($domains);
	$remove=array();
	$search=array(); $replace=array();
	$search[]='/\./'; $replace[]='_DOT_';
	for ($i=0;$i<$j;$i++) {
		$test='del_' . preg_replace($search, $replace, $domains[$i]);
		if(isset($_POST[$test])) {
			$remove[]=$domains[$i];
			}
		}
	if (sizeof($remove) > 0) {
		//should have just had the function take array argument - fix before 1.0
		$reList=implode(';', $remove);
		smartAvaRemoveDomains($reList);
		}
	
	//add white-list domains
	if(isset($_POST['addDomains'])) {
		$domainsToAdd=trim(urldecode($_POST['addDomains']));
		if (strlen($domainsToAdd) > 0) {
			$ee=smartAvaAddDomains($domainsToAdd);
			if((sizeof($ee)) > 0) {
				$error = array_merge($error, $ee);
				}
			}
		}
		
	//remove white-list e-mail addresses
	if(! $addys=get_option('smartAvaAddys')) {
		$addys=array();
		}
	$j=sizeof($addys);
	$remove=array();
	$search=array(); $replace=array();
	$search[]='/@/'; $replace[]='_AT_';
	$search[]='/\./'; $replace[]='_DOT_';
	for($i=0;$i<$j;$i++) {
		$test='del_' . preg_replace($search, $replace, $addys[$i]);
		if(isset($_POST[$test])) {
			$remove[]=$addys[$i];
			}
		}
	if (sizeof($remove) > 0) {
		$reList=implode(';', $remove);
		smartAvaRemoveAddresses($reList);
		}
	
	//add white-listed e-mails
	if(isset($_POST['addEmails'])) {
		$emailsToAdd=trim(urldecode($_POST['addEmails']));
		if (strlen($emailsToAdd) > 0) {
			$ee=smartAvaAddAddresses($emailsToAdd);
			if((sizeof($ee)) > 0) {
				$error = array_merge($error, $ee);
				}
			}
		}
		
	//salts
	$nsalt=array();
	if(isset($_POST['smartAvaRegenSalts'])) {
		delete_option('smartAvaSalt');
		}	

	if(isset($_POST['smartAvaSaltOne'])) {
		$sone=trim($_POST['smartAvaSaltOne']);
		} else {
		$sone='';
		}
	if(isset($_POST['smartAvaSaltTwo'])) {
		$stwo=trim($_POST['smartAvaSaltTwo']);
		} else {
		$stwo='';
		}
		
	if(strlen($sone) > 0) {
		if(strlen($sone) > 17) {
			$nsalt[]=$sone;
			} else {
			$error[]='First custom salt is too short. It must be at least 18 characters long.';
			}
		}
		
	if(strlen($stwo) > 0) {
		if(strlen($stwo) > 17) {
			$nsalt[]=$stwo;
			} else {
			$error[]='Second custom salt is too short. It must be at least 18 characters long.';
			}
		}
	if(sizeof($nsalt) == 1) {
		$error[]='If using custom salts, you need two custom salts, each at least 18 characters long.';
		}
	if(sizeof($nsalt) == 2) {
		update_option('smartAvaSalt', $nsalt);
		}
		
	//notify
	if(isset($_POST['avaSmartNotify'])) {
		update_option('smartAvaFooter', 't');
		} else {
		delete_option('smartAvaFooter');
		}
	$j=sizeof($error);
	if ($j > 0) {
		if($j == 1) {
			echo('<div class="error">' . "\n<p>The following error occurred:</p><ol>");
			} else {
			echo('<div class="error">' . "\n<p>The following errors occurred:</p><ol>");
			}
		for($i=0;$i<$j;$i++) {
			echo('<li>' . $error[$i] . '</li>' . "\n");
			}
		echo("</ol>\n</div>\n");
		}
	echo('<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div>' . "\n");
	} //end of form processing function
	
function smartAvaAdminOptions() {
	if(!current_user_can('manage_options')) {
		wp_die( __( 'What does the fox say?' ));
		}
	if(isset($_POST['smartAvaAuthKey'])) {
		$chk=trim($_POST['smartAvaAuthKey']);
		$key=trim(get_option('smartAvaAuthKey'));
		if(strcmp($chk, $key) == 0) {
			smartAvaProcessForm();
			}
		}
	echo('<div class="wrap">' . "\n");
	echo('<div id="icon-options-general" class="icon32"><br /></div><h2>Gr*vatar Obfuscation Administration</h2>' . "\n");
	echo('<form id="smartAvaForm" method="post" action="options-general.php?page=smartAva">' . "\n");
	$data=smartAvaSaltShaker();
	$key=hash('sha256', $data);
	update_option('smartAvaAuthKey', $key);
	echo('<input type="hidden" name="smartAvaAuthKey" id="smartAvaAuthKey" value="' . $key . '" />' . "\n");	
	
	smartAvaAdmDomainMenu();
	smartAvaAdmAddressMenu();
	smartAvaAdmSalty();
	smartAvaAdmNotify();
	
	echo('<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" /></p>');
	echo("</form>\n");
	echo("</div>\n");
	}
	
function smartAvaAdminMenu() {
	add_options_page('smartAva Administration', 'smartAva', 'manage_options', 'smartAva', 'smartAvaAdminOptions');
	//add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
	}

add_action( 'admin_menu', 'smartAvaAdminMenu' );



////////////////////////


if ( !function_exists( 'get_avatar' ) ) :
if($footerPermission=get_option('smartAvaFooter')) {
		add_action('wp_footer', 'smartAvaFooter');
		}

//below is direct from wordpress 3.7.1 pluggable.php with one line changed.
// It's not my style, I prefer DOMDocument to create HTML element nodes. Ah well.
/**
 * Retrieve the avatar for a user who provided a user ID or email address.
 *
 * @since 2.5
 * @param int|string|object $id_or_email A user ID,  email address, or comment object
 * @param int $size Size of the avatar image
 * @param string $default URL to a default image to use if no avatar is available
 * @param string $alt Alternative text to use in image tag. Defaults to blank
 * @return string <img> tag for the user's avatar
*/
function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false ) {
	if ( ! get_option('show_avatars') )
		return false;

	if ( false === $alt)
		$safe_alt = '';
	else
		$safe_alt = esc_attr( $alt );

	if ( !is_numeric($size) )
		$size = '96';

	$email = '';
	if ( is_numeric($id_or_email) ) {
		$id = (int) $id_or_email;
		$user = get_userdata($id);
		if ( $user )
			$email = $user->user_email;
	} elseif ( is_object($id_or_email) ) {
		// No avatar for pingbacks or trackbacks
		$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
		if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
			return false;

		if ( !empty($id_or_email->user_id) ) {
			$id = (int) $id_or_email->user_id;
			$user = get_userdata($id);
			if ( $user)
				$email = $user->user_email;
		} elseif ( !empty($id_or_email->comment_author_email) ) {
			$email = $id_or_email->comment_author_email;
		}
	} else {
		$email = $id_or_email;
	}

	if ( empty($default) ) {
		$avatar_default = get_option('avatar_default');
		if ( empty($avatar_default) )
			$default = 'mystery';
		else
			$default = $avatar_default;
	}

	if ( !empty($email) )
		//$email_hash = md5( strtolower( trim( $email ) ) ); //##### THIS IS WHAT I MODIFIED #####
		$email_hash = smartAvaHash($email);

	if ( is_ssl() ) {
		$host = 'https://secure.gravatar.com';
	} else {
		if ( !empty($email) )
			$host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
		else
			$host = 'http://0.gravatar.com';
	}

	if ( 'mystery' == $default )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
	elseif ( 'blank' == $default )
		$default = $email ? 'blank' : includes_url( 'images/blank.gif' );
	elseif ( !empty($email) && 'gravatar_default' == $default )
		$default = '';
	elseif ( 'gravatar_default' == $default )
		$default = "$host/avatar/?s={$size}";
	elseif ( empty($email) )
		$default = "$host/avatar/?d=$default&amp;s={$size}";
	elseif ( strpos($default, 'http://') === 0 )
		$default = add_query_arg( 's', $size, $default );

	if ( !empty($email) ) {
		$out = "$host/avatar/";
		$out .= $email_hash;
		$out .= '?s='.$size;
		$out .= '&amp;d=' . urlencode( $default );

		$rating = get_option('avatar_rating');
		if ( !empty( $rating ) )
			$out .= "&amp;r={$rating}";

		$out = str_replace( '&#038;', '&amp;', esc_url( $out ) );
		$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	} else {
		$avatar = "<img alt='{$safe_alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
	}

	return apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
}
endif;
?>

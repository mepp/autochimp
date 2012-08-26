<?php
/*
Plugin Name: AutoChimp
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Description: Keeps MailChimp mailing lists in sync with your WordPress site.  Now supports Register Plus, Register Plus Redux, BuddyPress, and Cimy User Extra fields and allows you to synchronize all of your profile fields with MailChimp.  Gives users the ability to create MailChimp mail campaigns from blog posts with the flexibility of sending different categories to different lists and interest groups.  You can use your user-defined templates as well.
Author: Wanderer LLC Dev Team
Version: 2.02
*/

if ( !class_exists( 'MCAPI_13' ) )
{
	require_once 'inc/MCAPI.class.php';
}

define( "WP88_MC_APIKEY", "wp88_mc_apikey" );

// Mailing List
define( "WP88_MC_LISTS", "wp88_mc_selectedlists" );
define( "WP88_MC_ADD", "wp88_mc_add" );
define( "WP88_MC_DELETE", "wp88_mc_delete" );
define( "WP88_MC_UPDATE", "wp88_mc_update" );
define( 'WP88_MC_BYPASS_OPT_IN', 'wp88_mc_bypass_opt_in' );
define( 'WP88_MC_PERMANENTLY_DELETE_MEMBERS', 'wp88_mc_permanently_delete_member' );
define( 'WP88_MC_SEND_GOODBYE', 'wp88_mc_send_goodbye' );
define( 'WP88_MC_SEND_ADMIN_NOTIFICATION', 'wp88_mc_send_admin_notification' );
define( "WP88_MC_LAST_MAIL_LIST_ERROR", "wp88_mc_last_ml_error" );
define( "WP88_MC_MANUAL_SYNC_PROGRESS", "wp88_mc_ms_progress" );
define( "WP88_MC_MANUAL_SYNC_STATUS", "wp88_mc_ms_status" );

// Campaigns
define( "WP88_MC_CAMPAIGN_FROM_POST", "wp88_mc_campaign_from_post" );	// Unused as of 2.0
define( "WP88_MC_CAMPAIGN_CATEGORY", "wp88_mc_campaign_category" );		// Unused as of 2.0
define( "WP88_MC_CAMPAIGN_EXCERPT_ONLY", "wp88_mc_campaign_excerpt_only" );
define( "WP88_MC_CREATE_CAMPAIGN_ONCE", "wp88_mc_create_campaign_once" );
define( "WP88_MC_SEND_NOW", "wp88_mc_send_now" );
define( "WP88_MC_LAST_CAMPAIGN_ERROR", "wp88_mc_last_error" );
define( "WP88_MC_CAMPAIGN_CREATED", "wp88_mc_campaign" ); // Flags a post that it's had a campaign created from it.

// Plugin integration
define( 'WP88_MC_FIX_REGPLUS', 'wp88_mc_fix_regplus' );
define( 'WP88_MC_FIX_REGPLUSREDUX', 'wp88_mc_fix_regplusredux' );
define( 'WP88_MC_SYNC_BUDDYPRESS', 'wp88_mc_sync_buddypress' );
define( 'WP88_MC_SYNC_CIMY', 'wp88_mc_sync_cimy' );
define( 'WP88_MC_INTEGRATE_VIPER', 'wp88_mc_integrate_viper' );
define( 'WP88_MC_VIDEO_SHOW_TITLE', 'wp88_mc_video_show_title' );
define( 'WP88_MC_VIDEO_SHOW_BORDER', 'wp88_mc_video_show_border' );
define( 'WP88_MC_VIDEO_TRIM_BORDER', 'wp88_mc_video_trim_border' );
define( 'WP88_MC_VIDEO_SHOW_RATINGS', 'wp88_mc_video_show_ratings' );
define( 'WP88_MC_VIDEO_SHOW_NUM_VIEWS', 'wp88_mc_video_show_num_views' );

// NOTE: The following two static defines shouldn't have anything to do with
// BuddyPress, but they do; they were introduced when the BuddyPress sync feature
// was written.  But, remember, these are always used regardless of additional
// plugins that are used.  It's since been moved away from BuddyPress and
// made part of the standard WordPress mappings.
define( 'WP88_MC_STATIC_TEXT', 'wp88_mc_bp_static_text' );
define( 'WP88_MC_STATIC_FIELD', 'wp88_mc_bp_static_field' );

define( 'MMU_ADD', 1 );
define( 'MMU_DELETE', 2 );
define( 'MMU_UPDATE', 3 );

define( 'WP88_SEARCHABLE_PREFIX', 'wp88_mc' );
define( 'WP88_WORDPRESS_FIELD_MAPPING', 'wp88_mc_wp_f_' );
define( 'WP88_BP_XPROFILE_FIELD_MAPPING', 'wp88_mc_bp_xpf_' );
define( 'WP88_CIMY_FIELD_MAPPING', 'wp88_mc_cimy_uef_' );
define( 'WP88_CATEGORY_LIST_MAPPING', 'wp88_mc_category_list_' );
define( 'WP88_PLUGIN_FIRST_ACTIVATION', 'wp88_mc_first_activation' );
define( 'WP88_CATEGORY_GROUP_SUFFIX', '_group' );
define( 'WP88_CATEGORY_TEMPLATE_SUFFIX', '_template' );
define( 'WP88_IGNORE_FIELD_TEXT', 'Ignore this field' );
define( 'WP88_NO_MAILING_LIST', 'None' );
define( 'WP88_NO_TEMPLATE', 'None' );
define( 'WP88_ANY_GROUP', 'Any' );
define( 'WP88_GROUPINGS_TEXT', 'GROUPINGS' ); // This value is required by MailChimp
define( 'WP88_FIELD_DELIMITER', '+++' );

// Global variables - If you change this, be sure to see AC_FetchMappedWordPressData()
// which has static comparisons to the values in this array.  FIX LATER.
$wpUserDataArray = array( 'Username', 'Nickname', 'Website', 'Bio' , /*'AIM', 'Yahoo IM', 'Jabber-Google Chat'*/ );

//
//	Actions to hook to allow AutoChimp to do it's work
//
//	See:  http://codex.wordpress.org/Plugin_API/Action_Reference
//
//	The THIRD argument is for the priority.  The default is "10" so choosing "101" is
//	to try to ensure that AutoChimp is called LAST.  For example, other plugins will
//	save their data during "profile_update", so AutoChimp wants them to do it first,
//	then run so that all the data is picked up.
//
add_action('admin_menu', 'AC_OnPluginMenu');				// Sets up the menu and admin page
add_action('user_register','AC_OnRegisterUser', 501);		// Called when a user registers on the site
add_action('delete_user','AC_OnDeleteUser', 501);			//   "      "  "  "   unregisters "  "  "
add_action('profile_update','AC_OnUpdateUser',501,2 );		// Updates the user using a second arg - $old_user_data.
add_action('publish_post','AC_OnPublishPost' );				// Called when an author publishes a post.
add_action('xmlrpc_publish_post', 'AC_OnPublishPost' );		// Same as above, but for XMLRPC
add_action('publish_phone', 'AC_OnPublishPost' );			// Same as above, but for email.  No idea why it's called "phone".
add_action('bp_init', 'AC_OnBuddyPressInstalled');			// Only load the component if BuddyPress is loaded and initialized.
add_action('xprofile_updated_profile', 'AC_OnBuddyPressUserUpdate', 101 ); // Used to sync users with MailChimp
add_action('bp_core_signup_user', 'AC_OnBuddyPressUserUpdate', 101 ); 
add_action('wp_ajax_query_sync_users', 'AC_OnQuerySyncUsers');
add_action('wp_ajax_run_sync_users', 'AC_OnRunSyncUsers');
add_action('admin_notices', 'AC_OnAdminNotice' );
add_action('admin_init', 'AC_OnAdminInit' );
register_activation_hook( WP_PLUGIN_DIR . '/autochimp/88-autochimp.php', 'AC_OnActivateAutoChimp' );

//
//	Ajax
//

//
//	Ajax call to sync all current users against the selected mailing list(s).
//
function AC_OnRunSyncUsers()
{
	$numSuccess = 0;
	$numFailed = 0;
	$summary = '<strong>Report: </strong>';

	// Get a list of users on this site.  For more, see:
	// http://codex.wordpress.org/Function_Reference/get_users
	$users = get_users('');
	$numUsers = count( $users );

	// Iterate over the array and retrieve that users' basic information.  The 
	// info is written to the DB so that the client can periodically make ajax
	// calls to learn the progress.
	foreach ( $users as $user )
	{
		$result = AC_OnUpdateUser( $user->ID, $user, FALSE );
		if ( 0 === $result )
		{
			$numSuccess++;
    		$message = "<br>Successfully synchronized: $user->user_email";
			update_option( WP88_MC_MANUAL_SYNC_STATUS, $message );
		}
		else
		{
			$numFailed++;
    		$message = "<br>Failed to sync email: $user->user_email, Error: $result";
			update_option( WP88_MC_MANUAL_SYNC_STATUS, $message );
			$summary .= $message;
		}
		$percent = intval( ( ($numFailed + $numSuccess) / $numUsers ) * 100 );
		update_option( WP88_MC_MANUAL_SYNC_PROGRESS, $percent );
	}
	if ( 0 == $numFailed )
		$summary .= '<br/>All ';
	else
		$summary .= '</br>';
	$summary .= $numSuccess.' profiles were <strong>successfully</strong> synced.</div>';
	echo $summary;
	// Clean out the records
	delete_option( WP88_MC_MANUAL_SYNC_STATUS );
	delete_option( WP88_MC_MANUAL_SYNC_PROGRESS );
	exit; // This is required by WordPress to return the proper result
}

//
//	Companion Ajax function for AC_OnRunSyncUsers() which checks the current status
//	and reports back.
//
function AC_OnQuerySyncUsers()
{
	$percent = get_option( WP88_MC_MANUAL_SYNC_PROGRESS, 0 );
	$status = get_option( WP88_MC_MANUAL_SYNC_STATUS, 'Running sync...' );
	echo $percent . '#' . $status;
	exit; // This is required by WordPress to return the proper result
}

//
//	End Ajax
//

//
//	AC_OnBuddyPressInstalled
//
//	Called when BuddyPress is installed and active
//
function AC_OnBuddyPressInstalled()
{
	require_once('buddypress_integration.php');
}

//
//	AC_OnBuddyPressUserUpdate
//
//	Called when a BP user updates his profile.  This is used to update
//	MailChimp Merge Variables.
//
function AC_OnBuddyPressUserUpdate( $user_id = 0 )
{
	if ( 0 == $user_id )
	{
		// Get the current user
		$user = wp_get_current_user();
	}
	else
	{
		$user = get_userdata( $user_id );
	}
	// Pass their ID to the function that does the work.
	AC_OnUpdateUser( $user->ID, $user, TRUE );
}

//
//	START Register Plus AND Register Plus Redux Workaround
//
//	Register Plus overrides this:
//	http://codex.wordpress.org/Function_Reference/wp_new_user_notification
//
//	Look at register-plus.php somewhere around line 1715.  Same thing in
//	register-plus-redux.php around line 2324. More on Pluggable functions
//	can be found here:  http://codex.wordpress.org/Pluggable_Functions
//
//	Register Plus's overridden wp_new_user_notification() naturally includes the
//	original WordPress code for wp_new_user_notification().  This function calls
//	wp_set_password() after it sets user meta data.  This, as far as I can tell,
//	is the only place we can hook WordPress to update the user's MailChimp mailing
//	list with the user's first and last names.  NOTE:  This is a strange and non-
//	standard place for Register Plus to write the user's meta information.  Other
//	plugins like Wishlist Membership work with AutoChimp right out of the box.
//	This hack is strictly to make AutoChimp work with the misbehaving Register Plus.
//
//	The danger with this sort of code is that if the function that is overridden
//	is updated by WordPress, we'll likely miss out!  The best solution is to
//	have Register Plus perform it's work in a more standard way.
//
//	See the readme for more information on this issue.  The good news is the folks
//	at Register Plus explained this problem and are working on fixing it.
//
function AC_OverrideWarning()
{
	if( current_user_can(10) &&  $_GET['page'] == 'autochimp' )
		echo '<div id="message" class="updated fade"><p><strong>You have another plugin installed that is conflicting with AutoChimp and Register Plus.  This other plugin is overriding the user notification emails or password setting.  Please see <a href="http://www.wandererllc.com/plugins/autochimp/">AutoChimp FAQ</a> for more information.</strong></p></div>';
}

if ( function_exists( 'wp_set_password' ) )
{
	// Check if the user wants to patch
	$fixRegPlus = get_option( WP88_MC_FIX_REGPLUS );
	$fixRegPlusRedux = get_option( WP88_MC_FIX_REGPLUSREDUX );
	if ( '1' === $fixRegPlus || '1' === $fixRegPlusRedux )
	{
		add_action( 'admin_notices', 'AC_OverrideWarning' );
	}
}

//
// Override wp_set_password() which is called by Register Plus's overridden
// pluggable function - the only place I can see to grab the user's first
// and last name.
//
if ( !function_exists('wp_set_password') && ( '1' === get_option( WP88_MC_FIX_REGPLUS ) ||
											  '1' === get_option( WP88_MC_FIX_REGPLUSREDUX ) ) ) :
function wp_set_password( $password, $user_id )
{
	//
	// START original WordPress code
	//
	global $wpdb;

	$hash = wp_hash_password($password);
	$wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );

	wp_cache_delete($user_id, 'users');
	//
	// END original WordPress code
	//

	//
	// START Detect Register Plus
	//

	// Write some basic info to the DB about the user being added
	$user_info = get_userdata( $user_id );
	update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Updating user within Register Plus Redux patch.  User name is:  $user_info->first_name $user_info->last_name" );
	// Do the real work
	AC_ManageMailUser( MMU_UPDATE, $user_info, $user_info, TRUE );

	//
	// END Detect
	//
}
endif;	// wp_set_password is not overridden yet

//
// 	END Register Plus Workaround
//

//
//	Filters to hook
//
add_filter( 'plugin_row_meta', 'AC_AddAutoChimpPluginLinks', 10, 2 ); // Expand the links on the plugins page

//
//	Function to create the menu and admin page handler
//
function AC_OnPluginMenu()
{
	$page = add_submenu_page('options-general.php',	'AutoChimp Options', 'AutoChimp', 'add_users', basename(__FILE__), 'AC_AutoChimpOptions' );
	// When the plugin menu is clicked on, call AC_OnLoadAutoChimpScripts()
	add_action( 'admin_print_styles-' . $page, 'AC_OnLoadAutoChimpScripts' );
}

//
//	Load function for AutoChimp scripts.  Does the following:
//	1) Loads jQuery.
//	2) Loads WordPress Ajax functionality.
//	3) Loads AutoChimp custom scripts.
//
function AC_OnLoadAutoChimpScripts() 
{
	// jQuery UI stuff - files for the progress bar and dependencies PLUS style for them.
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-widget');
	wp_enqueue_script('jquery-ui-progressbar');
    wp_register_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
    wp_enqueue_style('jquery-style');

	// Load the javascript file that makes the AJAX request
	wp_enqueue_script( 'autochimp-ajax-request' );
		 
	// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
	wp_localize_script( 'autochimp-ajax-request', 'AutoChimpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

function AC_AddAutoChimpPluginLinks($links, $file)
{
	if ( $file == plugin_basename(__FILE__) )
	{
		$links[] = '<a href="http://wordpress.org/extend/plugins/autochimp/">' . __('Overview', 'autochimp') . '</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US">' . __('Donate', 'autochimp') . '</a>';
	}
	return $links;
}

function AC_OnAdminInit() 
{
	global $current_user;
	$user_id = $current_user->ID;
	// If user clicks to ignore the notice, add that to their user meta so that
	// the notice doesn't come up anymore.
	if ( isset($_GET['ac_20_nag_ignore']) && '0' == $_GET['ac_20_nag_ignore'] ) 
	{
		add_user_meta( $user_id, 'ac_20_ignore_notice', 'true', true );
	}
	
	// Register the AutoChimp JS scripts - they'll be loaded later when the
	// AutoChimp admin menu is clicked on.  Ensures that these scripts are only
	// loaded when needed (flow is a little goofy - search for
	// "wp_enqueue_script( 'autochimp-ajax-request'" for the next step).
	$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/autochimp/';
	wp_register_script( 'autochimp-ajax-request', $pluginFolder.'js/autochimp.js', array( 'jquery' ) );
}

//
//	This function is responsible for saving the user's' the AutoChimp options.  It
//	also displays the admin UI, which happens at the very bottom of the function, 
//	with the require_once statement.
//
function AC_AutoChimpOptions()
{
	// Stop the user if they don't have permission
	if (!current_user_can('add_users'))
	{
    	wp_die( __('You do not have sufficient permissions to access this page.') );
  	}

	// If the upload_files POST option is set, then files are being uploaded
	if ( isset( $_POST['save_api_key'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		$newAPIKey = $_POST['api_key'];

		// Update the database (save the key, but also clean out other stuff)
		update_option( WP88_MC_APIKEY, $newAPIKey );
		update_option( WP88_MC_LAST_MAIL_LIST_ERROR, '' );
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, '' );

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your API Key!</p></div>';
	}

	// Save off the mailing list options here
	if ( isset( $_POST['save_mailing_list_options'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		// Step 1:  Save the mailing lists that the user wants to affect

		// Declare an empty string...add stuff later
		$selectionOption = '';

		// Go through here and generate the option - a list of mailing list IDs separated by commas
		foreach( $_POST as $postVar )
		{
			$pos = strpos( $postVar, WP88_SEARCHABLE_PREFIX );
			if ( false === $pos ){}
			else
			{
				$selectionOption .= $postVar . ',';
			}
		}

		// Update the database
		update_option( WP88_MC_LISTS, $selectionOption );

		// Step 2:  Save when the user wants to update the list

		AC_SetBooleanOption( 'on_add_subscriber', WP88_MC_ADD );
		AC_SetBooleanOption( 'on_bypass_opt_in', WP88_MC_BYPASS_OPT_IN );
		AC_SetBooleanOption( 'on_delete_subscriber', WP88_MC_DELETE );
		AC_SetBooleanOption( 'on_update_subscriber', WP88_MC_UPDATE );
		AC_SetBooleanOption( 'on_delete_member', WP88_MC_PERMANENTLY_DELETE_MEMBERS );
		AC_SetBooleanOption( 'on_send_goodbye', WP88_MC_SEND_GOODBYE );
		AC_SetBooleanOption( 'on_send_notify', WP88_MC_SEND_ADMIN_NOTIFICATION );

		// Step 3:  Save the extra WordPress fields that the user wants to sync.
		global $wpUserDataArray;
		foreach( $wpUserDataArray as $userField )
		{
			// Encode the name of the field
			$fieldName = AC_EncodeUserOptionName( WP88_WORDPRESS_FIELD_MAPPING, $userField );

			// Now dereference the selection
			$fieldData = $_POST[ $fieldName ];

			// Save the selection
			update_option( $fieldName, $fieldData );
		}

		// Now save the special static field and the mapping
		$staticText = $_POST[ 'static_select' ];
		update_option( WP88_MC_STATIC_TEXT, $staticText );
		update_option( WP88_MC_STATIC_FIELD, $_POST[ WP88_MC_STATIC_FIELD ] );

		// Step 4:
		// This hidden field allows the user to save their mappings even when the
		// sync button isn't checked
		if ( isset( $_POST['buddypress_running'] ) )
		{
			// Save the mappings of BuddyPress XProfile fields to MailChimp Merge Vars.
			// Uses the $_POST array.
			AC_SaveBuddyPressMappings();
		}
		
		// Step 5:
		// Another hidden field for Cimy.
		if ( isset( $_POST['cimy_running'] ) )
		{
			require_once 'cimy_integration.php';
			// Save Cimy settings - uses the $_POST array.
			AC_SaveCimyMappings();
		}

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp mailing list options.</p></div>';
	}

	if ( isset( $_POST['save_campaign_options'] ) )
	{
		// Save off the mappings of categories to campaigns.
		AC_SaveCampaignCategoryMappings();

		// The rest is easy...
		AC_SetBooleanOption( 'on_excerpt_only', WP88_MC_CAMPAIGN_EXCERPT_ONLY );
		AC_SetBooleanOption( 'on_send_now', WP88_MC_SEND_NOW );
		AC_SetBooleanOption( 'on_create_once', WP88_MC_CREATE_CAMPAIGN_ONCE );

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp campaign options.</p></div>';
	}

	if ( isset( $_POST['save_plugin_options'] ) )
	{
		AC_SetBooleanOption( 'on_fix_regplus', WP88_MC_FIX_REGPLUS );
		AC_SetBooleanOption( 'on_fix_regplusredux', WP88_MC_FIX_REGPLUSREDUX );
		AC_SetBooleanOption( 'on_sync_buddypress', WP88_MC_SYNC_BUDDYPRESS );
		AC_SetBooleanOption( 'on_sync_cimy', WP88_MC_SYNC_CIMY );
		AC_SetBooleanOption( 'on_integrate_viper', WP88_MC_INTEGRATE_VIPER );

		AC_SetBooleanOption( 'on_show_title', WP88_MC_VIDEO_SHOW_TITLE );
		AC_SetBooleanOption( 'on_show_border', WP88_MC_VIDEO_SHOW_BORDER );
		AC_SetBooleanOption( 'on_trim_border', WP88_MC_VIDEO_TRIM_BORDER );
		AC_SetBooleanOption( 'on_show_ratings', WP88_MC_VIDEO_SHOW_RATINGS );
		AC_SetBooleanOption( 'on_show_num_views', WP88_MC_VIDEO_SHOW_NUM_VIEWS );

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp plugin options.</p></div>';
	}

	// The file that will handle uploads is this one (see the "if" above)
	$action_url = $_SERVER['REQUEST_URI'];
	require_once '88-autochimp-settings.php';
}

//
//	Syncs a single user of this site with the AutoChimp options that the site owner
//	has selected in the admin panel.
//
//	The third argument, $old_user_data, is for the profile_update action, which calls
//	AC_OnUpdateUser.  If $mode is MMU_UPDATE, then ensure that this data is a copy
//	of user data.  Otherwise, null is fine. 
//
//	List of exceptions and error codes: http://www.mailchimp.com/api/1.3/exceptions.field.php
//
function AC_ManageMailUser( $mode, $user_info, $old_user_data, $writeDBMessages )
{
	$apiKey = get_option( WP88_MC_APIKEY );
	$api = new MCAPI_13( $apiKey );

	$myLists = $api->lists();
	$errorCode = 0;

	if ( null != $myLists )
	{
		$list_id = -1;

		// See if the user has selected some lists
		$selectedLists = get_option( WP88_MC_LISTS );

		// Put all of the selected lists into an array to search later
		$valuesArray = array();
		$valuesArray = preg_split( "/[\s,]+/", $selectedLists );

		foreach ( $myLists['data'] as $list )
		{
			$list_id = $list['id'];

			// See if this mailing list should be selected
			foreach( $valuesArray as $searchableID )
			{
				$pos = strpos( $searchableID, $list_id );
				if ( false === $pos ){}
				else
				{
					// First and last names are always added.  NOTE:  Email is only
					// managed when a user is updating info 'cause email is used as
					// the key when adding a new user.
					$merge_vars = array( 'FNAME'=>$user_info->first_name, 'LNAME'=>$user_info->last_name );

					// Grab the extra WP user info
					$data = AC_FetchMappedWordPressData( $user_info->ID );
					// Add that info into the merge array.
					AC_AddUserFieldsToMergeArray( $merge_vars, $data );

					// Grab extra mappings if the user wants to sync Buddy Press
					$syncBuddyPress = get_option( WP88_MC_SYNC_BUDDYPRESS );
					if ( '1' === $syncBuddyPress )
					{
						// Hunt down Buddy Press user data.
						$data = AC_FetchMappedXProfileData( $user_info->ID );
						// Add BuddyPress's data into the merge array
						AC_AddUserFieldsToMergeArray( $merge_vars, $data );
					}
					
					// Grab extra Cimy mappings?
					$syncCimy = get_option( WP88_MC_SYNC_CIMY );
					if ( '1' === $syncCimy )
					{
						// Pull in the Cimy file
						require_once "cimy_integration.php";
						// Get the Cimy data
						$data = AC_FetchMappedCimyData( $user_info->ID );
						// Add the Cimy data into the merge array
						AC_AddUserFieldsToMergeArray( $merge_vars, $data );
					}

					// This one gets static data...add it as well to the array.
					$data = AC_FetchStaticData();
					// Add that info into the merge array.
					AC_AddUserFieldsToMergeArray( $merge_vars, $data );

					switch( $mode )
					{
						case MMU_ADD:
						{
							// Check to see if the site wishes to bypass the double opt-in feature
							$doubleOptIn = ( 0 === strcmp( '1', get_option( WP88_MC_BYPASS_OPT_IN ) ) ) ? false : true;
							$retval = $api->listSubscribe( $list_id, $user_info->user_email, $merge_vars, 'html', $doubleOptIn );
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;

								if ( FALSE != $writeDBMessages )
								{
									// Set latest activity - displayed in the admin panel
									$errorString = "Problem adding $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id.  Error Code: $errorCode, Message: $api->errorMessage, Data: ";
									$errorString .= print_r( $merge_vars, TRUE );
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
								}
							}
							else
							{
								if ( FALSE != $writeDBMessages )
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Added $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id." );
							}
							break;
						}
						case MMU_DELETE:
						{
							$deleteMember = ( '1' === get_option( WP88_MC_PERMANENTLY_DELETE_MEMBERS ) );
							$sendGoodbye = ( '1' === get_option( WP88_MC_SEND_GOODBYE ) );
							$sendNotify = ( '1' === get_option( WP88_MC_SEND_ADMIN_NOTIFICATION ) );
							update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $lastMessage );
							$retval = $api->listUnsubscribe( $list_id, $user_info->user_email, $deleteMember, $sendGoodbye, $sendNotify );
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;

								if ( FALSE != $writeDBMessages )
								{
									// Set latest activity - displayed in the admin panel
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Problem removing $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.  Error Code: $errorCode, Message: $api->errorMessage" );
								}
							}
							else
							{
								if ( FALSE != $writeDBMessages )
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Removed $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id." );
							}
							break;
						}
						case MMU_UPDATE:
						{
							$updateEmail = $old_user_data->user_email;

							// Potential update to the email address (more likely than name!)
							$merge_vars['EMAIL'] = $user_info->user_email;

							// No emails are sent after a successful call to this function.
							$retval = $api->listUpdateMember( $list_id, $updateEmail, $merge_vars );
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;
								if ( FALSE != $writeDBMessages )
								{
									// Set latest activity - displayed in the admin panel
									$errorString = "Problem updating $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.  Error Code: $errorCode, Message: $api->errorMessage, Data: ";
									$errorString .= print_r( $merge_vars, TRUE );
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
								}
							}
							else
							{
								if ( FALSE != $writeDBMessages )
								{
									$errorString = "Updated $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.";
									// Uncomment this to see debug info on success
									//$errorString .= ' Data: ';
									//$errorString .= print_r( $merge_vars, TRUE );
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
								}
							}
							break;
						}
					}
				}
			}
		}
	}
	return $errorCode;
}

//
//	Arguments:
//		an instance of the MailChimp API class (for performance).
//		the post ID
//		the list ID that the campaign should be created for.
//		the interest group name.
//		the user template ID.
//
//	Returns STRING "-1" if the creation was skipped, "0" on failure, and a legit
//	ID on success.  Except for "-1", each return point will write the latest result
//	of the function to the DB which will be visible to the user in the admin page.
//
function AC_CreateCampaignFromPost( $api, $postID, $listID, $interestGroupName, $categoryTemplateID )
{
	// Does the user only want to create campaigns once?
	if ( '1' == get_option( WP88_MC_CREATE_CAMPAIGN_ONCE ) )
	{
		if ( '1' == get_post_meta( $postID, WP88_MC_CAMPAIGN_CREATED, true ) )
			return '-1';	// Don't create the campaign again!
	}

	// Get the info on this post
	$post = get_post( $postID );

	// If the post is somehow in an unsupported state (sometimes from email
	// posts), then just skip the post.
	if ('pending' == $post->post_status ||
		'draft' == $post->post_status ||
		'private' == $post->post_status )
	{
		return '-1'; // Don't create the campaign yet.
	}
	
	// Get info on the list
	$filters = array();
	$filters['list_id'] = $listID;
	$lists = $api->lists( $filters );
	$list = $lists['data'][0];

	// Time to start creating the campaign...
	// First, create the options array
	$htmlContentTag = 'html';
	$options = array();
	$options['list_id']	= $listID;
	$options['subject']	= $post->post_title;
	$options['from_email'] = $list['default_from_email'];
	$options['from_name'] = $list['default_from_name'];
	$options['to_email'] = '*|FNAME|*';
	$options['tracking'] = array('opens' =>	true, 'html_clicks' => true, 'text_clicks' => false );
	$options['authenticate'] = true;
	// See if a template should be used
	if ( 0 != strcmp( $categoryTemplateID, WP88_NO_TEMPLATE ) )
	{
		$options['template_id'] = $categoryTemplateID;
		// 'main' is the name of the section that will be replaced.  This is a
		// hardcoded decision.  Keeps things simple.  To view the sections of
		// a template, use MailChimp's templateInfo() function.  For more
		// information, go here:
		// http://apidocs.mailchimp.com/api/1.3/templateinfo.func.php
		// You need the campaign ID.  That can be retrieved with campaigns().
		$htmlContentTag = 'html_main';
	}

	// Start generating content
	$content = array();
	$postContent = '';
	
	// Get the excerpt option; if on, then show the excerpt
	if ( '1' === get_option( WP88_MC_CAMPAIGN_EXCERPT_ONLY ) )
	{
		if ( 0 == strlen( $post->post_excerpt ) )
		{
			// Handmade function which mimics wp_trim_excerpt() (that function won't operate
			// on a non-empty string)
			$postContent = AC_TrimExcerpt( $post->post_content );
		}
		else
		{
			$postContent = apply_filters( 'the_excerpt', $post->post_excerpt );
			// Add on a "Read the post" link here
			$permalink = get_permalink( $postID );
			$postContent .= "<p>Read the post <a href=\"$permalink\">here</a>.</p>";
			// See http://codex.wordpress.org/Function_Reference/the_content, which
			// suggests adding this code:
			$postContent = str_replace( ']]>', ']]&gt;', $postContent );
		}

		// Set the text content variables
		$content['text'] = strip_tags( $postContent );
	}
	else
	{
		// Check if video shortcode needs to be converted first
		if ( '1' === get_option( WP88_MC_INTEGRATE_VIPER ) )
		{
			require_once( "viper_integration.php" );
			// Convert the Viper codes
			$vipered = AC_ConvertViperShortcode( $post->post_content );
			// Now run the content through the_content engine.
			$postContent = apply_filters( 'the_content', $vipered );
			// Need to special case this for text
			$textPostContent = apply_filters( 'the_content', $post->post_content );
			$content['text'] = strip_tags( $textPostContent );
		}
		else 
		{
			$postContent = apply_filters( 'the_content', $post->post_content );
			$content['text'] = strip_tags( $postContent );
		}
	}

	// Set the content variables
	$content[$htmlContentTag] = $postContent;

	// Segmentation, if any (Interest groups)
	$segment_opts = NULL;
	if ( 0 != strcmp( $interestGroupName, WP88_ANY_GROUP ) )
	{
		$group = $api->listInterestGroupings( $listID );
		if ( NULL != $group )
		{
			$interestID = $group[0]['id'];
			$conditions = array();
			$conditions[] = array('field'=>"interests-$interestID", 'op'=>'all', 'value'=>$interestGroupName);
			$segment_opts = array('match'=>'all', 'conditions'=>$conditions);
		}
	}

	// More info here:  http://apidocs.mailchimp.com/api/1.3/campaigncreate.func.php
	$result = $api->campaignCreate( 'regular', $options, $content, $segment_opts );
	if ($api->errorCode)
	{
		// Set latest activity - displayed in the admin panel
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Problem with campaign with title '$post->post_title'.  Error Code: $api->errorCode, Message: $api->errorMessage" );
		$result = "0";
	}
	else
	{
		// Set latest activity
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Your latest campaign created is titled '$post->post_title' with ID: $result" );

		// Mark this post as having a campaign created from it.
		add_post_meta( $postID, WP88_MC_CAMPAIGN_CREATED, '1' );
	}

	// Done
	return $result;
}

function AC_OnPublishPost( $postID )
{
	// Get the info on this post
	$post = get_post( $postID );
	$categories = get_the_category( $postID );	// Potentially several categories

	// If it matches the user's category choice or is any category, then
	// do the work.  This needs to be a loop because a post can belong to
	// multiple categories.
	foreach( $categories as $category )
	{
		$categoryOptionName = AC_EncodeUserOptionName( WP88_CATEGORY_LIST_MAPPING , $category->name );
		$categoryMailingList = get_option( $categoryOptionName );
		$categoryGroupName = get_option( $categoryOptionName . WP88_CATEGORY_GROUP_SUFFIX );
		$categoryTemplateID = get_option( $categoryOptionName . WP88_CATEGORY_TEMPLATE_SUFFIX );

		// If the mailing list is NOT "None" then create a campaign.		
		if ( 0 != strcmp( $categoryMailingList, WP88_NO_MAILING_LIST ) )
		{
			// Create an instance of the MailChimp API
			$apiKey = get_option( WP88_MC_APIKEY );
			$api = new MCAPI_13( $apiKey );

			// Do the work
			$id = AC_CreateCampaignFromPost( $api, $postID, $categoryMailingList, $categoryGroupName, $categoryTemplateID );

			// Does the user want to send the campaigns right away?
			$sendNow = get_option( WP88_MC_SEND_NOW );

			// Send it, if necessary (if user wants it), and the $id is
			// sufficiently long (just picking longer than 3 for fun).
			if ( '1' == $sendNow && ( strlen( $id ) > 3 ) )
			{
				$api->campaignSendNow( $id );
			}

			// As soon as the first match is found, break out.
			break;
		}
	}
}

//
//	Given a mailing list, return an associative array of the names and tags of
//	the merge variables (custom fields) for that mailing list.
//
function AC_FetchMailChimpMergeVars( $api, $list_id )
{
	$mergeVars = array();
	$mv = $api->listMergeVars( $list_id );

	$ig = $api->listInterestGroupings( $list_id );

	// Bail here if nothing is returned
	if ( NULL == $mv && NULL == $ig )
		return $mergeVars;

	// Copy over the merge variables
	if ( !empty( $mv ) )
	{
		foreach( $mv as $i => $var )
		{
			$mergeVars[ $var['name'] ] = $var['tag'];
		}
	}

	// Copy over the interest groups
	if ( !empty( $ig ) )
	{
		foreach( $ig as $i => $var )
		{
			// Create a special encoding - grouping text, plus delimiter, then the name of the grouping
			$mergeVars[ $var['name'] ] = WP88_GROUPINGS_TEXT . WP88_FIELD_DELIMITER . $var['name'];
		}
	}

	return $mergeVars;
}

//
//	Looks up the user's additional WordPress user data and returns a meaningful
//	array of associations to the users based on what the user wants to sync.
//
function AC_FetchMappedWordPressData( $userID )
{
	// User data array
	$dataArray = array();

	// This global array holds the names of the WordPress user fields
	global $wpUserDataArray;

	// Get this user's data
	$user_info = get_userdata( $userID );

	// Loop through each field that the user wants to sync and hunt down the user's
	// values for those fields and stick them into an array.
	foreach ( $wpUserDataArray as $field )
	{
		// Figure out which MailChimp field to map to
		$optionName = AC_EncodeUserOptionName( WP88_WORDPRESS_FIELD_MAPPING, $field );
		$fieldData = get_option( $optionName );

		// If the mapping is not set, then skip everything and go on to the next field
		if ( 0 !== strcmp( $fieldData, WP88_IGNORE_FIELD_TEXT ) )
		{
			// Now, get the user's data.  Since the data is basically static,
			// this is just a collection of "if"s.
			if ( 0 === strcmp( $field, 'Username' ) )
			{
				$value = $user_info->user_login;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Nickname' ) )
			{
				$value = $user_info->user_nicename;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Website' ) )
			{
				$value = $user_info->user_url;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Bio' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'AIM' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Yahoo IM' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Jabber-Google Chat' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
		}
	}
	return $dataArray;
}

function AC_FetchStaticData()
{
	// Will hold a row of static data...assuming user wants this data, of course
	$dataArray = array();

	// Does the user want static data?
	$mapping = get_option( WP88_MC_STATIC_FIELD );

	// If the mapping is set...
	if ( 0 !== strcmp( $mapping, WP88_IGNORE_FIELD_TEXT ) )
	{
		$text = get_option( WP88_MC_STATIC_TEXT );

		if ( !empty( $text ) )
		{
			$dataArray[] = array( 	"name" => WP88_MC_STATIC_FIELD,
									"tag" => $mapping,
									"value" => $text );
		}
	}
	return $dataArray;
}

//
//	Takes a by-reference array argument and adds extra merge variable data
//	specific to the user ID passed in to the array.
//
function AC_AddUserFieldsToMergeArray( &$mergeVariables, $data )
{
	// Create a potentially used groupings array.  Tack this on at the end
	$groupingsArray = array();

	// Add this data to the merge variables
	foreach ( $data as $item )
	{
		// Catch the "GROUPINGS" tag and create a special array for that
		$groupTag = strpos( $item['tag'], WP88_GROUPINGS_TEXT );
		if ( FALSE === $groupTag )
		{
			$mergeVariables[ $item['tag'] ] = $item['value'];
		}
		else
		{
			$fields = explode( WP88_FIELD_DELIMITER, $item['tag'] );
			$groupingsArray[] = array('name' => $fields[1],
									'groups' => $item['value'] );
		}
	}

	// Tack on the group array now if there are groupings to add
	if ( !empty( $groupingsArray ) )
	{
		$mergeVariables[ WP88_GROUPINGS_TEXT ] = $groupingsArray;
	}
}

function AC_EncodeUserOptionName( $encodePrefix, $optionName )
{
	// Tack on the prefix to the option name
	$encoded = $encodePrefix . $optionName;

	// Make sure the option name has no spaces; replace them with hash tags.
	// Not using underscores or dashes since those are commonly used in place
	// of spaces.  If an option name has "#" in it, then this scheme breaks down.
	$encoded = str_replace( ' ', '#', $encoded );
	
	// Periods are also problematic, as reported on 8/7/12 by Katherine Boroski.
	$encoded = str_replace( '.', '*', $encoded );
	
	// "&" symbols are problematic, as reported on 8/23/12 by Enrique.
	$encoded = str_replace( '&', '_', $encoded );

	return $encoded;
}

function AC_DecodeUserOptionName( $decodePrefix, $optionName )
{
	// Strip out the searchable tag
	$decoded = substr_replace( $optionName, '', 0, strlen( $decodePrefix ) );

	// Replace hash marks with spaces, asterisks with periods, etc.
	$decoded = str_replace( '#', ' ', $decoded );
	$decoded = str_replace( '*', '.', $decoded );
	$decoded = str_replace( '_', '&', $decoded );

	return $decoded;
}

//
//	This function uses the global $_POST variable, so only call it at the appropriate times.
//	Consider refactoring this function to make it not dependent on $_POST.
//
function AC_SaveCampaignCategoryMappings()
{
	// Fetch this site's categories
	$category_args=array(	'orderby' => 'name',
	  						'order' => 'ASC',
	  						'hide_empty' => 0 );
	$categories=get_categories( $category_args );

	foreach( $categories as $category )
	{
		// Encode the name of the field
		$selectName = AC_EncodeUserOptionName( WP88_CATEGORY_LIST_MAPPING , $category->name );

		// Now dereference the selection
		$selection = $_POST[ $selectName ];

		// Save the selection
		update_option( $selectName, $selection );
		
		// Save off interest group selection now.  Exact same principle.
		$groupSelectName = $selectName . WP88_CATEGORY_GROUP_SUFFIX;
		$groupSelection = $_POST[ $groupSelectName ];
		update_option( $groupSelectName, $groupSelection );
		
		// Same thing for templates
		$templateSelectName = $selectName . WP88_CATEGORY_TEMPLATE_SUFFIX;
		$templateSelection = $_POST[ $templateSelectName ];
		update_option( $templateSelectName, $templateSelection );
	}
}

//
//	WordPress Action handlers
//

function AC_OnRegisterUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onAddSubscriber = get_option( WP88_MC_ADD );
	if ( '1' == $onAddSubscriber )
	{
		$result = AC_ManageMailUser( MMU_ADD, $user_info, NULL, TRUE );
	}
	return $result;
}

function AC_OnDeleteUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onDeleteSubscriber = get_option( WP88_MC_DELETE );
	if ( '1' == $onDeleteSubscriber )
	{
		$result = AC_ManageMailUser( MMU_DELETE, $user_info, NULL, TRUE );
	}
	return $result;
}

function AC_OnUpdateUser( $userID, $old_user_data, $writeDBMessages = TRUE )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( '1' === $onUpdateSubscriber )
	{
		$result = AC_ManageMailUser( MMU_UPDATE, $user_info, $old_user_data, $writeDBMessages );

		// 232 is the MailChimp error code for: "user doesn't exist".  This
		// error can occur when a new user signs up but there's a required
		// field in MailChimp which the software doesn't have access to yet.
		// The field will be populated when the user finally activates their
		// account, but their account won't exist.  So, catch that here and
		// try to re-add them.  This is a costly workflow, but that's how
		// it works.
		//
		// This can also happen when synchronizing users with MailChimp who
		// aren't subscribers to the MailChimp mailing list yet.
		//
		// 215 is the "List_NotSubscribed" error message which can happen if 
		// the user is in the system but not subscribed to that list.  So, do
		// an add for that too.
		//
		if ( 232 === $result || 215 === $result )
		{
			$onAddSubscriber = get_option( WP88_MC_ADD );
			if ( '1' === $onAddSubscriber )
			{
				// Don't need the $old_user_data variable anymore; pass NULL.
				$result = AC_ManageMailUser( MMU_ADD, $user_info, NULL, $writeDBMessages );
			}
		}
	}
	return $result;
}

//
//	Added for 2.0 to do some slight conversion work when upgrading from 1.x to 2.0.
//
function AC_OnActivateAutoChimp()
{
	$show = get_option( WP88_PLUGIN_FIRST_ACTIVATION, '0' );
	if ( '0' === $show )
	{
		global $wpdb;
		// Delete options that are no longer needed 
		delete_option( WP88_MC_CAMPAIGN_CATEGORY );
		delete_option( WP88_MC_CAMPAIGN_FROM_POST );

		// Delete a bunch of those temp email options
		$tableName = $wpdb->prefix . "options";
		$sql = "delete FROM $tableName WHERE option_name LIKE 'wp88_mc_temp%'";
		$wpdb->query( $sql );

		// Set defaults for new options		
		update_option( WP88_MC_PERMANENTLY_DELETE_MEMBERS, '0' );
		update_option( WP88_MC_SEND_GOODBYE, '1' );
		update_option( WP88_MC_SEND_ADMIN_NOTIFICATION, '1' );
		
		// Done.
		update_option( WP88_PLUGIN_FIRST_ACTIVATION, '1' );
	}
}

function AC_OnAdminNotice() 
{
	global $current_user;
	$user_id = $current_user->ID;
	// Check that the user hasn't already clicked to ignore the message
	if ( !get_user_meta( $user_id, 'ac_20_ignore_notice' ) ) 
	{
		global $pagenow;
	    if ( 'plugins.php' == $pagenow || 'options-general.php' == $pagenow ) 
	    {
	    	$currentPage = $_SERVER['REQUEST_URI'];
			
			// If there are already arguments, append the ignore message.  Otherwise
			// add it as the only variable.
			if ( FALSE === strpos( $currentPage, '?' ) )
				$currentPage .= '?ac_20_nag_ignore=0';
			else
				$currentPage .= '&ac_20_nag_ignore=0';
			
	    	$apiSetMessage = '';
	    	$apiSet = get_option( WP88_MC_APIKEY, '0' );
			if ( '0' == $apiSet )
			{
				$apiSetMessage = '<p>The first thing to do is set your MailChimp API key.  You can find your key on the MailChimp website under <em>Account</em> - <em>API Keys & Authorized Apps</em>.  Click <a target="_blank" href="options-general.php?page=88-autochimp.php">here</a> to set your API key now. | <a href="' . $currentPage . '">Dismiss</a></p>';
			}
			echo '<div class="updated"><p>';
			printf(__('Welcome to AutoChimp 2.0.  Be sure to review <em>all</em> of your settings to ensure they are correct.  For more detail, please visit the <a href="http://www.wandererllc.com/company/plugins/autochimp/"">AutoChimp homepage</a>. | <a href="%1$s">Dismiss</a>'), $currentPage );
			print( $apiSetMessage );
			echo "</p></div>";
		}
	}
}

//
//	Helpers
//

function AC_TrimExcerpt( $text )
{
	$text = strip_shortcodes( $text );
	$text = apply_filters('the_content', $text);
	$text = str_replace(']]>', ']]&gt;', $text);
	$excerpt_length = apply_filters('excerpt_length', 55);
	$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
	return wp_trim_words( $text, $excerpt_length, $excerpt_more );
}

//	(Note: AutoChimp 2.0 only supports the first level of interest groups.
//	Hence, the [0].)
function AC_AssembleGroupsHash( $mcGroupsArray )
{
	$groupHash = array();
	foreach ( $mcGroupsArray[0]['groups'] as $group )
	{
		$groupHash[$group['name']] = $group['name'];
	}
	return $groupHash;
}

//	(Note: AutoChimp 2.0 only supports the first level of interest groups.
//	Hence, the [0].)
function AC_AssembleGroupsArray( $mcGroupsArray )
{
	$groupArray = array();
	foreach ( $mcGroupsArray[0]['groups'] as $group )
	{
		$groupArray[] = $group['name'];
	}
	return $groupArray;
}

function AC_SetBooleanOption( $postVar, $optionName )
{
	if ( isset( $_POST[$postVar] ) )
		update_option( $optionName, '1' );
	else
		update_option( $optionName, '0' );
}

?>
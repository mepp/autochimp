<?php
/*
Plugin Name: AutoChimp
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Description: Gives users the ability to update their MailChimp mailing lists when users subscribe, unsubscribe, and update their WordPress profiles.
Author: Kyle Chapman
Version: 0.6
*/

if ( !class_exists( 'MCAPI' ) )
{
	require_once 'inc/MCAPI.class.php';
}

define( "WP88_MC_APIKEY", "wp88_mc_apikey" );
define( "WP88_MC_LISTS", "wp88_mc_selectedlists" );
define( "WP88_MC_ADD", "wp88_mc_add" );
define( "WP88_MC_DELETE", "wp88_mc_delete" );
define( "WP88_MC_UPDATE", "wp88_mc_update" );
define( "WP88_MC_TEMPEMAIL", "wp88_mc_tempemail" );

define( "MMU_ADD", 1 );
define( "MMU_DELETE", 2 );
define( "MMU_UPDATE", 3 );

define( "WP88_SEARCHABLE_PREFIX", "wp88_mc" );

add_action('user_register','OnEightEightRegisterUser');
add_action('delete_user','OnEightEightDeleteUser');
add_action('show_user_profile','OnEightEightAboutToUpdateUser');
add_action('profile_update','OnEightEightUpdateUser' );
add_action('admin_menu', 'OnEightEightPluginMenu');

function OnEightEightPluginMenu()
{
	add_submenu_page('options-general.php', 'AutoChimp Options', 'AutoChimp Options', 'manage_options', basename(__FILE__), EightEightOptions );
}

function EightEightOptions()
{
	// Stop the user if they don't have permission
	if (!current_user_can('manage_options'))
	{
    	wp_die( __('You do not have sufficient permissions to access this page.') );
  	}

	// If the upload_files POST option is set, then files are being uploaded
	if ( isset( $_POST['save_api_key'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		$newAPIKey = $_POST['api_key'];

		// Update the database
		update_option( WP88_MC_APIKEY, $newAPIKey );

		// Tell the user
		print "<div id=\"message\" class=\"updated fade\"><p>Saved API Key!</p></div>";
	}

	// Save off the requested mailing lists here
	if ( isset( $_POST['save_mailing_lists'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		// Step 1:  Save the mailing lists that the user wants to affect

		// Declare an empty string...add stuff later
		$selectionOption = "";

		// Go through here and generate the option - a list of mailing list IDs separated by commas
		foreach( $_POST as $postVar )
		{
			$pos = strpos( $postVar, WP88_SEARCHABLE_PREFIX );
			if ( false === $pos ){}
			else
			{
				$selectionOption .= $postVar . ",";
			}
		}

		// Update the database
		update_option( WP88_MC_LISTS, $selectionOption );

		// Tell the user
		print "<div id=\"message\" class=\"updated fade\"><p>Saved your mailing list selections!</p></div>";

		// Step 2:  Save when the user wants to update the list

		if ( isset( $_POST['on_add_subscriber'] ) )
			update_option( WP88_MC_ADD, "1" );
		else
			update_option( WP88_MC_ADD, "0" );

		if ( isset( $_POST['on_delete_subscriber'] ) )
			update_option( WP88_MC_DELETE, "1" );
		else
			update_option( WP88_MC_DELETE, "0" );

		if ( isset( $_POST['on_update_subscriber'] ) )
			update_option( WP88_MC_UPDATE, "1" );
		else
			update_option( WP88_MC_UPDATE, "0" );
	}

	// The file that will handle uploads is this one (see the "if" above)
	$action_url = $_SERVER['REQUEST_URI'];
	require_once '88-autochimp-settings.php';
}

function ManageMailUser( $mode, $user_info )
{
	$apiKey = get_option( WP88_MC_APIKEY );
	$api = new MCAPI( $apiKey );

	$myLists = $api->lists();

	if ( null != $myLists )
	{
		$list_id = -1;

		// See if the user has selected some lists
		$selectedLists = get_option( WP88_MC_LISTS );

		// Put all of the selected lists into an array to search later
		$valuesArray = array();
		$valuesArray = preg_split( "/[\s,]+/", $selectedLists );

		foreach ( $myLists as $list )
		{
			$list_id = $list['id'];

			// See if this mailing list should be selected
			foreach( $valuesArray as $searchableID )
			{
				$pos = strpos( $searchableID, $list_id );
				if ( false === $pos ){}
				else
				{
					$merge_vars = array('FNAME'=>$user_info->first_name, 'LNAME'=>$user_info->last_name, 'INTERESTS'=>'');

					switch( $mode )
					{
						case MMU_ADD:
						{
							// By default this sends a confirmation email - you will not see new members
							// until the link contained in it is clicked!
							$retval = $api->listSubscribe( $list_id, $user_info->user_email, $merge_vars );
						}
						case MMU_DELETE:
						{
							// By default this sends a goodbye email and fires off a notification to the list owner
							$retval = $api->listUnsubscribe( $list_id, $user_info->user_email );
						}
						case MMU_UPDATE:
						{
							// Get the old email - this feels a little dangerous...'cause users have to go
							// through the profile panel.  If they don't and email is updated, data can
							// get out of sync.  ADD TO README.
							$updateEmail = get_option( WP88_MC_TEMPEMAIL );

							// Potential update to the email address (more likely than name!)
							$merge_vars['EMAIL'] = $user_info->user_email;

							// No emails are sent after a successful call to this function.
							$retval = $api->listUpdateMember( $list_id, $updateEmail, $merge_vars );
						}
					}
				}
			}
		}
	}
}

function OnEightEightRegisterUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onAddSubscriber = get_option( WP88_MC_ADD );
	if ( "1" == $onAddSubscriber )
	{
		ManageMailUser( MMU_ADD, $user_info );
	}
}

function OnEightEightDeleteUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onDeleteSubscriber = get_option( WP88_MC_DELETE );
	if ( "1" == $onDeleteSubscriber )
	{
		ManageMailUser( MMU_DELETE, $user_info );
	}
}

function OnEightEightAboutToUpdateUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( "1" == $onUpdateSubscriber )
	{
		$updateEmail = $user_info->user_email;
		update_option( WP88_MC_TEMPEMAIL, $updateEmail );
	}
}

function OnEightEightUpdateUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( "1" == $onUpdateSubscriber )
	{
		ManageMailUser( MMU_UPDATE, $user_info );
		update_option( WP88_MC_TEMPEMAIL, "" );
	}
}

?>
<?php
/*
Plugin Name: AutoChimp
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Description: Gives users the ability to create MailChimp mail campaigns from blog posts. Also allows updating MailChimp mailing lists when users subscribe, unsubscribe, and update their WordPress profiles.
Author: Wanderer LLC Dev Team
Version: 0.88
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
define( "WP88_MC_CAMPAIGN_FROM_POST", "wp88_mc_campaign_from_post" );
define( "WP88_MC_CAMPAIGN_CATEGORY", "wp88_mc_campaign_category" );
define( "WP88_MC_CREATE_CAMPAIGN_ONCE", "wp88_mc_create_campaign_once" );
define( "WP88_MC_SEND_NOW", "wp88_mc_send_now" );
define( "WP88_MC_LAST_CAMPAIGN_ERROR", "wp88_mc_last_error" );
define( "WP88_MC_LAST_MAIL_LIST_ERROR", "wp88_mc_last_ml_error" );
define( "WP88_MC_CAMPAIGN_CREATED", "wp88_mc_campaign" );
define( 'WP88_MC_FIX_REGPLUS', 'wp88_mc_fix_regplus' );
define( 'WP88_MC_SYNC_BUDDYPRESS', 'wp88_mc_sync_buddypress' );
define( 'WP88_MC_STATIC_TEXT', 'wp88_mc_static_text' );

define( "AC_DEFAULT_CATEGORY", "Any category" );

define( "MMU_ADD", 1 );
define( "MMU_DELETE", 2 );
define( "MMU_UPDATE", 3 );

define( "WP88_SEARCHABLE_PREFIX", "wp88_mc" );
define( 'WP88_BP_XPROFILE_FIELD_MAPPING', 'wp88_mc_bp' );

//
//	Actions to hook to allow AutoChimp to do it's work
//
//	See:  http://codex.wordpress.org/Plugin_API/Action_Reference
//
add_action('admin_menu', 'OnPluginMenu');				// Sets up the menu and admin page
add_action('user_register','OnRegisterUser');			// Called when a user registers on the site
add_action('delete_user','OnDeleteUser');				//   "      "  "  "   unregisters "  "  "
add_action('show_user_profile','OnAboutToUpdateUser');	// Little trickier for update...need to save email in order to track them down later
add_action('profile_update','OnUpdateUser' );			// Uses the saved email to update the user.
add_action('publish_post','OnPublishPost' );			// Called when an author publishes a post.
add_action('xmlrpc_publish_post', 'OnPublishPost' );	// Same as above, but for XMLRPC
add_action('publish_phone', 'OnPublishPost' );			// Same as above, but for email.  No idea why it's called "phone".
add_action('bp_init', 'OnBuddyPressInstalled');			// Only load the component if BuddyPress is loaded and initialized.

//
//	OnBuddyPressInstalled
//
//	Called when BuddyPress is installed and active
//
function OnBuddyPressInstalled()
{
	require_once('buddypress_integration.php');
}

//
//	START Register Plus Workaround
//
//	Register Plus overrides this:
//	http://codex.wordpress.org/Function_Reference/wp_new_user_notification
//
//	Look at register-plus.php somewhere around line 1715.  More on Pluggable
//	functions can be found here:  http://codex.wordpress.org/Pluggable_Functions
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
function OverrideWarning()
{
	if( current_user_can(10) &&  $_GET['page'] == 'autochimp' )
		echo '<div id="message" class="updated fade"><p><strong>You have another plugin installed that is conflicting with AutoChimp and Register Plus.  This other plugin is overriding the user notification emails or password setting.  Please see <a href="http://www.wandererllc.com/plugins/">AutoChimp FAQ</a> for more information.</strong></p></div>';
}

if ( function_exists( 'wp_set_password' ) )
{
	// Check if the user wants to patch
	$fixRegPlus = get_option( WP88_MC_FIX_REGPLUS );
	if ( '1' === $fixRegPlus )
	{
		add_action( 'admin_notices', 'OverrideWarning' );
	}
}

//
// Override wp_set_password() which is called by Register Plus's overridden
// pluggable function - the only place I can see to grab the user's first
// and last name.
//
if ( !function_exists('wp_set_password') && '1' === get_option( WP88_MC_FIX_REGPLUS ) ) :
function wp_set_password( $password, $user_id )
{
	$lastMessage = get_option( WP88_MC_LAST_MAIL_LIST_ERROR );
	$lastMessage .= "In wp_set_password()...";

	// START original WordPress code
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
	$lastMessage .= "Register Plus is ACTIVE.";
	$lastMessage .= "YOU THERE!  Now updating $user_info->first_name $user_info->last_name ('$user_info->user_email') in list $list_id.";
	update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $lastMessage );

	update_option( WP88_MC_TEMPEMAIL, "" );
	$user_info = get_userdata( $user_id );
	ManageMailUser( MMU_UPDATE, $user_info );
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
add_filter( 'plugin_row_meta', 'AddAutoChimpPluginLinks', 10, 2 ); // Expand the links on the plugins page

//
//	Function to create the menu and admin page handler
//
function OnPluginMenu()
{
	add_submenu_page('options-general.php', 'AutoChimp Options', 'AutoChimp', 'manage_options', basename(__FILE__), AutoChimpOptions );
}

// Inspired by NextGen Gallery by Alex Rabe
function AddAutoChimpPluginLinks($links, $file)
{
	if ( $file == plugin_basename(__FILE__) )
	{
		$links[] = '<a href="http://wordpress.org/extend/plugins/autochimp/">' . __('Overview', 'autochimp') . '</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US">' . __('Donate', 'autochimp') . '</a>';
	}
	return $links;
}

//
//	This function is responsible for displaying the AutoChimp admin panel.  That
//	happens at the very bottom, with the require statement.  The rest of the code
//	is for saving the options.
//
function AutoChimpOptions()
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
		print '<div id="message" class="updated fade"><p>Saved API Key!</p></div>';
	}

	// Save off the autochimp options here
	if ( isset( $_POST['save_autochimp_options'] ) )
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
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp options!</p></div>';

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

		// Step 3:  Save the user's campaign-from-post choices

		if ( isset( $_POST['on_campaign_from_post'] ) )
			update_option( WP88_MC_CAMPAIGN_FROM_POST, "1" );
		else
			update_option( WP88_MC_CAMPAIGN_FROM_POST, "0" );

		if ( isset( $_POST['on_send_now'] ) )
			update_option( WP88_MC_SEND_NOW, "1" );
		else
			update_option( WP88_MC_SEND_NOW, "0" );

		if ( isset( $_POST['on_create_once'] ) )
			update_option( WP88_MC_CREATE_CAMPAIGN_ONCE, "1" );
		else
			update_option( WP88_MC_CREATE_CAMPAIGN_ONCE, "0" );

		$category = $_POST['campaign_category'];
		update_option( WP88_MC_CAMPAIGN_CATEGORY, $category );

		// Step 4:  Save other plugin integration choices

		if ( isset( $_POST['on_fix_regplus'] ) )
			update_option( WP88_MC_FIX_REGPLUS, "1" );
		else
			update_option( WP88_MC_FIX_REGPLUS, "0" );

		if ( isset( $_POST['on_sync_buddypress'] ) )
		{
			update_option( WP88_MC_SYNC_BUDDYPRESS, "1" );
			
			//
			// Save the mappings of BuddyPress XProfile fields to MailChimp Merge Vars
			//
			
			// Each XProfile field will have a select box selection assigned to it.
			// Save this selection.
			global $wpdb;
			$fields = $wpdb->get_results( "SELECT name,type FROM wp_bp_xprofile_fields WHERE type != 'option'", ARRAY_A );
			
			foreach( $fields as $field )
			{
				// Generate the name of the selection box by prepending the special text
				$selectName = WP88_BP_XPROFILE_FIELD_MAPPING . $field['name'];
				
				// Now dereference the selection
				$selection = $_POST[ $selectName ];
				
				// Save the selection, unless it should be ignored
				if ( "Ignore this field" !== $selection )
					update_option( $selectName, $selection );
			}
			
			// Now save the special static field and the mapping
			$staticText = $_POST[ 'static_select' ];
			update_option( WP88_MC_STATIC_TEXT, $staticText );

			$staticSelectName = WP88_BP_XPROFILE_FIELD_MAPPING . 'Static';
			if ( "Ignore this field" !== $_POST[ $staticSelectName ] )
				update_option( $staticSelectName, $_POST[ $staticSelectName ] );
			
		}
		else
			update_option( WP88_MC_SYNC_BUDDYPRESS, "0" );
	}

	// The file that will handle uploads is this one (see the "if" above)
	$action_url = $_SERVER['REQUEST_URI'];
	require_once '88-autochimp-settings.php';
}

//
//	Syncs a single user of this site with the options that the site owner has
//	selected in the admin panel.
//
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
							update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Adding $user_info->first_name $user_info->last_name ('$user_info->user_email') in list $list_id." );
//							$retval = $api->listSubscribe( $list_id, $user_info->user_email, $merge_vars );
//							if ($api->errorCode)
//							{
//								// Set latest activity - displayed in the admin panel
//								update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Problem adding $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id.  Error Code: $api->errorCode, Message: $api->errorMessage" );
//							}
//							else
//							{
//								update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Added $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id." );
//							}
						}
						case MMU_DELETE:
						{
							$lastMessage = get_option( WP88_MC_LAST_MAIL_LIST_ERROR );
							$lastMessage .= "YOU THERE!  Deleting $user_info->first_name $user_info->last_name ('$user_info->user_email') in list $list_id.";
							update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $lastMessage );
							// By default this sends a goodbye email and fires off a notification to the list owner
//							$retval = $api->listUnsubscribe( $list_id, $user_info->user_email );
//							if ($api->errorCode)
//							{
//								// Set latest activity - displayed in the admin panel
//								update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Problem removing $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.  Error Code: $api->errorCode, Message: $api->errorMessage" );
//							}
//							else
//							{
//								update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "Removed $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id." );
//							}
						}
						case MMU_UPDATE:
						{
							// Get the old email - this feels a little dangerous...'cause users have to go
							// through the profile panel.  If they don't and email is updated, data can
							// get out of sync.  See the readme.txt for more.
							$updateEmail = get_option( WP88_MC_TEMPEMAIL );

							// If this email is empty, then it means that some method other than viewing
							// the admin panel has invoked the update - another plugin like "Register
							// Plus", for instance.  In that case, there is no danger in the email getting
							// out of sync so AutoChimp can use the original email.
							if ( strlen( $updateEmail ) == 0 )
								$updateEmail = $user_info->user_email;

							// Potential update to the email address (more likely than name!)
							$merge_vars['EMAIL'] = $user_info->user_email;

							// No emails are sent after a successful call to this function.
							$lastMessage = get_option( WP88_MC_LAST_MAIL_LIST_ERROR );
							$lastMessage .= "HEY!  Updating $user_info->first_name $user_info->last_name ('$user_info->user_email') in list $list_id.";
							update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $lastMessage );
						}
					}
				}
			}
		}
	}
}

//
//	Given a post ID, creates a MailChimp campaign.  Returns STRING "-1" if the
//	creation was skipped, "0" on failure, and a legit ID on success.  Except for
//	"-1", each return point will write the latest result of the function to the
//	DB which will be visible to the user in the admin page.
//
//	Pass the post ID and an instance of the MailChimp API class (for performance).
//
function CreateCampaignFromPost( $postID, $api )
{
	$myLists = $api->lists();

	if ( null != $myLists )
	{
		$list_id = -1;

		// See if the user has selected some lists
		$selectedLists = get_option( WP88_MC_LISTS );

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

		// Put all of the selected lists into an array to search later
		$valuesArray = array();
		$valuesArray = preg_split( "/[\s,]+/", $selectedLists );

		foreach ( $myLists as $list )
		{
			$list_id = $list['id'];

			// See if this mailing list should have a campaign created for it
			foreach( $valuesArray as $searchableID )
			{
				$pos = strpos( $searchableID, $list_id );
				if ( false === $pos ){}
				else
				{
					// Time to start creating the campaign...
					// First, create the options array
					$options = array();
					$options['list_id']	= $list_id;
					$options['subject']	= $post->post_title;
					$options['from_email'] = $list['default_from_email'];
					$options['to_email'] = '*|FNAME|*';
					$options['from_name'] = $list['default_from_name'];
					$options['tracking'] = array('opens' =>	true, 'html_clicks' => true, 'text_clicks' => false );
					$options['authenticate'] = true;

					$postContent = apply_filters( 'the_content', $post->post_content );
					// Potentially an expensive call here to append text
					$permalink = get_permalink( $postID );
					$postContent .= "<p>Read the full story <a href=\"$permalink\">here</a>.</p>";
					$postContent = str_replace( ']]>', ']]&gt;', $postContent );
					$content = array();
					$content['html'] = $postContent;
					$content['text'] = strip_tags( $postContent );

					// More info here:  http://www.mailchimp.com/api/1.2/campaigncreate.func.php
					$result = $api->campaignCreate( 'regular', $options, $content );
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
						add_post_meta( $postID, WP88_MC_CAMPAIGN_CREATED, "1" );
					}

					// Done
					return $result;
				}
			}
		}
	}
}

//
//	Given a mailing list, return an array of the names of the merge variables (custom 
//	fields) for that mailing list.
//
function FetchMailChimpMergeVars( $api, $list_id )
{
	$mergeVars = array();
	$result = $api->listMergeVars( $list_id );
	foreach( $result as $i => $var ) 
	{
		$mergeVars[] = $var['name'];
	}
	return $mergeVars;
}

//
//	WordPress Action handlers
//

function OnRegisterUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onAddSubscriber = get_option( WP88_MC_ADD );
	if ( "1" == $onAddSubscriber )
	{
		ManageMailUser( MMU_ADD, $user_info );
	}
}

function OnDeleteUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onDeleteSubscriber = get_option( WP88_MC_DELETE );
	if ( "1" == $onDeleteSubscriber )
	{
		ManageMailUser( MMU_DELETE, $user_info );
	}
}

function OnAboutToUpdateUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( "1" == $onUpdateSubscriber )
	{
		$updateEmail = $user_info->user_email;
		update_option( WP88_MC_TEMPEMAIL, $updateEmail );
	}
}

function OnUpdateUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( "1" == $onUpdateSubscriber )
	{
		ManageMailUser( MMU_UPDATE, $user_info );
		update_option( WP88_MC_TEMPEMAIL, "" );
	}
}

function OnPublishPost( $postID )
{
	// Does the user want to create campaigns from posts
	$campaignFromPost = get_option( WP88_MC_CAMPAIGN_FROM_POST );
	if ( "1" == $campaignFromPost )
	{
		// Get the info on this post
		$post = get_post( $postID );
		$categories = get_the_category( $postID );	// Potentially several categories

		// What category does the user want to use to create campaigns?
		$campaignCategory = get_option( WP88_MC_CAMPAIGN_CATEGORY );

		// If it matches the user's category choice or is any category, then
		// do the work.  This needs to be a loop because a post can belong to
		// multiple categories.
		foreach( $categories as $category )
		{
			if ( $category->name == $campaignCategory || AC_DEFAULT_CATEGORY == $campaignCategory )
			{
				// Create an instance of the MailChimp API
				$apiKey = get_option( WP88_MC_APIKEY );
				$api = new MCAPI( $apiKey );

				// Do the work
				$id = CreateCampaignFromPost( $postID, $api );

				// Does the user want to send the campaigns right away?
				$sendNow = get_option( WP88_MC_SEND_NOW );

				// Send it, if necessary (if user wants it), and the $id is
				// sufficiently long (just picking longer than 3 for fun).
				if ( "1" == $sendNow && ( strlen( $id ) > 3 ) )
				{
					$api->campaignSendNow( $id );
				}

				// As soon as the first match is found, break out.
				break;
			}
		}
	}
}

?>
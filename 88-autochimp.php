<?php
/*
Plugin Name: AutoChimp
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Description: Gives users the ability to create MailChimp mail campaigns from blog posts. Also allows updating MailChimp mailing lists when users subscribe, unsubscribe, and update their WordPress profiles.
Author: Kyle Chapman
Version: 0.82
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
define( "WP88_MC_LAST_ERROR", "wp88_mc_last_error" );
define( "WP88_MC_CAMPAIGN_CREATED", "wp88_mc_campaign" );

define( "AC_DEFAULT_CATEGORY", "Any category" );

define( "MMU_ADD", 1 );
define( "MMU_DELETE", 2 );
define( "MMU_UPDATE", 3 );

define( "WP88_SEARCHABLE_PREFIX", "wp88_mc" );

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

//
//	Filters to hook
//
add_filter( 'plugin_row_meta', 'add_plugin_links', 10, 2 ); // Expand the links on the plugins page

//
//	Function to create the menu and admin page handler
//
function OnPluginMenu()
{
	add_submenu_page('options-general.php', 'AutoChimp Options', 'AutoChimp', 'manage_options', basename(__FILE__), AutoChimpOptions );
}

// Inspired by NextGen Gallery by Alex Rabe
function add_plugin_links($links, $file)
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
							$retval = $api->listSubscribe( $list_id, $user_info->user_email, $merge_vars );
                            break;
						}
						case MMU_DELETE:
						{
							// By default this sends a goodbye email and fires off a notification to the list owner
							$retval = $api->listUnsubscribe( $list_id, $user_info->user_email );
                            break;
						}
						case MMU_UPDATE:
						{
							// Get the old email - this feels a little dangerous...'cause users have to go
							// through the profile panel.  If they don't and email is updated, data can
							// get out of sync.  See the readme.txt for more.
							$updateEmail = get_option( WP88_MC_TEMPEMAIL );

							// Potential update to the email address (more likely than name!)
							$merge_vars['EMAIL'] = $user_info->user_email;

							// No emails are sent after a successful call to this function.
							$retval = $api->listUpdateMember( $list_id, $updateEmail, $merge_vars );
                            break;
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
						update_option( WP88_MC_LAST_ERROR, "Problem with campaign with title '$post->post_title'.  Error Code: $api->errorCode, Message: $api->errorMessage" );
						$result = "0";
					}
					else
					{
						// Set latest activity
						update_option( WP88_MC_LAST_ERROR, "Your latest campaign created is titled '$post->post_title' with ID: $result" );

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
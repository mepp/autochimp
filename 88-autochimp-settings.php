<div class="wrap" style="max-width:950px !important;">
<div id="poststuff" style="margin-top:10px;">
<div id="mainblock" style="width:710px">
<div class="dbx-content">

<form enctype="multipart/form-data" action="<?php echo $action_url ?>" method="POST">

<?php
require_once 'inc/MCAPI.class.php';
require_once 'ui_helpers.php';
require_once 'cimy_integration.php';
require_once 'viper_integration.php';
wp_nonce_field('mailchimpz-nonce');

// Set the master UI width here
$uiWidth = '708';
$tableWidth = '675';

$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/autochimp/';

// Fetch the Key from the DB here
$apiKey = get_option( WP88_MC_APIKEY, '0' );
// Show the mailing list tab first, unless the API key isn't set.
$openingTab = 'mailing_lists';
if ( 0 == strcmp( $apiKey, '0' ) )
	$openingTab = 'api_key';

$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : $openingTab; 
if( isset( $_GET[ 'tab' ] ) ) 
{
	// What tab did the user click on?  
    $active_tab = $_GET[ 'tab' ];
}
?>

<h2 class="nav-tab-wrapper">
	AutoChimp
	<a href="?page=88-autochimp.php&tab=mailing_lists" class="nav-tab<?php echo $active_tab == 'mailing_lists' ? ' nav-tab-active' : ''; ?>">Mailing Lists</a>
	<a href="?page=88-autochimp.php&tab=campaigns" class="nav-tab<?php echo $active_tab == 'campaigns' ? ' nav-tab-active' : ''; ?>">Campaigns</a>
	<a href="?page=88-autochimp.php&tab=plugins" class="nav-tab<?php echo $active_tab == 'plugins' ? ' nav-tab-active' : ''; ?>">Plugins</a>
	<a href="?page=88-autochimp.php&tab=api_key" class="nav-tab<?php echo $active_tab == 'api_key' ? ' nav-tab-active' : ''; ?>">API Key</a>
</h2>

<?php
//
//	API Key Management UI
//
if ( $active_tab == 'api_key' )
{
?>

	<div id="mailchimp_api_key" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>MailChimp API Key Management</span></h3>
	<div class="inside">
	
	<?php
		if ( empty( $apiKey ) )
		{
			print "<p><em>No API Key has been saved yet!</em></p>";
			print "<p>Set your Mailchimp API Key, which you can find on the <a target=\"_blank\" href=\"http://us1.admin.mailchimp.com/account/api\">MailChimp website</a>, ";
			print "in the text box below. Once the API Key is set, you will see the various options that AutoChimp provides.</p>";
			print '<p>Set Your MailChimp API Key: ';
		}
		else
		{
			print "<p>Your Current MailChimp API Key:  <strong>$apiKey</strong><p/>";
			print '<p><em>There is no need to set your API Key again unless you have acquired a new API key at <a href="http://eepurl.com/MnhD">mailchimp.com</a>.</em></p>';
			print '<p>Change Your MailChimp API Key: ';
		}
	?>
	
	<input type="text" name="api_key" size="45" /></p>
	<div class="submit"><input type="submit" class="button-primary" name="save_api_key" value="Save API Key" /></div>
	
	<div class="clear"></div>
	</div>
	</div>

<?php
}
?>

<?php
//
//	The big one:  Mailing List Management UI
//
if ( $active_tab == 'mailing_lists' )
{
?>
	<div id="mailchimp_lists" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>Mailing List Management</span></h3>
	<div class="inside">

	<?php
	if ( !empty( $apiKey ) )
	{
		// Create an object to interface with MailChimp
		$api = new MCAPI_13( $apiKey );
	
		// This array holds the lists that have been selected
		$listArray = array();
	
		//
		//	Options for managing mailing lists
		//
	
		$myLists = $api->lists();
	
		if ( null != $myLists )
		{
			$list_id = -1;
	
			// See if the user has selected some lists
			$selectedLists = get_option( WP88_MC_LISTS );
	
			// Put all of the selected lists into an array to search later
			$listArray = preg_split( '/[\s,]+/', $selectedLists );
	
			print '<p><strong>1) Which mailing lists would you like to update?</strong></p>';
			foreach ( $myLists['data'] as $list )
			{
				$listName = $list['name'];
				$list_id = $list['id'];
	
				// Form this list's ID for the list (so it's later searchable as a
				// POST variable!)
				$searchableListID = WP88_SEARCHABLE_PREFIX . $list_id;
	
				// See if this mailing list should be selected
				$selected = array_search( $searchableListID, $listArray );
	
				// Generate a checkbox here (checked if this list was selected previously)
				print "<p><input type=CHECKBOX value=\"$searchableListID\" name=\"$searchableListID\" ";
				if ( false === $selected ){} else
					print 'checked';
				print "> $listName</p>";
			}
	
			// Now add options for when to update the mailing list (add, delete, update)
			$onAddSubscriber = get_option( WP88_MC_ADD );
			$onDeleteSubscriber = get_option( WP88_MC_DELETE );
			$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
			$onBypassOptIn = get_option( WP88_MC_BYPASS_OPT_IN );
			$onDeleteMember = get_option( WP88_MC_PERMANENTLY_DELETE_MEMBERS );
			$onSendGoodbye = get_option( WP88_MC_SEND_GOODBYE );
			$onSendNotify = get_option( WP88_MC_SEND_ADMIN_NOTIFICATION );
	
			print '<p><strong>2) When would you like to update your selected Mailing Lists?</strong></p>';
	
			print '<p><input type=CHECKBOX value="on_add_subscriber" name="on_add_subscriber" ';
			if ( '0' === $onAddSubscriber ){} else
				print 'checked';
			print '> When a user subscribes <em>(Adds the user to your mailing list)</em></p>';
	
				print '<p><fieldset style="margin-left: 20px;"><input type=CHECKBOX value="on_bypass_opt_in" name="on_bypass_opt_in" ';
				if ( '1' === $onBypassOptIn )
					print 'checked';
				print '> Bypass the MailChimp double opt-in.  New registrants will <em>not</em> recieve confirmation emails from MailChimp. <em>(MailChimp <a target="_blank" href="http://www.mailchimp.com/kb/article/how-does-confirmed-optin-or-double-optin-work">does not recommend</a> abusing this so be careful)</em></fieldset></p>';
	
			print '<p><input type=CHECKBOX value="on_delete_subscriber" name="on_delete_subscriber" ';
			if ( '0' === $onDeleteSubscriber ){} else
				print 'checked';
			print '> When a user leaves your site <em>(Unsubscribes the user from your mailing list)</em></p>';
	
				print '<fieldset style="margin-left: 20px;">';

				print '<p><input type=CHECKBOX value="on_delete_member" name="on_delete_member" ';
				if ( '1' === $onDeleteMember )
					print 'checked';
				print '> Permanently delete members unsubscribing from your list from your MailChimp account.</p>';

				print '<p><input type=CHECKBOX value="on_send_goodbye" name="on_send_goodbye" ';
				if ( '1' === $onSendGoodbye )
					print 'checked';
				print '> Send your <em>Goodbye</em> email to the unsubscribing members.</p>';

				print '<p><input type=CHECKBOX value="on_send_notify" name="on_send_notify" ';
				if ( '1' === $onSendNotify )
					print 'checked';
				print '> Notify the MailChimp account admin when a member unsubscribes.</p>';

				print '</fieldset>';
	
			print '<p><input type=CHECKBOX value="on_update_subscriber" name="on_update_subscriber" ';
			if ( '0' === $onUpdateSubscriber ){} else
				print 'checked';
			print '> When a user updates his information <em>(Syncs the user with your mailing list)</em></p>';
	
			print '<p><strong>3) What additional WordPress user information do you want to sync with MailChimp?</strong></p>';
			print '<p><em>First name, last name, and email are always synchronized.</em></p>';
			print '<p>Use the following table to assign your WordPress User Fields to your MailChimp fields.  <strong>Tip:</strong> You can use the "Static Text" field at the bottom to assign the same value to each new user which will distinguish users from your site from users from other locations.</p>';
	
			//
			// START: 	Generate a list of controls here for mappings of various WordPress
			//			and other plugin user data to MailChimp fields.
			//
	
			// Hold table row output here
			$output = '';
	
			// NOTE:  This just takes the FIRST selected list!  Multiple selected lists
			// will just not work.
			$list = $listArray[ 0 ];
			
			// Strip out the searchable tag
			$list = substr_replace( $list, '', 0, strlen( WP88_SEARCHABLE_PREFIX ) );
			$mergeVars = AC_FetchMailChimpMergeVars( $api, $list );
			
			if ( empty( $mergeVars ) )
				print "<p><em><strong>Problem: </strong>AutoChimp could not retrieve your MailChimp Merge Variables. Make sure you have a selected mailing list.</em></p>";

			//
			//	Start:	Generate a table for WordPress mappings
			//	
			global $wpUserDataArray;
			foreach( $wpUserDataArray as $userField )
			{
				$fieldNameTag = AC_EncodeUserOptionName( WP88_WORDPRESS_FIELD_MAPPING, $userField );
				$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
				$output .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $userField . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
			}
	
			// This static field used to belong to the BuddyPress Sync UI, but has since
			// been moved to the main UI.  It's still represented by a DB value that makes
			// it look like it belongs to BuddyPress, so heads up.
			$selectBox = AC_GenerateSelectBox( WP88_MC_STATIC_FIELD, WP88_IGNORE_FIELD_TEXT, $mergeVars );
			$output .= '<tr class="alternate"><td width="65%">Static Text:<input type="text" name="static_select" value="' . $staticText . '"size="18" /></td><td width="30%">' . $selectBox . '</td></tr>';

			$tableCode = AC_GenerateFieldMappingCode( 'WordPress', $output );
			print $tableCode;
			//
			// END:		Generate a table for WordPress Mappings
			//

			//
			// Start:	Generate a table for BuddyPress Mappings
			//
			$syncBuddyPress = get_option( WP88_MC_SYNC_BUDDYPRESS );
			// If the user wants to sync BuddyPress AND the plugin is activated, then show
			// The UI which displays the mappings here.
			if ( '1' === $syncBuddyPress && function_exists( 'AC_ShowBuddyPressSettings' ) )
			{
				$uiOutput = AC_GenerateBuddyPressMappingsUI( $tableWidth, $mergeVars );
				print $uiOutput;
			}
			//
			// END:		Generate a table for BuddyPress Mappings
			//

			//
			// Start:	Generate a table for Cimy Mappings
			//
			$syncCimy = get_option( WP88_MC_SYNC_CIMY );
			// If the user wants to sync Cimy User Extra Fields AND the plugin is activated,
			// then show the UI which displays the mappings here.
			if ( '1' === $syncCimy && function_exists( 'get_cimyFields' ) )
			{
				$uiOutput = AC_GenerateCimyMappingsUI( $tableWidth, $mergeVars );
				print $uiOutput;
			}
			//
			// END:		Generate a table for Cimy Mappings
			//
			
			// Show the user the last message
			$lastMessage = get_option( WP88_MC_LAST_MAIL_LIST_ERROR );
			if ( empty( $lastMessage ) )
				$lastMessage = 'No mailing list activity yet.';
			print "<p><strong>Latest mailing list activity:</strong>  <em>$lastMessage</em></p>";
		}
		else
		{
			print '<p><em>Unable to retrieve your lists with this key!</em>  Did you paste it in correctly?  Visit <a href="?page=88-autochimp.php&tab=api_key">the API Key tab</a> and try again, just in case.  If you know it is correct, make sure you\'re connected to the internet and not working offline.</p>';
		}
	}
	?>
	<div class="submit"><input type="submit" name="save_mailing_list_options" class="button-primary" value="Save Options" /></div>
	
	<div class="clear"></div>
	</div>
	</div>

	<div id="manual_sync" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>Manual Sync</span></h3>
	<div class="inside">

	<p>You can also perform a <em>manual</em> sync with your existing user base.  This is recommended only once to bring existing users in sync.  After you've synchronized your users, and you use AutoChimp to keep your users in sync, <em>you should not need to do this again</em>.  <strong>Note: </strong>Depending on how many users you have, this could take a while.  Please be patient.  <strong>Also,</strong> you need to have the 'When a user updates his information' option checked above othewise your users will not sync.</p>
	<div id="manual_sync_status"></div>
	<div id="manual_sync_progressbar"></div>
	<?php
		// How many users need to be synchronized?
		$users = get_users('fields=ID');
		$numUsers = count( $users );
		// Calculate a rough time - the 3 is for 3 seconds per user.
		$totalTime = gmdate("H:i:s", $numUsers * 3);
	?>
	<div class="submit"><input type="button" name="sync_existing_users" id="sync_existing_users" value="Sync Existing Users" 
		onclick="this.disabled='true'; timerID = setInterval( getSyncProgress, 1000 ); runSyncUsers( timerID );"/>
	</div>

	<div class="clear"></div>
	</div>
	</div>
<?php
}

//
//	Campaigns UI
//
if ( $active_tab == 'campaigns' )
{
	$listHash = array();
	$groupHash = array();
	$templatesHash = array();
	if ( empty( $apiKey ) )
	{
		print "<p><em>No API Key has been saved yet!</em></p>";
	}
	else 
	{
		// Create an object to interface with MailChimp
		$api = new MCAPI_13( $apiKey );
	
		// This array holds the lists that have been selected
		$listArray = array();	
		$myLists = $api->lists();
	
		if ( null != $myLists )
		{
			// Need Javascript in order to switch out interest groups when a user changes
			// the mailing list.  ie, A change in the first select box causes the second
			// select box to change it's contents.
			$javaScript = " onchange=\"intGroupHash={};";
			// For each mailing list...
			foreach ( $myLists['data'] as $list )
			{
				$listName = $list['name'];
				$listID = $list['id'];
				// Add this list and it's ID to the hash which is used to create the select box.
				$listHash[ $listName ] = $listID;
				// Get this list's interest groups, if any.
				$newGroup = $api->listInterestGroupings( $listID );
				if ( !empty( $newGroup ) )
				{
					// Create the hash used to generate the select box for this list
					$groupHash[ $listID ] = AC_AssembleGroupsHash( $newGroup );
					// Cache those values in javascript.
					$groupCSVString = implode(',', AC_AssembleGroupsArray( $newGroup ));
					$javaScript .= "intGroupHash['$listID']='$groupCSVString'.split(',');";
				}
			}
		}
		else 
		{
			print '<p><em>Unable to retrieve your lists with this key!</em>  Did you paste it in correctly?  Visit <a href="?page=88-autochimp.php&tab=api_key">the API Key tab</a> and try again, just in case.  If you know it is correct, make sure you\'re connected to the internet and not working offline.</p>';
		}

		// Now get the templates		
		$types = array('user'=>true);
		$ret = $api->templates($types);
		foreach( $ret['user'] as $t )
		{
			$templatesHash[$t['name']] = $t['id'];
		}
	}
	// Load the options from the DB
	$excerptOnly = get_option( WP88_MC_CAMPAIGN_EXCERPT_ONLY );
	$createOnce = get_option( WP88_MC_CREATE_CAMPAIGN_ONCE );
	$sendNow = get_option( WP88_MC_SEND_NOW );

	// If $createOnce isn't set, default to "1"
	if ( 0 == strlen( $createOnce ) )
	{
		$createOnce = '1';
		update_option( WP88_MC_CREATE_CAMPAIGN_ONCE, $createOnce );
	}
?>
	<div id="campaigns" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>Mail Campaigns from Posts</span></h3>
	<div class="inside">

	<p><strong>Choose how you'd like to create campaigns from your post categories.</strong> <em>If you use a 'user template', be sure that the template's content section is called 'main' so that your post's content can be substituted in the template.</em></p>
	<p><fieldset style="margin-left: 20px;"><table>
		<tr><th>Category</th><th>Mailing List</th><th></th><th>Interest Group</th><th></th><th>User Template</th></tr>
	<?php
		// Fetch this site's categories
		$category_args=array(	'orderby' => 'name',
		  						'order' => 'ASC',
		  						'hide_empty' => 0 );
		$categories=get_categories( $category_args );

		// Loop through each category and create a management row for it.
		foreach($categories as $category)
		{
			$categoryOptionName = AC_EncodeUserOptionName( WP88_CATEGORY_LIST_MAPPING , $category->name );
			$categoryOptionGroupName = $categoryOptionName . WP88_CATEGORY_GROUP_SUFFIX;
			$categoryOptionTemplateName = $categoryOptionName . WP88_CATEGORY_TEMPLATE_SUFFIX;

			// Assemble the final Javascript
			$finalJavaScript = $javaScript . "switchInterestGroups('$categoryOptionGroupName',this.value,intGroupHash);\"";

			// Assemble the first select box's HTML
			print '<tr><td><em>' . $category->name . '</em> campaigns go to</td>' . PHP_EOL . '<td>';
			$selectBox = AC_GenerateSelectBox( $categoryOptionName, WP88_NO_MAILING_LIST, $listHash, $finalJavaScript );
			print $selectBox . '</td>' . PHP_EOL;
			
			// Start assembling the second select box
			print '<td>and group</td><td>';
			$selectedlist = get_option( $categoryOptionName );
			$selectBox = AC_GenerateSelectBox( $categoryOptionGroupName, WP88_ANY_GROUP, $groupHash[ $selectedlist ] );
			print $selectBox . '</td>' . PHP_EOL;
			print '<td>using</td><td>';
			
			$selectBox = AC_GenerateSelectBox( $categoryOptionTemplateName, WP88_NO_TEMPLATE, $templatesHash );
			print $selectBox . '</td></tr>' . PHP_EOL;
		}
		
		print '</table></fieldset></p>';		
	
		// Create a checkbox asking the user if they want to send campaigns right away
		print '<p><input type=CHECKBOX value="on_send_now" name="on_send_now" ';
		if ( '0' === $sendNow || empty( $sendNow ) ){} else
			print 'checked';
		print '> Send campaign <em>as soon as</em> a post is published. Not checking this option will save a draft version of your new MailChimp campaign.</p>';

		// Create a checkbox asking if the user wants to generate only excerpts
		print '<p><input type=CHECKBOX value="on_excerpt_only" name="on_excerpt_only"';
		if ( '1' === $excerptOnly )echo 'checked';
		print '></input> Only use an excerpt of the post (AutoChimp will include a link back to the post). <em>If you wrote an excerpt, that excerpt will be used.  Otherwise, the first 50 words of the post will be used.</em></p>';
	
		// Create a checkbox asking the user if they want to suppress additional campaigns when posts are updated
		print '<p><input type=CHECKBOX value="on_create_once" name="on_create_once" ';
		if ( '0' === $createOnce || empty( $createOnce ) ){} else
			print 'checked';
		print '> Create a campaign only once. Not checking this option will create an additional campaign each time you update your post. <em>Recommended <strong>ON</strong></em></p>';
	
		// Show the user the last message
		$lastMessage = get_option( WP88_MC_LAST_CAMPAIGN_ERROR );
		if ( empty( $lastMessage ) )
			$lastMessage = 'No campaign activity yet.';
	
		print "<p><strong>Latest campaign activity:</strong>  <em>$lastMessage</em></p>";
	?>
	<div class="submit"><input type="submit" name="save_campaign_options" class="button-primary" value="Save Options" /></div>
	
	<div class="clear"></div>
	</div>
	</div>
<?php
}
?>

<?php
//
//	External Plugin Management
//
if ( $active_tab == 'plugins' )
{
	$fixRegPlus = get_option( WP88_MC_FIX_REGPLUS );
	$fixRegPlusRedux = get_option( WP88_MC_FIX_REGPLUSREDUX );
?>
	<div id="plugin_integration" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>External Plugin Integration and Synchronization</span></h3>
	<div class="inside">
	
	<?php
		print '<p>AutoChimp provides integration and bux fixes for other plugins. If you are using any of these plugins, they will be listed here:</p>';
	
		if ( class_exists( 'RegisterPlusPlugin' ) )
		{
			print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/register-plus/">Register Plus</a></strong> which has a known issue preventing first and last name being synchronized with MailChimp. <em>AutoChimp can fix this</em>.</p>';
			print '<fieldset style="margin-left: 20px;">';
			print '<p><input type=CHECKBOX value="on_fix_regplus" name="on_fix_regplus" ';
			if ( '1' === $fixRegPlus )
				print "checked";
			print '> Patch Register Plus and sync first/last name with your selected mailing list. <em>Recommended <strong>ON</strong></em>.</p>';
			print '<p><em>News:</em> Register Plus <strong>Redux</strong> is the latest version of "Register Plus".  Please switch to the latest version of Register Plus Redux.</p>';
			print '</fieldset>';
		}
	
		if ( class_exists( 'RegisterPlusReduxPlugin' ) )
		{
			print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/register-plus-redux/">Register Plus Redux</a></strong> which has a known issue preventing first and last name being synchronized with MailChimp. <em>AutoChimp can fix this</em>.</p>';
			print '<fieldset style="margin-left: 20px;">';
			print "<p><input type=CHECKBOX value=\"on_fix_regplusredux\" name=\"on_fix_regplusredux\" ";
			if ( '1' === $fixRegPlusRedux )
				print "checked";
			print '> Patch Register Plus Redux and sync first/last name with your selected mailing list. <em>Recommended <strong>ON</strong></em>. <strong>Note:</strong> You must enable "<em>Require new users enter a password during registration...</em>" in your Register Plus Redux options in order for the AutoChimp patch to work.</p>';
			print '<p><em>News:</em> Sorry to the folks who were hoping that Register Plus Redux version 3.7.0 and up would fix this.  This patch is still required when running Register Plus Redux.  More info can be found <a href="http://radiok.info/blog/conflicts-begone/" target="_blank">here</a>.</p>';
			print '</fieldset>';
		}
	
		if ( function_exists( 'AC_ShowBuddyPressSettings' ) )
		{
			AC_ShowBuddyPressSettings();
		}
	
		if ( function_exists( 'get_cimyFields' ) )
		{
			AC_ShowCimySettings();
		}
		
		if ( class_exists( 'VipersVideoQuicktags' ) )
		{
			AC_ShowViperSettings();
		}
	?>
	
	<div class="submit"><input type="submit" name="save_plugin_options" class="button-primary" value="Save Options" /></div>
	
	<div class="clear"></div>
	</div>
	</div>

<?php
}
// End of all tab-specific code
?>

</form>

<?php 
	AC_ShowSupportInfo( $uiWidth );
?>

</div>
</div>
</div>
</div>

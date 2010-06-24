<div class="wrap" style="max-width:950px !important;">
<h2>AutoChimp</h2>
<div id="poststuff" style="margin-top:10px;">
<div id="mainblock" style="width:710px">
<div class="dbx-content">

<form enctype="multipart/form-data" action="<?php echo $action_url ?>" method="POST">

<?php
require_once 'inc/MCAPI.class.php';
wp_nonce_field('mailchimpz-nonce');

$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/';
?>

<div style="float:right;width:220px;margin-left:10px;border: 1px solid #ddd;background: #fdffee; padding: 10px 0 10px 10px;">
 	<h2 style="margin: 0 0 5px 0 !important;">Information</h2>
 	<ul id="dbx-content" style="text-decoration:none;">
    	<li><img src="<?php echo $pluginFolder;?>help.png"><a style="text-decoration:none;" href="http://www.wandererllc.com/company/plugins/autochimp"> Support and Help</a></li>
		<li><a style="text-decoration:none;" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US"><img src="<?php echo $pluginFolder;?>paypal.gif"></a></li>
	</ul>
</div>

	<?php
		// Fetch the Key from the DB here
		$apiKey = get_option( WP88_MC_APIKEY );
		if ( empty( $apiKey ) )
		{
			print "<p><em>No API Key has been saved yet!</em></p>";
			print "<p>Set your Mailchimp API Key, which you can find on the <a href=\"http://us1.admin.mailchimp.com/account/api\">MailChimp website</a>, ";
			print "in the text box below. Once the API Key is set, you will see your mailing lists listed below and you will be able to ";
			print "select which lists you wish to automatically add new subscribers to.</p>";
		}
		else
		{
			print "<p>Your Current MailChimp API Key:  <strong>$apiKey</strong><p/>";
			print "<p><em>There is no need to set your API Key again unless you have acquired a new API key at mailchimp.com.</em></p>";
		}
	?>

	<p>Set Your MailChimp API Key: <input type="text" name="api_key" size="55" /></p>
	<div class="submit"><input type="submit" name="save_api_key" value="Save API Key" /></div>

	<?php
		// Show available lists here - Only do this if apiKey exists
		if ( !empty( $apiKey ) )
		{
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

				print "<p>Which mailing lists would you like to update?</p>";
				print "<ul>";
				foreach ( $myLists as $list )
				{
					$listName = $list['name'];
					$list_id = $list['id'];

					// Form this plugin's ID for the list (so it's searchable!)
					$searchableListID = WP88_SEARCHABLE_PREFIX . $list_id;

					// See if this mailing list should be selected
					$selected = array_search( $searchableListID, $valuesArray );

					// Generate a checkbox here (checked if this list was selected previously)
					print "<li><input type=CHECKBOX value=\"$searchableListID\" name=\"$searchableListID\" ";
					if ( false === $selected ){} else
						print "checked";
					print "> $listName</li>";
				}

				print "</ul>";

				// Now add options for when to update the mailing list (add, delete, update)
				$onAddSubscriber = get_option( WP88_MC_ADD );
				$onDeleteSubscriber = get_option( WP88_MC_DELETE );
				$onUpdateSubscriber = get_option( WP88_MC_UPDATE );

				print "<p>When would you like to update your selected Mailing Lists?</p>";
				print "<ul>";

				print "<li><input type=CHECKBOX value=\"on_add_subscriber\" name=\"on_add_subscriber\" ";
				if ( "0" === $onAddSubscriber ){} else
					print "checked";
				print "> When a user subscribes</li>";

				print "<li><input type=CHECKBOX value=\"on_delete_subscriber\" name=\"on_delete_subscriber\" ";
				if ( "0" === $onDeleteSubscriber ){} else
					print "checked";
				print "> When a user unsubscribes</li>";

				print "<li><input type=CHECKBOX value=\"on_update_subscriber\" name=\"on_update_subscriber\" ";
				if ( "0" === $onUpdateSubscriber ){} else
					print "checked";
				print "> When a user updates his information (see readme.txt for special info)</li>";

				print "</ul>";

				print "	<div class=\"submit\"><input type=\"submit\" name=\"save_mailing_lists\" value=\"Save Mailing List Options\" /></div>";
			}
			else
			{
				print "<p><em>Unable to retrieve your lists with this key!</em>  Did you paste it in correctly?</p>";
			}
		}
	?>

</form>

</div>
</div>
</div>
</div>

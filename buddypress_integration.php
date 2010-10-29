<?php
function ShowBuddyPressUI()
{
	// Need to query data in the BuddyPress extended profile table
	global $wpdb;

	// Get settings
	$syncBuddyPress = get_option( WP88_MC_SYNC_BUDDYPRESS );

	// Start outputting UI
	print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/buddypress/">BuddyPress</a></strong>. With AutoChimp, you can synchronize your BuddyPress user Profile Fields with your selected MailChimp mailing lists.</p>';
	print '<fieldset style="margin-left: 20px;">';

	print '<p><input type=CHECKBOX value="on_sync_buddypress" name="on_sync_buddypress" ';
	if ( '1' === $syncBuddyPress )
		print 'checked';
	print '> Sync BuddyPress Profile Fields with Mail Chimp.</p>';
	print '<p>Use the following table to assign your BuddyPress Profile Fields to your MailChimp fields.  You can use the static field at the bottom to assign the same value to each new user which will distinguish users from your site from users from other locations.</p>';

	// Create a select box from Mail Chimp fields
	$selectBox = '<select name="folder_to_delete">';
	// Loop through each sub folder
	$mcFields = array( "Ignore this field", "Name", "Birthday", "Clown or not", "Hometown", "Favorite Team", "Favorite Movie", "Something else" );
	foreach( $mcFields as $mcField )
	{
		// print an option for each folder
		$selectBox .= '<option>' . $mcField . '</option>';
	}
	$selectBox .= '</select>';

	$fields = $wpdb->get_results( "SELECT name,type FROM wp_bp_xprofile_fields WHERE type != 'option'", ARRAY_A );
	if ( $fields )
	{
		foreach ( $fields as $field )
		{
			$output .= '<tr class="alternate"><td width="50%">' . $field['name'] . '</td><td width="20%">' . $field['type'] . '</td><td width="30%">' . $selectBox . '</td></tr>';
		}
		$output .= '<tr class="alternate"><td width="50%">Text:<input type="text" name="buddypress_static" size="25" /></td><td width="20%">static</td><td width="30%">' . $selectBox . '</td></tr>';
		$tableText .= '<div id=\'filelist\'>';
		$tableText .= '<table class="widefat" style="width:650px">
				<thead>
				<tr>
					<th scope="col">Profile Field</th>
					<th scope="col">Type</th>
					<th scope="col">Assign to:</th>
				</tr>
				</thead>';
		$tableText .= $output . PHP_EOL;
		$tableText .= '</table>' . PHP_EOL . '</div>' . PHP_EOL;
		print $tableText;
	}

	print '<p>You can also perform a one-time sync with your existing user base.  This is recommended <em>only once</em>.  After you\'ve synchronized your users, and have the "Sync" checkbox checked, you will not need to do this again.';
	print '<div class="submit"><input type="submit" name="sync_buddy_press" value="Sync BuddyPress Users" /></div></p>';

	print '</fieldset>';
}
?>
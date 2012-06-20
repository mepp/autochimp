<?php
function AC_ShowCimyUI( $api )
{
	// Need to query data in the BuddyPress extended profile table
	global $wpdb;

	// Get settings
	$syncCimy = get_option( WP88_MC_SYNC_CIMY );
	$staticText = get_option( WP88_MC_STATIC_TEXT );

	// Start outputting UI
	print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/cimy-user-extra-fields/">Cimy User Extra Fields</a></strong>. With AutoChimp, you can automatically synchronize your Cimy User Fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
	print '<fieldset style="margin-left: 20px;">';

	// Create a hidden field just to signal that the user can save their preferences
	// even if the sync button isn't checked
	print '<input type="hidden" name="cimy_running" />';
	print '<p><input type=CHECKBOX value="on_sync_cimy" name="on_sync_cimy" ';
	if ( '1' === $syncCimy )
		print 'checked';
	print '> Automatically Sync Cimy User Extra Fields with MailChimp.</p>';
	
	print '</fieldset>';

}
?>
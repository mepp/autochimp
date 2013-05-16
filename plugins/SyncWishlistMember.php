<?php
define( 'AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC', 'wp88_mc_sync_wishlistmember' );
define( 'AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING', 'wp88_mc_wishlistmember_' ); // DB field prefix for mappings

class SyncWishlistMember extends ACSyncPlugin
{
	public function SyncWishlistMember()
	{
	}

	public static function GetInstalled()
	{
		return class_exists('WishListMemberCore');
	}
	
	public static function GetUsePlugin()
	{
		return get_option( AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC );
	}

	public function RegisterHooks()
	{
		// This is no longer necessary since registering on Wishlist also triggers
		// the standard user_register action.
		//AC_Log( 'Registering wishlistmember_after_registration for Wishlist.' );
		//add_action( 'wishlistmember_after_registration', 'SyncWishlistMember::OnRegistrationComplete' );
	}
	
	public static function GetSyncVarName()
	{
		return 'on_sync_wishlistmember';
	}
	
	public static function GetSyncDBVarName()
	{
		return AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC;
	}

	//
	//	Function for displaying the UI for WPMembers integration.  
	//
	public function ShowSettings()
	{
		// Get settings
		$sync = SyncWishlistMember::GetUsePlugin();
		$varName = SyncWishlistMember::GetSyncVarName();
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://member.wishlistproducts.com/">Wishlist Member</a></strong>. With AutoChimp, you can automatically synchronize your Wishlist Member user profile fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $sync )
			print 'checked';
		print '> Automatically sync Wishlist Member profile fields with MailChimp.</p>';
		print '</fieldset>';
	}
	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		$finalText = '';
		return $finalText;
	}
	
	//
	//	This function uses the global $_POST variable..
	//
	public function SaveMappings()
	{
		// Select the fields from the options table (unserialized by WordPress)
		$fields = get_option( WP_MEMBERS_FIELDS );
		foreach( $fields as $field )
		{
			// Check that there's a string.  Sometimes WP-Members will have 
			// obnoxious empty arrays.
			if ( 0 == strlen( $field[2] ) )
				continue;

			// Encode the name of the field
			$selectName = AC_EncodeUserOptionName( WP_MEMBERS_FIELD_DB_MAPPING, $field[2] );
	
			// Now dereference the selection
			$selection = $_POST[ $selectName ];
	
			// Save the selection
			update_option( $selectName, $selection );
		}
	}
	
	//
	//	Looks up the user's Wishlist Member data and returns an array formatted for 
	//	MailChimp of fields mapped to data for the user.  The WP-Members plugin 
	//	saves user data in the wp_usermeta table, which makes things easy.
	//
	public function FetchMappedData( $userID )
	{
		// User data array
		$dataArray = array();
		return $dataArray;
	}
}
?>
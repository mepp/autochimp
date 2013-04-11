<?php
define( 'AUTOCHIMP_DB_EVENTS_MANAGER_PUBLISH', 'wp88_mc_events_manager_publish' );
define( 'AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX', 'wp88_mc_events_manager_post_type_' );

class PublishEventsManager extends ACPlugin
{
	public function PublishEventsManager()
	{
	}

	public static function GetInstalled()
	{
		return class_exists( 'EM_Scripts_and_Styles' );
	}
	
	public static function GetUsePlugin()
	{
		return get_option( AUTOCHIMP_DB_EVENTS_MANAGER_PUBLISH );
	}
	
	public static function GetPublishVarName()
	{
		return 'on_publish_events_manager';
	}
	
	public static function GetPostTypeVarPrefix()
	{
		return 'on_events_manager_post_';
	}
	
	public function RegisterHooks()
	{
		// Save the option to publish each post type
		foreach ( $this->m_PostTypes as $postType )
		{
			$optionName = AC_EncodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			if ( '1' === get_option( $optionName, '0' ) )
			{
				$method = 'PublishEventsManager::OnPublish' . ucfirst( $postType );
				//print "<p>Registering PublishEventsManager post type '$postType' running on $method().</p>";
				add_action( "publish_$postType", $method );
			}
		}
	}

	//
	//	Function for displaying the UI for Events Manager integration.  
	//
	public function ShowSettings()
	{
		// Get settings
		$publish = PublishEventsManager::GetUsePlugin();
		$varName = PublishEventsManager::GetPublishVarName();
		
		// UI that shows if the plugin should be supported
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/events-manager/">Events Manager</a></strong>. With AutoChimp, you can automatically publish Events Manager post types to your MailChimp mailing list.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $publish )
			print 'checked';
		print '> Create campaigns in MailChimp from Events Manager posts.</p>';

		// UI to display and publish the plugin's custom post types.
		print '<fieldset style="margin-left: 20px;">';
		print '<strong>Post Type Settings</strong> (Which Events Manager post types would you like to create campaigns for)';
		foreach ( $this->m_PostTypes as $postType )
		{
			$varName = AC_EncodeUserOptionName( PublishEventsManager::GetPostTypeVarPrefix(), $postType );
			$optionName = AC_EncodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			$publish = get_option( $optionName );
			print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
			if ( '1' === $publish )
				print 'checked';
			print '> ' . ucfirst( $postType ) . '</p>';
		}
		print '</fieldset>';

		print '</fieldset>';
	}

	//
	//	This method saves the settings that are displayed in the ShowSettings method.
	//	It relies on the _POST hash.
	//
	public function SaveSettings()
	{
		// Save the option to turn on the plugin
		$publish = PublishEventsManager::GetPublishVarName();
		AC_SetBooleanOption( $publish, AUTOCHIMP_DB_EVENTS_MANAGER_PUBLISH );
		
		// Save the option to publish each post type
		foreach ( $this->m_PostTypes as $postType )
		{
			$varName = AC_EncodeUserOptionName( PublishEventsManager::GetPostTypeVarPrefix(), $postType );
			$optionName = AC_EncodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			AC_SetBooleanOption( $varName, $optionName );
		}
	}
	
	//
	//	Action hooks for the supported posts types
	//
	public static function OnPublishEvents( $postID )
	{

	}

	public static function OnPublishLocations( $postID )
	{

	}

	public static function OnPublishBookings( $postID )
	{

	}

	// Array of custom post types that Events Manager supports
	protected $m_PostTypes = array( 'events', 'locations', 'bookings' );
}
?>
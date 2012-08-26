<?php

//
//	Function for displaying the UI for BuddyPress integration.  If this function
//	exists (because this file has been included), then it means that BuddyPress
//	is installed.  See "bp_init" action. 
//
function AC_ShowBuddyPressSettings()
{
	// Get settings
	$syncBuddyPress = get_option( WP88_MC_SYNC_BUDDYPRESS );

	// Start outputting UI
	print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/buddypress/">BuddyPress</a></strong>. With AutoChimp, you can automatically synchronize your BuddyPress user Profile Fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
	print '<fieldset style="margin-left: 20px;">';
	print '<p><input type=CHECKBOX value="on_sync_buddypress" name="on_sync_buddypress" ';
	if ( '1' === $syncBuddyPress )
		print 'checked';
	print '> Automatically Sync BuddyPress Profile Fields with MailChimp.</p>';
	print '</fieldset>';
}

function AC_GenerateBuddyPressMappingsUI( $tableWidth, $mergeVars )
{
	// Need to query data in the BuddyPress extended profile table
	global $wpdb;

	// Temporary variable for helping generate UI
	$rowCode = $finalText = '';

	$xprofile_table_name = $wpdb->prefix . 'bp_xprofile_fields';
	$fields = $wpdb->get_results( "SELECT name,type FROM $xprofile_table_name WHERE type != 'option'", ARRAY_A );
	// Create a hidden field just to signal that the user can save their preferences
	$finalText = '<br />'.PHP_EOL.'<input type="hidden" name="buddypress_running" />'.PHP_EOL;

	if ( $fields )
	{
		foreach ( $fields as $field )
		{
			// Generate a select box for this particular field
			$fieldNameTag = AC_EncodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['name'] );
			$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
			$rowCode .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $field['name'] . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
		}

		$finalText .= AC_GenerateFieldMappingCode( 'BuddyPress', $rowCode );
	}
	return $finalText;
}

//
//	This function uses the global $_POST variable, so only call it at the appropriate times.
//	Consider refactoring this function to make it not dependent on $_POST.
//
function AC_SaveBuddyPressMappings()
{
	// Each XProfile field will have a select box selection assigned to it.
	// Save this selection.
	global $wpdb;
	$xprofile_table_name = $wpdb->prefix . 'bp_xprofile_fields';
	$fields = $wpdb->get_results( "SELECT name,type FROM $xprofile_table_name WHERE type != 'option'", ARRAY_A );

	foreach( $fields as $field )
	{
		// Encode the name of the field
		$selectName = AC_EncodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['name'] );

		// Now dereference the selection
		$selection = $_POST[ $selectName ];

		// Save the selection
		update_option( $selectName, $selection );
	}
}

//
//	Looks up the user's BP XProfile data and returns a meaningful array of
//	associations to the user based on what the AutoChimp needs to sync.
//
function AC_FetchMappedXProfileData( $userID )
{
	// User data array
	$dataArray = array();

	// Need to query data in the BuddyPress extended profile table
	global $wpdb;
	
	// Generate table names
	$option_table = $wpdb->prefix . 'options';
	$xprofile_data_table = $wpdb->prefix . 'bp_xprofile_data';
	$xprofile_fields_table = $wpdb->prefix . 'bp_xprofile_fields';
	
	// Now, see which XProfile fields the user wants to sync.
	$sql = "SELECT option_name,option_value FROM $option_table WHERE option_name LIKE '" .
			WP88_BP_XPROFILE_FIELD_MAPPING .
			"%' AND option_value != '" .
			WP88_IGNORE_FIELD_TEXT . "'";
	$fieldNames = $wpdb->get_results( $sql, ARRAY_A );

	// Loop through each field that the user wants to sync and hunt down the user's
	// values for those fields and stick them into an array.
	foreach ( $fieldNames as $field )
	{
		$optionName = AC_DecodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['option_name'] );

		// Big JOIN to get the user's value for the field in question
		// Best to offload this on SQL than PHP.
		$sql = "SELECT name,value,type FROM $xprofile_data_table JOIN $xprofile_fields_table ON $xprofile_fields_table.id = $xprofile_data_table.field_id WHERE user_id = $userID AND name = '$optionName' LIMIT 1";
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Populate the data array
		if ( !empty( $results[0] ) )
		{
			$value = $results[0]['value'];

			// Now convert a checkbox type to a string
			if ( 0 === strcmp( $results[0]['type'],"checkbox" ) )
			{
				$checkboxData = unserialize( $value );
				$value = "";
				foreach( $checkboxData as $item )
				{
					$value .= $item . ',';
				}
				$value = rtrim( $value, ',' );
			}

			$dataArray[] = array( 	"name" => $optionName,
									"tag" => $field['option_value'],
									"value" => $value );
		}
	}
	return $dataArray;
}

?>
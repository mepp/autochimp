<?php

//
//	This function will generate table code that third party plugins can use to 
//  generate mappings between their user fields and MailChimp's fields.
//
//	$pluginName - pass in the name of the third-party plugin that you want to support.
//	This function will add a table header with this string in the heading.
//	$rowCode - pass in row code (everything within each "<tr></tr>") and this function
//  will return the full table code used to generate the mapping UI.
//
function AC_GenerateFieldMappingCode( $pluginName, $rowCode )
{
	// Generate the table now
	$tableText .= '<div id=\'filelist\'>' . PHP_EOL;
	$tableText .= '<table class="widefat" style="width:<?php echo $tableWidth; ?>px">
			<thead>
			<tr>
				<th scope="col">'.$pluginName.' User Field:</th>
				<th scope="col">Assign to MailChimp Field:</th>
			</tr>
			</thead>' . PHP_EOL;
	$tableText .= $rowCode;
	$tableText .= '</table>' . PHP_EOL . '</div>' . PHP_EOL;
	return $tableText;
}

//
//	This helpier function generates HTML select box code that can be used for selecting
//	a MailChimp mapping.  You typically call this function for each field that you want
//	to map a value in MailChimp.  This function will also auto-select a value by
// 	searching for the mapping string (stored in the options table) within the hash
//	of options passed in.
//
//	$selectBox - the name of the select box (so it can be identified later)
//	$specialOption - A special option value.  Typically, "All" or "None"
//	$options - A hash of options which maps option name to value.
//	$javaScript - Optional JavaScript (leave this argument out if you don't need it)
//	that's attached to the select box.  Most common usage, of course, is the onClick()
//	function.
//
function AC_GenerateSelectBox( $selectName, $specialOption, $options, $javaScript='')
{
	// See which field should be selected (if any)
	$selectedVal = get_option( $selectName );

	// Create a select box from MailChimp merge values
	$selectBox = '<select name="' . $selectName . '"' . $javaScript . '>' . PHP_EOL;

	// Create the special option
	$selectBox .= '<option>' . $specialOption . '</option>' . PHP_EOL;
	
	if ( !empty( $options ) )
	{
		// Loop through each merge value; use the name as the select
		// text and the tag as the value that gets selected.  The tag
		// is what's used to lookup and set values in MailChimp.
		foreach( $options as $field => $tag )
		{
			// Not selected by default
			$sel = '<option value="' . $tag . '"';
	
			// Should it be $tag?  Is it the same as the tag that the user selected?
			// Remember, the tag isn't visible in the combo box, but it's saved when
			// the user makes a selection.
			if ( 0 === strcmp( $tag, $selectedVal ) )
				$sel .= ' selected>';
			else
				$sel .= '>';
	
			// print an option for each merge value
			$selectBox .= $sel . $field . '</option>' . PHP_EOL;
		}
	}
	$selectBox .= '</select>' . PHP_EOL;
	// Return the HTML code
	return $selectBox;
}

//
//	This helper function generates the name of a field mapping (from WordPress or a 
//	supported plugin) to MailChimp for the database.  It generates this with a prefix
//	that is unique to the plugin and an option name.  It also cleans up any special
//	characters that are DB-sensitive.  It's not perfect, but can be extended in the
//	future to support other strange third party naming schemes.  If this code is
//	changed, just make sure that it doesn't break existing supported plugins.
//
//	$encodePrefix - the DB option name prefix.  Make sure this is unique to the
//	plugin being supported.
//	$optionName - the name of the option in the plugin.  This string is determined
//	by the supported plugin itself.
//
function AC_EncodeUserOptionName( $encodePrefix, $optionName )
{
	// Tack on the prefix to the option name
	$encoded = $encodePrefix . $optionName;

	// Make sure the option name has no spaces; replace them with hash tags.
	// Not using underscores or dashes since those are commonly used in place
	// of spaces.  If an option name has "#" in it, then this scheme breaks down.
	$encoded = str_replace( ' ', '#', $encoded );
	
	// Periods are also problematic, as reported on 8/7/12 by Katherine Boroski.
	$encoded = str_replace( '.', '*', $encoded );
	
	// "&" symbols are problematic, as reported on 8/23/12 by Enrique.
	$encoded = str_replace( '&', '_', $encoded );

	return $encoded;
}

//
//	This function is the inverse of the Encode function.  Given a decode prefix and
//	and the encoded option name, strips out the prefix, decodes special characters,
//	and returns the original option name.
//
//	Note that if you change the Encode function, then you must also change this one
//	and vice versa.
//
function AC_DecodeUserOptionName( $decodePrefix, $optionName )
{
	// Strip out the searchable tag
	$decoded = substr_replace( $optionName, '', 0, strlen( $decodePrefix ) );

	// Replace hash marks with spaces, asterisks with periods, etc.
	$decoded = str_replace( '#', ' ', $decoded );
	$decoded = str_replace( '*', '.', $decoded );
	$decoded = str_replace( '_', '&', $decoded );

	return $decoded;
}

//
//	Sets options for all kinds of AutoChimp variables.  Uses the _POST hash, so
//	this function is typically used when forms are submitted.
//
//	$postVar - the name of the HTML option (stored in the _POST hash)
//	$optionName - the name of the option in the database
//
function AC_SetBooleanOption( $postVar, $optionName )
{
	if ( isset( $_POST[$postVar] ) )
		update_option( $optionName, '1' );
	else
		update_option( $optionName, '0' );
}

//
//	Logger for AutoChimp.  Enable WP_DEBUG in wp-config.php to get messages.
//  Well, actually, the WP_DEBUG doesn't seem to work for me, but this does:
//
//		tail -f error.log | grep -i 'AutoChimp'
//
//	Run the above command on the Apache error log file.
//
function AC_Log( $message )
{
	if ( TRUE === WP_DEBUG )
	{
        if ( is_array( $message ) || is_object( $message ) )
        {
            error_log( "AutoChimp: " . print_r( $message, true ) );
        }
        else
        {
            error_log( "AutoChimp: $message" );
        }
    }
}

//
// This function is not needed by third-party developers of plugins that add support
// for other third party WordPress plugins (like BuddyPress, Wishlist, etc.).  This
// function just shows some support info and affiliate ads to help support the plugin.
//
function AC_ShowSupportInfo( $uiWidth )
{
	$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/autochimp/';
?>
	<div id="info_box" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>Support and Help</span></h3>
	<div class="inside">
	<table border="0">
		<tr>
			<td>
				<img src="<?php echo $pluginFolder;?>help.png"><a style="text-decoration:none;" href="http://www.wandererllc.com/company/plugins/autochimp" target="_blank"> Support and Help</a>,
				<br />
				<a style="text-decoration:none;" href="http://www.wandererllc.com/company/contact/" target="_blank">Custom plugins</a>,
				<br />
				Leave a <a style="text-decoration:none;" href="http://wordpress.org/extend/plugins/autochimp/" target="_blank">good rating</a>.
			</td>
			<td><a href="http://member.wishlistproducts.com/wlp.php?af=1080050" target="_blank"><img src="http://www.wishlistproducts.com/affiliatetools/images/WLM_120X60.gif" border="0"></a></td>
			<td><a href="http://themeforest.net?ref=Wanderer" target="_blank"><img src="http://envato.s3.amazonaws.com/referrer_adverts/tf_125x125_v5.gif" border=0 alt="ThemeForest - Premium WordPress Themes" width=125 height=125></a></td>
		</tr>
	</table>
	</div>
	</div>
<?php	
}

?>
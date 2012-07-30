<?php

//
//	Pass in a plugin name ("Cimy", "BuddyPress", or just "WordPress") and also table row
//  code for the supported user fields and this code will wrap it in a table.
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
//	Takes three arguments:
//	1) The name of the select box (so it can be identified later)
//	2) A "special" option value.  Typically, "All" or "None"
//	3) A hash of options which maps option name to value.
//
//	The key to AutoChimp's efficiency is that the name of the select box and
//	the option_name field in the database are the same.
//
function AC_GenerateSelectBox( $selectName, $specialOption, $optionArray, $javaScript='')
{
	// See which field should be selected (if any)
	$selectedVal = get_option( $selectName );

	// Create a select box from MailChimp merge values
	$selectBox = '<select name="' . $selectName . '"' . $javaScript . '>' . PHP_EOL;

	// Create the special option
	$selectBox .= '<option>' . $specialOption . '</option>' . PHP_EOL;
	
	if ( !empty( $optionArray ) )
	{
		// Loop through each merge value; use the name as the select
		// text and the tag as the value that gets selected.  The tag
		// is what's used to lookup and set values in MailChimp.
		foreach( $optionArray as $field => $tag )
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
	return $selectBox;
}

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
			<td><a style="text-decoration:none;" href="http://www.wandererllc.com/company/plugins/autochimp/contributelink/" target="_blank"><img src="http://www.wandererllc.com/company/plugins/autochimp/contributeimage/"></a></td>
			<td><a href="http://member.wishlistproducts.com/wlp.php?af=1080050" target="_blank"><img src="http://www.wishlistproducts.com/affiliatetools/images/WLM_120X60.gif" border="0"></a></td>
			<td><a href="http://themeforest.net?ref=Wanderer" target="_blank"><img src="http://envato.s3.amazonaws.com/referrer_adverts/tf_125x125_v5.gif" border=0 alt="ThemeForest - Premium WordPress Themes" width=125 height=125></a></td>
			</tr>
	</table>
	</div>
	</div>
<?php	
}

?>
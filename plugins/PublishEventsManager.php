<?php
// Database option names
define( 'AUTOCHIMP_DB_EVENTS_MANAGER_PUBLISH', 'wp88_mc_events_manager_publish' );
define( 'AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX', 'wp88_mc_events_manager_post_type_' );
define( 'AUTOCHIMP_DB_EVENTS_MANAGER_CATEGORY_PREFIX', 'wp88_mc_events_manager_category_' );
// Control names for the mapping rows
define( 'EVENTS_MANAGER_CATEGORY_CONTROL_PREFIX', 'emp_categories_select_' );
define( 'EVENTS_MANAGER_LIST_CONTROL_PREFIX', 'emp_lists_select_' );
define( 'EVENTS_MANAGER_GROUP_CONTROL_PREFIX', 'emp_groups_select_' );
define( 'EVENTS_MANAGER_TEMPLATE_CONTROL_PREFIX', 'emp_templates_select_' );
// Custom javascript identifier
define( 'AUTOCHIMP_EVENTS_MANAGER_SCRIPT', 'autochimp-events-manager' );

class PublishEventsManager extends ACPlugin
{
	public function PublishEventsManager()
	{
	}

	public static function GetInstalled()
	{
		// If this class exists, then Events Manager is running
		return class_exists( 'EM_Scripts_and_Styles' );
	}
	
	public static function GetUsePlugin()
	{
		// Does the user want to use the Events Manager plugin with AutoChimp?
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
	
	public static function GetTerms( $postID )
	{
		return get_the_terms( $postID, EM_TAXONOMY_CATEGORY );
	}
	
	public function RegisterHooks()
	{
		// Save the option to publish each post type
		foreach ( $this->m_PostTypes as $postType )
		{
			$optionName = AC_EncodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			if ( '1' === get_option( $optionName, '0' ) )
			{
				AC_Log( "Registering PublishEventsManager post type '$postType'." );
				add_action( "publish_$postType", 'PublishEventsManager::OnPublishEventsManagerPostType' );
			}
		}
	}
	
	public function RegisterScripts( $pluginFolder )
	{
		wp_register_script( AUTOCHIMP_EVENTS_MANAGER_SCRIPT, $pluginFolder.'plugins/PublishEventsManager.js', array( 'jquery' ) );
	}
	
	public function EnqueueScripts()
	{
		wp_enqueue_script( AUTOCHIMP_EVENTS_MANAGER_SCRIPT );
	}

	//
	//	Function for displaying the UI for Events Manager integration.  Asks the user
	//	what type of posts they'd like to create campaigns for.
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
			print '> ' . ucwords( $postType ) . '</p>';
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
	//	This fairly complex UI generator dynamically creates mappings which allow 
	//	the user to fine tune where and how campaigns are created.
	//
	public function GenerateMappingsUI( $lists, $groups, $templates, $javaScript )
	{
		print '<p><strong>Since you are using Events Manager</strong>, you can create campaigns based on Event categories as well.  <em>If you use a \'user template\', be sure that the template\'s content section is called \'main\' so that your post\'s content can be substituted in the template.</em></p>' . PHP_EOL;
		print '<p><fieldset style="margin-left: 20px;">' . PHP_EOL;
		print '<table id="event_manager_table">' . PHP_EOL;
		print '<tr><th>Category</th><th></th><th>Mailing List</th><th></th><th>Interest Group</th><th></th><th>User Template</th></tr>';

		// Will need a list of the EM categories (terms) that the user has created.
		$categories = get_terms( EM_TAXONOMY_CATEGORY, 'orderby=count&hide_empty=0' );
		$categories = AC_AssembleTermsArray( $categories );
		
		// Building array that contains the mappings.  Each entry is a row in the UI.
		$mappings = array();

		// Need to query data in the BuddyPress extended profile table
		global $wpdb;

		// Pull all of the mappings from the DB.  Each row will have three items.  The
		// category is encoded in the option_name of each of the three along with an
		// index.	
		$options_table_name = $wpdb->prefix . 'options';
		$sql = "SELECT option_name,option_value FROM $options_table_name WHERE option_name like '" . AUTOCHIMP_DB_EVENTS_MANAGER_CATEGORY_PREFIX . "%' ORDER BY option_name";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				$listSelectName = AC_DecodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_CATEGORY_PREFIX , $field['option_name'] );
				// Split the results into an array which contains info about this mapping
				$info = split( '&', $listSelectName );
				
				// The key should always have a number, which keeps everything in sync
				$key = $info[0] . '-' . $info[1];
				// If there's a new key, then create a new array.
				if ( !isset( $mappings[$key] ) )
					$mappings[$key] = array();
				
				// Push this item into the array.
				array_push( $mappings[$key], $field['option_value'] );
			}
		}
		
		// Now loop through the constructed array and generate a new row for each
		// mapping found.
		foreach( $mappings as $category => $mapping )
		{
			// The category is contained in the key
			$catInfo = split( '-', $category );
			$newRow = $this->GenerateCategoryMappingRow( $categories, $catInfo[0], 
														 $lists, $mapping[1], $javaScript,	// In alphabetical order!!  "list" is second
														 $groups, $mapping[0],				// "group" is second
														 $templates, $mapping[2] );			// "template" is third
			print $newRow;
		}
		// Close out the table.	
		print '</table>' . PHP_EOL;
		
		// Generate the javascript that lets users create new mapping rows.
		$nrScript = $this->GenerateNewRowScript( count( $mappings ), $categories, WP88_ANY, $lists, WP88_NONE, $groups, WP88_ANY, $templates, WP88_NONE );
		
		// Add in the "new row" script.  Clicking on this executes the javascript to
		// create a new row to map categories, lists, groups, and templates.
		print '<p><a href="#" id="addNewEMRow" onclick="' . $nrScript . '">Add new campaign category mapping</a></p>' . PHP_EOL;
		print '</fieldset></p>';
	}

	//
	//	Loops through the expected _POST variables and reads the data in each
	//	and saves it off.
	//	
	//	This function is tied closely to GenerateMappingsUI() and 
	//	GenerateNewRowScript().  So, if you mess with one, you'll likely have to
	//	mess with both.
	//
	public function SaveMappings()
	{
		$count = 0;
		// Loop through the Events Manager category post variables until one is not
		// found.
		while ( isset( $_POST['emp_categories_select_' . $count ]) )
		{
			$category = $_POST['emp_categories_select_' . $count ];
			// Encode the name of the field
			$selectName = AC_EncodeUserOptionName( AUTOCHIMP_DB_EVENTS_MANAGER_CATEGORY_PREFIX , $category . '_' . $count );

			// Save the list selection - note if one of these POST variables is here,
			// then they are all expected to be here.
			$listSelectName = $selectName . WP88_CATEGORY_LIST_SUFFIX;
			$listSelection = $_POST['emp_lists_select_' . $count ];
			update_option( $listSelectName, $listSelection );
			
			// Save off interest group selection now.  Exact same principle.
			$groupSelectName = $selectName . WP88_CATEGORY_GROUP_SUFFIX;
			$groupSelection = $_POST['emp_groups_select_' . $count ];
			update_option( $groupSelectName, $groupSelection );
			
			// Same thing for templates
			$templateSelectName = $selectName . WP88_CATEGORY_TEMPLATE_SUFFIX;
			$templateSelection = $_POST['emp_templates_select_' . $count ];
			update_option( $templateSelectName, $templateSelection );

			// Increment the counter			
			$count++;
		}
	}
	
	//
	//	Action hook for the supported posts types.  This is easy; just forward all
	// 	the work to an AutoChimp function().
	//
	public static function OnPublishEventsManagerPostType( $postID )
	{
		AC_Log( "An custom post type from Events Manager was published with ID $postID. Forwarding to AC_OnPublishPost()." );
		AC_OnPublishPost( $postID );
	}
	
	//
	//	Protected Methods
	//
	
	//
	//	Returns HTML row code for a new category/term assignment.
	//
	protected function GenerateCategoryMappingRow(  $categories, $selectedCat, 
													$lists, $selectedList, $javaScript,
													$groups, $selectedGroup,
													$templates, $selectedTemplate )
	{
		static $itemCount = 0;
		
		$out = '<tr><td>' . PHP_EOL;
		
		$selectBox = AC_GenerateSelectBox( EVENTS_MANAGER_CATEGORY_CONTROL_PREFIX . $itemCount, WP88_ANY, $categories, $selectedCat );
		$out .= $selectBox . '</td>' . PHP_EOL . '<td>campaigns go to</td><td>';

		// Assemble the final Javascript
		$groupSelectName = EVENTS_MANAGER_GROUP_CONTROL_PREFIX . $itemCount;
		$javaScript .= "switchInterestGroups('$groupSelectName',this.value,groupsHash);\"";
		$selectBox = AC_GenerateSelectBox( EVENTS_MANAGER_LIST_CONTROL_PREFIX . $itemCount, WP88_NONE, $lists, $selectedList, $javaScript );
		$out .= $selectBox . '</td>' . PHP_EOL . '<td>and group</td><td>';
		
		// Start assembling the group select box
		$selectBox = AC_GenerateSelectBox( $groupSelectName, WP88_ANY, $groups[$selectedList], $selectedGroup );
		$out .= $selectBox . '</td>' . PHP_EOL . '<td>using</td><td>';
		
		// Assemble the final select box - templates
		$selectBox = AC_GenerateSelectBox( EVENTS_MANAGER_TEMPLATE_CONTROL_PREFIX . $itemCount, WP88_NONE, $templates, $selectedTemplate );
		$out .= $selectBox . '</td></tr>' . PHP_EOL;
		
		$itemCount++;
		
		return $out;
	}

	//
	//	This function generates javascript that, when called, will generate a new row
	//	that users can use to map categories to lists, etc.  This is very similar to 
	//	GenerateCategoryMappingRow() so if you make changes there, then watch for your
	//	changes here AND in the javascript file itself.
	//
	protected function GenerateNewRowScript( $numExistingRows,
											 $categories, $specialCategory,
											 $lists, $specialList,
											 $groups, $specialGroup,
											 $templates, $specialTemplate )
	{
		// Set up the categories hash first
		$nrScript = 'var categories={';
		// Add the special category
		$nrScript .= "'$specialCategory':null"; 
		foreach ( $categories as $name => $slug ) 
		{
			$nrScript .= ",'$name':'$slug'";
		}
		$nrScript .= '};';

		// Now set up the lists (almost the same thing)
		$nrScript .= 'var lists={';
		$nrScript .= "'$specialList':null"; 
		foreach ( $lists as $list => $id ) 
		{
			$name = $list;
			$id = $id;
			$nrScript .= ",'$name':'$id'";
		}
		// As part of the lists, set up the change options which will affect the
		// groups select box.  Close off the previous array too!
		$nrScript .= "};listCO={};";
		foreach( $groups as $listID => $lg )
		{
			$groupCSVString = implode(',', array_values( $lg ));
			$nrScript .= "listCO['$listID']='$groupCSVString'.split(',');";
		}

		// Set up groups, which is very different.  It only starts with the special
		// option, and other options are added later as the user selects lists.
		$nrScript .= "var groups={'$specialGroup':null};";
		
		// Finally, set up the templates.  Straightforward.
		$nrScript .= 'var templates={';
		$nrScript .= "'$specialTemplate':null"; 
		foreach ( $templates as $template => $id ) 
		{
			$name = $template;
			$id = $id;
			$nrScript .= ",'$name':'$id'";
		}
		$nrScript .= '};';
				
		$nrScript .= "AddCategoryTableRow($numExistingRows,categories,lists,listCO,groups,templates);";
		return $nrScript;
	}

	// Array of custom post types that Events Manager supports
	protected $m_PostTypes = array( EM_POST_TYPE_EVENT, EM_POST_TYPE_LOCATION, 'event-recurring' );
}
?>
<?php

//
// ACPlugin - base class for all AutoChimp plugin classes.  You will not derive
// directly from this class, but from one of the subclasses below.  However, you
// will implement some of the functions in this class.
//
class ACPlugin
{
	// 
	// Usually, detecting if a plugin exists is as easy as detecting the presence
	// of a class or function belonging to the plugin.
	//
	public static function GetInstalled()
	{
		return FALSE;
	}
	
	//
	// Returns true if the user wants to use this plugin integration.
	//
	public static function GetUsePlugin()
	{
		return FALSE;
	}

	//
	// Function for registering hooks.  If you don't need any, then just don't 
	// implement your own version.
	//
	public function RegisterHooks()
	{}
	
	//
	// Function for displaying the UI for XXX integration.  This UI will appear
	// on the "Plugins" tab.
	//
	public function ShowSettings()
	{}
	
	//
	// Function called when saving settings.  You can access _POST variables here
	// and write them to the database.
	//
	public function SaveSettings()
	{}
}

//
// ACSyncPlugin - All Sync plugins must derive from this class
//
class ACSyncPlugin extends ACPlugin
{
	//
	// Returns the name of the HTML sync variable.  Make sure it's unique.
	//
	public static function GetSyncVarName()
	{}
	
	//
	// Returns the name of the option in the options table that holds whether the
	// user wants to sync this plugin.  Must be unique.
	//
	public static function GetSyncDBVarName()
	{}
	
	//
	// By implementing GetSyncVarName() and GetSyncDBVarName() AND you have standard
	// simple settings, you get saving for free.  Only implement if you have special
	// settings, but strive hard not to.
	//
	public function SaveSettings()
	{
		AC_SetBooleanOption( GetSyncVarName(), GetSyncDBVarName() );
	}
	
	//
	// This function displays a table of plugin field names to select boxes of
	// MailChimp fields. 
	//	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{}
	
	//
	// This function saves the user's choices of mappings to the database.  It
	// uses the global $_POST variable to read the mappings.
	//
	public function SaveMappings()
	{}
	
	//
	// This is the most challenging function.  It looks up data for the user ID
	// passed in, collects the data that the plugin has set, and formats an array
	// to be sent to sync MailChimp.
	//
	public function FetchMappedData( $userID )
	{}
}

//
// ACPublishPlugin - All Publish plugins must derive from this class
//
class ACPublishPlugin extends ACPlugin
{
	public static function GetPublishVarName()
	{}
	
	public static function GetPostTypeVarPrefix()
	{}
}

//
// ACContentPlugin - All Content plugins must derive from this class
//
class ACContentPlugin extends ACPlugin
{
	public function ConvertShortcode( $content )
	{
		return $content;
	}
}

//
// This class is used only by AutoChimp.  Third party plugins for AutoChimp do not
// need this class.
//
class ACPlugins
{
	public function ShowSettings()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$p = new $plugin;
				$p->ShowSettings();
			}
		}
	}

	public function SaveSettings()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$p = new $plugin;
				$p->SaveSettings();
			}
		}
	}
	
	public function RegisterHooks()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$p = new $plugin;
				$p->RegisterHooks();
			}
		}
	}
	
	protected function GetPluginClasses( $classType )
	{
		// array to build the list in
		$classlist = array();
	
		// Attempt to open the folder
		$path = WP_PLUGIN_DIR . '/autochimp/plugins';
		if ( ( $p = opendir( $path ) ) !== FALSE )
		{
			// Read the directory for items inside it.
			while ( ( $item = readdir( $p ) ) !== FALSE )
			{
				// First check if the filter succeeds for the class type
				$filter = TRUE;
				// For a blank classType, get everything.  Otherwise, only get matches.
				if ( 0 !== strlen( $classType ) )
					$filter = ( 0 === strpos( $item, $classType ) );
				
				if ( $item[0] != '.' && $filter )
				{
					$class = basename( $item, '.php' );
					array_push( $classlist, $class );
				}
			}
			closeDir($p);
		}
		return $classlist;
	}

	protected function GetType()
	{
		// This is the same as asking for all plugins
		return '';
	}
}

//
// ACSyncPlugins
//
class ACSyncPlugins extends ACPlugins
{
	public function SaveSettings()
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$sync = new $plugin;
				$sync->SaveSettings();
			}
		}
	}
	
	public function SaveMappings()
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$mapper->SaveMappings();
			}
		}
	}
	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		$totalOut = '';
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$totalOut .= $mapper->GenerateMappingsUI( $tableWidth, $mergeVars );
			}
		}
		return $totalOut;
	}
	
	public function SyncData( &$merge_vars, $userID )
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$sync = new $plugin;
				AC_Log( "About to sync data for the $plugin plugin." );
				$data = $sync->FetchMappedData( $userID );
				AC_AddUserFieldsToMergeArray( $merge_vars, $data );
			}
		}
	}

	protected function GetType()
	{
		return 'Sync';
	}
}

//
// ACPublishPlugins
//
class ACPublishPlugins extends ACPlugins
{
	protected function GetType()
	{
		return 'Publish';
	}
}

//
// ACContentPlugins
//
class ACContentPlugins extends ACPlugins
{
	public function ConvertShortcode( $content )
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUpdateContent() )
			{
				$converted = $plugin->ConvertShortcode( $content );
				// Now run the content through the_content engine.
				$content = apply_filters( 'the_content', $converted );
			}
		}
		return $content;				
	}

	protected function GetType()
	{
		return 'Content';
	}
}
?>
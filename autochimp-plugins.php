<?php

class ACPlugin
{
	public static function GetInstalled()
	{
		return FALSE;
	}
	
	public static function GetUsePlugin()
	{
		return FALSE;
	}

	public function RegisterHooks()
	{}
	
	public function ShowSettings()
	{}
	
	public function SaveSettings()
	{}
}

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
		// This causes all
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
				AC_SetBooleanOption( $sync->GetSyncVarName(), $sync->GetSyncDBVarName() );
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
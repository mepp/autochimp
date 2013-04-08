<?php

class ACPlugins
{
	protected function GetType()
	{
		return '';
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
			while ( ( $item = readdir( $p ) ) !== false )
			{
				if ( $item[0] != '.' && 0 === strpos( $item, $classType ) )
				{
					$class = basename( $item, '.php' );
					array_push( $classlist, $class );
				}
			}
			closeDir($p);
		}
		return $classlist;
	}
}

class ACSyncPlugins extends ACPlugins
{
	public function ShowSettings()
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$sync = new $plugin;
				$sync->ShowSettings();
			}
		}
	}
	
	public function SaveOptions()
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
			if ( $plugin::GetInstalled() && $plugin::GetSyncPlugin() )
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
			if ( $plugin::GetInstalled() && $plugin::GetSyncPlugin() )
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
			if ( $plugin::GetInstalled() && $plugin::GetSyncPlugin() )
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

?>
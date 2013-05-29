function AddCategoryTableRow( numExistingRows, categories, lists, listChangeOptions, groups, templates )
{
	jQuery(document).ready(function($)
	{
		if ( 'undefined' == typeof( AddCategoryTableRow.count ) )
			AddCategoryTableRow.count = numExistingRows;
		else
			AddCategoryTableRow.count++;
		$('#event_manager_table').append( $('<tr><td></td><td>campaigns go to</td><td></td><td>and group</td><td></td><td>using</td><td></td></tr>') );
		CreateSelect( 'emp_categories_select_' + AddCategoryTableRow.count, categories, $('#event_manager_table tr:last td:eq(0)') );
		CreateChangeSelect( 'emp_lists_select_' + AddCategoryTableRow.count, lists, $('#event_manager_table tr:last td:eq(2)'), 'emp_groups_select_' + AddCategoryTableRow.count, listChangeOptions );
		CreateSelect( 'emp_groups_select_' + AddCategoryTableRow.count, groups, $('#event_manager_table tr:last td:eq(4)') );
		CreateSelect( 'emp_templates_select_' + AddCategoryTableRow.count, templates, $('#event_manager_table tr:last td:eq(6)') );
	});
}

function CreateSelect( name, optionsHash, appendObj )
{
	jQuery(document).ready(function($)
	{
		var select = $('<select name="' + name + '" />');
		for( var val in optionsHash ) 
		{
			if ( null == val )
				$('<option />', {text: val}).appendTo(select);
			else
				$('<option />', {value: optionsHash[val], text: val}).appendTo(select);
		}
		select.appendTo( appendObj );
	});
}

function CreateChangeSelect( name, optionsHash, appendObj, changeTarget, changeOptions )
{
	jQuery(document).ready(function($)
	{
		var select = $('<select name="' + name + '" />');
		select.change(function(){switchInterestGroups(changeTarget,this.value,changeOptions);});
		for( var val in optionsHash ) 
		{
			if ( null == val )
				$('<option />', {text: val}).appendTo(select);
			else
				$('<option />', {value: optionsHash[val], text: val}).appendTo(select);
		}
		select.appendTo( appendObj );
	});
}
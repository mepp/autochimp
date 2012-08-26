function runSyncUsers( timerID )
{
	jQuery(document).ready(function($)
	{
		// Show the progress bar as starting.  Note that the sync button is disabled
		// in the onClick() handler in the form.  It works - the button is indeed 
		// disabled, but it's appearance doesn't change at all. Must be a WordPress
		// thing.
		$('#manual_sync_progressbar').progressbar({	value: 0});
		$.ajax(
		{
			type: 'POST', // Using POST to avoid caching
			url: AutoChimpAjax.ajaxurl,
			data: {action: 'run_sync_users'},
			success:function(response)
			{
				console.log( 'The sync has completed.' );
				$('#manual_sync_status').html( response );
				// Show the progress bar as complete
				$('#manual_sync_progressbar').progressbar({	value: 100});
				$('#manual_sync_progressbar').hide();
				// Stop the timer pronto
				clearInterval( timerID );
			},
			error:function (xhr, ajaxOptions, thrownError)
			{
				alert('The sync encountered an unknown problem');
			}    
		});
	});
}

function getSyncProgress()
{
	jQuery(document).ready(function($)
	{
		$.ajax(
		{
			type: 'POST',
			url: AutoChimpAjax.ajaxurl,
			data: {action: 'query_sync_users'},
			success:function(response)
			{
				// The string contains to parts: 1) a percent and 2) a message which
				// are separated by a # symbol.
				dataArray = response.split('#');
				percent = parseInt( dataArray[0] );
				console.log( response );
				$('#manual_sync_progressbar').progressbar({	value: percent});
				$('#manual_sync_status').html( dataArray[1] );
			},
			error:function (xhr, ajaxOptions, thrownError)
			{
				alert('The sync encountered an unknown problem');
			}
		});
	});
}

function switchInterestGroups( selectName, listID, groupHash )
{
	jQuery(document).ready(function($)
	{
		// Find the edit box.
		selectBox = document.getElementsByName( selectName )[0];
		
		// Clear out the options.
		selectBox.options.length = 0;
		
		// Add an "Any" option back in.
		selectBox.options[0] = new Option('Any', 'Any');
		
		// Now go through the interest groups and add them.
		groupList = groupHash[listID];
		if ( groupList )
		{
			for (i=0; i<groupList.length; i++)
			{
				selectBox.options[selectBox.options.length] = new Option( groupList[i], groupList[i]);
			}
		}
	});
}

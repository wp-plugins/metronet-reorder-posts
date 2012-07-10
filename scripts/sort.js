jQuery(document).ready(function($) {	
	//Variable sortnonce is declared globally	
	var postList = $('#post-list');
	
	var blah = postList.nestedSortable( {
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		maxLevels: 6,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div',
		listType: 'ul',
		update: function( event, ui ) {
			var order = $('ul#post-list').nestedSortable( 'toHierarchy',{ listType: 'ul'});
			order = JSON.stringify( order , null, 2);
			//console.log( order ); 
			//return;
			$.post( ajaxurl, { action: 'post_sort', nonce: sortnonce, data: order }, function( response ) {
			}, 'json' );
			
		}
	});
	
	/*

	postList.sortable({
		update: function(event, ui) {
			$('#loading-animation').show(); // Show the animate loading gif while waiting
 
			opts = {
				url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
				type: 'POST',
				async: true,
				cache: false,
				dataType: 'json',
				data:{
					action: 'post_sort', // Tell WordPress how to handle this ajax request
					order: postList.sortable('toArray').toString(), // Passes ID's of list items in	1,3,2 format
					nonce: sortnonce, 
				},
				success: function(response) {
					console.log( response );
					return;
					$('#loading-animation').hide(); // Hide the loading animation
					$('#reorder-error').html('There was an error worked'); // Error message
					$('#reorder-error').html(response); // Error message

					console.log(response);
					return; 
				},
				error: function(xhr,textStatus,e) {  // This can be expanded to provide more information
					alert('There was an error saving the updates');
					$('#loading-animation').hide(); // Hide the loading animation
					return; 
				}
			};
			$.ajax(opts);
		}
	});	
	*/
});
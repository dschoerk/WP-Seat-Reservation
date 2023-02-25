var maxseats = 8;

jQuery(function() {  
  jQuery(".seat").not(".occupied").click(function() {
		if(jQuery(this).hasClass("selected") || jQuery(".selected").size() < maxseats) {
			jQuery(this).toggleClass("selected");
			updateSeatSelection();
		} else {
			alert("Sie können nur " + maxseats + " Karten auf einmal reservieren");
		}
  });
});

function updateSeatSelection() {
	
	jQuery("#reservation_form > input[name='res_seats[]']").remove(); // remove old selection
	
	var sum = 0;
	jQuery(".selected").each(function(el) {
		var price = jQuery(this).attr("price");
		var seatid = jQuery(this).attr("seatid");
		sum += parseInt(price);
		
		jQuery('<input/>', {
			'type': 'hidden',
			'name': 'res_seats[]',
			'value': seatid
		}).appendTo('#reservation_form');
	});

	jQuery("#num-selected").text(jQuery(".selected").length);
	jQuery("#price").text(sum);
}


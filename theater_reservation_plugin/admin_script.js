jQuery(function() {  
  jQuery(".seat").not(".occupied").not(".occupied-buero").click(function() {
    jQuery(this).toggleClass("selected");
    updateSeatSelection();
  });

  jQuery(".seat.occupied-buero").click(function() {
    jQuery(this).toggleClass("deselected");
    updateSeatSelection();
  });
});

function updateSeatSelection() {
	
	jQuery("#reservation_form > input[name='res_seats_selected[]']").remove(); // remove old selection
    jQuery("#reservation_form > input[name='res_seats_deselected[]']").remove(); // remove old selection
	
	//var sum = 0;
	jQuery(".selected").each(function(el) {
		/*var price = jQuery(this).attr("price");
		sum += parseInt(price);*/
        var seatid = jQuery(this).attr("seatid");
		
		jQuery('<input/>', {
			'type': 'hidden',
			'name': 'res_seats_selected[]',
			'value': seatid
		}).appendTo('#reservation_form');
	});

    jQuery(".deselected").each(function(el) {
		/*var price = jQuery(this).attr("price");
		sum += parseInt(price);*/
        var seatid = jQuery(this).attr("seatid");
		
		jQuery('<input/>', {
			'type': 'hidden',
			'name': 'res_seats_deselected[]',
			'value': seatid
		}).appendTo('#reservation_form');
	});

	//jQuery("#num-selected").text(jQuery(".selected").length);
	//jQuery("#price").text(sum);
}


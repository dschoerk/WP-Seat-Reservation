<?php

function find_seat($seats, $seatid) {
	foreach($seats as $seat) {
		if ($seatid == $seat->id) {
			return $seat;
		}
	}
	
	die('error');
}

function sitzplan($showid, $frontend = true, $show_buttons = true) {
	global $wpdb;
	
	$rows = 14;
	$cols = 10;
	
	$showid = esc_sql($showid);
	$seats = $wpdb->get_results( "SELECT seat.id, seat.caption, seat.price, res.reservation IS NOT NULL as taken, (SELECT !STRCMP(email, 'a0s9a0ßs9df8ad8f') from wp_tgm_reservation reservation WHERE reservation.id=res.reservation) as taken_buero FROM wp_tgm_seat seat LEFT JOIN wp_tgm_seat_reservation res ON seat.id = res.seat AND seat.show=res.show WHERE seat.show = '$showid'");		
	
	if(count($seats) < 1)
		die("error");
	
	$html = "<div class='plan'>";
	$html .= "<div class='buehne'>Bühne</div>";
	for($row = 0; $row < $rows; $row++) {
		
		if($row == 6)
			$html .= "<div class='gang'>Gang</div>";
		
		for($col = 0; $col < $cols; $col++) {
			$seatid = $row * $cols + $col;
			$seat = find_seat($seats, $seatid);
			
			$taken_class = "";

			//echo($seat->taken_buero);

			if($frontend) {
				if($seat->taken)
					$taken_class = "occupied";
			} else if($seat->taken) {
				if($seat->taken_buero)
					$taken_class = "occupied-buero";
				else
					$taken_class = "occupied";
			}

			$rang_class = "";
			if($seat->price == 12)
				$rang_class = "rang1";
			else if($seat->price == 10)
				$rang_class = "rang2";
			
			$html .= "<div class='seat $taken_class $rang_class' seatid='$seat->id' price='$seat->price'>$seat->caption</div>";
		}
	}
	$html .= "</div>";
    
    if($frontend) {
        $html .= "
            <div style='display: inline-block; width: 360px; float: right;'>
                <div class='product-selection'>
                    <p>
                        <span id='num-selected'>0</span> Plätze ausgewählt
                    </p>
                    <p>
                        Betrag: <span id='price'>0</span>€
                    </p>
                    <p>
                        Reservierte Tickets sind an der Abendkasse zu bezahlen.
                    </p>
                    
                    <form id='reservation_form' action='' method='POST'>
                        <input type='hidden' value='2' name='reservation_step'>
                        <input type='hidden' value='$showid' name='res_show'>
                        <input type='submit' value='Weiter' name='submit_btn'>
                    </form>
                </div>
            </div>
        ";
    } else if($show_buttons){ // reservation form for backend
		$html .= "
			<form id='reservation_form' action='' method='POST'>
				<input type='hidden' value='$showid' name='res_show'>
				<input type='submit' value='Weiter' name='submit_btn'>
				<div style='display: inline'>
				  <input type='checkbox' name='already_payed'>
				  <label style='font-size: 12px' for='already_payed'>bezahlt!</label>
				</div>
			</form>
        ";
	}
	
	return $html;
}

?>
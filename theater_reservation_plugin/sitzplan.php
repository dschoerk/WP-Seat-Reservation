<?php

function find_seat($seats, $seatid) {
	foreach($seats as $seat) {
		if ($seatid == $seat->id) {
			return $seat;
		}
	}
	
	die('seat not found');
}

function make_seat_config() {
	$seat_config = [
		1 => [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  0,  0, 0],
		2 => [0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9, 0, 10,  0, 11,  0, 12,  0, 13, 0],
		3 => [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  12, 0,13],
		4 => [0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9, 0, 10,  0, 11,  0, 12,  0, 13, 0],
		5 => [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  0,  0, 0],
		6 => [0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9, 0, 10,  0, 11,  0, 12,  0,  0, 0],
		7 => [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  0,  0, 0],
		8 => [0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9, 0, 10,  0, 11,  0, 12,  0,  0, 0],
		9 => [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  0,  0, 0],
		10=> [0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9, 0, 10,  0, 11,  0, 12,  0,  0, 0],
		11=> [0, 0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 9,  0, 10,  0, 11,  0,  0,  0, 0],
		12 =>[1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 8, 0, 0, 0, 0,  0, 0 ,  0,  0,  0,  0,  0, 0]
	];

	return $seat_config;
}

function sitzplan($showid, $frontend = true, $show_buttons = true) {
	global $wpdb;

	$html = "<div class='buehne'>Bühne</div>";

	$html .= "<div class='plan'>";
	
	$seat_config = make_seat_config();

	$taken_buero_query = "(SELECT !STRCMP(email, 'a0s9a0ßs9df8ad8f') from wp_tgm_reservation reservation WHERE reservation.id=res.reservation) as taken_buero";
	$seat_data = $wpdb->get_results("SELECT seat.id, seat.price, res.reservation IS NOT NULL as taken, $taken_buero_query FROM wp_tgm_seat seat LEFT JOIN wp_tgm_seat_reservation res ON seat.id = res.seat AND res.show = '$showid'");
			
	foreach($seat_config as $row => $seats) {
		foreach($seats as $seat) {
			if($seat == 0) {
				// free space
				$html .= "<div class='space'></div>";
			} else {
				// seat
				$seat_entry = find_seat($seat_data, "$row-$seat");
				
				$taken_class = "";
				if($frontend) {
					if($seat_entry->taken)
						$taken_class = "occupied";
				} else if($seat_entry->taken) {
					if($seat_entry->taken_buero)
						$taken_class = "occupied-buero";
					else
						$taken_class = "occupied";
				}

				$rang_class = "";
				if($seat_entry->price == 15)
					$rang_class = "rang1";
				else if($seat_entry->price == 12)
					$rang_class = "rang2";
				else if($seat_entry->price == 10)
					$rang_class = "rang3";

				$html .= "<div class='seat $taken_class $rang_class' seatid='$row-$seat' price='$seat_entry->price'>$seat</div>";
			}
		}
		$html .= "<div style='grid-column: span 2'>Reihe $row</div>";
	}
		
	$html .= "</div>";

	
	if($frontend) {
        $html .= "
            <div>
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

function sitzplan_alt($showid, $frontend = true, $show_buttons = true) {
	global $wpdb;

	$rows = 14;
	$cols = 10;
	
	$showid = esc_sql($showid);
	$seats = $wpdb->get_results( "SELECT seat.id, seat.caption, seat.price, res.reservation IS NOT NULL as taken, (SELECT !STRCMP(email, 'a0s9a0ßs9df8ad8f') from wp_tgm_reservation reservation WHERE reservation.id=res.reservation) as taken_buero FROM wp_tgm_seat seat LEFT JOIN wp_tgm_seat_reservation res ON seat.id = res.seat AND res.show = '$showid'");		
	
	if(count($seats) < 1)
		die("error - no seats");
	
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
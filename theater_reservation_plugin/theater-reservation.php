<?php

/**
 * Theater Reservation
 *
 * @package theater_reservation
 * @version 0.1
 * @author Dominik Schoerkhuber
 *
 *
 * Plugin name: Theater Reservation
 * Plugin URI:  http://schoerkhuber.at/
 * Description: bla
 * Version:     0.1
 * Author:      Dominik Schoerkhuber
 * Author URI:  http://schoerkhuber.at/
 * License:     GPLv2
 * Text Domain: theater_reservation
 * Domain Path: /languages
 */

require_once('logging.php'); 
require_once('sitzplan.php'); 
require_once('reservation-service.php');
require_once('storno_form.php');

function activation_hook() {
	ReservationService::create_tables();
}
register_activation_hook(__FILE__, 'activation_hook');

function theater_reservation_init() {
    wp_register_style( 'theater_reservation_plugin', plugins_url( 'theater_reservation_plugin/style.css?v=1' ) );
	wp_enqueue_style('theater_reservation_plugin');
    
	//wp_deregister_script( 'jquery' );
    //wp_enqueue_script( 'script-name', '//code.jquery.com/jquery-2.2.3.min.js', array(), '2.2.3' );
	
	wp_register_script( 'theater_reservation_plugin', plugins_url('theater_reservation_plugin/script.js'), array('jquery'));
	wp_enqueue_script('theater_reservation_plugin');

	wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');

	/*wp_register_script( 'theater_reservation_plugin_xeditable', '//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/js/jquery-editable-poshytip.min.js', array('jquery'));
	wp_enqueue_script('theater_reservation_plugin_xeditable');
	wp_register_style( 'theater_reservation_plugin_xeditable', '//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/css/jquery-editable.css' );
    wp_enqueue_style('theater_reservation_plugin_xeditable');*/
	
}
add_action( 'wp_enqueue_scripts', 'theater_reservation_init');

function theater_reservation_admin_init() {
	wp_register_style( 'theater_reservation_plugin', plugins_url( 'theater_reservation_plugin/style.css?v=1' ) );
	wp_enqueue_style('theater_reservation_plugin');

	wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');

	wp_register_script( 'theater_reservation_plugin_admin', plugins_url('theater_reservation_plugin/admin_script.js'), array('jquery'));
	wp_enqueue_script('theater_reservation_plugin_admin');
}
add_action( 'admin_enqueue_scripts', 'theater_reservation_admin_init' );

function reg_shortcode_page_storno( $atts ) {

	$atts = shortcode_atts( array(

	), $atts, 'theater-reservation' );
	//print_r($atts);
	//print('cool');

	render_storno_form();

	return;
}

function selection_form($selected = null, $is_admin = false) {
	global $wpdb;
	$sql = "SELECT id, name, date FROM wp_tgm_show WHERE NOW() < reservation_until";
	if($is_admin) {
		$sql = "SELECT id, name, date FROM wp_tgm_show";
	}
	$shows = $wpdb->get_results( $sql );
	$shows_html = '';
	foreach($shows as $show) {
		$shows_html .= show_to_li($show, $selected == $show->id);
	}
	
	$html = '<form action="" method="POST">';
		$html .= '<div style="display: inline-block;"><select name="res_show" onchange="this.form.submit()">' . $shows_html . '</select></div>';
		$html .= '<input type="hidden" value="1" name="reservation_step">';
		$html .= '<input type="submit" style="margin-top: 5px; font-size: 12px; padding: 8px;" value="Vorstellung wählen" name="submit_btn">';
	$html .= '</form>';

	return $html;
}

function reg_shortcode_fun( $atts ) {
	
	global $wpdb;
	
	//TEST 
	//send_reservation_mail("Dominik Schörkhuber", "dschoerk@gmx.at");
	
	$atts = shortcode_atts( array(

	), $atts, 'theater-reservation' );
	
	$reservation_step = "0";
	if(isset($_POST["reservation_step"])) {
		$reservation_step = $_POST["reservation_step"]; // first step
	}
	
	$html = reservation_header($reservation_step);
	
	if($reservation_step == "0")
	{
		// Auswahl der Vorstellung
		$html .= selection_form();

		Logger::info("step 0/3");
	}
	else if($reservation_step == "1")
	{
		// Auswahl der Sitzplätze
		if(!isset($_POST["res_show"]))
			die('error');

		$showid = esc_sql($_POST["res_show"]);

		$html .= selection_form($showid);
		
		$show = get_show_by_id($showid);
		
		$datestr = date2str($show->date);
		$html .= "<h3>Schritt 1/3</h3>";
		$html .= "<h3>$show->name am $datestr</h3>";
		$html .= '<div style="margin-top: 20px; margin-bottom: 20px"><div style="display:inline-block"><span class="rang1" style="padding: 5px">Rang 1</span> 15€</div>';
		$html .= '<div style="display:inline-block; margin-left: 10px"><span class="rang2" style="padding: 5px">Rang 2</span> 12€</div></div>';
		//$html .= '<div style="display:inline-block; margin-left: 10px"><span class="rang2" style="padding: 5px">Rang 3</span> 10€</div></div>';
		
		$html .= sitzplan($showid);

		Logger::info("step 1/3 showid=$showid");
	}
	else if($reservation_step == "2")
	{
		// Eingabe der Kontaktdaten
		
		if(!isset($_POST["res_show"]) || !isset($_POST["res_seats"]))
			die('error');
		
		$seats = base64_encode(serialize($_POST["res_seats"]));
		$showid = esc_sql($_POST["res_show"]);
		// $show = $wpdb->get_results( "SELECT id, name, date FROM wp_tgm_show WHERE id = '$showid'");

		$html .= "<h3>Schritt 2/3</h3>";
		
		$html .= '<form action="" method="POST" style="max-width: 520px">';
			$html .= '<input type="hidden" value="3" name="reservation_step">';
			$html .= "
				
				<input type='text' style='display: block' name='res_forename' placeholder='Vorname' size='35' required>
				<input type='text' style='display: block' name='res_surename' placeholder='Nachname' size='35' required>
				<input type='email' style='display: block' name='res_email' placeholder='Email' size='35' required>
				<div>
				  <input type='checkbox' name='res_email_ads' unchecked>
				  <label style='font-size: 12px' for='res_email_ads'>Ich möchte Informationen zu weiteren Veranstaltungen erhalten</label>
				</div>
			</div>
			<input type='hidden' name='res_show' value='$showid'>
			<input type='hidden' name='res_seats' value='$seats'>
			<input type='submit' style='display: block' value='Weiter' name='submit_btn'>";
		$html .= '</form>';

		Logger::info("step 2/3 showid=$showid seats=".serialize($_POST["res_seats"]));
	}
	else if($reservation_step == "3")
	{
		// Daten Prüfen

		

		
		if(!isset($_POST["res_show"]))
			die('error');

			
		
		$seats_post = esc_sql($_POST["res_seats"]);
		$seatids = unserialize(base64_decode($seats_post));
		$showid = esc_sql($_POST["res_show"]);
		$forename = esc_sql($_POST["res_forename"]);
		$surename = esc_sql($_POST["res_surename"]);
		$email = esc_sql($_POST["res_email"]);



		$email_ads = isset($_POST["res_email_ads"]) ? esc_sql($_POST["res_email_ads"]) : "off";
		
		$show = get_show_by_id($showid);

		$show_date_str = date2str($show->date);

		
		$seats = get_seats_by_ids($showid, $seatids);

		

		$seats_str = [];
		$total_price = 0;

		foreach($seats as $seat) {			
			$seat_line = "<li class='list-seats-check'>$seat->caption ($seat->description)<span style='margin-left: 20px'>$seat->price €</span>";
			$seats_str[] = $seat_line;
			$total_price += (int)$seat->price;
		}
		$seats_str = implode("", $seats_str);

		//$seats_str = "<li class='list-seats-check'>".implode("</li><li class='list-seats-check'>", $seats_str)."</li>";
		
		$html .= "<h3>Schritt 3/3</h3>";
		$html .= "<h3>Bitte überprüfen Sie Ihre Reservierung</h3>";
		$html .= "<p>Name: $forename $surename</p>";
		$html .= "<p>Email: $email</p>";
		
/*		if($email_ads == "on")
			$html .= "<p>Sie erhalten Informationen zu kommenden Veranstaltungen per Email</p>";
		else 
			$html .= "<p>Sie erhalten KEINE Informationen zu kommenden Veranstaltungen per Email</p>";*/
		$html .= "<p>Vorstellung: $show->name am $show_date_str</p>";
		$html .= "<p><div>Plätze:</div> <ul style='margin-bottom: 0px'>$seats_str</ul><div>Summe: $total_price €</div></p>";
		
		$html .= '<form action="" method="POST">';
			$html .= '<input type="hidden" value="4" name="reservation_step">';
			$html .= "<input type='hidden' name='res_forename' value='$forename'>
			<input type='hidden' name='res_surename' value='$surename'>
			<input type='hidden' name='res_email' value='$email'>
			<input type='hidden' name='res_email_ads' value='$email_ads'>
			<input type='hidden' name='res_show' value='$showid'>
			<input type='hidden' name='res_seats' value='$seats_post'>
			<input type='submit' value='Reservieren!' name='submit_btn'>";
		$html .= '</form>';


		Logger::info("step 3/3 showid=$showid forename=$forename surename=$surename email=$email seats=".serialize($seatids));
	}
	else if($reservation_step == "4")
	{
		// Abschluss
		$error = false;
		$showid = esc_sql($_POST["res_show"]);
		$forename = esc_sql($_POST["res_forename"]);
		$surename = esc_sql($_POST["res_surename"]);
		$email = esc_sql($_POST["res_email"]);
		$email_ads = esc_sql($_POST["res_email_ads"]);

		if($email_ads == "on")
			$email_ads = 1;
		else
			$email_ads = 0;
		
		$wpdb->query('START TRANSACTION');
		$wpdb->query( "INSERT INTO wp_tgm_reservation (forename, surename, email, email_ads, storno_token) VALUES ('$forename', '$surename', '$email', '$email_ads', UUID())");
		
		if ($wpdb->last_error) {
			Logger::error($wpdb->last_error);
			$error = true;
		}
		
		$reservationid = $wpdb->insert_id;
		$storno_token = $wpdb->get_results("SELECT storno_token FROM wp_tgm_reservation WHERE id=" . $reservationid);
		$storno_token = $storno_token[0];
		
		$seats = $_POST["res_seats"];
		$seatids = unserialize(base64_decode($seats));

		if(count($seatids) > 8) {
			Logger::error("more seats selected than allowed $forename  $surename $email");
			$error = true;
		}
		
		//print_r($seatids);
		foreach($seatids as $seatid) {
			//echo($seatid);
			$sql = "INSERT INTO wp_tgm_seat_reservation (seat, reservation, `show`) VALUES ('$seatid', $reservationid, $showid)";
			//print($sql);
			$wpdb->query( $sql);
			if ($wpdb->last_error) {
				Logger::error($wpdb->last_error);
				$error = true;
			}
		}

		//die($wpdb->last_error);
			
		
		
		if ($error) {
			// error
			$html .= "<h3>Hoppala! Ein Fehler ist aufgetreten</h3>";
			$html .= "<p>Einer der gewählten Plätze is bereits belegt</p>";
			$html .= "<a href='/karten' class='button'>Zurück</a>";
			$wpdb->query('ROLLBACK');

			Logger::error("Reservation failed, rollback");
		} else {
			// success
			$html .= "<h3>Danke für Ihre Reservierung!</h3>";
			$html .= "<p>Sie erhalten eine Bestätigungsemail</p>";

			$wpdb->query('COMMIT');
			
		
			send_reservation_mail($forename . ' ' . $surename, $email, $reservationid, $showid, $storno_token->storno_token);
			Logger::info("step success showid=$showid forename=$forename surename=$surename email=$email seats=".serialize($seatids) . " token=" . $storno_token->storno_token);
		}
	}
	
	return $html;
	
	/*return '
		<form action="" method="POST">
		<input type="text" name="reg_name">
		<input type="submit" value="submit" name="submit_btn">
		</form>
	';*/
}

function reservation_header($step) {
	if($step == "0")
		return "<h3>Wählen Sie eine Vorstellung!</h3>";
	else if($step == "1")
		return "<h3>Wählen Sie Ihre Sitzplätze!</h3>";
	else if($step == "2")
		return "<h3>Geben Sie Ihre Kontaktdaten ein um die Reservierung abzuschließen!</h3>";
}

function show_to_li($show_row, $selected) {
	return '<option ' . ($selected ? 'selected="selected"' : '') . ' value="' . $show_row->id . '">' . $show_row->name . ' am ' . date2str($show_row->date) . '</option>';
}

function process_registration_form() {
    /**
     * At this point, $_GET/$_POST variable are available
     *
     * We can do our normal processing here
     */ 

    // Sanitize the POST field
    // Generate email content
    // Send to appropriate email
	
	echo('some registration stuff');
}

add_action( 'admin_post_nopriv_registration_form', 'process_registration_form' );
add_action( 'admin_post_registration_form', 'process_registration_form' );
add_shortcode( 'theater-reservation', 'reg_shortcode_fun' );
add_shortcode( 'theater-reservation-storno', 'reg_shortcode_page_storno' );

// inserts a new show plus seats
function create_show($name, $date) {
	global $wpdb;
	
	echo("Create a show");
	
	$until = esc_sql($date);
	$name = esc_sql($name);
	$date = esc_sql($date);
	
	$wpdb->query('START TRANSACTION');
	$wpdb->query( "INSERT INTO wp_tgm_show (name, date, reservation_until) VALUES ('$name', '$date', '$until')");
	if ($wpdb->last_error) {
		echo 'Error' . $wpdb->last_error;
		$wpdb->query('ROLLBACK');
		return;
	}
	
	$showid = $wpdb->insert_id;
	echo("showid: $showid");
	
	/*$rows = 14;
	$cols = 10;
	
	for($row = 0; $row < $rows; $row++) {
		for($col = 0; $col < $cols; $col++) {
			$seatid = $row * $cols + $col;
			$caption = "" . ($seatid + 1);
			$description = "Reihe: " . ($row+1) . " Platz: " . ($col+1);
			
			$price = 10;
			if($row <= 7)
				$price = 12;
			
			// echo("insert seat: ($seatid, $showid, $caption, $price) ");
			
			$showid = esc_sql($showid);
			$seatid = esc_sql($seatid);
			$caption = esc_sql($caption);
			$description = esc_sql($description);
			$price = esc_sql($price);
			
			$wpdb->query( "INSERT INTO `wp_tgm_seat` (`id`, `caption`, `description`, `price`) VALUES ('$seatid', '$caption', '$description', '$price')");
		}
	}*/
	
	if ($wpdb->last_error) {
		echo 'Error' . $wpdb->last_error;
		$wpdb->query('ROLLBACK');
		return;
	}
	
	$wpdb->query('COMMIT');
}

function send_reservation_mail($name, $mail, $reservationid, $showid, $storno_token) {
	//$to = "$name <$mail>";
	$to = $mail;
	$subject = 'Ihre Karten Reservierung';
	
	
	$show = get_show_by_id($showid);
	$seats = get_seats_for_reservation($reservationid);

	$seats_str = [];
	$total_price = 0;
	foreach($seats as $seat) {
		$seats_str[] = "<li>Platz $seat->caption ($seat->description)<span style='margin-left: 20px'>$seat->price €</span></li>";
		$total_price += (int)$seat->price;
	}

	$seats_str = implode("", $seats_str);
	//$seats_str = "<li>".implode($seats_str, "</li><li>")."</li>";
	
	//$randval = rand();
	//$random = "<input type='hidden' value='$randval'>";
	$show_str = "$show->name am " .date2str($show->date);
	
	$body = file_get_contents(plugins_url( 'theater_reservation_plugin/tgm_reservierungsmail.html' ));
	$body = str_replace("%%SEATS%%", $seats_str, $body);
	$body = str_replace("%%SHOW%%", $show_str, $body);
	$body = str_replace("%%NAME%%", $name, $body);
	$body = str_replace("%%STORNO_LINK%%", "https://theater-muehlbach.at/storno?token=$storno_token", $body);
	
	//$body = str_replace("%%RANDOM%%", $random, $body); // prevents gmail citing
	// $body = randomize($body);
	// $body = randomize($body, '</span>');
	
	//$body = 'Le great reservation <p style="color: red">Super HTML</p>';
	$headers = array('Content-Type: text/html; charset=UTF-8');

 
	wp_mail( $to, $subject, $body, $headers );
	wp_mail( "dschoerk@gmx.at", "ADMININFO: " . $subject, $body, $headers );
}

function send_storno_mail($res, $reserved_seats, $cancelled_seats, $storno_token) {


	//print_r($res);

	$name = $res->forename . " " . $res->surename;
	//$to = "$name <$res->email>";
	$to = $res->email;
	$subject = 'Bestätigung Ihrer Stornierung';
	
	// $show = get_show_by_id($showid);
	//$seats = get_seats_for_reservation($reservationid);
	
	$seats_str_reserved = array();
	$total_price = 0;
	foreach($reserved_seats as $seat) {
		$date = date2str($seat->date);
		array_push($seats_str_reserved, "Platz $seat->caption ( $seat->description ), $seat->name, $date, <span style='margin-left: 20px'> $seat->price €</span>");
		$total_price += (int)$seat->price;
	}
	$seats_str_reserved = "<li>".implode($seats_str_reserved, "</li><li>")."</li>";

	/*$seats_str_cancelled = array();
	foreach($cancelled_seats as $seat) {
		array_push($seats_str_cancelled, "Platz " . $seat->caption . " (" . $seat->description . ")<span style='margin-left: 20px'>" . $seat->price . " €</span>");
	}
	$seats_str_cancelled = "<li>".implode($seats_str_cancelled, "</li><li>")."</li>";*/

	
	//$randval = rand();
	//$random = "<input type='hidden' value='$randval'>";
	//$show_str = $res->name . " am " . date2str($res->date);
	
	$body = file_get_contents(plugins_url( 'theater_reservation_plugin/tgm_stornomail.html' ));


	if($cancelled_seats == 1)
		$cancelled_seats_msg = "wurde ein Platz";
	else
		$cancelled_seats_msg = "wurden $cancelled_seats Plätze";

	$body = str_replace("%%SEATS_CANCELLED%%", $cancelled_seats_msg, $body);
	$body = str_replace("%%SEATS%%", $seats_str_reserved, $body);
	//$body = str_replace("%%SHOW%%", $show_str, $body);
	$body = str_replace("%%NAME%%", $name, $body);
	$body = str_replace("%%STORNO_LINK%%", "https://theater-muehlbach.at/storno?token=$storno_token", $body);
	//$body = str_replace("%%RANDOM%%", $random, $body); // prevents gmail citing
	// $body = randomize($body);
	// $body = randomize($body, '</span>');
	
	//$body = 'Le great reservation <p style="color: red">Super HTML</p>';
	$headers = array('Content-Type: text/html; charset=UTF-8');
 
	wp_mail( $to, $subject, $body, $headers );
	wp_mail( "dschoerk@gmx.at", "ADMININFO: " . $subject, $body, $headers );
}

//Helper to randomize HTML contents
function randomize($html, $tag = '</p>') {  
  //Create a 5 char random string for email content to be unique
  $hash = /*crypto
    .createHash('md5')
    .update(time)
    .digest('hex')*/
	base64_encode(md5(time())).substr(0, 5);

  //Create HTML string to replace with and regex
  $str = '<span style="display: none !important;">' . $hash . '</span>' . $tag;
  //$regex = new RegExp(tag, 'g');

  //Replace in HTML
  return str_replace($tag, $str, $html);
}

function xyz_filter_wp_mail_from($email) {
	return "reservierung@theater-muehlbach.at";
}
//add_filter("wp_mail_from", "xyz_filter_wp_mail_from");

function xyz_filter_wp_mail_from_name($from_name){
	return "Theater Mühlbach";
}
//add_filter("wp_mail_from_name", "xyz_filter_wp_mail_from_name");


/*create_show("Zu früh getraut (Premiere)", "2018-02-17 19:30:00");
create_show("Zu früh getraut", "2018-02-18 19:30:00");
create_show("Zu früh getraut", "2018-02-20 19:30:00");
create_show("Zu früh getraut", "2018-02-21 19:30:00");
create_show("Zu früh getraut", "2018-02-23 19:30:00");
create_show("Zu früh getraut", "2018-02-24 19:30:00");*/



// admin page

function t($t, $cont) {
	echo("<{$t}>{$cont}</{$t}>");
}

function print_shows($shows) {
	echo('<table class="admin-table">');
		echo('<tr>');
		echo('<th>Title</th>');
		echo('<th>Date</th>');
		echo('<th>Reserved Seats</th>');
		echo('</tr>');
		foreach($shows as $show) {
			print_show($show);
		}
	echo('</table>');
}

function print_show($show) {
	echo('<tr>');
	echo('<td>' . $show->name . '</td>');
	echo('<td>' . $show->date . '</td>');
	echo('<td>' . $show->seats_reserved . '</td>');

	echo('<td><form action="" method="post">');
          echo('<input type="submit" value="Details">');
          echo('<input type="hidden" name="action" value="show_details">');
          echo('<input type="hidden" name="show_id" value="' . $show->id . '">');
        echo('</form></td>');

	echo('</tr>');
}

function print_reservations($reservations) {
	echo('<table>');
                echo('<tr>');
                echo('<th>Forename</th>');
                echo('<th>Surename</th>');
                echo('<th>Email</th>');
                echo('<th>Seats</th>');
		echo('<th>Price</th>');
                echo('</tr>');
                foreach($reservations as $res) {
                        print_reservation($res);
                }
        echo('</table>');
}

function reservations_to_csv_download($show, $reservations, $filename = "export.csv", $delimiter=";") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    $fname = $show->id . '_' . $show->name . '_' . $show->date . '.csv';
    $fname = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '_', $fname);
    $fname = '/csvexport/' . $fname;
//    $fname = plugin_dir_path( __FILE__ ) . $fname; //plugins_url( 'csvexport/'.$fname, __FILE__ );
    $f = fopen(plugin_dir_path( __FILE__ ) . $fname, 'w'); 
	// loop over the input array
	
	// table header
	$array = array("Vorname", "Nachname", "Email", "Plätze", "Preis");
	$array = array_map("utf8_decode", $array);
	fputcsv($f, $array, $delimiter); 

    foreach ($reservations as $res) { 
        // generate csv lines from the inner arrays
		$array = array($res->forename, $res->surename, $res->email, $res->seats, $res->totalprice);
		$array = array_map("utf8_decode", $array);
        fputcsv($f, $array, $delimiter); 
    }
    // reset the file pointer to the start of the file
//    fseek($f, 0);
    // tell the browser it's going to be a csv file
//    header('Content-Type: application/csv');
    // tell the browser we want to save it instead of displaying it
//    header('Content-Disposition: attachment; filename="'.$filename.'";');
    // make php send the generated csv lines to the browser
//    fpassthru($f);
    fclose($f);

	Logger::info("export as csv " . $show->id . " " . $filename. " " . $delimiter);

    return plugins_url( $fname, __FILE__ );;
}

function print_reservation($res) {

	if($res->email == "a0s9a0ßs9df8ad8f")
		$res->email = "";

	echo('<tr>');
	echo('<td>' . $res->forename . '</td>');
	echo('<td>' . $res->surename . '</td>');
	echo('<td>' . $res->email . '</td>');
	echo('<td>' . $res->seats . '</td>');
	echo('<td>' . $res->totalprice . '</td>');

/*	echo('<td><form action="" method="post">');
	  echo('<input type="submit" value="Details">');
	  echo('<input type="hidden" name="action" value="show_details">');
	  echo('<input type="hidden" name="show_id" value="' . $show->id . '">');
	echo('</form></td>');*/

	echo('</tr>');
}

function reservation_test_page()
{

	if(isset($_POST["action"]) && $_POST["action"] == "show_details") {
	    $show = $_POST["show_id"];
		$showdata = ReservationService::get_show($show);
		echo('<div class="wrap"><h2>Reservations for show ' . $showdata->name . '</h2></div>');
		echo('<div class="wrap"><h3>' . date2str($showdata->date) . '</h3></div>');

		echo(sitzplan($show, false, false) );

		$reservations = ReservationService::get_reservations($show);
		print_reservations($reservations);

/*		echo('<a href="" Download as CSV</a>');
		echo('<td><form action="" method="post">');
	          echo('<input type="submit" value="Export to CSV">');
        	  echo('<input type="hidden" name="action" value="export_reservations_for_show">');
	          echo('<input type="hidden" name="show_id" value="' . $show . '">');
	        echo('</form></td>');*/

		echo("<a href='" . reservations_to_csv_download($showdata, $reservations) . "'>Download as CSV</a>");
		//print_r($reservations);
	}
/*	else if(isset($_POST["action"]) && $_POST["action"] == "delete_seat_from_reservation") {
	        $show = $_POST["show_id"];
	        $res = $_POST["res_id"];
	        $seat = $_POST["seat_id"];

		

	}*/ else {
		echo('<div class="wrap"><h2>Shows</h2></div>');
		$shows = ReservationService::get_shows();
		print_shows($shows);
	}
}

function reservation_sub_create_show() {
	/*if(isset($_POST[""])) {

	} else {

	}*/ 

	echo("not implemented - use function create_show");
}

function reservation_settings_page()
{
	echo('<div class="wrap"><h2>Settings</h2></div>');
	
}

add_action( 'admin_menu', 'theater_admin_menu' );

function theater_admin_menu() {
	add_menu_page( 'Theater Reservation Plugin', 'Reservierungen', 'read', 'reservation/main.php', 'reservation_test_page', 'dashicons-tickets', 6  );
	add_submenu_page( 'reservation/main.php', 'Manage Registrations', 'Kartenbüro', 'read', 'reservation/manage_registrations.php', 'reservation_sub_manage' );
	add_submenu_page( 'reservation/main.php', 'Create Show', 'Create Show', 'read', 'reservation/manage_shows.php', 'reservation_sub_createshow' );
	
	//add_submenu_page( 'reservation/test.php', 'Settings', 'Settings', 'manage_options', 'reservation/settings.php', 'reservation_settings_page' );
}

function reservation_sub_createshow() {
	global $wpdb;

	if($_POST){
		$name = $_POST['name'];
		$date = $_POST['date'];

		create_show($name, $date);
	}


	$html = '<form method="POST">
		<label for="name">Name:</label>
		<input type="text" name="name" id="name">
		
		<label for="date">Date:</label>
		<input type="date" name="date" id="date">
		
		<button type="submit">Submit</button>
	</form>';
	echo($html);
}

// reservation admin menu - manage
function reservation_sub_manage() {

	if(isset($_POST["res_seats_selected"]) && isset($_POST["res_show"])) {

		$already_payed = 0;
		if(isset($_POST["already_payed"]) && $_POST["already_payed"] == "on")
			$already_payed = 1;

		$seats_selected = $_POST["res_seats_selected"];
		$success = ReservationService::add_reservation($_POST["res_show"], $already_payed, "Haidler", "Haidler", "a0s9a0ßs9df8ad8f", false, $seats_selected, false, false);

		if(!$success) {
			die("Etwas ist schiefgegangen");
		}
	}

	if(isset($_POST["res_seats_deselected"]) && isset($_POST["res_show"])) {
		$seats_deselected = $_POST["res_seats_deselected"];

		foreach($seats_deselected as $seatid)
			ReservationService::delete_seat_reservation_by_show($_POST["res_show"], $seatid);
	}

	if(isset($_POST["res_seats_deselected"])) {
		$seats_deselected = $_POST["res_seats_deselected"];
		//print_r($seats_deselected);
	}

	if(isset($_POST["res_show"]))
		$showid = esc_sql($_POST["res_show"]);
	else
		$showid = esc_sql(2); // just select something that exists

	
	
	$html = selection_form($showid, true);
	//echo("here");
	$show = get_show_by_id($showid);
	
	
	$datestr = date2str($show->date);
	$html .= "<h3>$show->name am $datestr</h3>";
	$html .= '<div style="margin-top: 20px; margin-bottom: 20px"><div style="display:inline-block"><span class="rang1" style="padding: 5px">Rang 1</span> 12€</div>';
	$html .= '<div style="display:inline-block; margin-left: 10px"><span class="rang2" style="padding: 5px">Rang 2</span> 10€</div></div>';
	$html .= sitzplan($showid, false);

	echo($html);


	$reservations = ReservationService::get_reservations($showid);
	$showdata = ReservationService::get_show($showid);
	
	print_reservations($reservations);
	echo("<a href='" . reservations_to_csv_download($showdata, $reservations) . "'>Download as CSV</a>");

	Logger::info("reservation_sub_manage showid=$showid");
}


function load_custom_wp_admin_style() {
        wp_register_style( 'wp_theater_res_style', plugins_url( 'css/style.css', __FILE__ ), false, '1.0.0' );
        wp_enqueue_style( 'wp_theater_res_style' );
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );


/// add reservation management capability
/*function register_theater_registration_capability() {
    register_setting( 'theater-registration', 'manage-theater-registration', array('type' => 'boolean', 'description' => 'Can manage theater registrations') ); 
} 
add_action( 'admin_init', 'register_theater_registration_capability' );
 
// Modify capability
function theater_registration_capability( $capability ) {
    return 'edit_theater_registrations';
}
add_filter( 'option_page_capability_theater-registration', 'theater_registration_capability' );*/

?>
<?php

require_once('logging.php');

class ReservationService {

//	public static prefix = "wp_tgm_";

	public static function get_shows() {
		global $wpdb;
        	$shows = $wpdb->get_results( "SELECT name, date, s.id, (SELECT COUNT(*) FROM wp_tgm_seat_reservation res WHERE res.show=s.id) as seats_reserved FROM wp_tgm_show s");
		$wpdb->show_errors();
        	return $shows;
	}

	public static function get_show($showid) {
		global $wpdb;

                $showid = esc_sql($showid);
                $res = $wpdb->get_results( "SELECT id, name, date FROM wp_tgm_show WHERE id=" . $showid . " LIMIT 1");
                $wpdb->show_errors();
		if(count($res) != 1)
			die('error');
                return $res[0];
	}

	public static function get_reservations($showid) {
		global $wpdb;
		
		$showid = esc_sql($showid);
        	$res = $wpdb->get_results( "SELECT forename, surename, email, reservation, GROUP_CONCAT(IF(sr.payed, CONCAT(s.caption, ' (bez)'), s.caption) SEPARATOR ', ') as seats, SUM(price) as totalprice FROM wp_tgm_reservation r LEFT JOIN wp_tgm_seat_reservation sr ON r.id = sr.reservation LEFT JOIN wp_tgm_seat s ON sr.seat=s.id AND sr.show=s.show WHERE sr.show={$showid} GROUP BY r.id ORDER BY surename ASC");
		$wpdb->show_errors();
        	return $res;
	}

	public static function delete_seat_reservation($token, $seatid) {
		global $wpdb;
		
		$token = esc_sql($token);
		$seatid = esc_sql($seatid);
        $res = $wpdb->get_results( "DELETE sr FROM wp_tgm_seat_reservation sr LEFT JOIN wp_tgm_reservation r ON r.id=sr.reservation WHERE r.storno_token='{$token}' AND sr.seat={$seatid}");
		$wpdb->show_errors();

		Logger::info("delete seat reservation by token " . $token . " seatid: " . $seatid);

        return $res;
	}

	public static function delete_seat_reservation_by_show($showid, $seatid) {
		global $wpdb;
		
		$showid = esc_sql($showid);
		$seatid = esc_sql($seatid);
        $res = $wpdb->get_results( "DELETE sr FROM wp_tgm_seat_reservation sr WHERE sr.show={$showid} AND sr.seat={$seatid}");
		$wpdb->show_errors();

		if ($wpdb->last_error) {
			Logger::error($wpdb->last_error);
		}

		Logger::info("delete seat reservation by show" . $showid . " seatid: " . $seatid);

        return $res;
	}

	public static function get_reservation_seats_for_token($token) {
		global $wpdb;
		
		$token = esc_sql($token);
		$res = $wpdb->get_results( "SELECT s.id, s.description, s.caption, s.price, ss.name, ss.date FROM wp_tgm_reservation r LEFT JOIN wp_tgm_seat_reservation sr ON sr.reservation = r.id LEFT JOIN wp_tgm_seat s ON s.show=sr.show AND s.id=sr.seat JOIN wp_tgm_show ss ON ss.id=s.show WHERE storno_token='{$token}'");
		$wpdb->show_errors();
		if ($wpdb->last_error) {
			Logger::error($wpdb->last_error);
		}

		return $res;
	}

	public static function get_reservation_for_token($token) {
		global $wpdb;
		
		$token = esc_sql($token);
		$res = $wpdb->get_results( "SELECT r.forename, r.surename, r.email FROM wp_tgm_reservation r WHERE storno_token='{$token}'");
		$wpdb->show_errors();

		if(count($res) != 1) {
			Logger::error("get_reservation_for_token returned more than one reservation for token=$token");
			die('error');
		}
                
		return $res[0];
	}

	public static function add_reservation($showid, $already_payed, $forename, $surename, $email, $email_ads, $seatids, $limit_seats = false, $send_mails = true) {
		global $wpdb;

		Logger::info("begin add_reservation");

		$wpdb->query('START TRANSACTION');
		$wpdb->query( "INSERT INTO wp_tgm_reservation (forename, surename, email, email_ads, storno_token) VALUES ('$forename', '$surename', '$email', '$email_ads', UUID())");
		$error = false;

		if ($wpdb->last_error) {
			Logger::error($wpdb->last_error);
			$error = true;
		}
		
		$reservationid = $wpdb->insert_id;
		$storno_token = $wpdb->get_results("SELECT storno_token FROM wp_tgm_reservation WHERE id=" . $reservationid);
		$storno_token = $storno_token[0];

		if($limit_seats && count($seatids) > 8) {
			Logger::error("more seats selected than allowed " . $forename . " " . $surename . " " . $email);
			$error = true;
		}
		
		foreach($seatids as $seatid) {
			$wpdb->query( "INSERT INTO wp_tgm_seat_reservation (seat, reservation, `show`, payed) VALUES ($seatid, $reservationid, $showid, $already_payed)");
			
			if ($wpdb->last_error) {
				Logger::error($wpdb->last_error);
				$error = true;
			}
		}

		if ($error) {
			$wpdb->query('ROLLBACK');
			Logger::error("add_reservation failed, rollback");

			return false;
		} else {
			$wpdb->query('COMMIT');

			if($send_mails)
				send_reservation_mail($forename . ' ' . $surename, $email, $reservationid, $showid, $storno_token->storno_token);
			
			Logger::info("add_reservation success showid=$showid forename=$forename surename=$surename email=$email seatids=".serialize($seatids) . " token=" . $storno_token->storno_token);
			return true;
		}
	}
}

// older utility functions

function date2str($date) {
	$data_f = date_format(date_create($date), 'd.m.Y H:i');
	return $data_f;
}

function get_show_by_id($showid) {
	global $wpdb;
	
	$showid = esc_sql($showid);
	$show = $wpdb->get_results( "SELECT id, name, date FROM wp_tgm_show WHERE id = '$showid'");
	if(count($show) != 1)
		die('error'); 
	return $show[0];
}

function get_seats_by_ids($showid, $seatids) {
	global $wpdb;
	
	$showid = esc_sql($showid);
	$seatids = esc_sql(implode($seatids,","));
	$seats = $wpdb->get_results( "SELECT id, price, caption, description FROM wp_tgm_seat s WHERE s.show = $showid AND s.id IN ($seatids)");
	
	if(count($seats) <= 0)
		die('error'); 
	return $seats;
}

function get_seats_for_reservation($reservationid) {
	global $wpdb;
	
	$reservationid = esc_sql($reservationid);
	$seats = $wpdb->get_results( "SELECT id, price, caption, description FROM wp_tgm_seat s LEFT JOIN wp_tgm_seat_reservation r ON s.id=r.seat AND s.show=r.show WHERE r.reservation = $reservationid");
	
	if(count($seats) <= 0)
		die('error'); 
	return $seats;
}

?>

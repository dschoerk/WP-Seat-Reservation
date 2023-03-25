<?php

require_once('logging.php');
require_once('sitzplan.php');

class ReservationService {

//	public static prefix = "wp_tgm_";

	public static function create_tables() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "
		CREATE TABLE `wp_tgm_show` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(128) NOT NULL,
		`date` datetime NOT NULL,
		`reservation_until` datetime NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		dbDelta($sql);

		$sql = "
			CREATE TABLE `wp_tgm_reservation` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`forename` varchar(64) NOT NULL,
			`surename` varchar(64) NOT NULL,
			`email` varchar(64) NOT NULL,
			`email_ads` tinyint(1) NOT NULL,
			`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`storno_token` varchar(32) NOT NULL,
			PRIMARY KEY (`id`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		dbDelta($sql);
		
		$sql = "
		CREATE TABLE `wp_tgm_seat` (
			`id` varchar(11) NOT NULL,
			`price` int(11) NOT NULL,
			`caption` varchar(12) NOT NULL,
			`description` varchar(64) NOT NULL,
			PRIMARY KEY (`id`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		dbDelta($sql);
		
		$sql = "
		CREATE TABLE `wp_tgm_seat_reservation` (
		`seat` varchar(11) NOT NULL,
		`reservation` int(11) NOT NULL,
		`show` int(11) NOT NULL,
		`payed` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`seat`,`show`),
		KEY `show` (`show`),
		KEY `reservation` (`reservation`),
		CONSTRAINT `wp_tgm_seat_reservation_ibfk_1` FOREIGN KEY (`seat`) REFERENCES `wp_tgm_seat` (`id`),
  		CONSTRAINT `wp_tgm_seat_reservation_ibfk_2` FOREIGN KEY (`show`) REFERENCES `wp_tgm_show` (`id`),
  		CONSTRAINT `wp_tgm_seat_reservation_ibfk_3` FOREIGN KEY (`reservation`) REFERENCES `wp_tgm_reservation` (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		dbDelta($sql);

		$sql = "
		CREATE TABLE `wp_tgm_settings` (
		`setting` varchar(64) NOT NULL,
		`value` varchar(128) NOT NULL,
		PRIMARY KEY (`setting`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		dbDelta($sql);

		if ($wpdb->get_var("SELECT COUNT(*) FROM wp_tgm_seat") > 0)
		{
			// check seat config
			//echo("check");
			$seat_config = make_seat_config();

			$seat_data = $wpdb->get_results("SELECT id FROM wp_tgm_seat");

			foreach($seat_config as $row => $seats) {
				foreach($seats as $seat) {
					if ($seat > 0) {// ignore 0's

						find_seat($seat_data, "$row-$seat");
					}
				}
			}
		}
		else
		{
			// create seat config

			$seat_config = make_seat_config();

			foreach($seat_config as $row => $seats) {
				foreach($seats as $seat) {
					if ($seat == 0) // ignore 0's
						continue;

					$seatid = "$row-$seat";
					$caption = "Reihe: $row Sitz: $seat";
					$description = $caption;
					$price = 10;
					if($row >= 1 && $row <= 5)
						$price = 15;
					elseif($row >= 6 && $row <= 9)
						$price = 12;
					else
						$price = 10;

					$wpdb->query( "INSERT INTO `wp_tgm_seat` (`id`, `caption`, `description`, `price`) VALUES ('$seatid', '$caption', '$description', '$price')");
				}
			}
		}	
		
		/*
		$rows = 14;
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
		
		/*if ($wpdb->last_error) {
			echo 'Error' . $wpdb->last_error;
			$wpdb->query('ROLLBACK');
			return;
		}*/

		

		/*$sql = "
		ALTER TABLE `wp_tgm_reservation` ADD PRIMARY KEY (`id`);
	    ALTER TABLE `wp_tgm_reservation` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}*/
		
		/*$sql = "
			ALTER TABLE `wp_tgm_seat` ADD PRIMARY KEY (`id`,`show`), ADD KEY `show` (`show`);
			ALTER TABLE `wp_tgm_seat` ADD CONSTRAINT `wp_tgm_seat_ibfk_1` FOREIGN KEY (`show`) REFERENCES `wp_tgm_show` (`id`);
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}*/
		
		/*$sql = "
		ALTER TABLE `wp_tgm_seat_reservation` 
		ADD PRIMARY KEY (`seat`,`show`),
		ADD KEY `show` (`show`),
		ADD KEY `reservation` (`reservation`);
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}*/

		/*$sql = "
		ALTER TABLE `wp_tgm_seat_reservation`
  		ADD CONSTRAINT `wp_tgm_seat_reservation_ibfk_1` FOREIGN KEY (`seat`) REFERENCES `wp_tgm_seat` (`id`),
  		ADD CONSTRAINT `wp_tgm_seat_reservation_ibfk_2` FOREIGN KEY (`show`) REFERENCES `wp_tgm_show` (`id`),
  		ADD CONSTRAINT `wp_tgm_seat_reservation_ibfk_3` FOREIGN KEY (`reservation`) REFERENCES `wp_tgm_reservation` (`id`);
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}
		

		$sql = "
		ALTER TABLE `wp_tgm_settings` ADD PRIMARY KEY (`setting`);
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}

		$sql = "
			ALTER TABLE `wp_tgm_show` ADD PRIMARY KEY (`id`);
			ALTER TABLE `wp_tgm_show` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
		";
		if(!$wpdb->query($sql)){die($wpdb->last_error);}*/
		
		

	}

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
        	$res = $wpdb->get_results( "SELECT forename, surename, email, reservation, GROUP_CONCAT(IF(sr.payed, CONCAT(s.caption, ' (bez)'), s.caption) SEPARATOR ', ') as seats, SUM(price) as totalprice FROM wp_tgm_reservation r LEFT JOIN wp_tgm_seat_reservation sr ON r.id = sr.reservation LEFT JOIN wp_tgm_seat s ON sr.seat=s.id WHERE sr.show={$showid} GROUP BY r.id ORDER BY surename ASC");
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
			$sql = "INSERT INTO wp_tgm_seat_reservation (seat, reservation, `show`, payed) VALUES ('$seatid', $reservationid, $showid, $already_payed)";
			$wpdb->query( $sql);
			
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
	$seat_ids_strs = [];
	foreach($seatids as $id) {
		$id = esc_sql($id);
		$seat_ids_strs[] = "'$id'";
	}
	$seat_ids_str = implode(",", $seat_ids_strs);
	//$seat_ids_str = esc_sql($seat_ids_str);
	//$seatids = esc_sql(implode(",", $seatids));

	//print_r($seat_ids_str);

	
	$sql = "SELECT id, price, caption, description FROM wp_tgm_seat s WHERE s.id IN ($seat_ids_str)";
	//print($sql);
	$seats = $wpdb->get_results( $sql);

	//print_r($seats);

	if(count($seats) <= 0)
		die('error'); 
	return $seats;
}

function get_seats_for_reservation($reservationid) {
	global $wpdb;
	
	$reservationid = esc_sql($reservationid);
	$seats = $wpdb->get_results( "SELECT id, price, caption, description FROM wp_tgm_seat s LEFT JOIN wp_tgm_seat_reservation r ON s.id=r.seat WHERE r.reservation = $reservationid");
	
	if(count($seats) <= 0)
		die('error'); 
	return $seats;
}

?>

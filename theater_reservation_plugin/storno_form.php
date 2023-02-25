<?php

require_once('logging.php'); 


function render_storno_form() {
    
    if(isset($_GET['token']))
        $storno_token = $_GET['token'];
    else if(isset($_POST['token']))
        $storno_token = $_POST['token'];
    else
        die('error');


    

    // if there is seat ids passed, delete them
    if(isset($_POST["seat_list"])) {
        $seat_ids = $_POST['seat_list'];
        //$seatids = unserialize(base64_decode($seats_post));
        foreach($seat_ids as $seat) {
            ReservationService::delete_seat_reservation($storno_token, $seat);
        }

        $res_seats = ReservationService::get_reservation_seats_for_token($storno_token);

        $reservation = ReservationService::get_reservation_for_token($storno_token);
        
        send_storno_mail($reservation, $res_seats, count($seat_ids), $storno_token);

        echo(count($seat_ids) . " Plätze storniert! Email bestätigung gesendet");
    }

//    echo("remove: ")

    $res = ReservationService::get_reservation_seats_for_token($storno_token);

    if(count($res) > 0)
    {
        $seats_str = array();
        foreach($res as $seat) {
            $style = 'style="font-weight: 800;"';
            $seat->description = '<span ' . $style . '>' . $seat->description . '</span>';
            $seat->date = '<span ' . $style . '>' . date2str($seat->date) . '</span>';
            $seat->name = '<span ' . $style . '>' . $seat->name . '</span>';
            array_push($seats_str, '<input type="checkbox" name="seat_list[]" value="' . $seat->id . '"> ' . $seat->caption . " (" . $seat->description . ') für ' . $seat->name . ' am ' . $seat->date);
        }

        $seats_str = '<li>' . implode($seats_str, "</li><li>") . '</li>';

        echo('<form action="" method="post">');
        echo("<ul style='list-style-type: none'>");
        echo($seats_str);
        echo("</ul>");
        echo('<input type="submit" value="Auswahl stornieren">');
        echo('<input type="hidden" name="token" value="' . $storno_token . '">');
        echo('</form>');
    } else {
        echo("keine Karten in dieser Reservierung");
    }
}

?>
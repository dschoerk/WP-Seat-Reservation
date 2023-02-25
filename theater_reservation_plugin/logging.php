<?php
    

// all actions are logged to a file 
    class Logger {
        static private function _log($text) {
            $fpath = plugin_dir_path( __FILE__ ) . "reservation.log";
            $f = file_put_contents($fpath, $text , FILE_APPEND | LOCK_EX);
        }

        static private function log($level, $msg) {
            $date = date('m/d/Y H:i:s', time());

            Logger::_log($_SERVER['REMOTE_ADDR'] . " - " . $date . " - " . $level . " - " . $msg . "\n");
        }

        static public function info($msg) {
            Logger::log("INFO", $msg);
        }

        static public function error($msg) {
            Logger::log("ERROR", $msg);
        }

        static public function debug($msg) {
            Logger::log("DEBUG", $msg);
        }
    }
?>
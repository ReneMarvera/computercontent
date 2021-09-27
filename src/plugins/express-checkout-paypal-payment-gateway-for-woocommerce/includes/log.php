<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eh_PayPal_Log {

    public static function init_log() {
        $content = "<------------------- WebToffee PayPal Express Payment Log File ( " . EH_PAYPAL_VERSION . " ) ------------------->\n";
        return $content;
    }

    public static function remove_data($data) {
        unset($data['USER']);
        unset($data['PWD']);
        unset($data['SIGNATURE']);
        unset($data['VERSION']);
        return $data;
    }

    public static function log_update($mg, $title) {
        $check = get_option('woocommerce_eh_paypal_express_settings');
        $msg = Eh_PayPal_Log::remove_data($mg);
        if ('yes' === $check['paypal_logging']) {
            if (WC()->version >= '2.7.0') {
                $log = wc_get_logger();
                $head = "<------------------- WebToffee PayPal Express Payment ( " . $title . " ) ------------------->\n";
                $log_text = $head . print_r((object) $msg, true);
                $context = array('source' => 'eh_paypal_express_log');
                $log->log("debug", $log_text, $context);
            } else {
                $log = new WC_Logger();
                $head = "<------------------- WebToffee PayPal Express Payment ( " . $title . " ) ------------------->\n";
                $log_text = $head . print_r((object) $msg, true);
                $log->add("eh_paypal_express_log", $log_text);
            }
        }
    }

}

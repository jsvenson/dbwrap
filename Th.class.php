<?php

require_once('Inflector.class.php');

mb_internal_encoding('UTF-8'); # set internal encoding to utf8 so mb_strlen('…') returns 1 instead of 3

@session_start(); # (re)start session if it hasn't yet been

/**
* TextHelper
*/
class Th {
    const ELLIPSIS_START = 0;
    const ELLIPSIS_MIDDLE = 1;
    const ELLIPSIS_END = 2;
    
    public static function pluralize($val, $singular, $plural='') {
        return $val == 1 ? $singular : ($plural == '' ? Inflector::pluralize($singular) : $plural);
    }
    
    # this doesn't really make sense to have in the texthelper class
    public static function flash($message = null, $message_type = '') {
        if (is_null($message)) {
            if (isset($_SESSION['flash']) && $_SESSION['flash'] != array()) {
                foreach ($_SESSION['flash'] as $msg) {
                    echo '<div class="message '.$msg['type'].'">'.$msg['msg'].'</div>';
                }

                $_SESSION['flash'] = array();
            }
        } else {
            $_SESSION['flash'][] = array('msg' => $message, 'type' => $message_type);
        }
    }

    public static function date($date, $type = 'short', $format = '', $show_time = true) {
        $time = strtotime($date);
        
        switch ($type) {
            case 'short':
                $datestr = 'M j, Y';
                if ($time < strtotime('+2 days')) $datestr = '\T\o\m\o\r\r\o\w';
                if ($time < strtotime('tomorrow')) $datestr = '\T\o\d\a\y';
                if ($time < strtotime('today')) $datestr = '\Y\e\s\t\e\r\d\a\y';
                if ($time < strtotime('yesterday') && $time >= strtotime('-5 days'))
                    $datestr = 'D';
                if ($time < strtotime('-5 days')) $datestr = 'M j, Y';
                
                if ($show_time) $datestr .= ' g:ia';
                
                break;
            case 'long':
                $datestr = 'l M j';
                if ($show_time) $datestr .= ', g:ia T';
                break;
            case 'custom':
                $datestr = $format;
                break;
            default:
                $datestr = 'Y-m-d';
                if ($show_time) $datestr .= ' H:i T';
                break;
        }
        
        return date($datestr, $time);
    }
    
    # restricts $string to $length characters by one of three methods: 
    # ELLIPSIS_START - returns the last $length characters of $string prefixed by $ellipsis_string
    # ELLIPSIS_MIDDLE - returns 
    public static function fixedLengthString($string, $length, $ellipsis_string = '…', $ellipsis_position = self::ELLIPSIS_MIDDLE) {
        if (is_null($ellipsis_string)) $ellipsis_string = '…';
        
        if (strlen($string) <= $length) return $string;
        
        switch ($ellipsis_position) {
            case self::ELLIPSIS_START:
                $shorter = $ellipsis_string . mb_substr($string, -($length - strlen($ellipsis_string)));
                break;
            case self::ELLIPSIS_MIDDLE:
                if ($length < 5) $shorter = mb_substr($string, 0, ($length - strlen($ellipsis_string))) . $ellipsis_string;
                else {
                    $half = (int)(($length - strlen($ellipsis_string)) / 2);
                    $shorter = mb_substr($string, 0, $half + (($length - strlen($ellipsis_string)) % 2)) . $ellipsis_string . mb_substr($string, -$half);
                }
                break;
            case self::ELLIPSIS_END:
                $shorter = mb_substr($string, 0, ($length - strlen($ellipsis_string))) . $ellipsis_string;
                break;
        }
        
        return $shorter;
    }
    
    # returns string with all applicable character encoded as html entities using UTF8 encoding
    # and converting double quotes while ignoring single quotes
    public static function escape($str) {
       return htmlentities($str, ENT_COMPAT, 'UTF-8');
    }
    
    # format $number as currency
    # precision -> sets the level of precision. Default 2
    # unit      -> sets denomination of currency. Default '$'
    # separator -> sets the decimal separator. Default '.'
    # delimiter -> sets the thousands separator. Default ','
    # format    -> sets the format of the output string. Default '%u%n'
    # format field types are '%u' = currency unit, '%n' = number
    public static function number_to_money($number, $opt = array()) {
        # this could likely be replaced with built-in money_format()
        
        $precision = isset($opt['precision']) ? $opt['precision'] : 2;
        $unit = isset($opt['unit']) ? $opt['unit'] : '$';
        $separator = isset($opt['separator']) ? $opt['separator'] : '.';
        $delimiter = isset($opt['delimiter']) ? $opt['delimiter'] : ',';
        $format = isset($opt['format']) ? $opt['format'] : '%u%n';
        
        $separator = $precision == 0 ? '' : $separator;
        
        try {
            return self::escape(preg_replace("/%u/", $unit, 
                preg_replace("/%n/", number_format($number, $precision, $separator, $delimiter), $format)));
        } catch (Exception $e) {
            return $number;
        }
    }
    
    # format $number into a Canadian/US phone number
    # area_code    -> add parantheses around the area code (leave false if $number does not include area code)
    # delimiter    -> specified delimiter. Default '-'
    # extension    -> specified an extension to add to the end of the number
    # country_code -> sets the country code for the number
    # raise_error  -> if true raises an exception when $number contains non-numeric characters
    public static function number_to_phone($number, $opt = array()) {
        $number = trim($number);
        $area_code = isset($opt['area_code']) ? $opt['area_code'] : false;
        $delimiter = isset($opt['delimiter']) ? $opt['delimiter'] : '-';
        $extension = isset($opt['extension']) ? trim($opt['extension']) : null;
        $country_code = isset($opt['country_code']) ? $opt['country_code'] : null;
        $raise_error = isset($opt['raise_error']) ? $opt['raise_error'] : false;
        
        if (preg_match('/[^\d]/', $number) > 0) {
            if ($raise_error) { # throw an error on non-numeric numbers
                throw new Exception('Invalid phone number.');
            } else { # return the invalid number
                return $number;
            }
        }
            
        
        $number = preg_replace('/[\s]/', '', $number); # remove non-numeric characters
        
        if ($area_code) {
            $number = preg_replace('/(\d{1,3})(\d{3})(\d{4}$)/', '($1) $2'.$delimiter.'$3', $number);
        } else {
            $number = preg_replace('/(\d{0,3})(\d{3})(\d{4})$/', '$1'.$delimiter.'$2'.$delimiter.'$3', $number);
            if ($delimiter != '' && $number[0] == $delimiter) {
                $number = substr($number, 1);
            }
        }
        
        $str = '';
        if (!is_null($country_code)) $str .= '+'.$country_code.$delimiter;
        $str .= $number;
        if (!is_null($extension)) $str .= ' x '.$extension;
        
        return self::escape($str);
    }
    
    # format $number into human readable file size. $number is in bytes
    # precision -> 
    public static function number_to_human_size($number, $opts = array()) {
        $unit      = isset($opts['unit']) ? $opts['unit'] : 'Bytes';
        $precision = isset($opts['precision']) ? $opts['precision'] : 2;
        $separator = isset($opts['separator']) ? $opts['separator'] : '.';
        $delimiter = isset($opts['delimiter']) ? $opts['delimiter'] : ',';
        $prefix    = isset($opts['si']) ? $opts['si'] : false;
        
        $storage_units = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
        $base = $prefix ? 1000 : 1024;
        
        if ((int)$number < $base) {
            return self::escape($number.' '.$unit);
        } else {
            $max_exp = count($storage_units) - 1;
            $exponent = (int)(log($number) / log($base));
            $exponent = $exponent > $max_exp ? $max_exp : $exponent;
            $number /= pow($base, $exponent);
            
            $unit_key = $storage_units[$exponent];
            
            $formatted_number = number_format($number, $precision, $separator, $delimiter);
            
            return self::escape($formatted_number.' '.$unit_key);
        }
    }
}

?>

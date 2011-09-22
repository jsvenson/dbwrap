<?php

/**
* Inflector
* http://snippets.dzone.com/posts/show/3205
* based on Ruby on Rails Inflector class
*/
class Inflector {
	private static $plural = array(
		array('/(quiz)$/i',                '$1zes'  ),
		array('/^(oxen)$/i',               '$1'     ),
		array('/^(ox)$/i',                 '$1en'   ),
		array('/([m|l])ice$/i',            '$1ice'  ),
		array('/([m|l])ouse$/i',           '$1ice'  ),
		array('/(matr|vert|ind)id|ex$/i',  '$1ices' ),
		array('/(x|ch|ss|sh)$/i',          '$1es'   ),
		array('/([^aeiouy]|qu)y$/i',       '$1ies'  ),
		array('/(hive)$/i',                '$1s'    ),
		array('/(?:([^f])fe|([lr])f)$/i',  '$1$2ves'),
		array('/sis$/i',                   'ses'    ),
		array('/([ti])a$/i',               '$1a'    ),
		array('/([ti])um$/i',              '$1a'    ),
		array('/(buffal|tomat)o$/i',       '$1oes'  ),
		array('/(bu)s$/i',                 '$1ses'  ),
		array('/(alias|status)$/i',        '$1es'   ),
		array('/(optop|vir)i$/i',          '$1i'    ),
		array('/(octop|vir)us$/i',         '$1uses' ),
		array('/(ax|test)is$/i',           '$1es'   ),
		array('/s$/i',                     's'      ),
		array('/$/',                       's'      )
	);
	
	private static $singular = array(
		array('/(database)s$/i',                                                  '$1'     ),
		array('/(quiz)zes$/i',                                                    '$1'     ),
		array('/(matr)ices$/i',                                                   '$1ix'   ),
		array('/(vert|ind)ices$/i',                                               '$1ex'   ),
		array('/^(ox)en/i',                                                       '$1'     ),
		array('/(octop|vir)(i|uses)$/i',                                          '$1us'   ),
		array('/(cris|ax|test)es$/i',                                             '$1is'   ),
		array('/(shoe)s$/i',                                                      '$1'     ),
		array('/(o)es$/i',                                                        '$1'     ),
		array('/(bus)es$/i',                                                      '$1'     ),
		array('/([m|l])ice$/i',                                                   '$1ouse' ),
		array('/(x|ch|ss|sh)es$/i',                                               '$1'     ),
		array('/(m)ovies$/i',                                                     '$1ovie' ),
		array('/(s)eries$/i',                                                     '$1eries'),
		array('/([^aeiouy]|qu)ies$/i',                                            '$1y'    ),
		array('/([lr])ves$/i',                                                    '$1f'    ),
		array('/(tive)s$/i',                                                      '$1'     ),
		array('/(hive)s$/i',                                                      '$1'     ),
		array('/([^f])ves$/i',                                                    '$1fe'   ),
		array('/(^analy)ses$/i',                                                  '$1sis'  ),
		array('/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '$1$2sis'),
		array('/([ti])a$/i',                                                      '$1um'   ),
		array('/(n)ews$/i',                                                       '$1ews'  ),
		array('/s$/i',                                                            ''       ),
		array('/$/i',                                                             ''       )
	);
	
	private static $irregular = array(
		array('zombie', 'zombies'),
		array('cow', 'cattle'),
		array('move',   'moves'),
		array('sex',    'sexes'),
		array('child',  'children'),
		array('man',    'men'),
		array('person', 'people')
	);
	
	private static $uncountable = array(
		'jeans', 'sheep', 'fish', 'series', 'species', 'money', 'rice', 'information', 'equipment'
	);
	
	private static $approximations = array(
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
		'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
		'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
		'Õ' => 'O', 'Ö' => 'O', '×' => 'x', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
		'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'Th', 'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a',
		'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
		'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd',
		'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
		'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
		'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C',
		'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c', 'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c',
		'Ď' => 'D', 'ď' => 'd', 'Đ' => 'D', 'đ' => 'd', 'Ē' => 'E', 'ē' => 'e', 'Ĕ' => 'E',
		'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E', 'ę' => 'e', 'Ě' => 'E', 'ě' => 'e',
		'Ĝ' => 'G', 'ĝ' => 'g', 'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G',
		'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h', 'Ĩ' => 'I', 'ĩ' => 'i',
		'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I', 'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'İ' => 'I',
		'ı' => 'i', 'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ķ' => 'K', 'ķ' => 'k',
		'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L', 'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l',
		'Ŀ' => 'L', 'ŀ' => 'l', 'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N',
		'ņ' => 'n', 'Ň' => 'N', 'ň' => 'n', 'ŉ' => "'n", 'Ŋ' => 'NG', 'ŋ' => 'ng',
		'Ō' => 'O', 'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o', 'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE',
		'œ' => 'oe', 'Ŕ' => 'R', 'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r',
		'Ś' => 'S', 'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S', 'ş' => 's', 'Š' => 'S',
		'š' => 's', 'Ţ' => 'T', 'ţ' => 't', 'Ť' => 'T', 'ť' => 't', 'Ŧ' => 'T', 'ŧ' => 't',
		'Ũ' => 'U', 'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u', 'Ů' => 'U',
		'ů' => 'u', 'Ű' => 'U', 'ű' => 'u', 'Ų' => 'U', 'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w',
		'Ŷ' => 'Y', 'ŷ' => 'y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z',
		'Ž' => 'Z', 'ž' => 'z'
	);
	
	public static function camelize($lowercase_and_underscored_word, $first_letter_uppercase = true) {
		if ($first_letter_uppercase) {
			return preg_replace('/(^|_)(.)/e', "strtoupper('\\2')", preg_replace('/\/(.?)/e', "'::'.strtoupper('\\1')", $lowercase_and_underscored_word));
		} else {
			return strtolower(substr($lowercase_and_underscored_word, 0, 1)) . substr(self::camelize($lowercase_and_underscored_word), 1);
		}
	}
	
	public static function classify($table_name) {
		return self::camelize(self::singularize($table_name));
	}
	
	public static function dasherize($underscored_word) {
		return preg_replace('/_/', '-', $underscored_word);
	}
	
	public static function demodulize($class_name_in_module) {
		return preg_replace('/^.*\\\\/', '', $class_name_in_module);
	}
	
	public static function foreign_key($class_name, $separate_class_name_and_id_with_underscore = true) {
		return self::underscore(self::demodulize($class_name)) . ($separate_class_name_and_id_with_underscore ? '_id' : 'id');
	}
	
	public static function ordinalize($number) {
		if (11 <= intval($number) % 100 && intval($number) % 100 <= 13) {
			return $number.'th';
		} else {
			switch (intval($number) % 10) {
				case  1: return $number.'st'; break;
				case  2: return $number.'nd'; break;
				case  3: return $number.'rd'; break;
				default: return $number.'th'; break;
			}
		}
	}
	
	public static function pluralize($word) {
		for ($i=0; $i < count(self::$uncountable); $i++) { 
			$uncountable = self::$uncountable[$i];
			if (strtolower($word) == $uncountable)
				return $uncountable;
		}
		
		for ($i=0; $i < count(self::$irregular); $i++) { 
			$singular = self::$irregular[$i][0];
			$plural   = self::$irregular[$i][1];
			if ((strtolower($word) == $singular) || (strtolower($word) == $plural))
				return $plural;
		}
		
		for ($i=0; $i < count(self::$plural); $i++) { 
			$regex   = self::$plural[$i][0];
			$replace = self::$plural[$i][1];
			if (preg_match($regex, $word) > 0)
				return preg_replace($regex, $replace, $word);
		}
	}	
	
	public static function singularize($word) {
		for ($i=0; $i < count(self::$uncountable); $i++) { 
			$uncountable = self::$uncountable[$i];
			if (strtolower($word) == $uncountable)
				return $uncountable;
		}
		
		for ($i=0; $i < count(self::$irregular); $i++) { 
			$singular = self::$irregular[$i][0];
			$plural   = self::$irregular[$i][1];
			if ((strtolower($word) == $singular) || (strtolower($word) == $plural))
				return $singular;
		}
		
		for ($i=0; $i < count(self::$singular); $i++) { 
			$regex   = self::$singular[$i][0];
			$replace = self::$singular[$i][1];
			if (preg_match($regex, $word) > 0)
				return preg_replace($regex, $replace, $word);
		}
	}
	
	public static function tableize($class_name) {
		return self::pluralize(self::underscore($class_name));
	}
	
	public static function underscore($camel_cased_word) {
		return strtolower(str_replace('-', '_', 
			preg_replace('/([a-z\d])([A-Z])/', '$1_$2',
				preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2',
					preg_replace('/::/', '/' , $camel_cased_word)
				)
			)
		));
	}
	
	public function transliterate($string) {
		return str_replace(array_keys(self::$approximations), array_values(self::$approximations), $string);
	}
	
	public function parameterize($string, $separator = '-') {
		# turn unwanted characters into $separator
		$parameterized_string = preg_replace('/[^a-z0-9\-_]+/i', $separator, self::transliterate($string));
		if ($separator != null && $separator != '') {
			$re_separator = preg_quote($separator);
			# no more than one of $seaparator in a row
			$parameterized_string = preg_replace("/$re_separator{2,}/", $separator, $parameterized_string);
			# remove leading/trailing $separator
			$parameterized_string = preg_replace("/^$re_separator|$re_separator$/i", '', $parameterized_string);
		}
		
		return strtolower($parameterized_string);
	}
}

?>
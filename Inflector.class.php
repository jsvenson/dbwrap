<?php

/**
* Inflector
* http://snippets.dzone.com/posts/show/3205
* based on Ruby on Rails Inflector class
*/
class Inflector {
	private static $plural = array(
		array('/(quiz)$/i',                '$1zes'  ),
		array('/^(ox)$/i',                 '$1en'   ),
		array('/([m|l])ouse$/i',           '$1ice'  ),
		array('/(matr|vert|ind)id|ex$/i',  '$1ices' ),
		array('/(x|ch|ss|sh)$/i',          '$1es'   ),
		array('/([^aeiouy]|qu)y$/i',       '$1ies'  ),
		array('/(hive)$/i',                 '$1s'    ),
		array('/(?:([^f])fe|([lr])f)$/i',  '$1$2ves'),
		array('/sis$/i',                   'ses'    ),
		array('/([ti])um$/i',              '$1a'    ),
		array('/(buffal|tomat)o$/i',        '$1oes'  ),
		array('/(bu)s$/i',                 '$1ses'  ),
		array('/(alias|status)$/i',        '$1es'   ),
		array('/(octop|vir)us$/i',         '$1uses' ),
		array('/(ax|test)is$/i',           '$1es'   ),
		array('/s$/i',                     's'      ),
		array('/$/',                       's'      )
	);
	
	private static $singular = array(
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
		array('move',   'moves'),
		array('sex',    'sexes'),
		array('child',  'children'),
		array('man',    'men'),
		array('person', 'people')
	);
	
	private static $uncountable = array(
		'sheep', 'fish', 'series', 'species', 'money', 'rice', 'information', 'equipment'
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
					preg_replace('/::/', '/' ,$camel_cased_word)
				)
			)
		));
	}
}

?>
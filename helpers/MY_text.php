<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package    core
 * @author     Josh Turmel
 * @copyright  (c) 2009 LifeChurch.tv
 */

class text extends text_Core {
	private static $stop_words = array("a", "about", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "amoungst", "amount", "an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway", "anywhere", "are", "around", "as", "at", "back", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom", "but", "by", "call", "can", "cannot", "cant", "co", "computer", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven", "else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "i", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own", "part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereof", "thereupon", "these", "they", "thick", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "unto", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves");
	private static $stop_words_google = array("I" ,"a" ,"about" ,"an" ,"are" ,"as" ,"at" ,"be" ,"by" ,"com" ,"de" ,"en" ,"for" ,"from" ,"how" ,"in" ,"is" ,"it" ,"la" ,"of" ,"on" ,"or" ,"that" ,"the" ,"this" ,"to" ,"was" ,"what" ,"when" ,"where" ,"who" ,"will" ,"with" ,"und" ,"the" ,"www");

	public static function keywords($str, $limit_chars = 255)
	{
		$clean_str = preg_replace('/[^\p{L}\p{M}\p{Z}\p{S}\p{N}\p{C}\s]{1,}/', '', $str);

		$clean_str = trim(preg_replace('/\s{2,}/', ' ', $clean_str));

		$words = explode(' ', $clean_str);

		foreach ($words as $key => $word)
		{
			if (in_array($word, self::$stop_words) === TRUE || in_array(mb_strtolower($word), self::$stop_words))
			{
				unset($words[$key]);
			}
		}

		// Limit to 255 characters total and preserve words
		$clean_str = text::limit_chars(implode(', ', $words), $limit_chars, '', TRUE);

		// Get rid of any trailing comma that may have been left behind from text::limit_chars
		$clean_str = preg_replace('/,$/', '', $clean_str);

		// Remove the extra spaces introduced on explode above and return the cleaned string
		return str_replace(' ', '', $clean_str);
	}
	
	public static function slugify($string)
	{
		$string = strtolower($string);
		$string = preg_replace('/[^ a-zA-Z0-9]/', ' ', $string);
		$string = str_replace(' ', '-', $string);
		$string = preg_replace('/-+/', '-', $string);
		
		return $string;
	}
}
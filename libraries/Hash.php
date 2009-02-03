<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class      Hash
 * @author     Joshua Turmel
 * @copyright  (c) 2008 LifeChurch.tv
 */
class Hash {

	public function __construct()
	{
		$this->key = Kohana::config('hash.key');
	}
	
	/**
	 * Returns a 256-bit string (hash) containing the calculated data as lowercase hexits.
	 *
	 * @param   string  data to be encrypted
	 * @return  string  encrypted data
	 */
	public function sha256($data)
	{
		return hash_hmac('sha256', $data, $this->key);
	}	
}
<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class twitter
 * @abstract Provides update Twitter status capability
 * @author Josh Turmel
 */
 
class twitter_Core {

	/**
	 * @method status_update
	 * @abstract Post status update to Twitter
	 * @author Josh Turmel
	 * @
	 * @return array
	 */
	public static function status_update($config)
	{
		$username = isset($config['username']) ? $config['username'] : '';
		$password = isset($config['password']) ? $config['password'] : '';
		$status   = isset($config['status'])   ? $config['status']   : '';
	
		$v = new Validation(array('username' => $username, 'password' => $password, 'status' => $status));
		
		// TODO: Add in the real requirements by Twitter, for now just required
		$v->add_rules('username', 'required');
		$v->add_rules('password', 'required');
		$v->add_rules('status', 'required');
		
		if ($v->validate() === false)
		{
		    return array('success' => false, 'errors' => $v->errors('twitter'));
		}
	
		$appended_message = Kohana::config('twitter.appended_message');
	
		$max_length = self::status_length();
	
		if (strlen($status) > $max_length)
		{
			$status = substr($status, 0, ($max_length - 2)) . '...';
		}
		
		// Now add appended_message
		$status = $status . $appended_message;
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, 'http://twitter.com/statuses/update.json');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'status='.$status);
		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		
		$output = curl_exec($ch);
		
		curl_close($ch);

		// Check for success or failure
		if (empty($output))
		{
			$v = new Validation(array('twitter_response' => ''));
			$v->add_error('twitter_response', 'connection_failed');
			
			return array('success' => false, 'errors'  => $v->errors('twitter'));
		}
		else
		{
			$output = json_decode($output);
			
			if (isset($output->error) && $output->error == 'Could not authenticate you.')
			{
				$v = new Validation(array('twitter_response' => ''));
				$v->add_error('twitter_response', 'authentication_failed');
				
				return array('success' => false, 'errors' => $v->errors('twitter'));
			}
			else
			{
				$output->success = true;
				return $output;
			}
		}
	}
	
	public static function status_length()
	{
		$appended_message = Kohana::config('twitter.appended_message');
		
		return (140 - strlen($appended_message));
	}
}
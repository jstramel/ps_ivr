<?php

/*
Filename: IfByPhone_Util.php
Created By: Joseph Stramel
Date: 11/16/2011
Purpose:
	Custom class for IfByPhone tasks
*/


class IfByPhone_Util {

	// Constant for API key assigned to Perfect Sweep from IfByPhone
	private static $API_KEY = API_KEY;
	// Constant for Primary IVR Phone Number
	private static $IVR_PHONE_NUM = IVR_PHONE_NUM;

	// Class constructor
	private function __construct() {}

	public static function processPassThrough($value) {

		// This function will parse the pass through data and populate and array with keys and values.
		// Passthrough format is key|value||key|value, etc.

		$passthrough_array = array();
		$cursor_pos = 0;
		$single_bar_pos = 0;
		$double_bar_pos = 0;

		// Find out how many key|value sets there are
		$single_bar_count = substr_count($value, '|');

		// loop passthrough data and add to array

		while ($cursor_pos < strlen($value)){

			// position of the next single bar (end of key)
			$single_bar_pos = strpos($value, '|', $cursor_pos);
			// position of the next double bar (end of value)
			$double_bar_pos = strpos($value, '||', $cursor_pos);
			// at the end of the string the double bar will be absent and return false.  Then set the $double_bar_pos to the entire length of $value
			if ($double_bar_pos == false) {
				$double_bar_pos = strlen($value);
			}

			// set the key
			$array_key = substr($value, $cursor_pos, $single_bar_pos - $cursor_pos);
			// set the value
			$array_value = substr($value, $single_bar_pos + 1, $double_bar_pos - $single_bar_pos - 1);
			// move the cursor to the next key||value
			$cursor_pos = $double_bar_pos + 2;
			// add the key||value to the passthrough array
			$passthrough_array[$array_key] = $array_value;

		}
		// return the final passthrough array
		return $passthrough_array;
	}

	public static function processConfirmationCode($value) {

		// This is used to break apart the confirmation code into individual numbers so they are read back
		// one at a time.  EG, "4 1 1 5" instead of "Four thousand, one hundred, fifteen"

		define('PAUSE', ', ');
		$cursor_pos = 0;
		$return_value = '';
		$conf_code = '';

		// Insert a "PAUSE" (a comma and space which the system will read back slowly) between numbers
		while ($cursor_pos < strlen($value)){
			$conf_code .= substr($value, $cursor_pos, 1) . PAUSE;
			$cursor_pos++;
		}

		// Repeat the code
		$return_value = $conf_code . ". Repeating, your confirmation number is " . $conf_code;

		return $return_value;


	}

	public static function removeInvalidChar($value) {

		// This is used to remove apostrophes and ampersands from the data being passed back

		$cursor_pos = 0;
		$return_value = '';

		while ($cursor_pos < strlen($value)){
			$character = substr($value, $cursor_pos, 1);
			if ($character != "'" && $character != '&') {
				$return_value .= substr($value, $cursor_pos, 1);
				$cursor_pos++;
			}else{
				$cursor_pos++;
			}
		}

		return $return_value;

	}

	public static function createSMS($to,$msg) {

		// This function makes the call to IfByPhone

		$url = 'https://secure.ifbyphone.com/ibp_api.php?';
		$action = 'sms.send_message';

		// See documentation in function below
		$msg = self::convertCharactersToHTML($msg);

		// Create the URL string to execute
		$sms = $url . 'api_key=' . self::$API_KEY . '&action=' . $action . '&to=' . $to . '&from=' . self::$IVR_PHONE_NUM . '&message=' . $msg;

		// Variable holds the resulting XML from the call.  file_get_contents() executes the call.
		$result = file_get_contents($sms);

		return $result;

	}

	public static function smsIvrEvent($db,$serviceCategory,$job_stage,$jobID,$timestamp,$employee_name,$job_name){
		// check sms campaigns to see if sms message is required to be sent
		// 		IF there are messages to send:
		//			create text of the message using passed data
		// 			LOOP through the array of phone numbers
		//				Call sendSMS with the $to and $msg parameters
		$query = "call proc_sms_alert('".$serviceCategory."',".$jobID.")";
		$results = $db->query($query);
		if ($results->rowCount() > 0){
			$sms_datetime = strtotime($timestamp);
			$sms_date = date('n/j/Y', $sms_datetime);
			$sms_time = date('g:i a', $sms_datetime);
			$sms_msg = $employee_name . " " . $job_stage . " ". $serviceCategory . " job: " . $job_name . " on " . $sms_date . " at " . $sms_time;
			foreach ($results as $result){
				self::createSMS($result['phone'],$sms_msg);
				sleep(3);
			}

		}
	}


	private static function convertCharactersToHTML($string) {
		// Convert traditional characters to HTML encoded characters

		$cursor_pos = 0;
		$return_value = '';

		while ($cursor_pos < strlen($string)){
			$character = substr($string, $cursor_pos, 1);
			switch ($character) {
				case ' ':
					$return_value .= '%20';		// space
					$cursor_pos++;
					break;
				case '#':
					$return_value .= '%23';		// #  pound sign
					$cursor_pos++;
					break;
				case '(':
					$return_value .= '%28';		// (  left brace
					$cursor_pos++;
					break;
				case ')':
					$return_value .= '%29';		// )  right brace
					$cursor_pos++;
					break;
				case '&':
					$return_value .= '%26';		// &  ampersand
					$cursor_pos++;
					break;
				case "'":
					$return_value .= '%27';		// '  single quote
					$cursor_pos++;
					break;
				default:
					$return_value .= $character;
					$cursor_pos++;
			}
		}

		return $return_value;
	}

}

?>

<?php

require_once ('../com/Database.php');

$to = substr($_GET['to_number'], -10);
$from = substr($_GET['from_number'], -10);
$message = strtolower($_GET['message']);
$result = '';

$db = Database::getDB();

// Evaluate for Family Dollar vendor request
if (strpos($message, 'fd') !== false){
    $store = substr($message, 2);
	$message = 'Family_Dollar';
}

switch ($message) {
	case ('stop'):
		$query = "CALL toggleAllowSMS(0," . $from . ")";
		$result = 'Text messages have been turned OFF';
		break;
	case ('start'):
		$query = "CALL toggleAllowSMS(1," . $from . ")";
		$result = 'Text messages have been turned ON';
		break;
	case ('status'):
		$query = "SELECT allowSMS FROM employee where phone = '" . $from . "'";
		break;
	case ('Family_Dollar'):
		$result = 'Store: ' . $store;
		break;
	default:
		$result = '';
		$query = $db->prepare("CALL proc_smsInbound(:to, :from, :message)");
		$query->bindParam(":to",$to,PDO::PARAM_STR);
		$query->bindParam(":from",$from,PDO::PARAM_STR);
		$query->bindParam(":message",$message,PDO::PARAM_STR);
		$query->execute();
		break;
}

try {
	$call = $db->query($query);
	if ($message == 'status'){
		foreach ($call as $a){
			switch ($a["allowSMS"]) {
				case ('0'):
					$result = 'Text messages are currently turned OFF';
					break;
				case ('1'):
					$result = 'Text messages are currently turned ON';
					break;
			}
		}
	}
	if ($result){
		IfByPhone_Util::createSMS($from, $result);
	}
}
catch ( PDOException $e ) {
	Database::logError('sms_inbound', $e);
	IfByPhone_Util::createSMS(ADMIN_PHONE_NUM, ('SMS INBOUND ERROR: ' . $from . ': ' . $message));
}

?>

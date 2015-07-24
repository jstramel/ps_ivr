<?php

require('com/Database.php');

$servicecategory = $_POST['servicecategory'];

// If the servicecategory was skipped the IVR sends 'NA' and that will generate an error in the database call.
// This is caused by a hangup without entering information.  Check for this first and exit without logging an error.

if ($servicecategory == 'NA'){
	Database::logMessage('Hangup without servicecategory with passthrough (' . $p_t . ')');
	exit();
}

$caller_id = $_POST['caller_id'];
$timestamp = $_POST['timestamp'];
$session_id = $_POST['session_id'];
$employee_id = '';
$job_id = '';
$job_pk_id = '';
$job_name = '';
$employee_name = '';

$p_t = $_POST['p_t'];  // passthrough data = employee_id|value||job_id|value

$passthrough_array = IfByPhone_Util::processPassThrough($p_t);

$employee_id = $passthrough_array['employee_id'];
$job_id = $passthrough_array['job_id'];
$job_pk_id = $passthrough_array['job_pk_id'];
$job_name = $passthrough_array['company'];
$employee_name = $passthrough_array['employee_name'];


try {
	// create connection to DB
	$conn = Database::getDB();
	$query = "CALL start_event('$servicecategory','$caller_id','$timestamp','$session_id','$employee_id','$job_id')";
	$conn->query($query);
	// update successful - play event started message
	echo "<action>
			<app>survo</app>
				<parameters>
					<id>368911</id>
				</parameters>
		  </action>";
} catch ( PDOException $e ) {
	Database::logError('start_event', $e);
	Database::sendToIvrError();
}

$sms = IfByPhone_Util::smsIvrEvent($conn,$servicecategory,'STARTED',$job_pk_id,$timestamp,$employee_name,$job_name);

?>

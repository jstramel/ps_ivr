<!--
Filename: snow.php
Created By: Joseph Stramel
Date: 11/14/2011
Purpose:
	Record the various snow tasks to the IVR Database
-->

<?php
require('../com/Database.php');

// Collect the data sent from the "Snow Removal Branch" of the IVR System

// Task variables
$plow = $_POST['plow'];
$shovel = $_POST['shovel'];
$salt = $_POST['salt'];

// Job variables
$message = $_POST['message'];
$caller_id = $_POST['caller_id'];
$timestamp = $_POST['timestamp'];
$session_id = $_POST['session_id'];
$p_t = $_POST['p_t'];

// Initialize variables
$job_name = '';
$employee_name = '';

// Parse passthrough data to an array
$p_t_Array = IfByPhone_Util::processPassThrough($p_t);

// Create variables from data needed in the passthrough array
$event_id = $p_t_Array['event_id'];
$job_name = $p_t_Array['company'];
$job_id = $p_t_Array['job_pk_id'];
$employee_name = $p_t_Array['employee_name'];

// If user hung up the tasks will be "NA". Exit script and do not update the database
if ($plow == 'NA' || $shovel == 'NA' || $salt == 'NA') {
	Database::logMessage('Hangup without service tasks with passthrough (' . $p_t . ')');
	exit();
}

// If user entered "No" (case sensitive) for all options, send them to the IVR error message:
// 4. Snow - All NO Answers Error and reprompt.
// Not all answers can be "No".
if ($plow == 'No' && $shovel == 'No' && $salt == 'No') {
	echo "<action>
			<app>survo</app>
				<parameters>
					<id>411731</id>
					<p_t>" . $p_t . "</p_t>
				</parameters>
			</action>";
	// Stop the PHP script before it hits the database;
	exit();
}


// Query statement
$query = "CALL proc_endSnowJob('$event_id', '$timestamp', '$caller_id', '$session_id', '$message',
					'$plow', '$shovel', '$salt', @confirmation)";

// Connect to the database
$conn = Database::getDB();

try {
	$conn->beginTransaction();
	$results = $conn->query($query);
	// Return value is the confirmation code
	$results = $conn->query("SELECT @confirmation");
	$conn->commit();
	foreach ($results as $result){
		// Process confirmation code to create a digit by digit message.
		// (e.g., 1 2 3 instead of 123)
		$conf = IfByPhone_Util::processConfirmationCode($result["@confirmation"]);
	}
	// Select the confirmation code survo on the IVR system
	echo "<action>
			<app>survo</app>
				<parameters>
					<id>362811</id>
					<user_parameters>
						<conf>" . $conf . "</conf>
					</user_parameters>
				</parameters>
			</action>";
} catch ( PDOException $e ) {
	// Error handling
	$conn->rollBack();
	Database::logError('snow' ,$e);
	Database::sendToIvrError();
}

// Send SMS message notifying job completion
$sms = IfByPhone_Util::smsIvrEvent($conn,'snow removal','FINISHED',$job_id,$timestamp,$employee_name,$job_name);

?>

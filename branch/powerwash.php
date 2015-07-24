<!--
Filename: powerwash.php
Created By: Joseph Stramel
Date: 11/16/2011
Purpose:
	Record the various power washing tasks to the IVR Database
-->


<?php

require('../com/Database.php');

// Collect the data sent from the "Power Wash Branch" of the IVR System

// Job variables
$message = $_POST['message'];
$caller_id = $_POST['caller_id'];
$timestamp = $_POST['timestamp'];
$session_id = $_POST['session_id'];
$p_t = $_POST['p_t'];

// Initialize variables
$job_name = '';
$employee_name = '';

// Parse passthrough data (p_t) to an array
$p_t_Array = IfByPhone_Util::processPassThrough($p_t);

// Create variables from data needed in the passthrough array
$event_id = $p_t_Array['event_id'];
$job_name = $p_t_Array['company'];
$employee_name = $p_t_Array['employee_name'];
$job_id = $p_t_Array['job_pk_id'];

// Query statement
$query = "CALL proc_endPowerWashJob('$event_id', '$timestamp', '$caller_id', '$session_id', '$message', @confirmation)";

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
	Database::logError('powerwash', $e);
	Database::sendToIvrError();
}

// Send SMS message notifying job completion
$sms = IfByPhone_Util::smsIvrEvent($conn,'power washing','FINISHED',$job_id,$timestamp,$employee_name,$job_name);

?>

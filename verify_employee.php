<?php

require ('com/Database.php');

$employee_id = $_POST['id'];
$passcode = $_POST['passcode'];


// If the employee_id or passcode were skipped the IVR sends 'NA' and that will generate an error in the database call.
// This is caused by a hangup without entering information.  Check for this first and exit without logging an error.

if ($employee_id == 'NA' || $passcode == 'NA'){
	Database::logMessage('Hangup without employee info');
	exit();
}


try {
	$conn = Database::getDB();
	$results = $conn->query("CALL verify_employee('$employee_id', '$passcode', @isValid, @employee_pkID,
		@remotePunch, @ivrAccess, @isActive, @employee_name)");
	$results = $conn->query("SELECT @isValid, @employee_pkID, @ivrAccess, @isActive, @employee_name");
	foreach ( $results as $result ) {
		if (!$result["@isValid"]) {

		// for invalid combination

			echo "<action>
					<app>survo</app>
						<parameters>
							<id>362191</id>
						</parameters>
					</action>";
		}else if (!$result["@ivrAccess"] || !$result["@isActive"]){

		// for IVR access not authorized

			echo "<action>
					<app>survo</app>
						<parameters>
							<id>397881</id>
						</parameters>
					</action>";

		}else{

		$employee_id = $result["@employee_pkID"];
		$employee_name = $result["@employee_name"];

		// for valid combination, check to see if there is an open job

		$open_event_check = $conn->query("CALL proc_checkForOpenEvent('$employee_id', @event_pk_id, @serviceCategory,
			@name, @survo, @job_pk_id)");
		$open_event_check = $conn->query("SELECT @event_pk_id, @serviceCategory, @name, @survo, @job_pk_id");

		// Create instance of Utility to clean data

		foreach ( $open_event_check as $checks ) {
			if ($checks["@event_pk_id"] != "0"){

				// this branch reflects a valid employee, with an open job which needs to be closed

				// Remove invalid characters from the @name field
				$name_clean = IfByPhone_Util::removeInvalidChar($checks["@name"]);

				echo "<action>
						<app>survo</app>
							<parameters>
								<id>" . $checks["@survo"] ."</id>
								<user_parameters>
									<name>" . $name_clean . "</name>
								</user_parameters>
								<p_t>employee_id|" . $employee_id . "||event_id|" . $checks["@event_pk_id"] . "||serviceCategory|" .
									$checks["@serviceCategory"] . "||company|" . $name_clean . "||employee_name|" . $employee_name .
									"||job_pk_id|" . $checks["@job_pk_id"] . "</p_t>
							</parameters>
						</action>";
			} else {

				// this branch reflects a valid employee, with no jobs open

				echo "<action>
							<app>survo</app>
								<parameters>
									<id>359731</id>
									<p_t>employee_id|" . $employee_id . "||employee_name|" . $employee_name . "</p_t>
								</parameters>
							</action>";
				}
			}
		}
    }
} catch ( PDOException $e ) {
	Database::logError('verify_employee', $e);
	Database::sendToIvrError();
}

$conn = null;

?>

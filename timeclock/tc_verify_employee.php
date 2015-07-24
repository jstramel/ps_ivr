<?php
$employee_id = $_POST['id'];
$passcode = $_POST['passcode'];
$timestamp = $_POST['timestamp'];


require ('../com/Database.php');

try {
	$conn = Database::getDB();
	$results = $conn->query("CALL verify_employee('$employee_id', '$passcode', @isValid, @employee_pkID,
													@remotePunch, @ivrAccess, @isActive, @employee_name, @employee_phone)");
	$results = $conn->query("SELECT @isValid, @employee_pkID, @remotePunch, @isActive, @employee_phone");

	foreach ( $results as $result ) {
		if (!$result["@isValid"]) {

		// for invalid combination

			echo "<action>
					<app>survo</app>
						<parameters>
							<id>394171</id>
						</parameters>
					</action>";
		}else{

		$employee_id = $result["@employee_pkID"];
		$remote = $result["@remotePunch"];
		$active = $result["@isActive"];
		$phone = $result["@employee_phone"];

		// if the employee does not have access to this feature  SURVO ID: 394181

		if (!$remote || !$active) {
			echo "<action>
					<app>survo</app>
						<parameters>
							<id>394181</id>
						</parameters>
					</action>";
		}else{



		// if the employee is valid, and has access, record the timeclock punch, send to survo confirming punch
		$query_timeclock = "INSERT into timeclock (employee_pk_id, punchDateTime) VALUES ('" . $employee_id . "','" .
												$timestamp . "')";
		$conn->query($query_timeclock);


		echo "<action>
				<app>survo</app>
					<parameters>
						<id>394191</id>
						<p_t>employee_id|" . $employee_id . "</p_t>
					</parameters>
				</action>";

		if ($employee_id == '2' || $employee_id == '355'){
			$time = strToTime($timestamp);
			$msg = "Time recorded: " . date('n-j-Y g:i:s A' , $time);
			IfByPhone_Util::createSMS($phone,$msg);
			}
		}
	}
}

} catch ( PDOException $e ) {
	Database::logError('tc_verify_employee', $e);
	Database::sendToIvrError();
}

$conn = null;

?>

<?php
require ('com/Database.php');
//require ('com/IfByPhone_Util.php');

$job_id = $_POST['job_id'];
$p_t = $_POST['p_t'];


// If the job_id was skipped the IVR sends 'NA' and that will generate an error in the database call.
// This is caused by a hangup without entering information.  Check for this first and exit without logging an error.

if ($job_id == 'NA'){
	Database::logMessage('Hangup without job number with passthrough (' . $p_t . ')');
	exit();
}


try {
	$conn = Database::getDB();
	$results = $conn->query("CALL verify_job('$job_id',@job_name,@job_pk_id,@isValid)");
	$results = $conn->query("SELECT @job_name,@job_pk_id,@isValid");
	foreach ( $results as $result ) {
		if (!$result["@isValid"]) {
		// for invalid combination
		// 1/29/13 UPDATE: must pass through data because employee information has already been verified
			echo "<action>
					<app>survo</app>
						<parameters>
							<id>362471</id>
							<p_t>" . $p_t . " </p_t>
						</parameters>
					</action>";
		}else{

		// for valid combination

		$name_clean = IfByPhone_Util::removeInvalidChar($result["@job_name"]);
		$job_pk_id = $result["@job_pk_id"];
			echo "<action>
					<app>survo</app>
						<parameters>
							<id>362481</id>
							<user_parameters>
								<job_name>" . $name_clean . "</job_name>
							</user_parameters>
							<p_t>" . $p_t . "||job_id|" . $job_id . "||company|" . $name_clean . "||job_pk_id|". $job_pk_id . " </p_t>
						</parameters>
					</action>";
		}
    }
} catch ( PDOException $e ) {
	Database::logError('verify_job', $e);
	Database::sendToIvrError();
}

$conn = null;

?>

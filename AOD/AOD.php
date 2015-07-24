<!--
Filename: AOD.php
Created By: Joseph Stramel
Date: 11/9/2011
Purpose:
	This application creates a CSV file which is uploaded to Attendance On Demand through their
	stand alone program. Time clock punches and transfers are in the CSV file.
-->

<?php
require_once('../com/Database.php');


// This function removes the newline and carriage returns
// SOLUTION TAKEN FROM THIS SITE: http://stackoverflow.com/questions/4080456/fputcsv-and-newline-codes


function getcsvline($list,  $seperator, $enclosure, $newline = "" ){
    $fp = fopen('php://temp', 'r+');

    fputcsv($fp, $list, $seperator, $enclosure );
    rewind($fp);

    $line = fgets($fp);
    if( $newline and $newline != "\n" ) {
      if( $line[strlen($line)-2] != "\r" and $line[strlen($line)-1] == "\n") {
        $line = substr_replace($line,"",-1) . $newline;
      } else {
        die( 'original csv line is already \r\n style' );
      }
    }

        return $line;
}


try{
	$conn =  Database::getDB();

	// The flag "1" tells the SQL server to update the global date time with the last time
  // the process was run to eliminate duplicates
	$query = "call AOD_process_transfers('1')";
	$results = $conn->query($query);

	$header = array();
	$fp = fopen('aod_csv.csv', 'w');

	while ($row = $results->fetch(PDO::FETCH_ASSOC))
	{
		if(empty($header)){ // do it only once!
		  $header = array_keys($row); // get the column names
		  $line = getcsvline( $header, ",", "\"", "\r\n" );
			fwrite( $fp, $line);  // write header

		  $line = getcsvline( $row, ",", "\"", "\r\n" );
			fwrite( $fp, $line);  // write first record
		}
	// write additional records
	while($row = $results->fetch(PDO::FETCH_ASSOC)) {
			$line = getcsvline( $row, ",", "\"", "\r\n" );
			fwrite( $fp, $line);
        }

	fclose($fp);

}

} catch ( PDOException $e ) {
	print $e;
}

?>

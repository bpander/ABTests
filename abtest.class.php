<?php

include_once $_SERVER['DOCUMENT_ROOT'].'/bootstrap.php';

class ABTest
{
	
	private $db_server	 = 'localhost';
	private $db_username = 'root';
	private $db_password = '';
	private $db_database = '';
	
	// Sets the testName
	function __construct($testName = NULL){
		if($testName != NULL) $this->testName = $testName;
	}
	
	/*
	 * addOption($value, $weight): Adds an option into an ABTest
	 *
	 * ARGS
	 * @value		str		The name of the option
	 * @weight(opt)	float	How often the option should appear compared to the other options, defaults to 1
	 *
	 */
	function addOption($value, $weight = 1){
		$this->options->$value = $weight;
	}



	/*
	 * runTest(): Takes the ABTest parameters, outputs an option at random, and records an impression in the db
	 *
	 * REQUIRES
	 * @options			array	The set of options to test against each other defined by addOption()
	 * @testName		str		Used to organize impressions and conversions for each option
	 * 
	 * GENERATES
	 * testKey			str		A key generated from the strings of the option and test name
	 * success			bool	Whether or not the query was successful
	 *
	 * RETUNS
	 * selectedOption	str		Randomly selected option based on respective weights
	 *
	 */
	function runTest(){
		
		if($this->testName == ''){
			return $this->error = 'testName not defined';
		}
		
		// This section selects an option at random given the weights provided
		// 1. Get the sum of the options' weights
		foreach($this->options as $val){
			$sum += $val;
		}
		// 2. Get a float between 0 and the sum of the options' weights
		$random = mt_rand(0, $sum * 100) / 100;
		// 3. Iterate through the weights concatenating them until the current value is greater than the random number
		foreach($this->options as $key => $val){
			$currentValue += $val;
			if($currentValue >= $random){
				$selectedOption = $this->selectedOption = $key;
				break;
			}
		}
		
		$this->testKey = md5($this->testName.'|'.$this->selectedOption);
		
		// We picked an option so add an impression if the option exists in the db or create a new row for it
		$mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
		if($mysqli->connect_errno) return $this->error = "Connect failed: ".$mysqli->connect_error;
		
		$sql = 'INSERT INTO ab_tests ( test_key, test_name, `option`, weight, impressions ) VALUES ( "'
						.$this->testKey.'", "'.$this->testName.'", "'.$this->selectedOption.'", "'.$this->options->$selectedOption.'", 1 )'
						.' ON DUPLICATE KEY UPDATE impressions = impressions + 1, weight = "'.$this->options->$selectedOption.'"';
		$this->success = ($_SERVER['REMOTE_ADDR'] != '184.75.15.195') ? $mysqli->query($sql) : 0;
		$mysqli->close();
		return $this->selectedOption;
	}
	
	

	/*
	 * runAll(): Marks an impression in the DB for all of the options set with addOption(). Good if you're testing all options simultaneously
	 *
	 * REQUIRES
	 * @options			array	The set of options to test against each other defined by addOption()
	 * @testName		str		Used to organize impressions and conversions for each option
	 * 
	 * RETUNS
	 * success			str		Returns TRUE if all of the SQL queries were executed successfully
	 *
	 */
	function runAll($isTesting = false){
		$mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
		if($mysqli->connect_errno) return $this->error = "Connect failed: ".$mysqli->connect_error;
		foreach($this->options as $key => $val){
			$testKey = md5($this->testName.'|'.$key);
			$sql = 'INSERT INTO ab_tests ( test_key, test_name, `option`, weight, impressions ) VALUES ';
			$sql .= '( "'.$testKey.'", "'.$this->testName.'", "'.$key.'", "'.$val.'", "1" ) ';
			$sql .= 'ON DUPLICATE KEY UPDATE impressions = impressions + 1, weight = "'.$val.'"';
			if($_SERVER['REMOTE_ADDR'] != '184.75.15.195' || $isTesting) $success = ($mysqli->query($sql)) ? 1 : 0;
			$total_successful += $success;
		}
		$mysqli->close();
		return $this->success = ($total_successful < count($this->options) - 1) ? 1 : 0;
	}



	/*
	 * markConversion(): increments the number of conversions for a given option by 1
	 *
	 * REQUIRES
	 * @testKey		str		the test key
	 *
	 * RETURNS
	 * success		bool	Whether or not the query was successful
	 *
	 */
	function markConversion($testKey){
		// Update the SQL db
		$mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
		if($mysqli->connect_errno) return $this->error = "Connect failed: ".$mysqli->connect_error;
		
		$sql = 'UPDATE ab_tests SET conversions = conversions + 1 WHERE test_key = "'.$testKey.'"';
		$success = $this->success = ($_SERVER['REMOTE_ADDR'] != '184.75.15.195') ? $mysqli->query($sql) : 0;
		$mysqli->close();
		return $success;
	}
	
	
	
	/*
	 * getTestResults($testName): input a test name and return the results
	 * 
	 * REQUIRES
	 * @testName	str		The name of the test you want to know about
	 *
	 * RETURNS
	 * results		obj		Object containing all database fields for all options in the given test
	 *
	 */
	function getTestResults($testName){
		$mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
		if($mysqli->connect_errno) return $this->error = "Connect failed: ".$mysqli->connect_error;
		
		$sql = "SELECT * FROM ab_tests WHERE test_name = '$testName'";
		$res = $mysqli->query($sql);
		$i = 0;
		while($row = $res->fetch_assoc()){
			foreach($row as $key => $val){
				$this->results[$i]->$key = $val;
			}
			$i++;
		}
		return $this->results;
	}
	
}

?>
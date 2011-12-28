<?php

/* Make sure you run config.php in your browser first */

class ABTest
{

	public $db_server;
	public $db_username;
	public $db_password;
	public $db_database;
	public $db_table;
	
	// Sets the test_name
	function __construct($test_name = NULL){
		if($test_name != NULL) $this->test_name = $test_name;
	}
	
	/*
	 * addOption($value, $weight): Adds an option into the ABTest
	 *
	 * ARGS
	 * @value			str		The name of the option
	 * @weight (opt)	float	How often the option should appear compared to the other options, defaults to 1
	 *
	 */
	function addOption($value, $weight = 1){
		$this->options->$value = $weight;
	}



	/*
	 * selectOption(): Takes the ABTest parameters, outputs an option at random, and records an impression in the db
	 *
	 * REQUIRES
	 * @options			array	The set of options to test against each other defined by addOption()
	 * @test_name		str		Used to organize impressions and conversions for each option
	 * 
	 * GENERATES
	 * option_key		str		A key generated from the strings of the option and test name
	 * success			bool	Whether or not the query was successful
	 *
	 * RETUNS
	 * selected_option	str		Randomly selected option based on respective weights
	 *
	 */
	function selectOption(){
		
		if($this->test_name == NULL){
			$this->errors[] = 'test_name not defined';
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
				$selected_option = $this->selected_option = $key;
				break;
			}
		}
		
		$this->option_key = md5($this->test_name.'|'.$this->selected_option);
		
		// We picked an option so add an impression if the option exists in the db or create a new row for it
		$mysqli = $this->db_connect();
		
		$sql = 'INSERT INTO '.$this->db_table.' ( test_key, test_name, `option`, weight, impressions ) VALUES ( "'
						.$this->option_key.'", "'.$this->test_name.'", "'.$this->selected_option.'", "'.$this->options->$selected_option.'", 1 )'
						.' ON DUPLICATE KEY UPDATE impressions = impressions + 1, weight = "'.$this->options->$selected_option.'"';
		$this->success = $mysqli->query($sql);
		$mysqli->close();
		return $this->selected_option;
	}
	
	

	/*
	 * runAll(): Marks an impression in the DB for all of the options set with addOption(). Good if you're testing all options simultaneously
	 *
	 * REQUIRES
	 * @options			array	The set of options to test against each other defined by addOption()
	 * @test_name		str		Used to organize impressions and conversions for each option
	 * 
	 * RETUNS
	 * success			str		Returns TRUE if all of the SQL queries were executed successfully
	 *
	 */
	function selectAll(){
		$mysqli = $this->db_connect();
		foreach($this->options as $key => $val){
			$option_key = md5($this->test_name.'|'.$key);
			$sql = 'INSERT INTO '.$this->db_table.' ( test_key, test_name, `option`, weight, impressions ) VALUES ';
			$sql .= '( "'.$option_key.'", "'.$this->test_name.'", "'.$key.'", "'.$val.'", "1" ) ';
			$sql .= 'ON DUPLICATE KEY UPDATE impressions = impressions + 1, weight = "'.$val.'"';
			$success = ($mysqli->query($sql)) ? 1 : 0;
			$total_successful += $success;
		}
		$mysqli->close();
		return $this->success = ($total_successful < count($this->options) - 1) ? 1 : 0;
	}



	/*
	 * markConversion(): increments the number of conversions for a given option by 1
	 *
	 * REQUIRES
	 * @option_key		str		the test key
	 *
	 * RETURNS
	 * success			bool	Whether or not the query was successful
	 *
	 */
	function markConversion($option_key){
		// Update the SQL db
		$mysqli = $this->db_connect();		
		$sql = 'UPDATE '.$this->db_table.' SET conversions = conversions + 1 WHERE test_key = "'.$option_key.'"';
		$this->success = $mysqli->query($sql);
		$mysqli->close();
		return $this->success;
	}
	
	
	
	/*
	 * getTestResults($test_name): input a test name and return the results
	 * 
	 * REQUIRES
	 * @test_name	str		The name of the test you want to know about
	 *
	 * RETURNS
	 * results		arr		Object containing all database fields for all options in the given test
	 *
	 */
	function getTestResults(){
		$mysqli = $this->db_connect();
		$sql = "SELECT * FROM $this->db_table WHERE test_name = '$this->test_name'";
		$res = $mysqli->query($sql);
		if($res){
			$i = 0;
			while($row = $res->fetch_assoc()){
				foreach($row as $key => $val){
					$results[$i][$key] = $val;
				}
				$i++;
			}
		}
		return $results;
	}
	
	
	/*
	 * Connects to the MySQL database and returns a MySQLi object
	 */
	function db_connect(){
		$mysqli = new mysqli($this->db_server, $this->db_username, $this->db_password, $this->db_database);
		if($mysqli->connect_errno) $this->errors[] = "Connect failed: ".$mysqli->connect_error;
		return $mysqli;
	}
	
}

?>
<?php

if(!empty($_POST)){
	
	/* 1. Try to connect tand create a MySQL table with the credentials posted */
	$db_server = $_POST['db_server'];
	$db_username = $_POST['db_username'];
	$db_password = $_POST['db_password'];
	$db_database = $_POST['db_database'];
	$mysqli = new MySQLi($db_server, $db_username, $db_password, $db_database);
	if($mysqli->connect_errno) $errors[] = "Connect failed: ".$mysqli->connect_error;
	
	if(count($errors) == 0){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$_POST['db_table'].'` (
		  `id` int(11) NOT NULL auto_increment,
		  `test_key` varchar(100) NOT NULL,
		  `test_name` varchar(100) NOT NULL,
		  `option` varchar(500) NOT NULL,
		  `weight` float(10,6) NOT NULL,
		  `impressions` int(11) NOT NULL,
		  `conversions` int(11) NOT NULL,
		  PRIMARY KEY  (`test_key`),
		  UNIQUE KEY `id` (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33;';
		$res = $mysqli->query($sql);
		if(!$res) $errors[] = 'Could not create ABTests database table';
	}
	
	/* 2. If we were able to connect, update the abtest.class.php file with the database credentials */
	if($res){
		$vars_to_define = array('db_server', 'db_username', 'db_password', 'db_database', 'db_table');
		$fp = fopen('abtest.class.php', 'r');
		if($fp){
			while(($buffer = fgets($fp, 4096)) !== false){
				$newline = $buffer;
				foreach($vars_to_define as $var){
					if(strstr($buffer, 'public $'.$var)){
						$newline = "\tpublic \$$var = '".$_POST[$var]."';\n";
						break;
					}
				}
				$contents .= $newline;
			}
			fclose($fp);
		}else{
			$errors[] = 'Could not read to abtest.class.php.';
		}
		$fp = fopen('abtest.class.php', 'w');
		if($fp){
			fwrite($fp, $contents);
			fclose($fp);
		}else{
			$errors[] = 'Could not write to abtest.class.php. Check file permissions and make sure config.php and abtest.class.php are in the same folder until configured.';
		}
	}
	
}

/* Check current configuration */
include 'abtest.class.php';
$abtest = new ABTest('test test');
if(!empty($abtest->db_table)){
	$mysqli = $abtest->db_connect();
	$is_configured = $mysqli->query('DESCRIBE '.$abtest->db_table);
	$mysqli->close();
}

?>
<html>
<head>
<style type="text/css">
*{margin:0; padding:0}
body, table, input{font:normal 12px "Lucida Sans Unicode", "Lucida Grande", sans-serif}
table label{display:block; margin-right:10px}
.wrap{margin:auto; width:600px}
.wrap > div{margin:20px 0}
ul{margin:10px 0 10px 20px}
</style>
</head>
<body>

<div class="wrap">

<?php
if(count($errors) > 0){
	echo '<div>The following errors occured:<br />'."\n";
	echo '<ul>';
	foreach($errors as $error){
		echo '<li>'.$error.'</li>';
	}
	echo '</ul></div>';
}
?>
	
	<div>
		<h3>Current Configuration</h3>
		<p style="margin:10px 0"><?php echo ($is_configured) ? 'ABTests is configured! <a href="example.php">Click here</a> to view examples.' : 'ABTests is not configured yet...';?></p>
		<table>
			<tr>
				<td><label>db_server:</label></td>
				<td><?php echo $abtest->db_server;?></td>
			</tr>
			<tr>
				<td><label>db_username:</label></td>
				<td><?php echo $abtest->db_username;?></td>
			</tr>
			<tr>
				<td><label>db_password:</label></td>
				<td><?php echo $abtest->db_password;?></td>
			</tr>
			<tr>
				<td><label>db_database:</label></td>
				<td><?php echo $abtest->db_database;?></td>
			</tr>
			<tr>
				<td><label>db_table:</label></td>
				<td><?php echo $abtest->db_table;?></td>
			</tr>
		</table>
	</div>
	
	<div>
		<h3>Configure</h3>
		<form action="" method="post">
		
			<table>
				<tr>
					<td><label>Database Server</label></td>
					<td><input type="text" name="db_server" value="<?php echo (!empty($_POST['db_server'])) ? $_POST['db_server'] : $abtest->db_server;?>" /></td>
				</tr>
				<tr>
					<td><label>Database Username</label></td>
					<td><input type="text" name="db_username" value="<?php echo (!empty($_POST['db_username'])) ? $_POST['db_username'] : $abtest->db_username;?>" /></td>
				</tr>
				<tr>
					<td><label>Database Password</label></td>
					<td><input type="password" name="db_password" value="<?php echo (!empty($_POST['db_password'])) ? $_POST['db_password'] : $abtest->db_password;?>" /></td>
				</tr>
				<tr>
					<td><label>Database Name</label></td>
					<td><input type="text" name="db_database" value="<?php echo (!empty($_POST['db_database'])) ? $_POST['db_database'] : $abtest->db_database;?>" /></td>
				</tr>
				<tr>
					<td><label>ABTest Table<br><span style="font-size:9px">To be created</span></label></td>
					<td><input type="text" name="db_table" value="<?php echo (!empty($_POST['db_table'])) ? $_POST['db_table'] : ((!empty($abtest->db_table)) ? $abtest->db_table : 'ab_tests');?>" /></td>
				</tr>
				<tr>
					<td colspan="2" align="right"><input type="submit" value="Configure" style="margin-top:10px" /></td>
				</tr>
			</table>
		</form>
	</div>
</div>

</body>
</html>
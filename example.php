<?php
include 'abtest.class.php';

/*
 1. SETTING AND PICKING OPTIONS
 
 Let's say you want to test 3 variations: a pink, red and blue background
 Let's say you also want it to be pink 50% of the time and red and blue 25% of the time each
*/
$abtest1 = new ABTest('Background Color');
$abtest1->addOption('Pink Page', .5);
$abtest1->addOption('Red Page', .25);
$abtest1->addOption('Blue Page', .25);
$selected_option = $abtest1->selectOption();

// We picked an option, how do we reflect that on the page? Any way you want! These are just two examples:

switch($selected_option){
	case 'Pink Page':
	$bgcolor = '#ff7777';
	break;
	
	case 'Red Page':
	$bgcolor = '#c22';
	break;
	
	case 'Blue Page':
	$bgcolor = '#2255ff';
	break;
}

// OR

$possible_bgcolors = array(
	'Pink Page' => '#ff7777',
	'Red Page' => '#c22',
	'Blue Page' => '#2255ff'
);
$bgcolor = $possible_bgcolors[$selected_option];


/*
 2. MARKING ALL OPTIONS AS SHOWN
 
 Maybe you have two buttons on the same page at the same time and you want to test their effectiveness against each other
*/
$abtest2 = new ABTest('Click Here Buttons');
$abtest2->addOption('Blue Button');
$abtest2->addOption('Red Button');
$abtest2->selectAll();


/*
 3. MARKING CONVERSIONS
 
 Okay, I know how to set options, how do I mark it when an option is successful?
 Remember when you called $abtest->selectOption()? That generated a unique key for the option selected
 You can access it by calling $abtest->option_key
 
 In this example, we put the option key in a hidden input in a form that posts to this page
 When the form data is posted to this page, we use that hidden input value to mark the conversion
*/
$abtest3 = new ABTest('Contact Form');
$abtest3->addOption('Short');
$abtest3->addOption('Long');
$form_length = $abtest3->selectOption();

if(!empty($_POST)){
	$variant_key = $_POST['abtest_variant'];
	$abtest3->markConversion($variant_key);
	$message_text = "<p>Conversion marked for <b>$variant_key</b></p>";
}


/*
 4. DISPLAYING ABTEST INFO

 Basically, if you want to print stats for the 'Contact Form' test,
 1. Set the test to 'Contact Form'
 2. Call getTestResults on the abtest object
 3. Do whatever you want with your new data
*/
$abtest4 = new ABTest('Contact Form');
$testresults = $abtest4->getTestResults();
$is_configured = (count($testresults) > 0);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>ABTest Example</title>
<style type="text/css">
/* EXAMPLE 1 */
body{background-color:<?php echo $bgcolor;?>;}

.button{display:inline-block; padding:10px; color:#fff; cursor:pointer}
.button.red{background-color:#f00}
.button.blue{background-color:#00f}

pre{padding:10px; background:#fff; border-radius:5px}
</style>
</head>

<body>

<?php if(!$is_configured) echo '<p>It appears you aren\'t configured properly. Please open <a href="config.php">config.php</a>.</p>';?>

<!-- EXAMPLE 2 -->
<a class="button red">Click Me!</a>
<a class="button blue">No, Click Me!</a>


<!-- EXAMPLE 3 -->
<?php echo $message_text;?>
<form action="" method="post">
	<input type="hidden" name="abtest_variant" value="<?php echo $abtest3->option_key;?>" />
	
	<div>
		<label>Name</label>
		<input type="text" name="name" />
	</div>
	<div>
		<label>Email</label>
		<input type="text" name="email" />
	</div>

<?php
if($form_length == 'Long'):
?>
	<div>
		<label>Phone</label>
		<input type="text" name="phone" />
	</div>
<?php endif;?>
	
	<div>
		<label>Comments</label>
		<textarea name="comments"></textarea>
	</div>
	
	<input type="submit" value="Send" />
</form>


<!-- EXAMPLE 4 -->
<pre>
TEST RESULTS FOR 'Contact Form'

<?php print_r($testresults);?>
</pre>

</body>
</html>
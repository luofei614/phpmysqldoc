<script>
	//location.href = location.href.replace();
</script>
<?php
define ( '_ROOT_PATH', dirname ( __FILE__ ) . '/../' );
require _ROOT_PATH.'src/phpmysqldoc.php';

$host = '';
$port = '';
$user = '';
$password = '';
$database = 'test';
// or $database = array('test','test2');

$mysqli = pmd_getMysqli($host, $port, $user,  $password);
$text = pmd_generateDoc($mysqli, $database);
writeToFile($text,'test.md');

?>

		

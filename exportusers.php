<?php
class MySQL
{
	private $database='sychidze_munchkin'; //�������� ���� �����
	private $host='localhost'; //���� ����
	private $username = 'sychidze'; //��� ����������� ����
	private $password='decideproblem'; //������ � ����
	
	var $queries = 0; //���������� �������� ����� �������
	var $arrays = 0; //���������� ��������
	var $timems = 0; //����� ���������� ��������
	
	//������������� ���������� � ������ ���������!
	function __construct()
	{
		$connection = @mysql_connect($this->host,$this->username,$this->password) or die('<link rel="stylesheet" type="text/css" href="style.css"><font color="#ff0000" size="5">���������� ����������� � �������� MySQL</font>');
		@mysql_select_db($this->database,$connection) or die('<link rel="stylesheet" type="text/css" href="style.css"><font color="#ff0000" size="5">���������� ����������� � ����� ������ �� ������� MySQL</font>');
		//$this->sql_query( "SET SESSION character_set_server=cp1251;");
		//$this->sql_query( "SET SESSION character_set_database=cp1251;");
		$this->sql_query( "SET SESSION character_set_connection=cp1251;");
		$this->sql_query( "SET SESSION character_set_results=cp1251;");
		$this->sql_query( "SET SESSION character_set_client=cp1251;");
	}
	
	//������ � ����
	function sql_query($str_query)
	{
		$start = microtime(true);
		$this->queries++;
		//echo "<b><font color=\"#FF0000\">#".$this->queries." = ".$str_query."</font></b><br/>"; //��� ������� � ��������� ������ ��������
		$link = @mysql_query($str_query);
		if($link)
		{
			$end = microtime(true);
			$this->timems=$this->timems+($end-$start);
			return $link;
		}
		else
		{
			echo "MySQL died because ";
			echo mysql_errno() . ": " . mysql_error(). "<br/>";
		}
	}
}


$mysql = new MySQL;
$count = 0;

$getusers = $mysql->sql_query("SELECT * FROM users");
while($userdata = mysql_fetch_assoc($getusers))
{
	$id = $userdata["id_user"];
	$login = $userdata["login"];
	$pass = $userdata["pass"];
	$email =  $userdata["email"];
	$lastvisit =  $userdata["active"];
	$registered =  $userdata["timeactive"];
	
	$mysql->sql_query("INSERT INTO `forum_users` VALUES ($id, 3, '$login', '$pass', '', '$email', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 'Russian', 'Oxygen', 1, 1304646541, NULL, NULL, $registered, '', $lastvisit, NULL, NULL, NULL);");
	echo "Export user ID $id with name $login<br/>";
	$count++;
}

echo "Exported $count users";


?>
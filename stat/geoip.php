<?php
header('Content-type: text/html; charset=windows-1251');
// ����������� ����������
require('geoip_class.php');

// ����� ������� ������: ��������� ���� ������

// ������������� ������
$geo_ip = new geo_ip();

// ��������� (���� ���������)
// $geo_ip->ip_limit = 4000;           // ������������ ���-�� IP � �������
// $geo_ip->encoding = 'windows-1251'; // ���������, � ������� ����� ������� ������ (UTF-8, KOI8-R, WINDOWS-1251)
// $geo_ip->return_type = 'array';     // ��� ������������ ������: array - ������, xml - ����� ������� (XML)
// $geo_ip->check_ip = false;          // true - ��������� IP �� ������������, false - �� ���������
// $geo_ip->bad_ip = false;            // ���� IP ��������: true - ����������, false - ������� ������
// $geo_ip->id = false;                // ������ ID ��������� � ����� �� ������
// $geo_ip->status = true;             // ��������� ���� status � ������������ ������: true - ��, false - ���
// $geo_ip->ip_array_index = false;    // ��� ������ �������: true - IP, false - ������� �������� ������� (0,1,...)
// $geo_ip->default_template = 'DEF';  // ������ ������������ �������� (��. � ����� geo_ip.class.inc)
                                       // D - �����, E - ������, F - ����������� ����� (��. geo_ip.class.inc)

$array = $geo_ip->get_ip($_GET['ip']);

if($array[0]['status'] == 'OK'){ // ��������� ������ ����� �������������� �������
	//echo '<b>C�����:</b> ', $array[0]['status'], '<br />'; // ������ ����������: OK - �����, Not Found - �� ������
	//echo '<b>[ IP ]:</b> ', $array[0]['ip'], '<br />'; // IP
	//echo '<b>[ Inetnum ]:</b> ', $array[0]['inetnum'], '<br />'; // ���� �������, � �������� ��������� ������� ip-�����)
    //echo '<b>[ Inet-status ]:</b> ', $array[0]['inet-status'], '<br />'; // ������ ����� �� ���� RIPE
    echo '<b>�����:</b> ', $array[0]['city'], '<br />'; // �����, � �������� ��������� ������� ip
    echo '<b>�������:</b> ', $array[0]['region'], '<br />'; // ������, � �������� ��������� ������� ip
    echo '<b>������:</b> ', $array[0]['district'], '<br />'; // ����������� ����� ��, � �������� ��������� ������� ip
	echo '<b>���������:</b> ', $array[0]['inet-descr'], '<br />'; // �������� ����� �� ���� RIPE (www.ripe.net)
    //echo '<b>[ Lat ]:</b> ', $array[0]['lat'], '<br />'; // �������������� ������ ������
    //echo '<b>[ Lng ]:</b> ', $array[0]['lng'], '<br />'; // �������������� ������� ������
}
else
{
    echo $array[0]['status'];
}
?>

<?php
require_once dirname(__FILE__).'/Thou.php';


$T2 = date("Y-m-d h:i:s");
$T1 = date('Y-m-d H:i:s',strtotime('-2632020 minutes',strtotime($T2)));
$datetime1 =  explode(" ",$T1);
$date1 = implode("",explode("-",$datetime1[0]));
$time1 = implode("",explode(":",$datetime1[1]));
$datetime1 =$datetime1[0]."T".$datetime1[1];

$datetime2 =  explode(" ",$T2);
$date2 = implode("",explode("-",$datetime2[0]));
$time2 = implode("",explode(":",$datetime2[1]));
$datetime2 =$datetime2[0]."T".$datetime2[1];

$instance = new Thou();
$instance->getProspects($datetime1,$datetime2 );
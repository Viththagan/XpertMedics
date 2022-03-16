<?php
	$file=array(1=>'sales',2=>'weekly_sales',3=>'daily_sales',4=>'hourly_sales');//,5=>'day_basis',6=>'delivery',7=>'electricity'
	$i=$_POST['i'];
	require_once "analytics/".$file[$i].".php";
?>
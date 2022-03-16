<script type="text/javascript">
	$(function(){
		var i=1;
		function load(){
			$.post(config_url+'remote.php?file=dashboard/dashboard',{i:i,width: screen.width, height:screen.height},function(d){
				$('#dashboard').html(d);
				i++;
				if(i==100)i=1;
				$.agoy_init();
				setTimeout(load,60000);
			});
		}
		load();
	});
</script>
<div id="dashboard"></div>
<div id="data"></div>
<?php
	$tables_all_devices=array("Pending cheques","payments check","repack approvals");
	$tables_only_lap=array("return summary","pending sales invoices","overstock check","data entry errors","data entry missing",
	"receivables","uncategorized");//"loss sales",
	$tables=array();
	if($client=='00001' AND $_SESSION['user_id']==1)$tables[]='sms duplicates';
	foreach($tables_only_lap as $table){
		if(in_array($table,$permission))$tables[]=$table;
	}
	if($_GET['width']>1024){
		foreach($tables_all_devices as $table){
			if(in_array($table,$permission))$tables[]=$table;
		}
	}
	echo '<script>
	tables='.json_encode($tables).';
	</script>';
?>
<script>
$(document).ready(function(){
	var i=0;
	function load_tables(){
		$.post(config_url+'remote.php?file=dashboard/ajax_home',{table:tables[i]},function(d){
			$('#data').append(d);
			i++;
			if(i<tables.length)setTimeout(load_tables,1000);
			else $.agoy_init();
		});
	}
	load_tables();
});
</script>
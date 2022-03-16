<?php
	if(!isset($_GET['id']))$_GET['id']='';
	echo '<script type="text/javascript">
	var company="'.$_GET['id'].'";
	</script>';
?>
<script type="text/javascript">
	$(function(){
		var i=1;
		var max_files=4;
		function load(){
			$.post(config_url+'remote.php?file=dashboard/ajax',{i:i,company:company},function(d){
				$('#analytics').html(d);
				i++;
				if(i==(max_files+1))i=1;
				setTimeout(load,30000);
			});
		}
		load();
	});
</script>
<div id="analytics"></div>
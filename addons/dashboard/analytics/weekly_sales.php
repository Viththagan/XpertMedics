<?php
	if(isset($_POST['company']) AND $_POST['company']>=0 AND $_POST['company']<>''){
		$date=date('Y-m-01',strtotime('-3 months'));
		$result=mysqli_query($con,"SELECT SUM(l.qty*l.selling_price) total,WEEK(l.added) label,CONCAT(YEAR(l.added), '/', WEEK(l.added)) week 
		FROM products_logs l LEFT JOIN products p ON p.id=l.product 
		WHERE l.added>='$date' AND p.company='$_POST[company]' and l.type=0 GROUP BY week ORDER BY l.added");
		$row=array();
		$row['data']='[';
		$row['label']='[';
		while($row1=mysqli_fetch_assoc($result)){
			$row['data'].=($row1['total']/10000).',';
			$row['label'].='"'.$row1['label'].'",';
		}
		$row['data'].=']';
		$row['label'].=']';
	}
	else {
		$result=mysqli_query($con,"SELECT * FROM analytics WHERE title='Sales_SUM_weekly'");
		$row=$result->fetch_assoc();
	}
	echo '<script type="text/javascript">
	var weekly_temp='.json_encode($row['data']).';//alert(weekly_temp);
	var json=JSON.stringify(eval("(" + weekly_temp + ")"));
	var weekly=jQuery.parseJSON( json );
	var label_temp='.json_encode($row['label']).';//alert(label_temp);
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label=jQuery.parseJSON( json );//alert(label);
	</script>';
?>
<div class="col-lg-16">
	<div class="card">
		<div class="card-body">
			<h4 class="mt-0 header-title">Sales Summary weekly</h4>
			<div id="sales_summary_weekly" class="ct-chart ct-golden-section"></div>
		</div>
	</div>
</div>
<script>
new Chartist.Line('#sales_summary_weekly',{labels: label,series: [weekly]},{low: 0,showArea: true,plugins: [Chartist.plugins.tooltip()]});
</script>
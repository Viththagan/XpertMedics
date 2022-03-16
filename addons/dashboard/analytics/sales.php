<?php
	if(isset($_POST['company']) AND $_POST['company']>=0 AND $_POST['company']<>''){
		$date=date('Y-m-01',strtotime('-12 months'));
		$result=mysqli_query($con,"SELECT ifnull(SUM(l.qty*l.selling_price),0) total,extract(YEAR_MONTH from l.added) month FROM products_logs l LEFT JOIN products p ON p.id=l.product 
		WHERE l.added>='$date' AND p.company='$_POST[company]' AND l.type=0 GROUP BY month");
		$row['data']='[';
		$row['label']='[';
		while($ro=mysqli_fetch_assoc($result)){
			$row['data'].=($ro['total']/1000).',';
			$row['label'].='"'.$ro['month'].'",';
		}
		$row['data'].=']';
		$row['label'].=']';
	}
	else {
		$result=mysqli_query($con,"SELECT * FROM analytics WHERE title='Sales_SUM_monthly'");
		$row=$result->fetch_assoc();
	}
	echo '<script type="text/javascript">
	var monthly_temp='.json_encode($row['data']).';//alert(monthly_temp);
	var json=JSON.stringify(eval("(" + monthly_temp + ")"));
	var monthly=jQuery.parseJSON( json );
	var label_temp='.json_encode($row['label']).';//alert(label_temp);
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label=jQuery.parseJSON( json );//alert(label);
	</script>';
?>
<div class="col-lg-16">
	<div class="card">
		<div class="card-body">
			<h4 class="mt-0 header-title">Sales Summary Monthly</h4>
			<div id="sales_summary_monthly" class="ct-chart ct-golden-section"></div>
			
		</div>
	</div>
</div>
<script>
new Chartist.Line('#sales_summary_monthly',{labels: label,series: [monthly]},{low: 0,showArea: true,plugins: [Chartist.plugins.tooltip()]});
// var $data = [
                // {y: '2012', a: 50, b: 80, c: 20},
                // {y: '2013', a: 130, b: 100, c: 80},
                // {y: '2014', a: 80, b: 60, c: 70},
                // {y: '2015', a: 70, b: 200, c: 140},
                // {y: '2016', a: 180, b: 140, c: 150},
                // {y: '2017', a: 105, b: 100, c: 80},
                // {y: '2018', a: 250, b: 150, c: 200}
            // ];<div id="morris-line-example" class="morris-charts morris-chart-height"></div>
// this.createLineChart('morris-line-example', data, 'y', ['a', 'b'], ['Activated', 'Pending'], ['#ccc', '#3c4ccf']);
</script>
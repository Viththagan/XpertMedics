<?php
	
	if(isset($_POST['company']) AND $_POST['company']>=0 AND $_POST['company']<>''){
		function get_data_daily($start,$end){
			global $summary,$con,$_POST;
			$result=mysqli_query($con,"SELECT SUM(l.qty*l.selling_price) total,extract(DAY from l.added) day 
				FROM products_logs l LEFT JOIN products p ON p.id=l.product WHERE l.added BETWEEN '$start' AND '$end' AND p.company='$_POST[company]' AND l.type=0 AND l.product NOT IN(12371,12372,13558,13559,13560,13561,2968) GROUP BY day ORDER BY l.added ASC");
			$sum='[';
			$total=0;
			while($row=mysqli_fetch_assoc($result)){
				$sum.=number_format(($row['total']/1000),2,'.','').',';
				$total+=number_format(($row['total']/1000),2,'.','');
			}
			$sum.=']';
			$summary.=$total.',';
			return $sum;
		}
		$label='[';
		for($i=1; $i<date('d'); $i++)$label.='"'.$i.'",';
		$label.=']';
		
		$summary='[';
		$data=array();
		$data['This Month']=get_data_daily(date('Y-m-01'),date('Y-m-d',strtotime('yesterday')));
		$data['Last month']=get_data_daily(date('Y-m-01',strtotime('last month')),date('Y-m-d',strtotime('last month -1 day')));
		$data['2nd Last Month']=get_data_daily(date('Y-m-01',strtotime('-2 month')),date('Y-m-d',strtotime('-2 month -1 day')));
		$data['sum']=$summary.']';
		$data['label']=$label;
	}
	else {
		$result=mysqli_query($con,"SELECT * FROM analytics WHERE title='Sales_SUM_daily'");
		$row=$result->fetch_assoc();
		$data=json_decode($row['data'],true);
	}
	echo '<script type="text/javascript">
	var this_month_temp='.json_encode($data['This Month']).';
	var json=JSON.stringify(eval("(" + this_month_temp + ")"));
	var this_month=jQuery.parseJSON( json );
	
	var last_month_temp='.json_encode($data['Last month']).';
	var json=JSON.stringify(eval("(" + last_month_temp + ")"));
	var last_month=jQuery.parseJSON( json );
	
	var second_last_month_temp='.json_encode($data['2nd Last Month']).';
	var json=JSON.stringify(eval("(" + second_last_month_temp + ")"));
	var second_last_month=jQuery.parseJSON( json );
	
	var label_temp='.json_encode($data['label']).';
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label=jQuery.parseJSON( json );
	
	var data_temp='.json_encode($data['sum']).';
	var json=JSON.stringify(eval("(" + data_temp + ")"));
	var data=jQuery.parseJSON( json );
	</script>';
?>
<div class="row">
	<div class="col-lg-9">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title">Sales Summary Daily</h4>
				<div id="sales_sum_hourly" class="ct-chart ct-golden-section"></div>
			</div>
		</div>
	</div>
	<div class="col-lg-3">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title">Hourly Daily</h4>
				<div id="sales_sum_compare" class="ct-chart ct-golden-section"></div>
			</div>
		</div>
	</div>
</div>
<script>
var data1 = {
  labels: label,
  series: [this_month,last_month,second_last_month]
};

var options = {
  seriesBarDistance: 7
};
var responsiveOptions = [
  ['screen and (max-width: 640px)', {
    seriesBarDistance: 5,
    axisX: {
      labelInterpolationFnc: function (value) {
        return value[0];
      }
    }
  }]
];
new Chartist.Bar('#sales_sum_hourly', data1, options, responsiveOptions);

var data2 = {
  labels: ["This Month","Last month","2nd Last Month"],
  series: [data]
};
new Chartist.Bar('#sales_sum_compare', data2, options, responsiveOptions);
</script>
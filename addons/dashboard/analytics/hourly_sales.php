<?php
	$today=date('Y-m-d');
	$label='';
	$max_hours=7;
	$data='[';
	function get_data_hourly($date){
		global $label,$max_hours,$data,$con;
		$result=mysqli_query($con,"SELECT SUM(s.total) total,Hour(s.added) hour FROM sales s LEFT JOIN customers c ON c.id=s.customer
		WHERE s.date='$date' AND (c.plat<>3 OR s.customer=0) AND Hour(s.added)>=7 GROUP BY Hour(s.added) ORDER BY s.added ASC");
		$sum='[';
		$hours='[';
		while($row=mysqli_fetch_assoc($result)){
			if($date==date('Y-m-d')){
				$sum.=($row['total']).',';
				$hours.=$row['hour'].',';
				$max_hours=$row['hour'];
			}
			else if($row['hour']<=$max_hours)$sum.=$row['total'].',';
		}
		$sum.=']';
		$hours.=']';
		if($date==date('Y-m-d'))$label=$hours;
		$hours_to_get=$max_hours-1;
		$result=mysqli_query($con,"SELECT SUM(s.total) total FROM sales s LEFT JOIN customers c ON c.id=s.customer
		WHERE s.date='$date' AND (c.plat<>3 OR s.customer=0) AND Hour(s.added)<=$hours_to_get");
		$row=mysqli_fetch_assoc($result);
		$data.=$row['total'].',';
		return $sum;
	}
	// $monthly="[2,3,4,5]";
	echo '<script type="text/javascript">
	var today_sum_temp='.json_encode(get_data_hourly($today)).';
	var json=JSON.stringify(eval("(" + today_sum_temp + ")"));
	var today_sum=jQuery.parseJSON( json );
	
	var lastday_temp='.json_encode(get_data_hourly(date('Y-m-d',strtotime("-1 Days")))).';
	var json=JSON.stringify(eval("(" + lastday_temp + ")"));
	var lastday=jQuery.parseJSON( json );
	
	var lastweek_temp='.json_encode(get_data_hourly(date('Y-m-d',strtotime("-7 Days")))).';
	var json=JSON.stringify(eval("(" + lastweek_temp + ")"));//alert(lastweek_temp);
	var lastweek=jQuery.parseJSON( json );
	
	var lastmonth_temp='.json_encode(get_data_hourly(date('Y-m-d',strtotime("-1 Month")))).';
	var json=JSON.stringify(eval("(" + lastmonth_temp + ")"));
	var lastmonth=jQuery.parseJSON( json );
	
	var label_temp='.json_encode($label).';
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label=jQuery.parseJSON( json );
	
	var data_temp='.json_encode($data.']').';
	var json=JSON.stringify(eval("(" + data_temp + ")"));
	var data=jQuery.parseJSON( json );
	</script>';
?>
<div class="row">
	<div class="col-lg-9">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title">Sales Summary Hourly</h4>
				<div id="sales_sum_hourly" class="ct-chart ct-golden-section"></div>
			</div>
		</div>
	</div>
	<div class="col-lg-3">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title">Hourly Compare</h4>
				<div id="sales_sum_compare" class="ct-chart ct-golden-section"></div>
			</div>
		</div>
	</div>
</div>
<script>
var data1 = {
  labels: label,
  series: [today_sum,lastday,lastweek,lastmonth]
};

var options = {
  seriesBarDistance: 10
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
  labels: ["Today","Yesterday","Lastweek","Last month"],
  series: [data]
};
new Chartist.Bar('#sales_sum_compare', data2, options, responsiveOptions);
</script>
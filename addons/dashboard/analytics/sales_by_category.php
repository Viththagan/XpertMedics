<?php
	echo'<div class="row">';
	echo '<div class="col-xs-1 col-lg-6">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Sales Summary by Category</h4>
				<div id="sales_summary_by_category" class="e-chart"></div>
			</div>
		</div>
	</div>';
	echo '<div class="col-xs-1 col-lg-6">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Profit by Category</h4>
				<div id="profit_by_category" class="e-chart"></div>
			</div>
		</div>
	</div>';
	echo '</div>';
	$date=date('Y-m-d');
	$result=mysqli_query($con,"SELECT SUM(si.qty*(si.price-si.discount)) sales,c.title FROM sales_items si LEFT JOIN sales s ON s.id=si.invoice LEFT JOIN products p ON p.id=si.product 
	LEFT JOIN product_category pc ON pc.id=p.category LEFT JOIN product_category c ON c.id=pc.parent
	WHERE s.date='$date' GROUP BY c.id");
	$label='[';
	$data='[';
	while($row=mysqli_fetch_assoc($result)){
		if($row['title']=='')$row['title']='Unknown';
		$label.='"'.$row['title'].'",';
		$data.="{value:$row[sales], name:'".$row['title']."'},";
	}
	$data.=']';
	$label.=']';
	echo '<script type="text/javascript">
	var label_temp='.json_encode($label).';
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label3=jQuery.parseJSON( json );
	
	var data_temp='.json_encode($data).';
	var json=JSON.stringify(eval("(" + data_temp + ")"));
	var data3=jQuery.parseJSON( json );
	</script>';
	
	$result=mysqli_query($con,"SELECT SUM(si.qty*(si.price-si.discount-si.purchase_price)) profit,c.title FROM sales_items si LEFT JOIN sales s ON s.id=si.invoice 
	LEFT JOIN products p ON p.id=si.product LEFT JOIN product_category pc ON pc.id=p.category LEFT JOIN product_category c ON c.id=pc.parent
	WHERE s.date='$date' AND si.purchase_price>0 GROUP BY c.id");
	$label='[';
	$data='[';
	while($row=mysqli_fetch_assoc($result)){
		if($row['title']=='')$row['title']='Unknown';
		$label.='"'.$row['title'].'",';
		$data.="{value:$row[profit], name:'".$row['title']."'},";
	}
	$data.=']';
	$label.=']';
	echo '<script type="text/javascript">
	var label_temp='.json_encode($label).';
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label4=jQuery.parseJSON( json );
	
	var data_temp='.json_encode($data).';
	var json=JSON.stringify(eval("(" + data_temp + ")"));
	var data4=jQuery.parseJSON( json );
	</script>';
?>
<script>
var dom = document.getElementById("sales_summary_by_category");
var myChart = echarts.init(dom);
var app = {};
option = null;
option = {
    tooltip : {trigger: 'item',formatter: "{a} <br/>{b} : {c} ({d}%)"},
    legend: {orient: 'vertical',left: 'left',data: label3},
    color: ['#e74c5e', '#47bd9a', '#06c2de', '#f9d570', '#4090cb'],
    series : [
        {
            name: 'Total sales',type: 'pie',radius : '55%',center: ['50%', '60%'],
            data:data3,
            itemStyle: {emphasis: {shadowBlur: 10,shadowOffsetX: 0,shadowColor: 'rgba(0, 0, 0, 0.5)'}}
        }
    ]
};
if (option && typeof option === "object") {
    myChart.setOption(option, true);
}

var dom = document.getElementById("profit_by_category");
var myChart = echarts.init(dom);
var app = {};
option = null;
option = {
    tooltip : {trigger: 'item',formatter: "{a} <br/>{b} : {c} ({d}%)"},
    legend: {orient: 'vertical',left: 'left',data: label4},
    color: ['#e74c5e', '#47bd9a', '#06c2de', '#f9d570', '#4090cb'],
    series : [
        {
            name: 'Total sales',type: 'pie',radius : '55%',center: ['50%', '60%'],
            data:data4,
            itemStyle: {emphasis: {shadowBlur: 10,shadowOffsetX: 0,shadowColor: 'rgba(0, 0, 0, 0.5)'}}
        }
    ]
};
if (option && typeof option === "object") {
    myChart.setOption(option, true);
}
</script>
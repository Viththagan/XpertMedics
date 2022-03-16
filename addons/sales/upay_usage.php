<?php
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>';
									if(!isset($_GET['date']))$_GET['date']=date('d-m-Y');
									echo '<input type="text" class="form-control date" name="date" value="'.$_GET['date'].'"/>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Customer</label>
								<div><select class="form-control select" name="customer[]" multiple>';
									$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile FROM upay u LEFT JOIN customers c ON c.id=u.customer WHERE u.customer>0 AND u.sales is NULL GROUP BY u.customer") or die(mysqli_error($con));
									$customers=array();
									while($row=mysqli_fetch_array($result)){
										$customers[$row['customer']]=$row['fname'].' ('.$row['mobile'].','.$row['customer'].')';
										echo '<option value="'.$row['customer'].'" '.((isset($_GET['customer']) AND in_array($row['customer'],$_GET['customer']))?'selected':'').'>'.$row['fname'].' ('.$row['customer'].') '.$row['mobile'].'</option>';
									}
									echo '<option value="-1" '.((isset($_GET['customer']) AND in_array('-1',$_GET['customer']))?'selected':'').'>None</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Type</label>
								<div><select class="form-control select form_submit_change" name="type">';
									$types=array(0=>'Real Sales',1=>'Cash Back');
									if(!isset($_GET['type']))$_GET['type']=1;
									foreach($types as $key=>$title)echo '<option value="'.$key.'" '.($_GET['type']==$key?'selected':'').'>'.$title.'</option>';
									echo '<option value="All" '.($_GET['type']=='All'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=1000;
									foreach($values as $value)echo '<option value="'.$value.'" '.((isset($_GET['limit']) AND $_GET['limit']==$value)?'selected':'').'>'.$value.'</option>';
									echo '<option value="0">All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label></label>
								<div><button type="button" class="btn btn-primary waves-effect waves-light form_submit_click">Filter</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
    </div>';
	$date=date('Y-m-d',strtotime($_GET['date']));
	$result=mysqli_query($con,"SELECT SUM(amount) total,Hour(paid) hour FROM upay WHERE date='$date' and status='1' GROUP BY Hour(paid) ORDER BY paid ASC");
	$sum='[';
	$label='[';
	while($row=mysqli_fetch_assoc($result)){
		$sum.=($row['total']/1000).',';
		$label.=$row['hour'].',';
	}
	$sum.=']';
	$label.=']';
	// $monthly="[2,3,4,5]";
	echo '<script type="text/javascript">
	var today_sum_temp='.json_encode($sum).';
	var json=JSON.stringify(eval("(" + today_sum_temp + ")"));
	var today_sum=jQuery.parseJSON( json );
	
	var label_temp='.json_encode($label).';
	var json=JSON.stringify(eval("(" + label_temp + ")"));
	var label=jQuery.parseJSON( json );
	
	</script>';
	
?>
<div class="row">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title">Summary Hourly</h4>
				<div id="sales_sum_hourly" class="ct-chart ct-golden-section"></div>
			</div>
		</div>
	</div>
</div>
<script>
var data1 = {labels: label,series: [today_sum]};
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
</script>
<?php
	echo '<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<table table="upay_payments" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
				<thead><tr><th>#</th><th>'.language('Customer').'</th><th>'.language('Total').'</th><th>'.language('Amount').'</th><th>'.language('Qty').'</th>
				<th>'.language('Days').'</th><th>'.language('Profit').'</th></tr></thead><tbody>';
				// $result=mysqli_query($con,"SELECT * FROM upay_payments WHERE qty is NULL") or die(mysqli_error($con));
				// while($row=mysqli_fetch_array($result)){
					// mysqli_query($con,"SELECT * FROM upay WHERE customer='$row[customer]' AND date='$row[date]'") or die(mysqli_error($con));
					// $qty=mysqli_affected_rows($con);
					// mysqli_query($con,"UPDATE upay_payments SET qty='$qty' WHERE id='$row[id]'") or die(mysqli_error($con));
				// }
				$result=mysqli_query($con,"SELECT SUM(u.total) total,SUM(u.amount) amount,SUM(u.paid) paid,SUM(u.commission) comm,SUM(u.qty) qty,COUNT(*) days,c.fname,c.mobile 
				FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer GROUP BY u.customer") or die(mysqli_error($con));
				$counter=1;
				$sum=array('total'=>0,'amount'=>0,'qty'=>0,'profit'=>0);
				while($row=mysqli_fetch_array($result)){
					if($row['total']>0){
						$profit=$row['paid']+$row['comm']-$row['total'];
						echo'<tr style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td>'.$row['fname'].'<br/>'.$row['mobile'].'</td>
							<td class="right">'.Number_format($row['total'],2).'</td>
							<td class="right">'.Number_format($row['amount'],2).'</td>
							<td>'.$row['qty'].'</td>
							<td>'.$row['days'].'</td>
							<td class="right">'.Number_format($profit,2).'</td>
						</tr>';
						$sum['total']+=$row['total'];
						$sum['amount']+=$row['amount'];
						$sum['qty']+=$row['qty'];
						$sum['profit']+=$profit;
					}
				}
			echo '</tbody><tfoot><tr><td></td><td>Total</td>
				<td class="right">'.Number_format($sum['total'],2).'</td>
				<td class="right">'.Number_format($sum['amount'],2).'</td>
				<td class="right">'.$sum['qty'].'</td><td></td>
				<td class="right">'.Number_format($sum['profit'],2).'</td>
				</tr></tfoot></table>
			</div>
		</div>
	</div>';
?>
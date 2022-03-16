<?php
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		return;
	}
	if(isset($_GET['from']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-4">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control form_submit_change date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Till</label>
								<div><select class="form-control select form_submit_change" name="till">';
									if(!isset($_GET['till']))$_GET['till']='';
									$result-mysqli_query($con,"SELECT * FROM sales_counters WHERE status='1' ORDER BY id ASC");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" '.($_GET['till']==$row['id']?'selected':'').'>'.$row['title'].'</option>';
									echo '<option value="" '.($_GET['till']==''?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
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
	$start=date('Y-m-d',strtotime($_GET['from']));
	$end=date('Y-m-d',strtotime($_GET['from']));
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$start=date('Y-m-d',strtotime($_GET['from']));
			$end=date('Y-m-d',strtotime($_GET['to']));
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$start=date('Y-m-d',strtotime($_GET['to']));
			$end=date('Y-m-d',strtotime($_GET['to']));
		}
	}
	$q="";
	if(isset($_GET['status'])){
		if($_GET['status']=='All'){}
		else $q=" AND p.status='$_GET[status]'";
	}
	$result=mysqli_query($con,"SELECT SUM(si.selling_price*si.qty) sales,SUM(if(si.purchased>0,si.purchased*si.qty,0)) cost 
	FROM sales s LEFT JOIN products_logs si ON (si.referrer=s.id AND si.type='0') WHERE s.date BETWEEN '$start' AND '$end' AND si.product<>2968") or die(mysqli_error($con));
	$row=mysqli_fetch_array($result);
	$result=mysqli_query($con,"SELECT SUM(si.selling_price*si.qty) sales,SUM(if(si.purchased>0,si.purchased*si.qty,0)) cost 
	FROM sales s LEFT JOIN products_logs si ON (si.referrer=s.id AND si.type='0') WHERE s.date BETWEEN '$start' AND '$end' AND si.product=2968") or die(mysqli_error($con));
	$bill=mysqli_fetch_array($result);
	// $profit=$row1['profit'];
	// $result=mysqli_query($con,"SELECT SUM(discount) discount FROM po WHERE date='$date' AND discount_type='0'") or die(mysqli_error($con));
	// $row2=mysqli_fetch_array($result);
	// $profit+=$row2['discount'];
	// $result=mysqli_query($con,"SELECT SUM(if(pi.unit>0,((pi.purchased/pi.unit)*pi.free),pi.purchased*pi.free)) free FROM products_logs pi LEFt JOIN po ON (po.id=pi.referrer AND pi.type=1) 
	// WHERE po.date='$date' AND pi.qty='0' AND pi.free>0") or die(mysqli_error($con));
	// $row2=mysqli_fetch_array($result);
	// $profit+=$row2['free'];
	echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="table-responsive">
					<table class="table table-striped mb-0">
						<thead><tr><th>Description</th><th class="right">Credit</th><th class="right">Debit</th></tr></thead>
						<tbody>';
							echo '<tr><th scope="row">Sales</th><td class="right">'.number_format($row['sales'],2).'</td><td></td></tr>';
							$profit=$row['sales'];
							echo '<tr><th scope="row">Bill Payments</th><td class="right">'.number_format($bill['sales'],2).'</td><td></td></tr>';
							$profit+=$bill['sales'];
							echo '<tr><td><b>Cost</b></td><td class="right">('.number_format($row['cost']+$bill['cost'],2).')</td><td></td></tr>';
							$profit-=$row['cost']+$bill['cost'];
							echo '<tr><td><b>Gross Profit Before Sales Cost</b></td><td></td><td class="right">'.number_format($profit,2).'</td></tr>';
							$result=mysqli_query($con,"SELECT SUM(amount) total,type FROM `sales_payments`WHERE date BETWEEN '$start' AND '$end' group BY type ") or die(mysqli_error($con));
							while($row2=mysqli_fetch_assoc($result)){
								switch($row2['type']){
									case 4:
										echo '<tr><td><b>Card Cost</b></td><td class="right">('.number_format($row2['total']*0.025,2).')</td><td></td></tr>';
										$profit-=$row2['total']*0.025;
										break;
									case 5:
										echo '<tr><td><b>Redeemed Points</b></td><td class="right">('.number_format($row2['total'],2).')</td><td></td></tr>';
										$profit-=$row2['total'];
										break;
									case 6:
										echo '<tr><td><b>Gift Voucher</b></td><td class="right">('.number_format($row2['total'],2).')</td><td></td></tr>';
										$profit-=$row2['total'];
										break;
									case 8:
										echo '<tr><td><b>QR Payment</b></td><td class="right">('.number_format($row2['total']*0.005,2).')</td><td></td></tr>';
										$profit-=$row2['total']*0.005;
										break;
										
								}
							}
							echo '<tr><td><b>Gross Profit</b></td><td></td><td class="right">'.number_format($profit,2).'</td></tr>';
							echo '<tr><td><b>Administrative Cost</b></td><td></td><td class="right"></td></tr>';
							$result=mysqli_query($con,"SELECT SUM(amount) total,category FROM payments WHERE date BETWEEN '$start' AND '$end' AND category<>1 GROUP BY category ") or die(mysqli_error($con));
							while($row2=mysqli_fetch_assoc($result)){
								echo '<tr><td><b>'.$payments_type[$row2['category']].'</b></td><td class="right">('.number_format($row2['total'],2).')</td><td></td></tr>';
								$profit-=$row2['total'];
							}
							echo '<tr><td><b>Net Profit</b></td><td></td><td class="right">'.number_format($profit,2).'</td></tr>';
							echo '
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>';
?>
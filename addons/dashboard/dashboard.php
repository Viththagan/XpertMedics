<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_GET['form'])){//print_r($_POST);
		switch($_GET['form']){
			case 'product':
				echo view_a_product($_GET['id'],str_replace('.php','',str_replace($dir,'',__FILE__)),str_replace($dir,'',dirname(__FILE__)));
				break;
			case 'view_invoice':
				echo '<div class="card col-lg-5 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">';
				echo print_receipt($_GET['id']);
				echo '</div>
						</div>
					</div>
					</div>
					</div>
				</div>';
				break;
			case 'product_po':
				echo '<div class="card col-lg-9 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">
							<table class="table table-sm">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('PO').'</th><th class="right">'.language('Qty').'</th>
							<th class="right">'.language('UIC').'</th><th class="right">'.language('Free').'</th><th class="right">'.language('Price').'</th>
							<th class="right">'.language('Cost').'</th><th class="right">'.language('MRP').'</th><th class="right">'.language('Gold').'</th></tr></thead><tbody>';
							$counter=1;
							$from=date('Y-01-01',strtotime('last year'));
							$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,ifnull(po.date,y.date) date,po.id pid 
							FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN po ON (po.id=l.referrer AND l.type=1) LEFT JOIN yura_batch_history y ON (y.id=l.referrer AND l.type=3) 
							WHERE l.type IN(1,3) AND l.product='$_GET[product]' AND (po.date>='$from' OR y.date>='$from') ORDER BY id DESC LIMIT 0,10") or die(mysqli_error($con));
							if(mysqli_affected_rows($con)==0){
								$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,ifnull(po.date,y.date) date,po.id pid 
								FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN po ON (po.id=l.referrer AND l.type=1) LEFT JOIN yura_batch_history y ON (y.id=l.referrer AND l.type=3) 
								WHERE l.type IN(1,3) AND l.product='$_GET[product]' ORDER BY id DESC LIMIT 0,10") or die(mysqli_error($con));
							}
							while($row=mysqli_fetch_array($result)){
								$qty=($row['uic']>0?($row['uic']*$row['qty']):$row['qty'])+$row['free'];
								$cost=($row['purchased']*$row['qty'])/$qty;
								if($cost==$row['mrp']){
									$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
									FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
									$ro=mysqli_fetch_assoc($re);
									$diff=$ro['total']-$ro['tot'];
									if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
										$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
										$cost=$row['mrp']*$discounted_price;
										$cost=($cost*$row['qty'])/$qty;
									}
								}
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;" class="select_price '.($row['status']==1?'discount_color_0':'').'" tr_id="'.$_GET['id'].'">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['date'].'</td>
									<td>'.$row['pid'].'</td>
									<td class="right">'.$row['qty'].'</td>
									<td class="right">'.$row['uic'].'</td>
									<td class="right">'.$row['free'].'</td>
									<td class="right">'.$row['purchased'].'</td>
									<td class="right">'.number_format($cost,2).'</td>
									<td class="right">'.$row['mrp'].'</td>
									<td class="right">'.$row['selling_price'].'</td>
								</tr>';
							}
						echo '</tbody></table>
						</div>
						</div>
						</div>
						</div>
					</div>
				</div>';
				break;
			case 'yura_view':
				$result=mysqli_query($con,"SELECT * FROM yura_batch_history WHERE id='$_GET[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_array($result);
				echo '<div class="card col-lg-9 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">Cost : '.$row['cost'].'
							<table class="table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('Code').'</th><th class="right">'.language('Qty').'</th>
							<th class="right">'.language('Cost').'</th><th class="right">'.language('MRP').'</th></tr></thead><tbody>';
							$class='';
							if($_SESSION['soft']['user_data']['user_level']==0)$class='editable';
							$counter=1;
							$input=0;
							$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title FROM products_logs l LEFT JOIN products p ON p.id=l.product WHERE l.type=4 AND l.referrer='$_GET[id]'") or die(mysqli_error($con));
							while($row=mysqli_fetch_array($result)){
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['title'].'</td>
									<td>'.$row['ui_code'].'</td>
									<td class="right '.$class.'" field="qty">'.$row['qty'].'</td>
									<td class="right">'.$row['purchased'].'</td>
									<td class="right">'.$row['mrp'].'</td>
								</tr>';
								$input+=$row['qty'];
							}
						echo '</tbody></table>
							<table class="table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('Code').'</th><th class="right">'.language('Qty').'</th>
							<th class="right">'.language('Cost').'</th><th class="right">'.language('MRP').'</th><th class="right">'.language('Selling').'</th></tr></thead><tbody>';
							$counter=1;
							$total=0;
							$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,p.uic FROM products_logs l LEFT JOIN products p ON p.id=l.product WHERE l.type=3 AND l.referrer='$_GET[id]'") or die(mysqli_error($con));
							while($row=mysqli_fetch_array($result)){
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['title'].'</td>
									<td>'.$row['ui_code'].'</td>
									<td class="right '.$class.'" field="qty">'.$row['qty'].'</td>
									<td class="right '.$class.'" field="purchased">'.$row['purchased'].'</td>
									<td class="right '.$class.'" field="mrp">'.$row['mrp'].'</td>
									<td class="right '.$class.'" field="selling_price">'.$row['selling_price'].'</td>
								</tr>';
								$total+=($row['qty']/$row['uic']);
							}
						echo '</tbody><tfoot><tr class="'.($total<>$input?'warning':'').'"><td></td><td colspan="2">Total</td><td class="right">'.$total.'</td><td colspan="3"></td></tr></table>
						</div>
						</div>
						</div>
						</div>
					</div>
				</div>';
				break;
			case 'payments':
				$result=mysqli_query($con,"SELECT pp.*,c.title company,po.date,(SELECT SUM(l.qty*l.purchased) FROM products_logs l WHERE l.referrer=po.id AND l.type=1) total,po.discount 
				FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN products_company c ON c.id=po.company WHERE pp.payment='$_GET[id]'") or die(mysqli_error($con));
				echo '<div class="card col-lg-9 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">
							<table class="table table-sm" table="po_payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Company').'</th><th>'.language('PO').'</th><th class="right">'.language('Total').'</th>
							<th class="right">'.language('Amount').'</th></tr></thead><tbody>';
							while($row=mysqli_fetch_array($result)){
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['date'].'</td>
									<td>'.$row['company'].'</td>
									<td><a class="btn btn-primary waves-effect waves-light" target="_blank" href="'.$branch_url.'#purchases/new%20purchase\\'.$row['po'].'" role="button">'.$row['po'].'</a></td>
									<td class="right">'.number_format(($row['total']-$row['discount']),2).'</td>
									<td class="right">'.number_format($row['amount'],2).'</td>
								</tr>';
								$input+=$row['qty'];
							}
						echo '</tbody></table>
						</div>
						</div>
						</div>
						</div>
					</div>
				</div>';
				break;
			case 'sms_duplicates':
				$result=mysqli_query($con,"SELECT * FROM sms_sent 
				WHERE seen=0 AND message=(SELECT message FROM sms_sent WHERE id='$_GET[id]') AND number=(SELECT number FROM sms_sent WHERE id='$_GET[id]')") or die(mysqli_error($con));
				echo '<div class="card col-lg-9 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">
							<table class="table table-sm" table="sms_sent">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>Number</th><th>'.language('Message').'</th><th></th></tr></thead><tbody>';
							$count=1;
							while($row=mysqli_fetch_assoc($result)){
								echo '<tr id="'.$row['id'].'">
								<td>'.$count++.'</td>
								<td class="center">'.$row['date'].'</td>
								<td class="center">'.$row['number'].'</td>
								<td>'.$row['message'].'</td>
								<td class="center" style="cursor:pointer;"><i class="fa fa-check fa-1x sms_duplicates"></i></td>
								</tr>';
							}
						echo '</tbody></table>
						</div>
						</div>
						</div>
						</div>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_POST['popup_form_submit'])){
		switch($_POST['popup_form_submit']){
			case 'po_checked':
				mysqli_query($con,"UPDATE products_logs SET checked='1' WHERE id='$_POST[id]'") or die(mysqli_error($con));
				break;
			case 'payment_checked':
				mysqli_query($con,"UPDATE payments SET checked='1' WHERE id='$_POST[id]'") or die(mysqli_error($con));
				break;
			case 'return_checked':
				mysqli_query($con,"UPDATE sales SET checked='1' WHERE id='$_POST[id]'") or die(mysqli_error($con));
				break;
			case 'yura_checked':
				mysqli_query($con,"UPDATE products_logs SET checked='1' WHERE referrer='$_POST[id]'") or die(mysqli_error($con));
				mysqli_query($con,"UPDATE yura_batch_history SET status='1' WHERE id='$_POST[id]'") or die(mysqli_error($con));
				break;
			case 'purchase_price':
				$result=mysqli_query($con,"SELECT * FROM products_logs WHERE id='$_POST[po]'") or die(mysqli_error($con));
				$row=mysqli_fetch_array($result);
				$qty=($row['uic']>0?($row['uic']*$row['qty']):$row['qty'])+$row['free'];
				$cost=($row['purchased']*$row['qty'])/$qty;
				$result=mysqli_query($con,"SELECT * FROM products_logs WHERE id='$_POST[id]'") or die(mysqli_error($con));
				$row1=mysqli_fetch_array($result);
				$result=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row1[product]' AND mrp='$row1[mrp]' AND type='0' AND (purchased='0' OR purchased is NULL)") or die(mysqli_error($con));
				$ids=array();
				while($row=mysqli_fetch_array($result)){
					$ids[]=$row['id'];
					mysqli_query($con,"UPDATE products_logs SET purchased='$cost' WHERE id='$row[id]'");
				}
				echo json_encode($ids);
				break;
		}
		return;
	}
	else if(isset($_POST['edit'])){//print_r($_POST);
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if($_POST['field']=='available_items'){
			stock_adj($_POST['id'],$value);
		}
		else mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		if($_POST['edit']=='products_logs' AND in_array($_POST['field'],array('qty','free','uic'))){
			$result=mysqli_query($con,"SELECT * FROM products_logs WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			$result=mysqli_query($con,"SELECT product FROM $_POST[edit] WHERE id='$row[product]'");
			$row=mysqli_fetch_assoc($result);
			update_qty($row['product']);
		}
		else if($_POST['edit']=='products_logs' AND $_POST['field']=='checked'){
			$result=mysqli_query($con,"SELECT l.*,s.date FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE l.id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			$result=mysqli_query($con,"SELECT l.* FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE s.date='$row[date]' AND l.product='$row[product]' AND l.mrp='$row[mrp]' AND l.purchased='$row[purchased]' AND l.selling_price='$row[selling_price]'");
			$ids=array();
			while($row=mysqli_fetch_assoc($result)){
				$ids[]=$row['id'];
				mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$row[id]'");
			}
			echo json_encode($ids);
		}
		return;
	}
	$date=date('Y-m-d');
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL,"https://soft.lk/api/balance.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS,"client=$client&branch=$_SESSION[branch]");        
	$response = curl_exec($curl);
	$network_error = curl_error($curl);
	curl_close($curl);
	$data=json_decode($response,true);
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
		<strong>Topup Account!</strong> AgoySoft (Pvt) Ltd, 212020039308, Hatton National Bank, Mallavi Branch.<br/>
		Remarks : Bill Payments, <strong>1'.$client.$_SESSION['branch'].'</strong> | SMS, <strong>2'.$client.$_SESSION['branch'].'</strong> | Software Payments, <strong>3'.$client.'</strong> 
	</div>';
	echo '<div class="row">
		<div class="col-xl-3 col-md-6 agoy_menu" href="sales/bill payments" style="cursor:pointer;">
			<div class="card mini-stat bg-primary text-white">
				<div class="card-body">
					<div class="mb-4">
						 <h5 class="font-16 text-uppercase mt-0 text-white-50">Bill Payments</h5>
						 <h4 class="font-500">'.number_format($data['bill_payments'],2).'</h4>
					 </div>
				</div>
			</div>
		</div>';
	echo '<div class="col-xl-3 col-md-6 agoy_menu" href="sales/sms history" style="cursor:pointer;">
			<div class="card mini-stat bg-primary text-white">
				<div class="card-body">
					<div class="mb-4">
						 <h5 class="font-16 text-uppercase mt-0 text-white-50">SMS Wallet</h5>
						 <h4 class="font-500">'.number_format($data['sms'],2).'</h4>
					 </div>
				</div>
			</div>
		</div>';
	$result=mysqli_query($con,"SELECT SUM(c.amount) pending FROM payments_cheques c LEFT JOIN payments p ON p.id=c.payment 
	WHERE p.branch='$_SESSION[branch]' AND c.status=0 AND c.transfer<=CURDATE()");
	$row=mysqli_fetch_array($result);
	echo '<div class="col-xl-3 col-md-6 agoy_menu" href="accounts/cheque list" style="cursor:pointer;">
			<div class="card mini-stat bg-primary text-white">
				<div class="card-body">
					<div class="mb-4">
					   <h5 class="font-16 text-uppercase mt-0 text-white-50">Pending Cheques</h5>
						<h4 class="font-500">'.number_format($row['pending'],2).'</h4>
					</div>
				 </div>
			</div>
		</div>
		<div class="col-xl-3 col-md-6">
			<div class="card mini-stat bg-primary text-white">
				<div class="card-body">
					<div class="mb-4">
						<h5 class="font-16 text-uppercase mt-0 text-white-50">Average Price</h5>
						<h4 class="font-500"></h4>
					</div>
				</div>
			</div>
		</div>
	</div>';
	$htm='';
	if(in_array('incoming sms check',$permission)){
		$counter=1;
		$result=mysqli_query($con,"SELECT s.*,c.id cid,c.fname FROM sms_sent s LEFT JOIN customers c ON SUBSTRING(c.mobile,-9,9)=SUBSTRING(s.number,-9,9) 
		WHERE s.type='1' AND s.status='0'");
		while($row=mysqli_fetch_assoc($result)){
			$htm.='<tr id="'.$row['id'].'" table="sms_sent">
				<td class="center">'.$counter++.'</td>
				<td>SMS Received</td>
				<td>'.date('d-m-Y',strtotime($row['messaged'])).'</td>
				<td>['.date('h:i A',strtotime($row['messaged'])).'] '.$row['number'].' : '.$row['message'].(isset($row['fname'])?' ('.$row['fname'].')':'').'</td>
				<td class="center pointer"><i class="fa fa-check fa-1x boolean_check" field="status" value="1"></i></td>
			</tr>';
		}
	}
	if(in_array('check vouchers',$permission)){
		$before=date('Y-m-d',strtotime('-2 weeks'));
		$result=mysqli_query($con,"SELECT v.*,p.date,c.mobile,c.fname,s.total 
		FROM sales_payments p LEFT JOIN sales s ON s.id=p.sales LEFT JOIN customers c ON c.id=s.customer 
		LEFT JOIN sales_vouchers v ON (v.id=p.child AND p.type='6')
		WHERE v.used>0 AND s.date>'$before' AND v.status='1' ORDER BY p.date DESC") or die(mysqli_error($con));
		while($row=mysqli_fetch_assoc($result)){
			$available=0;
			if($row['type']==1)$row['amount'].='%';
			else $available=$row['amount']-$row['used'];
			$date1=date_create($row['issue_date']);
			$date2=date_create($row['date']);
			$diff=date_diff($date1,$date2);
			$htm.='<tr id="'.$row['id'].'" table="sales_vouchers">
				<td class="center">'.$counter++.'</td>
				<td>Vouchers USED</td>
				<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
				<td>['.(trim($row['fname'])<>''?$row['fname'].' : ':'').($row['mobile']<>$row['phone']?$row['mobile'].' - ':'').$row['phone'].'] '
				.'<span class="red">'.$row['amount'].($available>0?' ('.$available.')':'').', '.$row['total'].' ('.$row['min_bill'].')</span> '
				.$row['name'].' ('.$row['reason'].') '.$row['issue_date'].' (<span class="red">'.$diff->format("%R%a days").'</span>)</td>
				<td class="center pointer"><i class="fa fa-check fa-1x boolean_check" field="status" value="2"></i></td>
			</tr>';
		}
		$result=mysqli_query($con,"SELECT v.*,c.fname,c.lname,c.mobile FROM sales_vouchers v LEFT JOIN customers c ON c.id=v.customer 
		WHERE v.status='0' ORDER BY v.issue_date DESC,v.added DESC");
		while($row=mysqli_fetch_assoc($result)){
			$available=0;
			if($row['type']==1)$row['amount'].='%';
			else $available=$row['amount']-$row['used'];
			$date1=date_create(date('Y-m-d'));
			$date2=date_create($row['valid']);
			$diff=date_diff($date1,$date2);
			if($row['type']==1)$amount='<span class="green">10%</span> ';
			else $amount='<span class="'.(($row['min_bill']*0.1)>$row['amount']?'green':'red').'">'.$available.($available<>$row['amount']?' ('.$row['amount'].')':'').' ('.$row['min_bill'].')</span> ';
			$htm.='<tr title="'.$row['code'].'" id="'.$row['id'].'" table="sales_vouchers">
				<td class="center">'.$counter++.'</td>
				<td>Vouchers</td>
				<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
				<td>['.($row['fname']<>''?$row['fname'].' '.$row['lname'].' - ':'').$row['mobile'].'] '.$amount
				.$row['name'].' ('.$row['reason'].') '.$row['valid'].' (<span class="red">'.$diff->format("%R%a days").'</span> : '.$row['code'].')</td>
				<td class="center pointer"><i class="fa fa-check fa-1x boolean_check" field="status" value="1"></i></td>
			</tr>';
		}
	}
	if($htm<>''){
		echo '<div class="row">
				<div class="col-12"><div class="card">
				<div class="card-body table-responsive">
					<table class="customized_table table table-sm" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
					<thead><tr><th>#</th><th>'.language('Type').'</th><th>'.language('Date').'</th><th>'.language('Description').'</th><th>'.language('Act').'</th></tr></thead>
					<tbody>'.$htm.'</tbody></table>
				</div>
			</div>
			</div>
		</div>';
	}
	$result=mysqli_query($con,"SELECT COUNT(*) invoices,SUM(p.paid) paid,
	SUM((SELECT SUM(qty*selling_price) total FROM products_logs WHERE type='0' AND referrer=s.id GROUP BY referrer)+charge) total 
	FROM sales s LEFT JOIN (SELECT SUM(amount) paid,sales FROM sales_payments WHERE date='$date' GROUP BY sales) p ON p.sales=s.id WHERE s.date='$date' AND s.branch='$_SESSION[branch]'")or die(mysqli_error($con));
	$row=mysqli_fetch_assoc($result);
	$total_sales=$row['total'];
	echo '<div class="row">';
	if(in_array('sales summary by customer',$permission)){
		echo'<div class="col-xs-1 col-lg-6"><div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Sales Summary by Customer ('.number_format($row['total'],2).')</h4>
				<div id="sales_summary_by_type" class="e-chart"></div>
			</div>
		</div></div>';
	}
	if(in_array('sales summary by payment',$permission)){
		echo'<div class="col-xs-1 col-lg-6"><div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Sales Summary by Payment</h4>
				<div id="sales_summary_by_payment" class="e-chart"></div>
			</div>
		</div></div>';
	}
	if(in_array('sales summary by category',$permission)){
		echo'<div class="col-xs-1 col-lg-6"><div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Sales Summary by Category</h4>
				<div id="sales_summary_by_category" class="e-chart"></div>
			</div>
		</div></div>';
	}
	if(in_array('profit summary by category',$permission)){
		echo '<div class="col-xs-1 col-lg-6"><div class="card">
			<div class="card-body">
				<h4 class="mt-0 header-title mb-4">Profit by Category</h4>
				<div id="profit_by_category" class="e-chart"></div>
			</div>
		</div></div>';
	}
	echo '</div>';
	// if(in_array('sms summary',$permission)){
		// echo '<div class="row">
			// <div class="col-xs-1 col-lg-6">
			// <div class="card">
				// <div class="card-body">
					// <table class="table table-sm">
					// <thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Nos').'</th>';
					// foreach($sms_mask as $title)echo '<th>'.language(substr($title,0,4)).'</th>';
					// echo '<th>'.language('430').'</th></tr></thead><tbody>';
					
					// $result=mysqli_query($con,"SELECT * FROM sms_sent_summary ORDER BY date DESC LIMIT 0,10");
					// $count=1;
					// while($row=mysqli_fetch_assoc($result)){
						// echo '<tr id="'.$row['id'].'">
						// <td>'.$count++.'</td>
						// <td>'.date('d-m',strtotime($row['date'])).'</td>
						// <td>'.$row['nos'].'</td>';
						// foreach($sms_mask as $title)echo'<td>'.$row[$title].'</td>';
						// echo '<td>'.$row['tele_430'].'</td>
						// </tr>';
					// }
				// echo '</tbody></table>
				// </div>
			// </div>
			// </div>
		// </div>';
	// }
	$date=date('Y-m-d');
	if(in_array('sales summary by customer',$permission)){
		$label='[';
		$data='[';
		$result=mysqli_query($con,"SELECT c.plat,SUM((SELECT SUM(qty*selling_price) total FROM products_logs WHERE type='0' AND referrer=s.id GROUP BY referrer)+charge) total 
		FROM sales s LEFT JOIN customers c ON c.id=s.customer WHERE s.date='$date' AND s.branch='$_SESSION[branch]' GROUP BY c.plat")or die(mysqli_error($con));
		while($row=mysqli_fetch_assoc($result)){
			$label.='"'.$memberships[$row['plat']].'",';
			if($row['total']>0)$data.="{value:$row[total], name:'".$memberships[$row['plat']]."'},";
		}
		$data.=']';
		$label.=']';
		echo '<script type="text/javascript">
		var label_temp='.json_encode($label).';
		var json=JSON.stringify(eval("(" + label_temp + ")"));
		var label=jQuery.parseJSON( json );
		
		var data_temp='.json_encode($data).';
		var json=JSON.stringify(eval("(" + data_temp + ")"));
		var data=jQuery.parseJSON( json );
		</script>';
	}
	if(in_array('sales summary by payment',$permission)){
		$result=mysqli_query($con,"SELECT SUM(p.amount) amount,p.type FROM sales s LEFT JOIN sales_payments p ON p.sales=s.id 
		WHERE s.date='$date' AND s.branch='$_SESSION[branch]' GROUP BY type")or die(file_put_contents($file,"$current_time : ".mysqli_error($con), FILE_APPEND | LOCK_EX));
		$label='[';
		$data='[';
		while($row=mysqli_fetch_assoc($result)){
			if($row['amount']>0){
				$label.='"'.$payment_methods[$row['type']].'",';
				$data.="{value:$row[amount], name:'".$payment_methods[$row['type']]."'},";
				$total_sales-=$row['amount'];
			}
		}
		$label.='"Credit",';
		$data.="{value:$total_sales, name:'Credit'},";
		$data.=']';
		$label.=']';
		echo '<script type="text/javascript">
		var label_temp='.json_encode($label).';
		var json=JSON.stringify(eval("(" + label_temp + ")"));
		var label2=jQuery.parseJSON( json );
		
		var data_temp='.json_encode($data).';
		var json=JSON.stringify(eval("(" + data_temp + ")"));
		var data2=jQuery.parseJSON( json );
		</script>';
	}
	if(in_array('sales summary by category',$permission)){
		$result=mysqli_query($con,"SELECT SUM(l.qty*l.selling_price) sales,c.title FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) LEFT JOIN products p ON p.id=l.product 
		LEFT JOIN product_category pc ON pc.id=p.category LEFT JOIN product_category c ON c.id=pc.parent
		WHERE s.date='$date' AND s.branch='$_SESSION[branch]' GROUP BY c.id");
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
	}
	if(in_array('profit summary by category',$permission)){
		$result=mysqli_query($con,"SELECT SUM(l.qty*(l.selling_price-l.purchased)) profit,c.title FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
		LEFT JOIN products p ON p.id=l.product LEFT JOIN product_category pc ON pc.id=p.category LEFT JOIN product_category c ON c.id=pc.parent
		WHERE s.date='$date' AND s.branch='$_SESSION[branch]' AND l.purchased>0 GROUP BY c.id");
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
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
var dom = document.getElementById("sales_summary_by_type");
var myChart = echarts.init(dom);
var app = {};
option = null;
option = {
    tooltip : {trigger: 'item',formatter: "{a} <br/>{b} : {c} ({d}%)"},
    legend: {orient: 'vertical',left: 'left',data: label},
    color: ['#e74c5e', '#47bd9a', '#06c2de', '#f9d570', '#4090cb'],
    series : [
        {
            name: 'Total sales',type: 'pie',radius : '55%',center: ['50%', '60%'],
            data:data,
            itemStyle: {emphasis: {shadowBlur: 10,shadowOffsetX: 0,shadowColor: 'rgba(0, 0, 0, 0.5)'}}
        }
    ]
};
if (option && typeof option === "object") {
    myChart.setOption(option, true);
}

var dom = document.getElementById("sales_summary_by_payment");
var myChart = echarts.init(dom);
var app = {};
option = null;
option = {
    tooltip : {trigger: 'item',formatter: "{a} <br/>{b} : {c} ({d}%)"},
    legend: {orient: 'vertical',left: 'left',data: label2},
    color: ['#e74c5e', '#47bd9a', '#06c2de', '#f9d570', '#4090cb'],
    series : [
        {
            name: 'Total sales',type: 'pie',radius : '55%',center: ['50%', '60%'],
            data:data2,
            itemStyle: {emphasis: {shadowBlur: 10,shadowOffsetX: 0,shadowColor: 'rgba(0, 0, 0, 0.5)'}}
        }
    ]
};
if (option && typeof option === "object") {
    myChart.setOption(option, true);
}

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
	function after_ajax(){
		$('.select_price').click(function(){
			$('#'+$(this).attr('tr_id')).attr('po',$(this).attr('id'));
			$('#'+$(this).attr('tr_id')).find('.po_check').show();
			$('.mfp-close').trigger('click');
		});
		$('.sms_duplicates').click(function(){
			tr=$(this).parents('tr');
			$.post(remote_file+"/dashboard",{edit:'sms_sent',id:tr.attr('id'),field:'seen',value:1},function(data){
				tr.remove();
			});
		});
	}
</script>
<style>
.green{color: lawngreen;}
.red{color: #ef0000;}
</style>
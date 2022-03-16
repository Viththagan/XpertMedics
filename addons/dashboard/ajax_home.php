<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){//print_r($_POST);
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
	if(isset($_POST['table'])){
		switch($_POST['table']){
			case 'Pending cheques':
				$result=mysqli_query($con,"SELECT c.*,ba.account,b.short,p.date,
				(SELECT s.title FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier WHERE pp.payment=c.payment GROUP BY s.id) supplier,
				(SELECT GROUP_CONCAT(DISTINCT(pc.title)) FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN products_company pc ON pc.id=po.company WHERE pp.payment=c.payment) company
				FROM payments_cheques c LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank 
				LEFT JOIN payments p ON p.id=c.payment
				WHERE p.branch='$_SESSION[branch]' AND c.status=0 AND c.transfer<=CURDATE() ORDER BY c.transfer ASC") or die(mysqli_error($con));
				$edit=0;
				if(isset($_SESSION['soft']['menus']['accounts/cheque_list']['permission']) AND in_array('edit',$_SESSION['soft']['menus']['accounts/cheque_list']['permission']))$edit=1;
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Pending cheques</h4>';
					echo'<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-body table-responsive">
								<table class="customized_table table table-sm" style="width:100%;">
									<thead><tr><th class="center">#</th><th>'.language('Account').'</th><th class="center">'.language('CQ#').'</th>
									<th class="center">'.language('Date').'</th><th>'.language('Transfer').'</th><th>'.language('Description').'</th>
									<th class="right">'.language('Amount').'</th><th></th></tr></thead><tbody>';
									$counter=1;
									$total=0;
									while($row=mysqli_fetch_array($result)){
										similar_text($row['supplier'],$row['company'], $perc);
										$sms=0;
										if($row['transfer']<=$today){
											mysqli_query($con,"SELECT id FROM sms_sent WHERE message LIKE '%$row[amount]%' AND message LIKE '%debit%' AND type='1' AND promo=1");
											$sms=mysqli_affected_rows($con);
										}
										echo'<tr id="'.$row['id'].'" class="'.($sms>0?'success':'').'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td>'.$row['short'].'</td>
											<td class="center">'.$row['number'].'</td>
											<td class="center">'.date('d-m-Y',strtotime($row['date'])).'</td>
											<td class="center">'.date('d-m-Y',strtotime($row['transfer'])).'</td>
											<td class="popup" href="'.$config_url.'remote.php?file=accounts/cheque list&form=sms&id='.$row['id'].'">'.$row['supplier'].($perc<70?'<br/>'.$row['company']:'').'</td>
											<td class="right">'.number_format($row['amount'],2).'</td>
											<td class="center">'.($edit==1?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 transferred">Transferred</button>':'').'</td>
										</tr>';
										$total+=$row['amount'];
									}
									echo '</tbody><tfoot>
										<tr><td colspan="5"></td><td>Total</td><td class="right">'.number_format($total,2).'</td><td></td></tr>
									<tfoot>
								</table>
								</div>
								</div>
							</div>
						</div>
					</div>';
					?>
					<script>
					$('.transferred').click(function(){
						tr=$(this).parents('tr');
						button=$(this);
						$.post(config_url+'remote.php?file=accounts/cheque list',{form_submit:'transferred',id:tr.attr('id')},function(data){
							tr.remove();
						});
					});
					</script>
					<?php
				}
				break;
			case 'return summary':
				$result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,
				(SELECT SuM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id) total
				FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user 
				WHERE s.date>'2021-08-10' AND s.checked=0 AND s.branch='$_SESSION[branch]' 
				AND s.id in (SELECT referrer FROM products_logs WHERE type='0' AND qty<0)") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Return Summary</h4>';
					echo'<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-body table-responsive">
								<table class="customized_table table table-sm" style="width: 100%;">
									<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th>
									<th>'.language('Total').'</th><th>'.language('Staff').'</th><th>'.language('Action').'</th><th></th>
									</tr></thead><tbody>';
									$counter=1;
									while($row=mysqli_fetch_array($result)){
										echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="center">'.($row['invoice']>0?$row['invoice']:$row['id']).'</td>
											<td class="center">'.date('d-m-Y',strtotime($row['date'])).date(' h:i A',strtotime($row['added'])).'</td>
											<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
											<td>'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
											<td class="right">'.Number_format($row['total'],2).'</td>
											<td class="right">'.$row['first_name'].'</td>
											<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/dashboard&form=view_invoice&id='.$row['id'].'">View</button></td>
											<td class="center pointer">'.($_SESSION['soft']['user_data']['user_level']==0?'<i class="fa fa-check fa-1x return_checked"></i>':'').'</td>
										</tr>';
									}
								echo '</tbody></table>
								</div>
								</div>
							</div>
						</div>
					</div>';
					?>
					<script>
					$('.return_checked').click(function(){
						tr=$(this).parents('tr');
						$.post(remote_file+"/dashboard",{popup_form_submit:'return_checked',id:tr.attr('id')},function(data){
							tr.remove();
						});
					});
					</script>
					<?php
				}
				break;
			case 'pending sales invoices':
				$result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,
				(SELECT COUNT(*) FROM products_logs WHERE type='0' AND referrer=s.id) logs,(SELECT COUNT(*) FROM products_logs_temp WHERE type='0' AND referrer=s.id) logs_temp,
				(SELECT SUM(amount) FROM sales_payments WHERE sales=s.id) paid
				FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_points p ON p.sale=s.id 
				WHERE s.closed<=0 AND s.date>'2021-08-10' AND s.date<'$date' AND s.branch='$_SESSION[branch]'") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Pending Sales Invoices</h4>';
					echo'<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-body table-responsive">
								<table class="customized_table table table-sm" table="sales" style="width: 100%;">
									<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th>
									<th>'.language('Count').'</th><th>'.language('Staff').'</th><th>'.language('Action').'</th>
									</tr></thead><tbody>';
									$counter=1;
									while($row=mysqli_fetch_array($result)){
										if($row['logs_temp']>0 OR $row['logs']>0 OR $row['paid']>0){
											echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="pending_invoice">
												<td class="center">'.$counter++.'</td>
												<td class="center">'.($row['invoice']>0?$row['invoice']:$row['id']).'</td>
												<td class="center">'.date('d-m-Y',strtotime($row['date'])).date(' h:i A',strtotime($row['added'])).'</td>
												<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
												<td>'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
												<td class="right">'.Number_format($row['logs_temp'],2).'</td>
												<td class="right">'.$row['first_name'].'</td>
												<td class="center"><a href="'.$branch_url.'#sales/pos\\'.$row['id'].'"><button type="button" class="btn btn-primary waves-effect waves-light">Edit</button></td>
											</tr>';
										}
										else {
											delete_invoice($row['id']);
										}
									}
								echo '</tbody></table>
								</div>
							</div>
						</div>
					</div>';
				}
				break;
			case 'overstock check':
				// if(file_exists('overstock_min_amount'))$mrp=file_get_contents('overstock_min_amount');
				// else $mrp=1000;
				$yesterday=date('Y-m-d',strtotime('-1 days'));
				$result=mysqli_query($con,"SELECT p.*,(SELECT l.balance FROM products_logs l WHERE l.product=p.id AND l.branch='$_SESSION[branch]' ORDER BY l.id DESC LIMIT 0,1) stock,
				(SELECT SUM(l.qty) FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
					WHERE l.product=p.id AND l.type=0 AND s.branch='$_SESSION[branch]' AND s.date BETWEEN DATE(NOW() - INTERVAL pc.visits day) AND DATE(NOW() - INTERVAL 1 day)) running 
				FROM products p LEFT JOIN products_company pc ON p.company=pc.id HAVING stock>running ORDER BY p.mrp DESC LIMIT 0,25") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Overstock</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">
							<table class="customized_table table table-sm" table="payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Product').'</th><th class="right">'.language('MRP').'</th><th class="right">'.language('Stock').'</th><th class="right">'.language('Running').'</th>
							<th class="right">'.language('Vary').'</th></tr></thead><tbody>';
							$counter=1;
							$min_mrp='';
							while($row=mysqli_fetch_array($result)){
								if($row['mrp']<$min_mrp OR $min_mrp=='')$min_mrp=$row['mrp'];
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['title'].'<br/>'.$row['ui_code'].'</td>
									<td class="right">'.$row['mrp'].'</td>
									<td class="right">'.$row['stock'].'</td>
									<td class="right">'.$row['running'].'</td>
									<td class="right">'.($row['stock']-$row['running']).'</td>
								</tr>';
							}
							if($_SESSION['soft']['user_data']['user_level']==0)file_put_contents('overstock_min_amount',$min_mrp);
						echo '</tbody></table>
						</div>
						</div>
						</div>
					</div>';
				}
				break;
			case 'payments check':
				if(file_exists('payment_check_min_date'))$from=file_get_contents('payment_check_min_date');
				else $from='2021-09-01';
				$yesterday=date('Y-m-d',strtotime('-1 days'));
				$result=mysqli_query($con,"SELECT p.*,s.title supplier,s.id sid,b.short bank,c.number,c.transfer,u.short,u2.first_name staff,sa.year,sa.month,br.title branch_title
				FROM payments p LEFT JOIN payments_cheques c ON c.payment=p.id 
				LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank LEFT JOIN po_payments pp ON p.id=pp.payment 
				LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier LEFT JOIN users u ON u.id=p.user LEFT JOIN salary_staff ss ON (p.category=2 AND ss.id=p.referrer)
				LEFT JOIN users u2 ON u2.id=ss.staff LEFT JOIN salary sa ON sa.id=ss.salary LEFT JOIN branches br ON br.id=p.branch
				WHERE p.checked='0' AND p.added>='$from' AND p.branch='$_SESSION[branch]' GROUP BY p.id LIMIT 0,100") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Payments to Verify</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body table-responsive">
							<table class="customized_table table table-sm" table="payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th>'.($_SESSION['branches']>1?'<th>'.language('Branch').'</th>':'').'<th>'.language('Date').'</th><th>'.language('Method').'</th><th>'.language('Type').'</th><th>'.language('Description').'</th>
							<th class="right">'.language('Staff').'</th><th class="right">'.language('Amount').'</th><th></th>
							</tr></thead><tbody>';
							$counter=1;
							$min_date='';
							while($row=mysqli_fetch_array($result)){
								if($row['date']<$min_date OR $min_date=='')$min_date=$row['date'];
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.(($row['sid']=='' AND $row['category']==1)?'warning':'').'">
									<td class="center">'.$counter++.'</td>
									'.($_SESSION['branches']>1?'<td>'.$row['branch_title'].'</td>':'').'
									<td>'.$row['date'].'</td>
									<td>'.$payment_methods[$row['type']].'</td>
									<td>'.$payments_type[$row['category']].'</td>
									<td class="popup" href="'.$remote_file.'/dashboard&form=payments&id='.$row['id'].'">
									'.($row['supplier']<>''?'['.$row['sid'].'] '.$row['supplier']:$row['description']).
									($row['type']==2?'<br/>['.$row['bank'].'] '.$row['number'].' - '.$row['transfer']:'').($row['category']==2?'<br/>['.$row['staff'].'] '.$row['year'].'-'.$row['month']:'').'</td>
									<td class="right" field="amount">'.number_format($row['amount'],2).'</td>
									<td class="right">'.$row['short'].'</td>
									<td class="center pointer">'.($_SESSION['soft']['user_data']['user_level']==0?'<i class="fa fa-check fa-1x payment_checked"></i>':'').'</td>
								</tr>';
							}
							if($_SESSION['soft']['user_data']['user_level']==0)file_put_contents('payment_check_min_date',$min_date);
						echo '</tbody></table>
						</div>
						</div>
						</div>
					</div>';
					?>
					<script>
					$('.payment_checked').click(function(){
						tr=$(this).parents('tr');
						$.post(remote_file+"/dashboard",{popup_form_submit:'payment_checked',id:tr.attr('id')},function(data){
							tr.remove();
						});
					});
					</script>
					<?php
				}
				break;
			case 'data entry errors':
				$from=file_get_contents('po_check_min_date');
				$yesterday=date('Y-m-d',strtotime('-1 days'));
				$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,p.category,po.date,u.short,pc.visits,pc.credit,
				(SELECT SUM(l.qty) FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE l.product=p.id AND l.type=0 AND s.date BETWEEN DATE_ADD('$yesterday', INTERVAL -if(pc.visits>0,pc.visits,7) DAY) AND '$yesterday') running,		
				(SELECT SUM(l.qty) FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE l.product=p.id AND l.type=0 AND s.date BETWEEN DATE_ADD('$yesterday', INTERVAL -if(pc.credit>0,pc.credit,7) DAY) AND '$yesterday') running2		
				FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN products_company pc ON pc.id=p.company LEFT JOIN po ON (po.id=l.referrer AND l.type=1) LEFT JOIN users u ON u.id=l.user
				WHERE l.checked='0' AND l.type=1 AND l.status='1' AND l.added>='$from' AND po.branch='$_SESSION[branch]' LIMIT 0,100") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">GRN Errors/Vary</h4>';
					echo '<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-body table-responsive">
								<table class="customized_table table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Title').'</th>
							<th class="right">'.language('Purchase').'</th><th class="right">'.language('Cost').'</th><th class="right">'.language('MRP').'</th>
							<th class="right">'.language('Gold').'</th><th class="right">%</th><th class="right">'.language('Qty').'</th><th class="right">'.language('UIC').'</th><th class="right">'.language('Free').'</th>
							<th class="right">'.language('Stock').'</th><th>'.language('Visit').'</th><th>'.language('Credit').'</th><th></th><th></th><th></th></tr></thead><tbody>';
							$counter=1;
							$min_date='';
							while($row=mysqli_fetch_array($result)){
								if($row['date']<$min_date OR $min_date=='')$min_date=$row['date'];
								$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
								$cost=0;
								if($qty>0)$cost=number_format(($row['purchased']*$row['qty'])/$qty,2,'.','');
								if($cost==$row['mrp']){
									$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
									FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
									$ro=mysqli_fetch_assoc($re);
									$diff=$ro['total']-$ro['tot'];
									if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
										$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
										$cost=$row['mrp']*$discounted_price;
										$cost=number_format((($cost*$row['qty'])/$qty),2,'.','');
									}
								}
								$percentage=0;
								if($row['mrp']>0)$percentage=number_format((($row['mrp']-$cost)/$row['mrp'])*100,2);
								$alert=array();
								if($percentage>=30)$alert[1]=1;
								
								$htm="";
								$result1 = mysqli_query($con, "SELECT * FROM products_logs WHERE product='$row[product]' AND type='1' AND qty>0 AND id<'$row[id]' ORDER BY id DESC LIMIT 0,1") or die(mysqli_error($con));
								if(mysqli_affected_rows($con)==1){
									$old=$result1->fetch_assoc();
									$unit_price_old=($old['purchased']*$old['qty'])/(($old['uic']>0?($old['qty']*$old['uic']):$old['qty'])+$old['free']);
									$margin_old=number_format((($old['mrp']-number_format($unit_price_old,2,'.',''))/$old['mrp'])*100,2);
									$htm.='<tr class="text-muted" pid="pl_'.$row['id'].'">
										<td>'.$counter.'</td><td>'.date('Y-m-d',strtotime($old['added'])).'</td><td>'.$old['referrer'].'</td>
										<td class="right">'.($old['purchased']+0).'</td><td class="right">'.number_format($unit_price_old,2).'</td>
										<td class="right">'.$old['mrp'].'</td>
										<td class="right">'.$old['selling_price'].'</td>
										<td class="right">'.$margin_old.'</td>
										<td class="right">'.($old['qty']+0).'</td><td class="right">'.($old['uic']>0?($old['uic']+0):'').'</td><td class="right">'.$old['free'].'</td>
										<td></td><td></td><td></td><td></td><td></td><td></td>
									</tr>';
									if($percentage<>$margin_old AND ($old['mrp']==$row['mrp'] OR abs($percentage-$margin_old)>1))$alert[2]=2;
								}
								if($row['mrp']<>$row['selling_price'] AND $row['selling_price']>0){
									$percentage.=' - '.number_format((($row['selling_price']-$cost)/$row['mrp'])*100,2);
								}
								// if($row['balance']<$qty)$alert[0]=0;
								// if($row['balance']<>$qty){
									$max_stock=($row['running2']*($row['running2']>50?1.1:($row['running2']>20?1.2:($row['running2']>10?1.3:($row['running2']>5?1.4:($row['running2']>2?1.7:2))))));
									
									$max_stock1=($row['running']*($row['running']>50?1.1:($row['running']>20?1.2:($row['running']>10?1.3:($row['running']>5?1.4:($row['running']>2?1.7:2))))));
									$min_stock=($row['running']*($row['running']>50?0.9:($row['running']>20?0.8:($row['running']>10?0.7:($row['running']>5?0.6:($row['running']>2?0.5:0))))));
									if($min_stock==0)$min_stock=1;
									// if($max_stock<$row['balance'])$alert[5]=5;
									// else if($min_stock>$row['balance'])$alert[6]=6;
									
									// if($max_stock1<$row['balance'])$alert[3]=3;
									// else if($min_stock>$row['balance'])$alert[4]=4;
								// }
								$update=0;
								if($row['qty']==0 AND $row['free']>0)$update=1;
								if($row['date']<'2021-08-01' AND !isset($alert[2]))$update=1;
								if(in_array($row['category'],array(55,52,53)) AND $percentage<20 AND $percentage>8)$update=1;
								if(sizeof($alert)==0 OR $update==1){
									mysqli_query($con,"UPDATE products_logs SET checked='1' WHERE id='$row[id]'") or die(mysqli_error($con));
								}
								else {
									echo '<tr id="'.$row['id'].'" style="cursor:pointer;" pid="pl_'.$row['id'].'">
										<td class="center popup" href="'.$remote_file.'/dashboard&form=product&id='.$row['product'].'&type=1">'.$counter++.'</td>
										<td>'.$row['date'].'</td>
										<td class="popup" href="'.$remote_file.'/dashboard&form=product_po&id='.$row['id'].'&product='.$row['product'].'">'.$row['title'].'<br/>'.$row['ui_code'].'</td>
										<td class="right editable" field="purchased">'.number_format($row['purchased'],2).'</td>
										<td class="right">'.number_format($cost,2).'</td>
										<td class="right editable" field="mrp">'.$row['mrp'].'</td>
										<td class="right editable" field="selling_price">'.$row['selling_price'].'</td>
										<td class="right '.(in_array(1,$alert)?'warning':'').' '.(in_array(2,$alert)?'warning2':'').'">'.$percentage.'</td>
										<td class="right editable" field="qty">'.($row['qty']+0).'</td>
										<td class="right editable" field="uic">'.($row['uic']>0?($row['uic']+0):'').'</td>
										<td class="right editable" field="free">'.$row['free'].'</td>
										<td class="right '.(in_array(0,$alert)?'warning':'').'">'.($row['balance']+0).'</td>
										<td class="right '.(in_array(3,$alert)?'warning':'').' '.(in_array(4,$alert)?'warning2':'').'">'.($row['running']+0).'</td>
										<td class="right '.(in_array(5,$alert)?'warning':'').' '.(in_array(6,$alert)?'warning':'').'">'.($row['running2']+0).'</td>
										<td class="center">'.$row['short'].'</td>
										<td class="center"><a class="btn btn-primary waves-effect waves-light" target="_blank" href="'.$branch_url.'#purchases/new%20purchase\\'.$row['referrer'].'" role="button">PO</a></td>
										<td class="center pointer">'.($_SESSION['soft']['user_data']['user_level']==0?'<i class="fa fa-check fa-1x po_checked"></i>':'').'</td>
									</tr>'.$htm;
								}// ('.($row['visits']>0?$row['visits']:7).', '.$min_stock.' - '.$max_stock1.') ('.($row['credit']>0?$row['credit']:7).', '.$min_stock.' - '.$max_stock.')
							}
							if($_SESSION['soft']['user_data']['user_level']==0)file_put_contents('po_check_min_date',$min_date);
						echo '</tbody></table>
						</div>
						</div>
						</div>
						</div>
					</div>';
					?>
					<script>
					$('.po_checked').click(function(){
						tr=$(this).parents('tr');
						$.post(remote_file+"/dashboard",{popup_form_submit:'po_checked',id:tr.attr('id')},function(data){
							$('tr[pid="pl_'+tr.attr('id')+'"]').remove();
						});
					});
					</script>
					<?php
				}
				break;
			case 'repack approvals':
				if($_SESSION['soft']['settings']['repack_approval']==1){
					if(file_exists('overstock_min_amount'))$from=file_get_contents('packing_check_min_date');
					else $from='2021-01-01';
					$yesterday=date('Y-m-d',strtotime('-1 days'));
					$visit=date('Y-m-d',strtotime('-14 days'));
					$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,p.category,y.date,u.short,
					(SELECT SUM(l.qty) FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE l.product=p.id AND l.type=0 AND s.date BETWEEN '$visit' AND '$yesterday') running	
					FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN yura_batch_history y ON (y.id=l.referrer AND l.type=3) LEFT JOIN users u ON u.id=l.user
					WHERE l.checked='0' AND l.type=3 AND l.status='1' AND l.added>='$from'") or die(mysqli_error($con));
					if(mysqli_affected_rows($con)>0){
						echo '<h4 class="page-title">Repack Approvals</h4>';
						echo '<div class="row">
							<div class="col-12"><div class="card">
							<div class="card-body table-responsive">
								<table class="customized_table table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
								<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Title').'</th>
								<th class="right">'.language('Purchase').'</th><th class="right">'.language('MRP').'</th>
								<th class="right">'.language('Gold').'</th><th class="right">%</th><th class="right">'.language('Qty').'</th>
								<th class="right">'.language('Stock').'</th><th>'.language('Running (14)').'</th><th></th><th></th></tr></thead><tbody>';
								$counter=1;
								$min_date='';
								while($row=mysqli_fetch_array($result)){
									if($row['date']<$min_date OR $min_date=='')$min_date=$row['date'];
									$percentage=number_format((($row['mrp']-$row['purchased'])/$row['mrp'])*100,2);
									$alert=array();
									if($percentage>=30)$alert[1]=1;
									
									$htm="";
									$result1 = mysqli_query($con, "SELECT * FROM products_logs WHERE product='$row[product]' AND type='3' AND qty>0 AND id<'$row[id]' ORDER BY id DESC LIMIT 0,1") or die(mysqli_error($con));
									if(mysqli_affected_rows($con)==1){
										$old=$result1->fetch_assoc();
										$margin_old=number_format((($old['mrp']-number_format($old['purchased'],2,'.',''))/$old['mrp'])*100,2);
										$htm.='<tr class="text-muted" pid="yura_'.$row['referrer'].'">
											<td>'.$counter.'</td><td></td><td>'.$old['added'].'</td>
											<td class="right">'.($old['purchased']+0).'</td>
											<td class="right">'.$old['mrp'].'</td>
											<td class="right">'.$old['selling_price'].'</td>
											<td class="right">'.$margin_old.'</td>
											<td class="right">'.($old['qty']+0).'</td>
											<td></td><td></td><td></td><td></td>
										</tr>';
										if($percentage<>$margin_old AND ($old['mrp']==$row['mrp'] OR abs($percentage-$margin_old)>1))$alert[2]=2;
									}
									if($row['mrp']<>$row['selling_price'] AND $row['selling_price']>0){
										$percentage.=' - '.number_format((($row['selling_price']-$row['purchased'])/$row['mrp'])*100,2);
									}
									// if($row['balance']<>$qty){
										$max_stock=($row['running']*($row['running']>50?1.1:($row['running']>20?1.2:($row['running']>10?1.3:($row['running']>5?1.4:($row['running']>2?1.7:2))))));
										$min_stock=($row['running']*($row['running']>50?0.9:($row['running']>20?0.8:($row['running']>10?0.7:($row['running']>5?0.6:($row['running']>2?0.5:0))))));
										if($max_stock<$row['balance'])$alert[3]=3;
										else if($min_stock>$row['balance'])$alert[4]=4;
									// }
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;" pid="yura_'.$row['referrer'].'" referrer="'.$row['referrer'].'">
											<td class="center popup" href="'.$remote_file.'/dashboard&form=product&id='.$row['product'].'&type=1">'.$counter++.'</td>
											<td class="popup" href="'.$remote_file.'/dashboard&form=yura_view&id='.$row['referrer'].'">'.$row['date'].'</td>
											<td class="popup" href="'.$remote_file.'/dashboard&form=product_po&id='.$row['id'].'&product='.$row['product'].'">'.$row['title'].'<br/>'.$row['ui_code'].'</td>
											<td class="right editable" field="purchased">'.number_format($row['purchased'],2).'</td>
											<td class="right editable" field="mrp">'.$row['mrp'].'</td>
											<td class="right editable" field="selling_price">'.$row['selling_price'].'</td>
											<td class="right '.(in_array(1,$alert)?'warning':'').' '.(in_array(2,$alert)?'warning2':'').'">'.$percentage.'</td>
											<td class="right editable" field="qty">'.($row['qty']+0).'</td>
											<td class="right">'.($row['balance']+0).'</td>
											<td class="right '.(in_array(3,$alert)?'warning':'').' '.(in_array(4,$alert)?'warning2':'').'">'.($row['running']+0).'</td>
											<td class="center">'.$row['short'].'</td>
											<td class="center pointer">'.($_SESSION['soft']['user_data']['user_level']==0?'<i class="fa fa-check fa-1x yura_checked"></i>':'').'</td>
										</tr>'.$htm;
								}
								if($_SESSION['soft']['user_data']['user_level']==0)file_put_contents('packing_check_min_date',$min_date);
							echo '</tbody></table>
							</div>
							</div>
							</div>
						</div>';
						?>
						<script>
						$('.yura_checked').click(function(){
							tr=$(this).parents('tr');
							$.post(remote_file+"/dashboard",{popup_form_submit:'yura_checked',id:tr.attr('referrer')},function(data){
								$('tr[pid="yura_'+tr.attr('referrer')+'"]').remove();
							});
						});
						</script>
						<?php
					}
				}
				break;
			case 'data entry missing':
				$from=date('Y-08-01');
				$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,s.date 
				FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
				WHERE l.purchased='0' AND l.type=0 AND l.added>'$from' AND s.branch='$_SESSION[branch]' ORDER BY l.added DESC LIMIT 0,100") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">GRN Not Found</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body table-responsive">
							<table class="customized_table table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Code').'</th><th>'.language('Title').'</th><th>'.language('Date').'</th>
							<th>'.language('Purchase').'</th><th>'.language('Price').'</th><th>'.language('Gold').'</th><th>'.language('Qty').'</th><th></th></tr></thead><tbody>';
							$counter=1;
							while($row=mysqli_fetch_array($result)){
								// $result1=mysqli_query($con,"SELECT * FROM products_logs WHERE mrp=$row[mrp] AND type=1 AND product='$row[product]' ORDER BY id DESC LIMIT 0,1") or die(mysqli_error($con));
								// if(mysqli_affected_rows($con)==1){
									// $row1=mysqli_fetch_array($result1);
									// $qty=($row1['uic']>0?($row1['uic']*$row1['qty']):$row1['qty'])+$row1['free'];
									// $cost=($row1['purchased']*$row1['qty'])/$qty;
									// $margin=(($row['mrp']-$cost)/$row['mrp'])*100;
									// if($cost>0 AND $cost<$row['mrp'] AND $margin<30)mysqli_query($con,"UPDATE products_logs SET purchased='$cost' WHERE id='$row[id]'");
								// }else {
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td class="popup" href="'.$remote_file.'/dashboard&form=product&id='.$row['product'].'&type=1">'.$row['ui_code'].'</td>
									<td>'.$row['date'].'</td>
									<td class="popup" href="'.$remote_file.'/dashboard&form=product_po&id='.$row['id'].'&product='.$row['product'].'">'.$row['title'].'</td>
									<td class="right editable" field="purchased">0</td>
									<td class="right">'.$row['mrp'].'</td>
									<td class="right">'.$row['selling_price'].'</td>
									<td class="right">'.$row['qty'].'</td>
									<td class="center pointer"><i class="fa fa-check fa-1x po_check hide"></i></td>
								</tr>';
								// }
							}
						echo '</tbody></table>
						</div>
						</div>
						</div>
					</div>';
					?>
					<script>
					$('.po_check').click(function(){
						if($(this).parents('tr').attr('po')>0){
							tr=$(this).parents('tr');
							$.post(remote_file+"/dashboard",{popup_form_submit:'purchase_price',id:tr.attr('id'),po:tr.attr('po')},function(data){
								ids=jQuery.parseJSON(data);
								$.each( ids, function( key, value ) {
									$('#'+value).remove();
								});
								// tr.remove();
							});
						}
					});
					</script>
					<?php
				}
				break;
			case 'loss sales':
				$from=date('Y-08-01');
				$result=mysqli_query($con,"SELECT l.*,p.ui_code,p.title,s.date 
				FROM products_logs l LEFT JOIN products p ON p.id=l.product LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
				WHERE l.purchased>=l.selling_price AND l.type=0 AND l.checked='0' AND l.added>'$from' AND s.branch='$_SESSION[branch]'") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Sales Below Cost</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body table-responsive">
							<table class="customized_table table table-sm" table="products_logs" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Code').'</th><th>'.language('Title').'</th><th>'.language('Date').'</th>
							<th>'.language('Purchase').'</th><th>'.language('Price').'</th><th>'.language('Gold').'</th><th>%</th><th>%</th><th>'.language('Qty').'</th><th></th></tr></thead><tbody>';
							$counter=1;
							while($row=mysqli_fetch_array($result)){
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td class="popup" href="'.$remote_file.'/dashboard&form=product&id='.$row['product'].'&type=1">'.$row['ui_code'].'</td>
									<td>'.$row['date'].'</td>
									<td class="popup" href="'.$remote_file.'/dashboard&form=product_po&id='.$row['id'].'&product='.$row['product'].'">'.$row['title'].'</td>
									<td class="right editable" field="purchased">'.$row['purchased'].'</td>
									<td class="right">'.$row['mrp'].'</td>
									<td class="right">'.$row['selling_price'].'</td>
									<td class="right">'.number_format((($row['mrp']-$row['selling_price'])/$row['mrp'])*100,2).'%</td>
									<td class="right">'.number_format((($row['purchased']-$row['selling_price'])/$row['purchased'])*100,2).'%</td>
									<td class="right">'.$row['qty'].'</td>
									<td class="center pointer"><i class="fa fa-check fa-1x si_check"></i></td>
								</tr>';
							}
						echo '</tbody></table>
						</div>
						</div>
						</div>
					</div>';
					?>
					<script>
					$('.si_check').click(function(){
						tr=$(this).parents('tr');
						$.post(remote_file+"/dashboard",{edit:'products_logs',id:tr.attr('id'),field:'checked',value:1},function(data){
							ids=jQuery.parseJSON(data);
							tr.remove();
							$.each( ids, function( key, value ) {
								$('#'+value).remove();
							});
						});
					});
					</script>
					<?php
				}
				break;
			case 'receivables':
				// $result=mysqli_query($con,"SELECT s.*,c.mobile,c.fname,c.lname,SUM(l.qty*l.selling_price) si_total,(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid 
				// FROM sales s LEFT JOIN products_logs l ON (s.id=l.referrer AND l.type=0) LEFT JOIN customers c ON c.id=s.customer 
				// WHERE s.closed=1 AND s.date>'2021-01-01' GROUP BY s.id ORDER BY s.added DESC LIMIT 0,10")or die(mysqli_error($con));
				$result=mysqli_query($con,"SELECT s.*,c.mobile,c.fname,c.lname,
				(SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0) si_total,
				(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid 
				FROM sales s LEFT JOIN customers c ON c.id=s.customer 
				WHERE s.closed<>2 AND s.date>'2021-01-01' AND s.branch='$_SESSION[branch]' HAVING (si_total-ifnull(paid,0))>1 ORDER BY s.added DESC")or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Credit Sales/Receivables</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
							<div class="card-body table-responsive">
								<table class="customized_table table table-sm" table="sales" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>ID</th><th>'.language('Date').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th>
							<th>'.language('Total').'</th><th>'.language('Paid').'</th><th>'.language('Receivable').'</th>
							<th></th><th></th><th></th>
							</tr></thead><tbody>';
							$count=1;
							while($row=mysqli_fetch_assoc($result)){
								$receivable=($row['si_total']-$row['paid']);
								if($receivable>1){
									echo '<tr id="'.$row['id'].'">
									<td>'.$count++.'</td>
									<td>'.$row['id'].'</td>
									<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
									<td>'.date('h:i A',strtotime($row['added'])).'</td>
									<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
									<td class="right">'.number_format($row['si_total'],2).'</td>
									<td class="right">'.number_format($row['paid'],2).'</td>
									<td class="right">'.number_format($receivable,2).'</td>
									<td class="center"><i class="mdi mdi mdi-view-list invoice_view" style="cursor:pointer;" data-toggle="modal" data-target=".invoice_data"></i></td>
									<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$config_url.'remote.php?file=sales/sales&form=add_payment&id='.$row['id'].'&amount='.($row['si_total']-$row['paid']).'">Add Payment</button></td>
									<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$config_url.'remote.php?file=sales/sales&form=view_invoice&id='.$row['id'].'">View</button></td>
									</tr>';
								}
								else mysqli_query($con,"UPDATE sales SET closed=2 WHERE id='$row[id]'");
							}
						echo '</tbody></table>
						</div>
						</div>
					</div>
					</div>';
					?>
					<script>
					$('.invoice_view').click(function(){
						$('#invoice_data').html('');
						$.get(config_url+"remote.php?file=sales/sales&form=invoice_data",{id:$(this).parents('tr').attr('id')},function(data){
							$('#invoice_data').html(data);
							$.agoy_init();
						});
					});
					</script>
					<div class="modal fade invoice_data" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="invoice_data"></div>
			</div>
		</div>
	</div>
</div>
					<?php
				}
				break;
			case 'uncategorized':
				$result=mysqli_query($con,"SELECT p.id,p.title,p.mrp,SUM(l.qty*l.selling_price) sales FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0)
				LEFT JOIN products p ON p.id=l.product WHERE s.date='$date' AND (p.category is null OR p.category=0) GROUP BY p.id ORDER BY sales DESC LIMIT 0,10");
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">Uncategorized Products</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body table-responsive">
							<table class="customized_table table table-sm" table="products" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>ID</th><th>'.language('Title').'</th><th>'.language('MRP').'</th><th>'.language('Sales').'</th></tr></thead><tbody>';
							$count=1;
							while($row=mysqli_fetch_assoc($result)){
								echo '<tr id="'.$row['id'].'" table="withdrawal">
								<td>'.$count++.'</td>
								<td>'.$row['id'].'</td>
								<td class="popup_select" value="" data-toggle="modal" data-target=".category">'.$row['title'].'</td>
								<td class="right">'.number_format($row['mrp'],2).'</td>
								<td class="right">'.number_format($row['sales'],2).'</td>
								</tr>';
							}
						echo '</tbody></table>
						</div>
						</div>
					</div>
					<div class="modal fade category" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
						 <div class="modal-dialog modal-dialog-centered">
							<div class="modal-content">
								<div class="modal-body">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<div class="form-group">
										<label>Category</label>
										<div><select class="form-control select" id="category" required>
											<option>Select</option>';
											$category=mysqli_query($con,"SELECT * FROM product_category WHERE level='1' ORDER BY title ASC");
											while($row=mysqli_fetch_assoc($category))echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
										echo '</select>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					</div>';
				}
				break;
			case 'sms duplicates':
				$result=mysqli_query($con,"SELECT id,date,number,message,COUNT(*) c FROM sms_sent WHERE status='1' AND seen=0 AND type=0 and date>'2021-08-01' 
				AND number NOT IN('0773518574','0769405186') GROUP BY number,date,message having c>1 ORDER BY date ASC");
				if(mysqli_affected_rows($con)>0){
					echo '<h4 class="page-title">SMS Duplicates</h4>';
					echo '<div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body table-responsive">
							<table class="customized_table table table-sm" table="sms_sent" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>Number</th><th>'.language('Message').'</th><th>'.language('Count').'</th></tr></thead><tbody>';
							$count=1;
							while($row=mysqli_fetch_assoc($result)){
								echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
								<td>'.$count++.'</td>
								<td class="center">'.$row['date'].'</td>
								<td class="center">'.$row['number'].'</td>
								<td class="popup" href="'.$remote_file.'/dashboard&form=sms_duplicates&id='.$row['id'].'">'.$row['message'].'</td>
								<td class="right">'.$row['c'].'</td>
								</tr>';
							}
						echo '</tbody></table>
						</div>
						</div>
					</div>
					</div>';
					
				}
				break;
		}
	}
?>
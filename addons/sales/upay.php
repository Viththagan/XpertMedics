<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// echo '<pre>';
	$commission=150;
	$bill_over=1500;
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['select'])){
		switch($_POST['select']){
			case 'select_accounts' :
				$data=array();
				$result=mysqli_query($con,"SELECT count(*) acc FROM upay WHERE customer='$_POST[id]' GROUP BY date ORDER BY acc DESC LIMIT 0,1");
				$row=mysqli_fetch_array($result);
				$data['qty']=$row['acc'];
				$date=date('Y-m-d');
				mysqli_query($con,"SELECT * FROM upay_payments WHERE customer='$_POST[id]' AND date='$date'");
				if(mysqli_affected_rows($con)==0)$data['date']=date('d-m-Y');
				else $data['date']=date('d-m-Y',strtotime('tomorrow'));
				echo json_encode($data);
				break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'customers' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">';
										if(isset($_GET['type']) AND $_GET['type']=='bulk'){
											echo '<div class="form-group"><label>Prefix</label><div><input type="number" class="x-large form-control" name="prefix"></div></div>';
											echo '<div class="form-group"><label>ID</label><div><textarea name="ids" class="x-large form-control"></textarea></div></div>';
										}
										else echo '<input type="hidden" name="id" value="'.$_GET['id'].'"/>';
										echo '<div class="form-group"><label>Commission</label><div><input type="text" class="x-large form-control" name="comm"></div></div>
										<div class="form-group"><label>Customer</label><div><input type="text" class="x-large form-control customer" name="mobile"></div></div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="add_customer_to_invoice"/>
												<button type="submit" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Update</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>';
				break;
			case 'customers_add' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Date</label><div><input type="text" class="x-large form-control date" name="date" value="'.date('d-m-Y',strtotime('+1 day')).'"></div></div>
										<div class="form-group"><label>Customer</label>
										<div><select class="form-control select" name="customer[]" id="customer">';
											$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile FROM upay u LEFT JOIN customers c ON c.id=u.customer WHERE u.customer>0 AND u.sales is NULL GROUP BY u.customer") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['customer'].'">'.$row['fname'].' '.$row['mobile'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Customer 2</label>
										<div><select class="form-control select" name="customer[]">';
											$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile FROM upay u LEFT JOIN customers c ON c.id=u.customer WHERE u.customer>0 AND u.sales is NULL GROUP BY u.customer") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['customer'].'">'.$row['fname'].' '.$row['mobile'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Time</label>
										<div><input type="text" class="x-large form-control" name="min" id="min"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="add_customer_time"/>
												<button type="submit" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Add</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>';
				// <div><div class="input-daterange input-group">
											// <input type="time" class="form-control" name="start" />
											// <input type="time" class="form-control" name="end" />
										// </div></div>
				break;
			case 'select_customer':
				$q = strtolower($_GET["term"]);
				if (get_magic_quotes_gpc()) $q = stripslashes($q);
				$result1=mysqli_query($con,"SELECT * FROM customers WHERE (fname LIKE '%$q%' OR mobile LIKE '%$q%' OR remind LIKE '%$q%' OR street LIKE '%$q%') AND lname LIKE '%upay%' LIMIT 0,10");
				$result = array();
				while($row=mysqli_fetch_array($result1)){
					array_push($result, array("id"=>$row['id'], "label"=>$row['mobile'].' : '.$row['fname'].' '.$row['lname'], "value" =>$row['mobile']));
				}
				echo json_encode($result);
				break;
			case 'add_payment' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Amount</label>
											<div><input type="text" class="x-large form-control" name="amount"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="id" value="'.$_GET['id'].'"/>
												<input type="hidden" name="popup_form_submit" value="add_payment"/>
												<button type="submit" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Add</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_POST['popup_form_submit'])){
		switch($_POST['popup_form_submit']){
			case 'add_payment':
				$date=date('Y-m-d');
				mysqli_query($con,"INSERT INTO upay_payments_paid(date,payment,amount) VALUES('$date','$_POST[id]','$_POST[amount]')");
				$result=mysqli_query($con,"SELECT u.*,c.mobile,(SELECT SUM(amount) FROM upay_payments_paid WHERE payment=u.id) paid2 FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer WHERE u.id='$_POST[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				if($row['status']==3){
					echo 'Already Paid!';
				}
				else {
					$balance=$row['paid']-$row['paid2'];
					$msg=$row['date']."\nDelivery Order Total: $row[paid]\nPayment Status : Partially Paid\nPaid : $row[paid2]\nBalance : $balance";
					mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$row[mobile]',1,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
				}
				break;
			case 'upay_remove_customer':
				mysqli_query($con,"UPDATE upay SET customer=NULL WHERE id='$_POST[id]'");
				break;
			case 'upay_add_customer':
				mysqli_query($con,"UPDATE upay SET customer='$_POST[customer]' WHERE id='$_POST[id]'");
				break;
			case 'add_customer_to_invoice':
				if($_POST['mobile']=='r')mysqli_query($con,"UPDATE upay SET customer=NULL WHERE id='$_POST[id]'");
				$result=mysqli_query($con,"SELECT id,delivery_charge FROM customers WHERE mobile='$_POST[mobile]'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					if($_POST['comm']>0)$row['delivery_charge']=$_POST['comm'];
					if(isset($_POST['ids'])){
						if (strpos($_POST['ids'], ',') !== false){
							$ids=explode(',',$_POST['ids']);
							foreach($ids as $id){
								if (strpos($id, '-') !== false){
									$ids1=explode('-',$id);
									for($ids1[0]; $ids1[0]<=$ids1[1]; $ids1[0]++){//echo $ids[0];
										if($ids1[0]>0 || $ids[0]=='00'){
											$id1=$ids1[0];
											if(isset($_POST['prefix']) AND $_POST['prefix']>0 AND strlen($ids1[0])<=3)$id1=$_POST['prefix'].$ids1[0];
											$id1=sprintf('%05d',$id1);
											$result=mysqli_query($con,"SELECT * FROM upay WHERE transaction_id='$id1'");
											if(mysqli_affected_rows($con)==1){
												$row1=mysqli_fetch_assoc($result);
												if($row1['customer']>0)echo "$id1 : $row1[customer]<br/>";
												else mysqli_query($con,"UPDATE upay SET customer='$row[id]',commission='$row[delivery_charge]' WHERE transaction_id='$id1'") or die(mysqli_error($con));
											}
										}
									}
								}
								else if($id>0 || $ids[0]=='00'){
									if(isset($_POST['prefix']) AND $_POST['prefix']>0 AND strlen($id)<=3)$id=$_POST['prefix'].$id;
									$id=sprintf('%05d',$id);
									$result=mysqli_query($con,"SELECT * FROM upay WHERE transaction_id='$id'");
									if(mysqli_affected_rows($con)==1){
										$row1=mysqli_fetch_assoc($result);
										if($row1['customer']>0)echo "$id : $row1[customer]<br/>";
										else mysqli_query($con,"UPDATE upay SET customer='$row[id]',commission='$row[delivery_charge]' WHERE transaction_id='$id'") or die(mysqli_error($con));
									}
								}
							}
						}
						else if (strpos($_POST['ids'], '-') !== false){
							$ids=explode('-',$_POST['ids']);
							$start=$ids[0];
							for($ids[0]; $ids[0]<=$ids[1]; $ids[0]++){//echo $ids[0];
								if($ids[0]>0 || $ids[0]=='00'){
									$id=$ids[0];
									if(isset($_POST['prefix']) AND $_POST['prefix']>0 AND strlen($ids[0])<=3)$id=$_POST['prefix'].$ids[0];
									$id=sprintf('%05d',$id);
									$result=mysqli_query($con,"SELECT * FROM upay WHERE transaction_id='$id'");
									if(mysqli_affected_rows($con)==1){
										$row1=mysqli_fetch_assoc($result);
										if($row1['customer']>0)echo "$id : $row1[customer]<br/>";
										else mysqli_query($con,"UPDATE upay SET customer='$row[id]',commission='$row[delivery_charge]' WHERE transaction_id='$id'") or die(mysqli_error($con));
									}
								}
							}
						}
						else if($_POST['ids']>0){
							if(isset($_POST['prefix']) AND $_POST['prefix']>0)$_POST['ids']=$_POST['prefix'].$_POST['ids'];
							$_POST['ids']=sprintf('%05d',$_POST['ids']);
							$result=mysqli_query($con,"SELECT * FROM upay WHERE transaction_id='$_POST[ids]'");
							if(mysqli_affected_rows($con)==1){
								$row1=mysqli_fetch_assoc($result);
								if($row1['customer']>0)echo "$id : $row1[customer],";
								else mysqli_query($con,"UPDATE upay SET customer='$row[id]',commission='$row[delivery_charge]' WHERE transaction_id='$_POST[ids]'");
							}
							
						}
					}
					else mysqli_query($con,"UPDATE upay SET customer='$row[id]',commission='$row[delivery_charge]' WHERE id='$_POST[id]'");
				}
				break;
			case 'add_customer_time' :
				$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
				$result=mysqli_query($con,"SELECT * FROM upay_payments WHERE date='$_POST[date]' AND line=(SELECT MAX(line) line FROM upay_payments WHERE date='$_POST[date]')");
				$line=1;
				$start='06:30:00';
				if(mysqli_affected_rows($con)>0){
					$row=mysqli_fetch_assoc($result);
					$line=$row['line']+1;
					$start=$row['end'];
				}
				$end=date('H:i:s',strtotime($start." +$_POST[min] Minutes"));
				foreach($_POST['customer'] as $customer){
					if($customer>5){
						if(mysqli_query($con,"INSERT INTO upay_payments(date,customer,allocated_min,line,start,end,user) 
							VALUES('$_POST[date]','$customer','$_POST[min]','$line','$start','$end','$_SESSION[user_id]')")){
							$id=mysqli_insert_id($con);
							if($start=='06:30:00')mysqli_query($con,"UPDATE upay_payments SET started='$start',status=-1 WHERE id='$id'");
							$result=mysqli_query($con,"SELECT * FROM customers WHERE id='$customer'");
							$row=mysqli_fetch_assoc($result);
							$start1=date('h:i A',strtotime($start));
							$end1=date('h:i A',strtotime($end));
							$msg="Your Time Allocation For $_POST[date], is $start1 to $end1. When you receive \'start\' message, you can start. otherwise dont. Also make sure you use your time fully. finishing early creates the Gap. if you could not finish within your time, its ok to do few out of your time, but you should definitly send those IDs manually to this no (Comma Seperated). eg: id 11111,11112";
							mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$row[mobile]',1,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
						}
						else echo mysqli_error($con);
					}
				}
				break;
			case 'upay_update' :
				$result=mysqli_query($con,"SELECT * FROM upay_payments WHERE customer='$_POST[id]' AND date='$_POST[date]'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					mysqli_query($con,"UPDATE upay_payments SET total='$_POST[total]',amount='$_POST[amount]',paid='$_POST[amount]',commission='$_POST[commission]',qty='$_POST[qty]',status='2' 
					WHERE customer='$_POST[id]' AND date='$_POST[date]'");
				}
				else {
					mysqli_query($con,"INSERT INTO upay_payments(date,customer,total,amount,paid,commission,qty,status,user) 
					VALUES('$_POST[date]','$_POST[id]','$_POST[total]','$_POST[amount]','$_POST[amount]','$_POST[commission]','$_POST[qty]','2','$_SESSION[user_id]')");
				}
				$result=mysqli_query($con,"SELECT * FROM customers WHERE id='$_POST[id]'");
				$row=mysqli_fetch_assoc($result);
				$msg=$_POST['date']."\nDelivery Order\nTotal: $_POST[total]\nNet Total: $_POST[amount]\nItems: $_POST[qty]\nPayment Status : Pending";
				mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$row[mobile]',1,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
				echo 'Updated!';
				break;
			case 'upay_updated' :
				$result=mysqli_query($con,"SELECT u.*,c.mobile FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer WHERE u.id='$_POST[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				if($row['status']==3){
					echo 'Already Paid!';
				}
				else {
					mysqli_query($con,"UPDATE upay_payments SET status='3' WHERE id='$_POST[id]'");
					$msg=$row['date']."\nDelivery Order Total: $row[paid]\nPayment Status : Paid";
					mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$row[mobile]',1,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
					echo 'Updated!';
				}
				break;
			case 'upay_update_time' :
				$result=mysqli_query($con,"SELECT * FROM upay_payments WHERE id='$_POST[id]'");
				$row=mysqli_fetch_assoc($result);
				$result=mysqli_query($con,"SELECT * FROM customers WHERE id='$_POST[customer]'");
				$cust=mysqli_fetch_assoc($result);
				$row['start']=date('h:i A',strtotime($row['start']));
				$row['end']=date('h:i A',strtotime($row['end']));
				$msg="Your Time Allocation For date $row[date], is Changed, $row[start] to $row[end].";
				mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$cust[mobile]',1,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
				echo 'Updated!';
				break;
		}
		return;
	}
	if(isset($_GET['status']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
		echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Customer</label>
								<div><select class="form-control select" name="customer[]" multiple>';
									$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile FROM upay u LEFT JOIN customers c ON c.id=u.customer WHERE u.customer>0 AND u.date>'2021-09-18' AND u.sales is NULL GROUP BY u.customer") or die(mysqli_error($con));
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
								<label>Status</label>
								<div><select class="form-control select form_submit_change" name="status">';
									$types=array(0=>'Failed',1=>'Success');
									if(!isset($_GET['status']))$_GET['status']=1;
									foreach($types as $key=>$title)echo '<option value="'.$key.'" '.($_GET['status']==$key?'selected':'').'>'.$title.'</option>';
									echo '<option value="All" '.($_GET['status']=='All'?'selected':'').'>All</option>
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
	}
	{
		echo '<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#payment" aria-expanded="false" aria-controls="collapseExample">Payment</a></p>';
		echo '<div class="row collapse" id="payment">
			<div class="col-sm-12">
				<div class="card">
					<table table="upay_payments" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Customer').'</th><th>'.language('Account').'</th><th>'.language('Amount').'</th><th></th></tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile,c.remind,(SELECT SUM(amount) FROM upay_payments_paid WHERE payment=u.id) paid2
					FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer WHERE u.status=2 ORDER BY u.date ASC,u.id ASC") or die(mysqli_error($con));
					$counter=1;
					$total=0;
					$amount=0;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
							<td>'.$row['fname'].' '.$row['id'].'<br/>'.$row['mobile'].'</td>
							<td>'.wordwrap($row['remind'],20, "<br />", false).'</td>
							<td class="right">'.Number_format(($row['amount']-$row['paid2']),2).'</td>
							<td><button type="button" payment_id="'.$row['id'].'" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in upay_updated">Pay Fully</button>
							<button type="button" href="'.$remote_file.'/upay&form=add_payment&id='.$row['id'].'" class="popup btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Pay Part</button></td>
						</tr>';
						$amount+=($row['amount']-$row['paid2']);
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="3">Total</td>
					<td class="right">'.Number_format($amount,2).'</td><td></td>
					</tr></tfoot></table>
				</div>
			</div>
		</div>';
		/*echo '<div class="row">
			<div class="col-sm-12">
				<div class="card" style="font-size: 10px;">
					<table data-page-length="100" class="table table-sm">
					<thead><tr><th>#</th><th>'.language('Customer').'</th><th>'.language('Total').'</th><th>'.language('Amount').'</th><th>'.language('Qty').'</th>
					<th>'.language('Days').'</th><th>'.language('Profit').'</th><th>'.language('Pending').'</th><th>'.language('Pending').'</th></tr></thead><tbody>';
					// $result=mysqli_query($con,"SELECT * FROM upay_payments WHERE qty is NULL") or die(mysqli_error($con));
					// while($row=mysqli_fetch_array($result)){
						// mysqli_query($con,"SELECT * FROM upay WHERE customer='$row[customer]' AND date='$row[date]'") or die(mysqli_error($con));
						// $qty=mysqli_affected_rows($con);
						// mysqli_query($con,"UPDATE upay_payments SET qty='$qty' WHERE id='$row[id]'") or die(mysqli_error($con));
					// }
					$result=mysqli_query($con,"SELECT SUM(u.total) total,SUM(u.amount) amount,SUM(u.paid) paid,SUM(u.commission) comm,SUM(u.qty) qty,COUNT(*) days,c.fname,c.mobile,
					(SELECT COUNT(*) FROM upay_payments p WHERE p.status='2' AND p.customer=u.customer) pending,
					(SELECT SUM(p.paid) FROM upay_payments p WHERE p.status='2' AND p.customer=u.customer) pending_amount
					FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer  GROUP BY u.customer HAVING pending>0") or die(mysqli_error($con));
					$counter=1;
					$sum=array('total'=>0,'amount'=>0,'qty'=>0,'profit'=>0,'pending'=>0);
					while($row=mysqli_fetch_array($result)){
						if($row['total']>0){
							$profit=$row['paid']+$row['comm']-$row['total'];
							echo'<tr style="cursor:pointer;" class="'.($row['pending_amount']>$profit?'warning':'').'">
								<td class="center selectable">'.$counter++.'</td>
								<td>'.$row['fname'].'<br/>'.$row['mobile'].'</td>
								<td class="right">'.Number_format($row['total'],2).'</td>
								<td class="right">'.Number_format($row['amount'],2).'</td>
								<td>'.$row['qty'].'</td>
								<td>'.$row['days'].'</td>
								<td class="right">'.Number_format($profit,2).'</td>
								<td>'.$row['pending'].'</td>
								<td class="right">'.Number_format($row['pending_amount'],2).'</td>
							</tr>';
							$sum['total']+=$row['total'];
							$sum['amount']+=$row['amount'];
							$sum['qty']+=$row['qty'];
							$sum['profit']+=$profit;
							$sum['pending']+=$row['pending_amount'];
						}
					}
				echo '</tbody><tfoot><tr><td></td><td>Total</td>
					<td class="right">'.Number_format($sum['total'],2).'</td>
					<td class="right">'.Number_format($sum['amount'],2).'</td>
					<td class="right">'.$sum['qty'].'</td><td></td>
					<td class="right">'.Number_format($sum['profit'],2).'</td><td></td>
					<td class="right">'.Number_format($sum['pending'],2).'</td>
					</tr></tfoot></table>
				</div>
			</div>
		</div>';*/
	}
	$date=date('Y-m-d',strtotime($_GET['from']));
	$where="WHERE u.date='$date' ";
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE u.date BETWEEN '$from' AND '$to' ";
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE u.date='$to' ";
		}
	}
	if(isset($_GET['customer']) AND sizeof($_GET['customer'])>0){
		$customers_id=array();
		$q="";
		foreach($_GET['customer'] as $id){
			if($id==-1)$q.=" u.customer is NULL";
			else $customers_id[]=$id;
		}
		
		if(sizeof($customers_id)>0){
			$customer=implode(',',$customers_id);
			$where.=" AND (u.customer IN($customer)";
			if($q<>'')$where.="OR $q";
			$where.=")";
		}
		else $where.=" AND $q";
	}
	if(isset($_GET['status']) AND $_GET['status']<>'All')$where.=" AND u.status='$_GET[status]'";
	if(isset($_GET['type']) AND $_GET['type']<>'All'){
		if($_GET['type']==0)$where.=" AND u.sales>0";
		else $where.=" AND u.sales is NULL";
	}
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	{//echo $where;
		$start=file_get_contents('upay_missing')-10;
		$max=99999;
		$result=mysqli_query($con,"SELECT (t1.transaction_id + 1) as start, (SELECT MIN(t3.transaction_id) -1 FROM upay t3 WHERE t3.transaction_id<$max AND t3.transaction_id > t1.transaction_id) as end 
		FROM upay t1 WHERE t1.transaction_id>=$start AND t1.transaction_id<$max AND NOT EXISTS (SELECT t2.transaction_id FROM upay t2 WHERE t2.transaction_id = t1.transaction_id + 1) HAVING start IS NOT NULL");
		if(mysqli_affected_rows($con)>0){
			$start=9999999;
			echo '<div class="form-group col-lg-6">Missing IDs : ';
			while($row=mysqli_fetch_array($result)){
				if($start>$row['start'])$start=$row['start'];
				if($row['start']==$row['end'])echo $row['start'].',';
				else if($row['end']>0)echo $row['start'].' - '.$row['end'].',';
			}
			file_put_contents('upay_missing',$start);
			echo '</div>';
		}
		echo'<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-body">
						<div class="row">
						<div class="form-group col-lg-3">
							<label>Customer</label>
							<div><select class="form-control select" id="customer_de"><option></option>';
								$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile FROM upay u LEFT JOIN customers c ON c.id=u.customer WHERE u.customer>0 AND u.sales is NULL GROUP BY u.customer") or die(mysqli_error($con));
								while($row=mysqli_fetch_array($result)){
									echo '<option value="'.$row['customer'].'">'.$row['fname'].' ('.$row['customer'].') '.$row['mobile'].'</option>';
								}
								echo '</select>
							</div>
						</div>
					</div>
				</div>
				</div>
				</div>
			</div>';
	echo '<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#upay_list" aria-expanded="false" aria-controls="collapseExample">List</a></p>';
	
	echo'<div class="row" id="upay_list">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
				<table table="upay" data-page-length="10" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Date').'</th><th>'.language('Customer').'</th><th>'.language('ID').'</th>
					<th>'.language('Amount').'</th><th></th><th>'.language('Comm').'</th>
					<th>'.language('To Pay').'</th><th>'.language('Status').'</th><th>'.language('Time').'</th><th></th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile,c.delivery_charge FROM upay u LEFT JOIN customers c ON c.id=u.customer $where $limit") or die(mysqli_error($con));
					$counter=0;
					$total=0;
					$total_to_pay=0;
					$status=array(0=>'Failed',1=>'Success');
					$i=0;
					while($row=mysqli_fetch_array($result)){
						$to_pay=$row['amount'];
						$commission=$row['delivery_charge'];
						if($row['commission']>0)$commission=$row['commission'];
						if($row['amount']>=$bill_over){
							$to_pay=$to_pay-$commission;
							// if($row['type']==1)$to_pay=$to_pay+$commission;
						}
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center selectable">'.++$counter.'</td>
							<td class="center">'.$row['id'].'</td>
							<td class="center editable" field="date">'.$row['date'].'</td>
							<td class="popup" href="'.$remote_file.'/upay&form=customers&id='.$row['id'].'&callback=">'.($row['fname']<>''?$row['fname']:$row['mobile']).'</td>
							<td class="center">'.$row['transaction_id'].'</td>
							<td class="right" field="amount">'.Number_format($row['amount'],2).'</td>
							<td class="right editable" field="type">'.$row['type'].'</td>
							<td class="right editable" field="commission">'.(($row['sales']=='' AND $row['amount']>=$bill_over AND $row['type']==1)?Number_format($commission,2):'').'</td>
							<td class="right">'.($row['sales']==''?Number_format($to_pay,2):'').'</td>
							<td>'.$status[$row['status']].'</td>
							<td class="center">'.date('h:i A',strtotime($row['paid'])).'</td>
							<td class="center"><button type="button" class="upay_remove_customer btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Remove</button>
							<button type="button" class="upay_add_customer btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Add</button></td>
						</tr>';
						$total+=$row['amount'];
						if($row['sales']=='')$total_to_pay+=$to_pay;
						if($row['amount']>$bill_over)$i++;
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="4">Total</td>
					<td class="right">'.Number_format($total,2).'</td><td></td><td></td><td class="right">'.Number_format($total_to_pay,2).'</td>
					<td>'.((isset($_GET['customer']) AND sizeof($_GET['customer'])==1 AND $_GET['customer'][0]>0 AND (!isset($_GET['to']) OR $_GET['to']==''))?
					'<button type="button" customer="'.$_GET['customer'][0].'" commission="'.($i*$commission).'" qty="'.$counter.'" date="'.$date.'" total="'.$total.'" amount="'.$total_to_pay.'" class="upay_update btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Update</button>':'').'</td>
					<td></td><td></td>
					</tr></tfoot></table>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
	$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile,
	(SELECT MIN(paid) FROM upay WHERE date='$date' AND customer=c.id AND status=1) start_t,
	(SELECT MAX(paid) FROM upay WHERE date='$date' AND customer=c.id AND status=1) end_t 
	FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer WHERE u.date='$date' AND u.status<=1 ORDER BY u.line ASC") or die(mysqli_error($con));
	if(mysqli_affected_rows($con)>0){
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table table="upay_payments" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
						<thead><tr><th>#</th><th>'.language('Customer').'</th><th>'.language('Start').'</th><th>'.language('End').'</th>
						<th>'.language('Time').'</th><th>'.language('Status').'</th><th></th></tr></thead><tbody>';
						$counter=1;
						$status=array(-1=>'Processing',0=>'Pending',1=>'Completed');
						while($row=mysqli_fetch_array($result)){
							echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
								<td class="center selectable">'.$counter++.'</td>
								<td>'.$row['fname'].'<br/>'.$row['mobile'].'</td>
								<td class="center editable" field="start">'.date('h:i A',strtotime($row['start'])).'</td>
								<td class="center editable" field="end">'.date('h:i A',strtotime($row['end'])).'</td>
								<td class="center">'.($row['status']==1?date('h:i A',strtotime($row['start_t'])).' - '.date('h:i A',strtotime($row['end_t'])):'').'</td>
								<td>'.$status[$row['status']].($row['status']==1?"($row[transaction_id])":'').'</td>
								<td>'.($row['status']==0?'<button type="button" id="'.$row['id'].'" customer="'.$row['customer'].'" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in upay_update_time">Update</button>':'').'</td>
							</tr>';
							unset($customers[$row['customer']]);
							// telerivet_add_contacts($row['mobile'],$row['fname']);
						}
					echo '</tbody></table>
					</div>
				</div>
			</div>
		</div>';
	}
	$q='';
	if(isset($_GET['customer']) AND sizeof($_GET['customer'])==1 AND $_GET['customer'][0]>0 AND (!isset($_GET['to']) OR $_GET['to']=='')){
		$customer=$_GET['customer'][0];
		$q="AND u.customer='$customer'";
	}
	$result=mysqli_query($con,"SELECT c.*,COUNT(*) qty,u.commission,
	(SELECT SUM(amount) FROM upay WHERE customer=c.id AND date='$date' AND amount>=$bill_over) total,
	(SELECT SUM(amount) FROM upay WHERE customer=c.id AND date='$date' AND amount<$bill_over) total_no_com
	FROM upay u LEFT JOIN customers c ON c.id=u.customer LEFT JOIN upay_payments up ON (up.customer=c.id AND up.date='$date')
	WHERE u.date='$date' AND u.customer>0 AND u.sales is NULL AND up.status is null GROUP BY u.customer") or die(mysqli_error($con));
	if(mysqli_affected_rows($con)>0){
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body table-responsive">
					<table table="upay_payments" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
						<thead><tr><th>#</th><th>'.language('Customer').'</th><th>'.language('Qty').'</th><th>'.language('Total').'</th><th>'.language('to Pay').'</th><th></th></tr></thead><tbody>';
						$counter=1;
						while($row=mysqli_fetch_array($result)){
							$commission=$row['delivery_charge'];
							if($row['commission']>0)$commission=$row['commission'];
							$to_pay=$row['total']-($commission*$row['qty']);//+($row['qty']*$commission);
							$total=($row['total']+$row['total_no_com']);
							$to_pay+=$row['total_no_com'];
							echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
								<td class="center selectable">'.$counter++.'</td>
								<td>'.$row['fname'].'<br/>'.$row['mobile'].'</td>
								<td class="center">'.$row['qty'].'</td>
								<td class="right">'.Number_format($total,2).'</td>
								<td class="right">'.Number_format($to_pay,2).'</td>
								<td><button type="button" customer="'.$row['id'].'" commission="'.($row['qty']*$commission).'" qty="'.$row['qty'].'" date="'.$date.'" total="'.$total.'" amount="'.$to_pay.'" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in upay_update">Update</button></td>
							</tr>';
							
						}
					echo '</tbody></table>
					</div>
				</div>
			</div>
		</div>';
	}
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
				<table table="upay_payments" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('Customer').'</th><th>'.language('Account').'</th><th>'.language('Allocated').'</th><th>'.language('Time').'</th>
					<th>'.language('USED').'</th><th>'.language('Qty').'</th><th>'.language('Total').'</th><th>'.language('Amount').'</th><th>'.language('Paid').'</th><th>'.language('Profit').'</th><th></th></tr></thead><tbody>';
					$q='';
					// if(isset($_GET['customer']) AND sizeof($_GET['customer'])==1 AND $_GET['customer'][0]>0 AND (!isset($_GET['to']) OR $_GET['to']=='')){
						// $customer=$_GET['customer'][0];
						// $q="AND u.customer='$customer'";
					// }
					$result=mysqli_query($con,"SELECT u.*,c.fname,c.mobile,c.remind,
					(SELECT MIN(paid) FROM upay WHERE date='$date' AND customer=c.id AND status=1) start_t,
					(SELECT MAX(paid) FROM upay WHERE date='$date' AND customer=c.id AND status=1) end_t 
					FROM upay_payments u LEFT JOIN customers c ON c.id=u.customer WHERE u.date='$date' AND u.status>=2 $q ORDER BY u.id ASC") or die(mysqli_error($con));
					$counter=1;
					$total=0;
					$amount=0;
					$paid=0;
					$qty=0;
					while($row=mysqli_fetch_array($result)){
						$allocated=(strtotime($row['end'])-strtotime($row['start']))/60;
						$real=(strtotime($row['end_t'])-strtotime($row['start_t']))/60;
						$over=$real-$allocated;
						echo'<tr id="'.$row['id'].'" class="'.($over>10?'warning':'').'" style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td>'.$row['fname'].'<br/>'.$row['mobile'].'</td>
							<td>'.wordwrap($row['remind'],10, "<br />", false).'</td>
							<td class="center">'.date('h:i A',strtotime($row['start'])).'<br/>'.date('h:i A',strtotime($row['end'])).'</td>
							<td class="center">'.date('h:i A',strtotime($row['start_t'])).'<br/>'.date('h:i A',strtotime($row['end_t'])).'</td>
							<td class="center">'.$real.'</td>
							<td class="center">'.$row['qty'].'</td>
							<td class="right">'.Number_format($row['total'],2).'</td>
							<td class="right">'.Number_format($row['amount'],2).'</td>
							<td class="right editable" field="paid">'.($row['status']==2?'':Number_format($row['paid'],2)).'</td>
							<td class="right">'.Number_format(($row['total']-$row['paid']),2).'</td>
							<td>'.($row['status']==2?'<button type="button" payment_id="'.$row['id'].'" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in upay_updated">Pay</button>':'').'</td>
						</tr>';
						$total+=$row['total'];
						$amount+=$row['amount'];
						$qty+=$row['qty'];
						if($row['status']==3)$paid+=$row['paid'];
						unset($customers[$row['customer']]);
						// telerivet_add_contacts($row['mobile'],$row['fname']);
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="5">Total</td>
					<td class="right">'.$qty.'</td>
					<td class="right">'.Number_format($total,2).'</td>
					<td class="right">'.Number_format(($amount-$paid),2).'</td>
					<td class="right">'.Number_format($paid,2).'</td>
					<td class="right">'.Number_format(($total-$amount),2).'</td><td></td>
					</tr></tfoot></table>
				</div>
			</div>
		</div>
	</div>';
	// unset($customers[5]);
	// unset($customers[12]);
	// unset($customers[11297]);
	// unset($customers[40473]);
	// unset($customers[40501]);
	// unset($customers[40388]);
	// unset($customers[29218]);
	// unset($customers[40459]);
	// unset($customers[40497]);
	// unset($customers[40499]);
	// unset($customers[40502]);
	// unset($customers[40660]);
	// unset($customers[40709]);
	// echo implode(',',$customers);
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
    function after_ajax(){
		if($( ".customer" ).length >0){
			var cache = {};
			$( ".customer" ).autocomplete({minLength: 1,
			  source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
				  response( cache[ term ] );
				  return;
				}
		 
				$.getJSON(remote_file+"/upay&form=select_customer", request, function( data, status, xhr ) {
				  cache[ term ] = data;
				  response( data );
				});
			  }
			});
		}
		$('#customer').unbind('change').change(function(){
			$.post(remote_file+"/upay",{select:'select_accounts',id:$(this).val()},function(data){
				data=jQuery.parseJSON(data);
				$('.date').val(data.date);
				$('#min').val(data.qty);
			});
		});
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$(".plus").trigger('click');
		return false;
	});
	$(document).bind('keydown',"Ctrl++", function(e) {
		$(".ctrl_plus").trigger('click');
		return false;
	});
	$('.upay_update_time').click(function(){
		but=$(this);
		$.post(remote_file+"/upay",{popup_form_submit:'upay_update_time',id:$(this).attr('id'),customer:$(this).attr('customer')},function(data){
			Swal.fire({title: data,confirmButtonColor: '#4090cb',});
			but.remove();
		});
	});
	$('.upay_remove_customer').click(function(){
		id=$(this).parents('tr').attr('id');
		$(this).parents('tr').remove();
		$.post(remote_file+"/upay",{popup_form_submit:'upay_remove_customer',id:id},function(data){});
	});
	$('.upay_add_customer').click(function(){
		id=$(this).parents('tr').attr('id');
		$(this).parents('tr').remove();
		$.post(remote_file+"/upay",{popup_form_submit:'upay_add_customer',id:id,customer:$('#customer_de').val()},function(data){});
	});
	$('.upay_update').click(function(){
		but=$(this);
		$.post(remote_file+"/upay",{popup_form_submit:'upay_update',id:$(this).attr('customer'),date:$(this).attr('date'),total:$(this).attr('total'),amount:$(this).attr('amount'),qty:$(this).attr('qty'),commission:$(this).attr('commission')},function(data){
			Swal.fire({title: data,confirmButtonColor: '#4090cb',});
			but.remove();
		});
	});
	$('.upay_updated').click(function(){
		but=$(this);
		$.post(remote_file+"/upay",{popup_form_submit:'upay_updated',id:$(this).attr('payment_id')},function(data){
			Swal.fire({title: data,confirmButtonColor: '#4090cb',});
			but.remove();
		});
	});
</script>
<span class="hide popup plus" href="<?php echo $remote_file.'/upay&form=customers&type=bulk';?>"></span>
<span class="hide popup ctrl_plus" href="<?php echo $remote_file.'/upay&form=customers_add';?>"></span>
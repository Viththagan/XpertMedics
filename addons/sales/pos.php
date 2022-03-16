<?php
	// print_r($_COOKIE);
	// print_r($_GET);print_r($_POST);
	// setcookie('Counter_login',0, time(), "/");
	if (strpos(dirname(__FILE__),'/') !== false)$folders=explode('/',dirname(__FILE__));
	else $folders=explode('\\',dirname(__FILE__));
	$plugin=end($folders);
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	$date=date('Y-m-d');
	if(isset($_POST['cash_register'])){
		$denomination=json_encode($_POST['denomination']);
		if(isset($_COOKIE['Counter_login'])){
			$Counter_login=json_decode($_COOKIE['Counter_login']);
			$counter_id=$Counter_login->id;
			mysqli_query($con,"UPDATE sales_session SET close='$current_time',close_staff='$_SESSION[user_id]',close_amount='$_POST[cash_register]',close_denomination='$denomination' WHERE id='$counter_id'") or die(mysqli_error($con));
			setcookie('Counter_login', null, -1, '/');
			unset($_COOKIE['Counter_login']);
			echo 'close';
		}
		else {
			mysqli_query($con,"INSERT INTO sales_session(date,branch,till,open,open_amount,open_denomination,open_staff) 
			VALUES('$date','$_SESSION[branch]','$_COOKIE[sales_counter]','$current_time','$_POST[cash_register]','$denomination','$_SESSION[user_id]')") or die(mysqli_error($con));
			echo $id=mysqli_insert_id($con);
			$Counter_login=array('id'=>$id,'user'=>$_SESSION['user_id'],'time'=>time());
			$_COOKIE['Counter_login']=json_encode($Counter_login);
			setcookie('Counter_login',$_COOKIE['Counter_login'], time() + $counter_expiry, "/");
		}
		return;
	}
	else if(isset($_POST['type'])){//print_r($_POST);
		$counter_id=0;
		if(isset($_COOKIE['Counter_login'])){
			$Counter_login=json_decode($_COOKIE['Counter_login']);
			$counter_id=$Counter_login->id;
		}
		if(isset($_POST['session']) AND $_POST['session']>0)$counter_id=$_POST['session'];
		if(isset($_POST['date']))$date=date('Y-m-d',strtotime($_POST['date']));
		if($_POST['type']=='out'){
			switch($_POST['payment_type']){
				case '1' :
					if($_POST['p_type']==2){
						pay_salary($_POST['staff'],$_POST['amount'],array('description'=>$_POST['description'],'counter_id'=>$counter_id,'date'=>$date));
					}
					else if($_POST['p_type']==1){
						mysqli_query($con,"INSERT INTO payments(branch,date,description,amount,category,type,session,referrer,added,user) 
						VALUES('$_SESSION[branch]','$date','$_POST[description]','$_POST[amount]','$_POST[p_type]',1,'$counter_id','$_POST[supplier]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					}
					else {
						mysqli_query($con,"INSERT INTO payments(branch,date,description,amount,category,type,session,added,user) 
						VALUES('$_SESSION[branch]','$date','$_POST[description]','$_POST[amount]','$_POST[p_type]',1,'$counter_id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					}
					break;
				case '2' :
					mysqli_query($con,"INSERT INTO bank_transactions(title,date,amount,flow,f_bank,bank,added,user) 
						VALUES('$_POST[description]','$date','$_POST[amount]',2,'$counter_id','$_POST[creditor]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					break;
			}
		}
		else if($_POST['type']=='in'){
			mysqli_query($con,"INSERT INTO bank_transactions(title,date,amount,flow,f_bank,bank,added,user) 
			VALUES('$_POST[description]','$date','$_POST[amount]',3,'$counter_id','$_POST[source]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
		}
		return;
	}
	else if(isset($_POST['print'])){
		if($_POST['print']=='invoice_old'){
			echo print_receipt($_POST['id']);
		}
		else if($_POST['print']=='whatsapp_invoice_old'){
			$re=mysqli_query($con,"SELECT c.mobile,c.whatsapp FROM sales s LEFT JOIN customers c ON c.id=s.customer WHERE s.id='$_POST[id]'");
			$row=mysqli_fetch_assoc($re);
			$return=array();
			$return['text']='';
			$return['mobile']='';
			if(substr($row['mobile'], 0,2)=='07' AND $row['whatsapp']==1){
				$return['text']=whatsapp_receipt($_POST['id']);
				$return['mobile']='94'.substr($row['mobile'], 1);//'94773518574';
			}
			echo json_encode($return);
		}
		else if($_POST['id']>0){//if($_SESSION['user_id']==1)
			$date=date('Y-m-d');
			$title='';
			$re=mysqli_query($con,"SELECT * FROM sales WHERE id='$_POST[id]'");
			$sales=mysqli_fetch_assoc($re);
			if(isset($_POST['items']) AND $sales['closed']==0){
				mysqli_query($con,"UPDATE sales SET closed='1' WHERE id='$_POST[id]'");
				foreach($_POST['items'] as $data){
					$rows=json_decode($data,true);
					$row=$rows[0];
					$result1=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[id]' AND branch='$_SESSION[branch]' ORDER BY id DESC LIMIT 0,1")or die(mysqli_error($con));
					$balance=0;
					if(mysqli_affected_rows($con)==1){
						$row1=mysqli_fetch_assoc($result1);
						if(is_numeric($row1['balance']))$balance=$row1['balance'];
					}
					$balance-=$row['qty'];
					mysqli_query($con,"INSERT INTO products_logs(branch,product,type,referrer,qty,balance,purchased,mrp,selling_price,added,user) 
						VALUES('$_SESSION[branch]','$row[id]',0,'$_POST[id]','$row[qty]','$balance','$row[purchased]','$row[mrp]','$row[selling_price]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					$log=mysqli_insert_id($con);
					mysqli_query($con,"UPDATE products SET mrp='$row[mrp]',purchased='$row[purchased]' WHERE id='$row[id]'")or die(mysqli_error($con));
					$charge=0;
					if(strpos($row['log_id'],'|')!==false){
						$data=explode('|',$row['log_id']);
						$q="'$data[2]'";
						if($data[2]=='')$q="NULL";
						if($data[0]==7){
							$charge=10;
							if($row['mrp']>1020)$charge=20;
							else if($row['mrp']>210)$charge=15;
							$row['mrp']-=$charge;
							mysqli_query($con,"UPDATE products_logs SET mrp='$row[mrp]' WHERE id='$log'")or die(mysqli_error($con));
						}
						$data[1]=trim($data[1]);
						mysqli_query($con,"INSERT INTO bill_payments(branch,date,log,service,number,amount,reference,notify,status,added,user) 
						VALUES('$_SESSION[branch]','$date','$log','$data[0]','$data[1]','$row[mrp]',$q,'0',0,'$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					}
					else if($row['log_id']>0){
						$ids=implode(',',$partners);
						$result1=mysqli_query($con,"SELECT po.id FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type='1') 
						WHERE l.id='$row[log_id]' AND po.supplier IN($ids)")or die(mysqli_error($con));
						if(mysqli_affected_rows($con)>0){
							$row1=mysqli_fetch_assoc($result1);
							mysqli_query($con,"INSERT INTO suppliers_partners(product_log,po) VALUES('$log','$row1[id]')")or die(mysqli_error($con));
						}
					}
				}
				
			}
			else {
				$title='old';
				$result=mysqli_query($con,"SELECT * FROM products_logs_temp WHERE type='0' AND referrer='$_POST[id]'");
				while($row=mysqli_fetch_assoc($result)){
					$result1=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[product]' AND branch='$_SESSION[branch]' ORDER BY id DESC LIMIT 0,1")or die(mysqli_error($con));
					$balance=0;
					if(mysqli_affected_rows($con)==1){
						$row1=mysqli_fetch_assoc($result1);
						if(is_numeric($row1['balance']))$balance=$row1['balance'];
					}
					$balance-=$row['qty'];
					mysqli_query($con,"INSERT INTO products_logs(branch,product,type,referrer,qty,balance,purchased,mrp,selling_price,added,user) 
						VALUES('$_SESSION[branch]','$row[product]','$row[type]','$row[referrer]','$row[qty]','$balance','$row[purchased]','$row[mrp]','$row[selling_price]','$row[added]','$row[user]')")or die(mysqli_error($con));
					$log=mysqli_insert_id($con);
					mysqli_query($con,"UPDATE products SET mrp='$row[mrp]',purchased='$row[purchased]' WHERE id='$row[product]'")or die(mysqli_error($con));
					$charge=0;
					if(strpos($row['status'],'|')!==false){
						$data=explode('|',$row['status']);
						$status=0;
						$q="'$data[2]'";
						if($data[2]=='')$q="NULL";
						if($data[0]==7){
							$charge=10;
							if($row['mrp']>1020)$charge=20;
							else if($row['mrp']>210)$charge=15;
							$row['mrp']-=$charge;
							mysqli_query($con,"UPDATE products_logs SET mrp='$row[mrp]' WHERE id='$log'")or die(mysqli_error($con));
						}
						$data[1]=trim($data[1]);
						mysqli_query($con,"INSERT INTO bill_payments(branch,date,log,service,number,amount,reference,notify,status,added,user) 
						VALUES('$_SESSION[branch]','$date','$log','$data[0]','$data[1]','$row[mrp]',$q,'0','$status','$row[added]','$row[user]')")or die(mysqli_error($con));
					}
					else if($row['status']>0){
						$ids=implode(',',$partners);
						$result1=mysqli_query($con,"SELECT po.id FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type='1') 
						WHERE l.id='$row[status]' AND po.supplier IN($ids)")or die(mysqli_error($con));
						if(mysqli_affected_rows($con)>0){
							$row1=mysqli_fetch_assoc($result1);
							mysqli_query($con,"INSERT INTO suppliers_partners(product_log,po) VALUES('$log','$row1[id]')")or die(mysqli_error($con));
						}
					}
				}
			}
			mysqli_query($con,"DELETE FROM products_logs_temp WHERE type='0' AND referrer='$_POST[id]'")or die(mysqli_error($con));
			
			$cash=0;
			$paid=0;
			$profit=0;
			foreach($_POST['data'] as $data){
				$payment=json_decode($data,true);
				$child=0;
				switch($payment['method']){
					case 1:
						if($payment['amount']!=0 AND $payment['amount']!='')$cash+=$payment['amount'];
						break;
					case 2:
						$transfer=date('Y-m-d',strtotime($payment['transfer']));
						mysqli_query($con,"INSERT INTO sales_cheque(date,customer,cheque,bank,transfer,amount,used,added,user) 
						VALUES('$date','$_POST[customer]','$payment[cheque]','$payment[bank]','$transfer','$payment[amount]','$payment[amount]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
						$child=mysqli_insert_id($con);
						break;
					case 4:
						mysqli_query($con,"INSERT INTO sales_card(amount,bank) VALUES('$payment[amount]','$payment[pos]')") or die(mysqli_error($con));
						$child=mysqli_insert_id($con);
						break;
					case 5:
						$amount=$payment['amount'];
						$profit-=$amount;
						$resul=mysqli_query($con,"SELECT * FROM sales_points WHERE (points-used)>0 AND customer='$_POST[customer]' ORDER BY expiry ASC")or die(mysqli_error($con));
						while($row=mysqli_fetch_assoc($resul) AND $amount>0){
							$available=$row['points']-$row['used'];
							$use=$amount;
							if($available<$amount)$use=$available;
							$amount-=$use;
							mysqli_query($con,"UPDATE sales_points SET used=used+$use WHERE id='$row[id]'") or die(mysqli_error($con));
						}
						break;
					case 6:
						$amount=$payment['amount'];
						$profit-=$amount;
						mysqli_query($con,"UPDATE sales_vouchers SET used=used+$amount WHERE code='$payment[code]'");
						$result=mysqli_query($con,"SELECT * FROM sales_vouchers WHERE code='$payment[code]'");
						$row=mysqli_fetch_assoc($result);
						$child=$row['id'];
						if($row['used']==$row['amount'] || $row['type']==1)mysqli_query($con,"UPDATE sales_vouchers SET code='',code_used='$payment[code]',used=amount WHERE code='$payment[code]'") or die(mysqli_error($con));
						break;
				}
				if($payment['method']>1){
					$counter_id=0;
					if(isset($_COOKIE['Counter_login'])){
						$Counter_login=json_decode($_COOKIE['Counter_login']);
						$counter_id=$Counter_login->id;
					}
					$paid+=$payment['amount'];
					if($payment['method']==8){
						$child=$payment['upay'];
						mysqli_query($con,"UPDATE upay SET customer='$_POST[customer]',sales='$_POST[id]' WHERE id='$child'");
					}
					mysqli_query($con,"INSERT INTO sales_payments(date,amount,cash,type,sales,child,session,added,user) 
					VALUES('$date','$payment[amount]','0','$payment[method]','$_POST[id]','$child','$counter_id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				}
			}
			$delivery_charge=0;
			if($_POST['delivery_charge']<>'')$delivery_charge=$_POST['delivery_charge'];
			if($cash!=0){
				$result=mysqli_query($con,"SELECT SUM(qty*selling_price) total FROM products_logs WHERE type='0' AND referrer='$_POST[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				$payable=$row['total']+$delivery_charge-$paid;
				if($cash<$payable)$payable=$cash;
				$counter_id=0;
				if(isset($_COOKIE['Counter_login'])){
					$Counter_login=json_decode($_COOKIE['Counter_login']);
					$counter_id=$Counter_login->id;
				}
				mysqli_query($con,"INSERT INTO sales_payments(date,amount,cash,type,sales,child,session,added,user) 
					VALUES('$date','$payable','$cash','1','$_POST[id]','$child','$counter_id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
			}
			$q="";
			$plat=0;
			if(isset($_POST['plat']) AND $_POST['plat']>0)$plat=$_POST['plat'];
			if($_POST['customer']>0)$q=",customer=$_POST[customer],plat='$plat'";
			$counter_id=0;
			if(isset($_COOKIE['Counter_login'])){
				$Counter_login=json_decode($_COOKIE['Counter_login']);
				$counter_id=$Counter_login->id;
			}
			$delivery_charge+=$charge;
			mysqli_query($con,"UPDATE sales SET closed='1',charge='$delivery_charge',till='$_COOKIE[sales_counter]',branch='$_SESSION[branch]',session='$counter_id',added='$current_time',user='$_SESSION[user_id]'$q WHERE id='$_POST[id]'");
			$return=array();
			$return['html']='';
			$return['text']='';
			$return['mobile']='';
			if($_POST['customer']>0){//(mrp*0.9)
				$result=mysqli_query($con,"SELECT s.*,c.mobile,c.whatsapp,c.fname,c.sms,c.plat,d.id delivery_id,d.status,
				(SELECT SUM(qty*(selling_price-if(purchased>0,purchased,(selling_price*0.9)))) FROM products_logs WHERE type='0' AND referrer=s.id AND product<>2968) profit,
				(SELECT SUM(points-used) total FROM sales_points WHERE customer=s.customer AND (expiry>='$date' OR expiry is null)) total_points
				FROM sales s LEFT JOIN sales_delivery d ON d.sales=s.id LEFT JOIN customers c ON c.id=s.customer WHERE s.id='$_POST[id]' AND s.customer>0")or die(mysqli_error($con));
				while($row=mysqli_fetch_assoc($result)){
					$profit+=$row['profit'];
					if(in_array($row['plat'],array(0,2,4,6,8))){//points and coupon
						$points=($row['profit']*0.1);
						mysqli_query($con,"INSERT INTO sales_points(sale,customer,points) VALUES('$_POST[id]','$row[customer]','$points')")or die(mysqli_error($con));
						$total_points=number_format($row['total_points']+$points,2);
						$points=number_format($points,2);
						$message="Dear $row[fname], you have earned $points Points for your purchase at $business_name on $current_time.\nBalance is $total_points";
						$messaged=date("Y-m-d H:i:s");//,strtotime('+10 Minutes',time())
						if($points>3 AND substr($row['mobile'],1,1)==7 AND $row['sms']==1){
							mysqli_query($con,"INSERT INTO sms_sent(branch,number,type,message,messaged,user) VALUES('$_SESSION[branch]','$row[mobile]','0','$message','$messaged','$_SESSION[user_id]')")or die(mysqli_error($con));
						}
						$msg="";
						if($row['profit']>=100 AND isset($_SESSION['soft']['settings']['cash_voucher']) AND $_SESSION['soft']['settings']['cash_voucher']==1){
							$code=generate_random_code_for_voucher();
							$amount=round(($row['profit']/2)/25)*25;
							if($amount>=150)$amount=150;
							$cons=15;
							$date_diff=7;
							$min_bill=$amount*$cons;
							$valid_from = date('Y-m-d', strtotime("+1 days",time()));
							$valid = date('Y-m-d', strtotime("+$date_diff days",time()));
							$msg="Enjoy a ".number_format($amount,2)." Rs Saving when you purchase On OR Before ".date('d-m-Y',strtotime($valid))."\nCode :  $code";
							$msg.="\nMin.Bill Amount : ".number_format($min_bill,2);
							$msg.="\nNot Valid today";
							$msg.="\n$business_name";
							mysqli_query($con,"INSERT INTO sms_sent(branch,number,type,message,messaged,user) VALUES('$_SESSION[branch]','$row[mobile]','0','$msg','$current_time','0')")or die(mysqli_error($con));
							mysqli_query($con,"INSERT INTO sales_vouchers(date,customer,reason,code,type,amount,valid_from,valid,user_eligiblity,min_bill,status,added,user) 
							VALUES('$date','$row[customer]','ON Sales Promo','$code','0','$amount','$valid_from','$valid','0','$min_bill',1,'$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
							$return['html'].='<style>
							.promo_box{-webkit-border-radius: 30px;-moz-border-radius: 30px;border-radius: 30px;border: 1px solid #000000;background-color: #FFFFFF;padding: 6px;
							font-family: Verdana, Geneva, sans-serif;font-size: 11pt;color: #888888;text-align: left;}
							</style>
							<table class="main promo_box print_invoice" style="page-break-before:always">
							<thead><tr><th colspan="2" style="text-align: center;">*** Coupon ***</th></tr></thead><tbody>
								<tr><td>Code : </td><td>'.$code.'</td></tr>
								<tr><td>Expires : </td><td>'.date('d-m-Y',strtotime($valid)).'</td></tr>
								<tr><td>Amount : </td><td>'.number_format($amount,2).'</td></tr>
								<tr><td>Min.Bill Amount : </td><td>'.number_format($min_bill,2).'</td></tr>
							</tbody></table>';
						}
					}
					
					if($row['delivery_id']>0 AND $row['status']<2){
						mysqli_query($con,"UPDATE sales_delivery SET status='2' WHERE id='$row[delivery_id]'");
						$msg="Your Order $row[delivery_id] is Ready.\nEither you may Pickup at DFC or wait for the delivery.";
						$msg.="\n$business_name";
						mysqli_query($con,"INSERT INTO sms_sent(branch,number,mask,type,message,messaged,user) VALUES('$_SESSION[branch]','$row[mobile]','1','0','$msg','$current_time','0')")or die(mysqli_error($con));
					}
					if(substr($row['mobile'], 0,2)=='07' AND $row['whatsapp']==1){
						$return['text']=whatsapp_receipt($_POST['id']);
						$return['mobile']='94'.substr($row['mobile'], 1);//'94773518574';
					}
				}
			}
			$return['html'].=print_receipt($_POST['id']);
			echo json_encode($return);
			// if($title=='old'){
				// echo '<table class="main promo_box print_invoice" style="page-break-before:always">
				// <thead><tr><th colspan="2" style="text-align: center;">*** Error Report ***</th></tr></thead><tbody>
					// <tr><td>ID : </td><td>'.$_POST['id'].'</td></tr>
					// <tr><td>Description : </td><td>Older Type</td></tr>
					// <tr><td colspan="2">Plz Pass this note to IT Department!</td></tr>
				// </tbody></table>';
			// }
		}
		return;
	}
	else if(isset($_POST['print_delivery_list'])){
		echo print_delivery_list($_POST['print_delivery_list']);
		return;
	}
	else if(isset($_POST['form_submit'])){//print_r($_POST);
		switch($_POST['form_submit']){
			case 'add_sales':
				if(!isset($_COOKIE['sales_counter']))return;
				if($_POST['invoice']==0 AND $_POST['log']==0){
					if(!isset($_POST['date']))$_POST['date']=date('Y-m-d');
					$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
					$customer='NULL';
					if(isset($_POST['customer']) AND $_POST['customer']>0)$customer=$_POST['customer'];
					$plat=0;
					if(isset($_POST['plat']) AND $_POST['plat']>0)$plat=$_POST['plat'];
					$counter_id=0;
					if(isset($_COOKIE['Counter_login'])){
						$Counter_login=json_decode($_COOKIE['Counter_login']);
						$counter_id=$Counter_login->id;
					}
					mysqli_query($con,"INSERT INTO sales(customer,date,plat,till,branch,session,added,user) 
					VALUES($customer,'$_POST[date]','$plat','$_COOKIE[sales_counter]','$_SESSION[branch]','$counter_id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					$_POST['invoice']=mysqli_insert_id($con);
				}
				else if(isset($_POST['customer']) AND $_POST['customer']>0){
					mysqli_query($con,"UPDATE sales SET customer='$_POST[customer]' WHERE id='$_POST[invoice]'");
				}
				$pid=0;
				if(isset($_POST['log_id']) AND $_POST['log_id']!='')$pid=$_POST['log_id'];
				if($_POST['invoice']>0){
					if($_POST['log']>0){
						mysqli_query($con,"UPDATE products_logs_temp SET qty='$_POST[qty]',mrp='$_POST[mrp]',selling_price='$_POST[selling_price]',purchased='$_POST[purchased]' WHERE id='$_POST[log]'")or die(mysqli_error($con));
					}
					else {
						mysqli_query($con,"INSERT INTO products_logs_temp(product,type,referrer,qty,purchased,mrp,selling_price,status,added,user) 
						VALUES('$_POST[id]',0,'$_POST[invoice]','$_POST[qty]','$_POST[purchased]','$_POST[mrp]','$_POST[selling_price]','$pid','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
						$_POST['log']=mysqli_insert_id($con);
					}
				}
				echo json_encode(array('invoice'=>$_POST['invoice'],'log'=>$_POST['log']));
				break;
			case 'update_customer':
				mysqli_query($con,"UPDATE sales SET customer='$_POST[customer]' WHERE id='$_POST[id]'");
				break;
			case 'no_whatsapp':
				if($_POST['id']>0)mysqli_query($con,"UPDATE customers SET whatsapp='0' WHERE id='$_POST[id]'");
				break;
			case 'attendance':
				mysqli_query($con,"INSERT INTO users_log(branch,user,logged) VALUES('$_SESSION[branch]','$_POST[id]','$current_time')")or die(mysqli_error($con));
				break;
			case 'bill_payments_retry':
				$result=mysqli_query($con,"SELECT * FROM bill_payments WHERE id='$_POST[id]'");
				$row=mysqli_fetch_assoc($result);
				$added=date('Y-m-d H:i:s',strtotime("+1 seconds ".$row['added']));
				mysqli_query($con,"UPDATE bill_payments SET added='$added',status='0' WHERE id='$_POST[id]'");
				break;
			case 'bill_payments':
				
				break;
			case 'delivery_invoice'://print_r($_POST);
				if($_POST['id']>0){
					mysqli_query($con,"UPDATE sales SET closed='-1' WHERE id='$_POST[id]'");
					mysqli_query($con,"INSERT INTO sales_delivery(sales) VALUES('$_POST[id]')");
					$delivery_id=mysqli_insert_id($con);
					$result=mysqli_query($con,"SELECT c.id,c.mobile FROM sales s LEFT JOIN customers c ON c.id=s.customer WHERE s.id='$_POST[id]'");
					$customer=mysqli_fetch_assoc($result);
					if($customer['id']>0 AND $customer['mobile']<>''){
						$result=mysqli_query($con,"SELECT COUNT(*) items,SUM(mrp*qty) total FROM products_logs_temp WHERE referrer='$_POST[id]' AND type=0");
						$row=mysqli_fetch_assoc($result);
						$msg="Order Received.\nOrder No : $delivery_id\nNo of items : $row[items]\nEstimated Amount : $row[total]\n";
						$msg.="\nClick this URL to Update Delivery Location : https://dfc.lk/l/?i=$delivery_id";
						$msg.="\n$business_name";
						mysqli_query($con,"INSERT INTO sms_sent(branch,number,mask,type,message,messaged,user) VALUES('$_SESSION[branch]','$customer[mobile]','1','0','$msg','$current_time','0')")or die(mysqli_error($con));
					}
				}
				break;
			case 'delete_product':
				mysqli_query($con,"DELETE FROM products_logs_temp WHERE id='$_POST[id]'");
				break;
			case 'password_verify'://print_r($_POST);
				$password=md5($_POST['password']);
				mysqli_query($con,"SELECT * FROM users WHERE user_level='0' AND login_auth=1 AND status=1 AND password='$password'");
				echo mysqli_affected_rows($con);
				break;
		}
		return;
	}
	else if(isset($_POST['select'])){//if($_SESSION['user_id'])print_r($_POST);
		switch($_POST['select']){
			case 'product':
				$prices = array();
				$promo=array();
				$avoid=array(9398,12371,12372);
				$avoid_categories=array(99);
				$q="";
				if($_POST['price_vary']==0)$q=" ORDER BY id DESC LIMIT 0,1";
				$result = mysqli_query($con, "SELECT *,(SELECT discount FROM products WHERE id=product) discount,(SELECT category FROM products WHERE id=product) category 
				FROM products_logs WHERE product='$_POST[id]' and type IN (1,3) AND status='1' AND branch='$_SESSION[branch]' and 
				id in(SELECT max(id) FROM products_logs WHERE product='$_POST[id]' AND mrp>0 AND purchased>0 AND qty>0 AND status='1' AND branch='$_SESSION[branch]' and type IN (1,3) group by mrp,mrpc) $q") or die(mysqli_error($con));
				$margin=0;
				$uic=0;
				if(mysqli_affected_rows($con)==0){
					$result = mysqli_query($con, "SELECT *,(SELECT discount FROM products WHERE id=product) discount,(SELECT category FROM products WHERE id=product) category 
				FROM products_logs WHERE product='$_POST[id]' and type IN (1,3) AND status='1' AND branch='$_SESSION[branch]' and 
				id in(SELECT max(id) FROM products_logs WHERE product='$_POST[id]' AND mrp>0 AND purchased>0 AND status='1' AND branch='$_SESSION[branch]' and type IN (1,3) group by mrp,mrpc) $q") or die(mysqli_error($con));
				}
				while($row = $result->fetch_assoc()){
					$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
					$row['cost']=($row['purchased']*$row['qty'])/$qty;
					if($row['qty']==0 Or $row['qty']==''){
						$row['cost']=($row['purchased']>0?($row['purchased']/$row['uic']):$row['purchased']);
					}
					if($row['cost']==$row['mrp']){
						$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
						FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
						$ro=mysqli_fetch_assoc($re);
						$diff=$ro['total']-$ro['tot'];
						if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
							$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
							$row['cost']=$row['mrp']*$discounted_price;
							$row['cost']=($row['cost']*$row['qty'])/$qty;
						}
					}
					$row['cost']=($row['cost']<$row['mrp']?number_format($row['cost'],2, '.', ''):0);
					if($row['cost']>0)$margin=(($row['mrp']-$row['cost'])/$row['mrp'])*100;
					if($margin>=30 AND $row['checked']==0)$row['cost']=0;
					$row['selling_price']=(($row['mrpc']>0 AND ($row['mrpc']<=$row['selling_price'] OR $row['selling_price']==null) AND $row['mrpc']<$row['mrp'])?$row['mrpc']:(($row['selling_price']<$row['mrp'] AND $row['selling_price']>0 AND $row['discount']==1)?$row['selling_price']:$row['mrp']));
					if($uic>0){
						$row['selling_price']=$row['selling_price']/$uic;
						$row['mrp']=$row['mrp']/$uic;
						$row['cost']=$row['cost']/$uic;
					}
					array_push($prices,array('id'=>$row['id'],'mrp'=>$row['mrp'],'selling_price'=>$row['selling_price'],'cost'=>$row['cost']));
					
					if(!in_array($_POST['id'],$avoid) AND !in_array($row['category'],$avoid_categories) AND date('G')>16 AND date('G')<20 AND date('Y-m-d')=='2021-12-07'){
						$pro=array('mrp'=>$row['mrp'],'price'=>($row['mrp']*0.9),'min_bill'=>2000,'exclude_promo'=>0,'limit_variety'=>0,'cash_only'=>1);
						if($margin>=10 || $margin==0)$pro['max_qty']=0;
						else if($margin>=8)$pro['max_qty']=3;
						else $pro['max_qty']=1;
						array_push($promo,$pro);
					}
				}
				$result=mysqli_query($con,"SELECT mrp,price,max_qty,min_qty,min_bill,exclude_promo,limit_variety,cash_only FROM products_offer 
				WHERE start_date<='$date' AND end_date>='$date' AND product='$_POST[id]'");
				while($row = $result->fetch_assoc()){
					array_push($promo,$row);
				}
				echo json_encode(array('prices'=>$prices,'promo'=>$promo));
				break;
			case 'customer' :
				if($_POST['new_customer']==1){
					$mobile=phone_number_format($_POST['id']);
					$result=mysqli_query($con,"SELECT id FROM customers WHERE mobile='$mobile'") or die(mysqli_error($con));
					if(mysqli_affected_rows($con)==0){
						mysqli_query($con,"INSERT INTO customers(mobile,branch,added,user) VALUES('$mobile','$_SESSION[branch]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
						$_POST['id']=mysqli_insert_id($con);
					}
					else {
						$row=mysqli_fetch_assoc($result);
						$_POST['id']=$row['id'];
					}
				}
				
				$result=mysqli_query($con,"SELECT c.*,(SELECT SUM(points-used) FROM sales_points WHERE customer=c.id AND (expiry>='$date' OR expiry is null)) points 
				FROM customers c WHERE c.id='$_POST[id]'") or die(mysqli_error($con));
				$row['points']=0;
				if(mysqli_affected_rows($con)>0){
					$row=mysqli_fetch_assoc($result);
					if($row['points']=='' OR $row['points']==null)$row['points']=0;
				}
				// $result=mysqli_query($con,"SELECT SUM((SELECT SUM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id)-ifnull(paid,0)) receivable 
				// FROM sales s LEFT JOIN (SELECT SUM(amount) paid,sales FROM sales_payments p LEFT JOIN sales s ON s.id=p.sales WHERE s.customer='$_POST[id]' AND s.date>'2021-01-01' AND s.closed=1 GROUP BY p.sales) p ON p.sales=s.id 
				// WHERE s.customer='$_POST[id]' AND s.date>'2021-01-01' AND s.closed=1") or die(mysqli_error($con));
				$result=mysqli_query($con,"SELECT (SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0) si_total,
				(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid,
					SUM(ifnull((SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0),0)-
					ifnull((SELECT sum(amount) FROM sales_payments WHERE s.id=sales),0)) receivable
				FROM sales s LEFT JOIN customers c ON c.id=s.customer 
				WHERE s.closed<>2 AND s.date>'2021-01-01' AND s.customer='$_POST[id]' HAVING (si_total-ifnull(paid,0))>1 ORDER BY s.added DESC") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					$row1=mysqli_fetch_assoc($result);
					if($row1['receivable']>0)$row['credit_limit']-=$row1['receivable'];
				}
				// else 
					// $row['credit_limit']=10000;
				
				$row['payment_methods']='';
				foreach($payment_methods as $val=>$title){
					if($val==0 AND $row['credit_limit']<=0)continue;
					$row['payment_methods'].='<option value="'.$val.'">'.$title.'</option>';
				}
				if($row['fname']=='')$row['fname']='';
				if($row['lname']=='')$row['lname']='';
				echo json_encode($row);
				break;
			case 'points' :
				if($_POST['code']=='')$_POST['code']=mt_rand(100000,999999);
				$result=mysqli_query($con,"SELECT mobile FROM customers c WHERE id='$_POST[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				echo $_POST['code'];
				$message="You are about to redeem your points at $business_name.\nConfirm the redemption with this code : ".$_POST['code'];
				$messaged=date("Y-m-d H:i:s");
				// if($ratio<0.1)$messaged=date("Y-m-d H:i:s",strtotime('+10 Minutes',time()));
				// if($_SESSION['user_id']==1)$row['mobile']='0773518574';
				mysqli_query($con,"INSERT INTO sms_sent(branch,number,mask,type,message,messaged,priority,user) VALUES('$_SESSION[branch]','$row[mobile]','0','0','$message','$messaged',1,'$_SESSION[user_id]')")or die(mysqli_error($con));
				break;
			case 'credit_code' :
				if($_POST['code']=='')$_POST['code']=mt_rand(100000,999999);
				$result=mysqli_query($con,"SELECT mobile,credit_limit FROM customers WHERE id='$_POST[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				echo $_POST['code'];
				$result=mysqli_query($con,"SELECT (SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0) si_total,
				(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid,
					SUM(ifnull((SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0),0)-
					ifnull((SELECT sum(amount) FROM sales_payments WHERE s.id=sales),0)) receivable
				FROM sales s LEFT JOIN customers c ON c.id=s.customer 
				WHERE s.closed<>2 AND s.date>'2021-01-01' AND s.customer='$_POST[id]' HAVING (si_total-ifnull(paid,0))>1 ORDER BY s.added DESC") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0)$row1=mysqli_fetch_assoc($result);
				$message="You have purchased for Rs.$_POST[amount] Credit at $business_name.\nConfirm the Credit with this code : ".$_POST['code'];
				if(isset($row1['receivable']))$message.="\nAvailable Credits : ".$row1['receivable'];
				$message.="\nCredit Limit : ".$row['credit_limit'];
				// if($_SESSION['user_id']==1)$row['mobile']='0773518574';
				mysqli_query($con,"INSERT INTO sms_sent(branch,number,mask,type,message,messaged,priority,user) VALUES('$_SESSION[branch]','$row[mobile]','0','0','$message','$current_time',1,'$_SESSION[user_id]')")or die(mysqli_error($con));
				break;
			case 'voucher' ://print_r($_POST);
				$result=mysqli_query($con,"SELECT * FROM sales_vouchers WHERE code='$_POST[code]'") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					$row['message']='';
					$row['usable']=1;
					if($row['valid_from']>$date){
						$row['usable']=0;
						$row['message']="Voucher Valid From : $row[valid_from]";
					}
					if($row['valid']<$date){
						$row['usable']=0;
						$row['message']="Voucher Expired On $row[valid]";
					}
					if($row['user_eligiblity']==1 AND $row['customer']!=$_POST['customer']){
						$row['usable']=0;
						$row['message']="Code Eligible only for the same user.";
					}
					if($row['min_bill']>0 && $row['min_bill']>$_POST['net_total']){
						$row['usable']=0;
						$row['min_bill']=number_format($row['min_bill']);
						$row['message']="Code Eligible only bills Over Rs.$row[min_bill]";
					}
					$row['amount']=($row['amount']-$row['used']);
				}
				else {
					$row['message']='Invalid Code.';
					$row['usable']=0;
				}
				echo json_encode($row);
				break;
			case 'product_disabled' ://print_R($_POST);
				$result=mysqli_query($con,"SELECT * FROM products WHERE ui_code='$_POST[code]' AND status>=0");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					mysqli_query($con,"UPDATE products SET status='1' WHERE id='$row[id]'");
					echo json_encode($row);
				}
				break;
			case 'list_payments' ://print_R($_POST);
				$Counter_login=json_decode($_COOKIE['Counter_login']);
				$counter_id=$Counter_login->id;
				$q="p.session='$counter_id'";
				if($_SESSION['soft']['user_data']['user_level']==0)$q="p.date='$date' AND p.session is NOT NULL";
				$result=mysqli_query($con,"SELECT p.*,s.title,ss.till,u.first_name FROM payments p LEFT JOIN suppliers s ON (s.id=p.referrer AND p.category=1) 
				LEFT JOIN salary_staff sas ON (sas.id=p.referrer AND p.category=2) LEFT JOIN users u ON u.id=sas.staff
				LEFT JOIN sales_session ss ON ss.id=p.session WHERE p.branch='$_SESSION[branch]' AND $q")or die(mysqli_error($con));
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr><td>'.date('h:i A',strtotime($row['added'])).' : '.($_SESSION['soft']['user_data']['user_level']==0?'['.$row['till'].'] ':'').$payments_type[$row['category']].($row['description']<>''?' ('.$row['description'].')':'').($row['title']<>''?' ['.$row['title'].']':'').($row['first_name']<>''?' ['.$row['first_name'].']':'').'</td><td class="right">'.number_format($row['amount'],2).'</td></tr>';
				}
				$q="t.flow IN (2,3) AND t.f_bank='$counter_id'";
				if($_SESSION['soft']['user_data']['user_level']==0)$q="t.date='$date' AND t.flow IN (2,3)";
				$result=mysqli_query($con,"SELECT t.*,ba.account,b.short,ss.till FROM bank_transactions t LEFT JOIN bank_accounts ba ON ba.id=t.bank 
				LEFT JOIN banks b ON b.id=ba.bank LEFT JOIN sales_session ss ON ss.id=t.f_bank WHERE $q");
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr><td>'.date('h:i A',strtotime($row['added'])).' : '.($_SESSION['soft']['user_data']['user_level']==0?'['.$row['till'].'] ':'').'Account Transfer '.($row['flow']==2?'to':'from').' '.$row['account'].' '.($row['short']<>''?'['.$row['short'].'] ':'').($row['title']<>''?' ('.$row['title'].')':'').'</td><td class="right">'.number_format($row['amount'],2).'</td></tr>';
				}
				break;
			case 'upay' ://print_R($_POST);
				$from_q=date("Y-m-d H:i:s",strtotime("-1000 minutes"));
				$from=date("Y-m-d H:i:s",strtotime("-10 minutes"));
				$to=date("Y-m-d H:i:s");
				$amount_from=floor($_POST['amount']);
				$amount_to=ceil($_POST['amount']);
				$result=mysqli_query($con,"SELECT * FROM upay 
				WHERE date='$date' AND customer is NULL AND (amount BETWEEN '$amount_from' AND '$amount_to') AND status='1' AND (paid BETWEEN '$from' AND '$to' OR (paid BETWEEN '$from_q' AND '$to' AND merchant_id LIKE 'Q-%'))");
				echo '<table class="table mb-0 form-group">
				<tbody>';
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr class="upay" id="'.$row['id'].'"><td>'.date('h:i A',strtotime($row['added'])).'</td><td>'.$row['transaction_id'].'</td><td class="right">'.number_format($row['amount'],2).'</td></tr>';
				}
				echo '</tbody></table>';
				break;
			case 'pending_invoice_list':
				$q="AND s.user='$_SESSION[user_id]'";
				// if(file_get_contents('session_push')==1){
					// if(!isset($_SESSION['soft']['settings'])){
						// $result=mysqli_query($con,"SELECT * FROM settings WHERE branch='$_SESSION[branch]'");
						// while($row=mysqli_fetch_assoc($result))$_SESSION['soft']['settings'][$row['title']]=$row['description'];
						// $user_id=$_SESSION['soft']['user_id'];
					// }
					// if($_SESSION['soft']['settings']['language']<>'en' AND !isset($_SESSION['soft']['language'])){
						// $result=mysqli_query($con,"SELECT * FROM language");
						// $_SESSION['soft']['language']=array();
						// while($row=mysqli_fetch_assoc($result))$_SESSION['soft']['language'][$row['en']]=$row[$_SESSION['soft']['settings']['language']];
					// }
					// $result=mysqli_query($con,"SELECT * FROM users WHERE id='$_SESSION[user_id]'");
					// $row=mysqli_fetch_assoc($result);
					// if($row['user_level']==0)$q="";
					// else {
						// $row['permissions']=json_decode($row['permissions'],true);
						// $ids=implode(',',array_keys($row['permissions']));
						// $q=" AND id IN($ids)";
					// }
					// $menus = mysqli_query($con,"SELECT * FROM menu WHERE status=1 $q");
					// while($menu=mysqli_fetch_assoc($menus)){
						// $title=str_replace(' ','_',strtolower($menu['title']));
						// $_SESSION['soft']['menus'][$menu['plugin'].'/'.$title]=$menu;
						// $_SESSION['soft']['menus'][$menu['plugin'].'/'.$title]['permission']=($row['user_level']==0?json_decode($menu['permissions'],true):$row['permissions'][$menu['id']]);
					// }
				// }
				$result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,
				(SELECT COUNT(*) FROM products_logs WHERE type='0' AND referrer=s.id) logs,(SELECT COUNT(*) FROM products_logs_temp WHERE type='0' AND referrer=s.id) logs_temp
				FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_points p ON p.sale=s.id 
				WHERE s.closed<=0 AND s.date='$date' AND s.id<>'$_POST[id]' $q GROUP BY s.id LIMIT 0,10") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					$htm='';
					$counter=1;
					while($row=mysqli_fetch_assoc($result)){
						if($row['logs_temp']>0 OR $row['logs']>0){
							$htm.='<tr id="'.$row['id'].'" style="cursor:pointer;" class="pending_invoice">
								<td class="center">'.$counter++.'</td>
								<td class="center">'.($row['invoice']>0?$row['invoice']:$row['id']).'</td>
								<td class="center">'.date('d-m-Y',strtotime($row['date'])).date(' h:i A',strtotime($row['added'])).'</td>
								<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
								<td>'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
								<td class="right">'.Number_format($row['logs_temp'],2).'</td>
								<td class="right">'.$row['first_name'].'</td>
								<td class="center"><a href="'.$branch_url.'#sales/pos\\'.$row['id'].'"><button type="button" class="btn btn-primary waves-effect waves-light">Edit</button></a></td>
							</tr>';
						}
					}
					if($htm<>''){
						echo'<div class="row"  style="font-size: 10px;">
							<div class="col-12">
								<div class="card">
									<div class="card-body table-responsive">
									<table table="sales" style="width: 100%;">
										<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th>
										<th>'.language('Count').'</th><th>'.language('Staff').'</th><th>'.language('Action').'</th>
										</tr></thead><tbody>'.$htm.'</tbody></table>
									</div>
								</div>
							</div>
						</div>';
					}
				}
				// $result=mysqli_query($con,"SELECT s.*,
				// (SELECT COUNT(*) FROM products_logs WHERE type='0' AND referrer=s.id) logs,(SELECT COUNT(*) FROM products_logs_temp WHERE type='0' AND referrer=s.id) logs_temp
				// FROM sales s LEFT JOIN sales_points p ON p.sale=s.id WHERE s.closed<=0 AND s.date<'$date' GROUP BY s.id LIMIT 0,10") or die(mysqli_error($con));
				// while($row=mysqli_fetch_assoc($result)){
					// if($row['logs_temp']==0 AND $row['logs']==0){
						// mysqli_query($con,"DELETE FROM sales WHERE id='$row[id]'")or die(mysqli_error($con));
					// }
				// }
				// if(isset($_COOKIE['sales_counter']) AND $_COOKIE['sales_counter']==1)return;
				
				
				// $result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,d.id delivery_id,
				// (SELECT COUNT(*) FROM products_logs WHERE type='0' AND referrer=s.id) total
				// FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_delivery d ON d.sales=s.id 
				// LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type='0')
				// WHERE d.status='2' AND s.date>'2021-05-20' AND s.id<>'$_POST[id]' GROUP BY s.id ORDER BY d.id ASC LIMIT 0,10") or die(mysqli_error($con));
				// if(mysqli_affected_rows($con)>0){
					// echo'<div class="row"  style="font-size: 10px;">
						// <div class="col-12">
							// <div class="card">
								// <div class="card-body table-responsive">
								// <table table="sales" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
									// <thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th>
									// <th>'.language('Total').'</th><th>'.language('Staff').'</th><th>'.language('Action').'</th>
									// </tr></thead><tbody>';
									// $counter=1;
									// while($row=mysqli_fetch_assoc($result)){
										// echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
											// <td class="center">'.$counter++.'</td>
											// <td class="center">'.$row['delivery_id'].'</td>
											// <td class="center">'.date('d-m-Y',strtotime($row['date'])).date(' h:i A',strtotime($row['added'])).'</td>
											// <td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
											// <td>'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
											// <td class="right">'.Number_format($row['total'],2).'</td>
											// <td class="right">'.$row['first_name'].'</td>
											// <td class="center">
											// <button type="button" class="btn btn-primary waves-effect waves-light print_delivery_list">Dispatched</button></td>
										// </tr>';
									// }
								// echo '</tbody></table>
								// </div>
							// </div>
						// </div>
					// </div>';
				// }
				
				$result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,d.id delivery_id,d.print,
				(SELECT COUNT(*) FROM products_logs WHERE type='0' AND referrer=s.id) total
				FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_delivery d ON d.sales=s.id 
				LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type='0')
				WHERE s.closed='-1' AND s.date>'2021-04-01' AND s.id<>'$_POST[id]' GROUP BY s.id Having total=0 ORDER BY d.id ASC") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo'<div class="row"  style="font-size: 10px;">
						<div class="col-12">
							<div class="card">
								<div class="card-body table-responsive">
								<table table="sales" style="width: 100%;">
									<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('DID').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th>
									<th>'.language('Total').'</th><th>'.language('Staff').'</th><th>'.language('Action').'</th>
									</tr></thead><tbody>';
									$counter=1;
									while($row=mysqli_fetch_assoc($result)){
										echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.($row['print']==1?'plat_1':'').'">
											<td class="center">'.$counter++.'</td>
											<td class="center">'.$row['id'].'</td>
											<td class="center">'.$row['delivery_id'].'</td>
											<td class="center">'.date('d-m-Y',strtotime($row['date'])).date(' h:i A',strtotime($row['added'])).'</td>
											<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
											<td>'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
											<td class="right">'.Number_format($row['total'],2).'</td>
											<td class="right">'.$row['first_name'].'</td>
											<td class="center"><a href="'.$branch_url.'#sales/pos\\'.$row['id'].'">
											<button type="button" class="btn btn-primary waves-effect waves-light">Edit</button></a>
											<button type="button" class="btn btn-primary waves-effect waves-light print_delivery_list">Print</button></td>
										</tr>';
									}
								echo '</tbody></table>
								</div>
							</div>
						</div>
					</div>';
				}
				break;
			case 'stock_count' :
				$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code,p.mrp price,u.first_name 
					FROM products_logs l LEFT JOIN products p on l.product=p.id LEFT JOIN users u ON u.id=l.user
					WHERE p.ui_code='$_POST[code]' AND l.type='2' AND l.branch='$_SESSION[branch]' AND date(l.added)='$today'") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					echo '<fieldset class="container">
						<div class="row">
							<div class="col-12">
								<table class="table mb-0 form-group">
									<thead><tr><th id="selectable">#</th><th>Product</th><th class="right">MRP</th><th class="right">Qty</th><th>Staff</th></tr></thead>
									<tbody>';
										$counter=1;
										while($row=$result->fetch_assoc()){
											echo '<tr changed="0" id="'.$row['id'].'">
											<td>'.$counter.'</td>
											<td>'.$row['ui_code'].' - '.$row['title'].'</td>
											<td class="right">'.$row['price'].'</td>
											<td class="right">'.$row['qty'].'</td>
											<td>'.$row['first_name'].'</td>
											</tr>';
										}
										$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code,p.mrp price,u.first_name 
											FROM products_logs_temp l LEFT JOIN products p on l.product=p.id LEFT JOIN users u ON u.id=l.user
											WHERE p.ui_code='$_POST[code]' AND l.type='2' AND l.branch='$_SESSION[branch]' AND date(l.added)='$today'") or die(mysqli_error($con));
										while($row=$result->fetch_assoc()){
											echo '<tr changed="0" id="'.$row['id'].'">
											<td>'.$counter.'</td>
											<td>'.$row['ui_code'].' - '.$row['title'].'</td>
											<td class="right">'.$row['price'].'</td>
											<td class="right">'.$row['qty'].'</td>
											<td>'.$row['first_name'].'</td>
											</tr>';
										}
									echo '</tbody>
								</table>
							</div>
						</div>
					</fieldset>';
				}
				break;
		}
		return;
	}
	else if(isset($_POST['edit']) AND isset($_POST['value']) AND isset($_POST['field']) AND isset($_POST['id'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if($_POST['field']=='available_items'){
			stock_adj($_POST['id'],$value);
		}
		else mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		if($_POST['edit']=='products_logs' AND in_array($_POST['field'],array('qty','free','uic'))){
			$result=mysqli_query($con,"SELECT product FROM $_POST[edit] WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			update_qty($row['product']);
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'recent_invoices' :
				echo '<div class="card col-lg-10 mx-auto">
				<div class="card-body">
					<table class="table table-sm">
					<thead><tr><th class="center">#</th><th class="center">ID</th><th class="center">'.language('Time').'</th><th>'.language('Customer').'</th>
					<th>'.language('Type').'</th><th class="right">'.language('Total').'</th><th class="right">'.language('Discount').'</th>
					<th class="right">%</th><th class="right">'.language('Paid').'</th><th class="right">'.language('Balance').'</th>
					<th>'.language('Staff').'</th><th class="center"></th><th class="center"></th>
					</tr></thead><tbody>';
					$q="WHERE s.date='$date' AND s.branch='$_SESSION[branch]'";
					if($_GET['type']==0 AND isset($_COOKIE['Counter_login'])){
						$Counter_login=json_decode($_COOKIE['Counter_login']);
						$counter_id=$Counter_login->id;
						$q.=" AND s.session='$counter_id'";
					}
					if(isset($_GET['customer']) AND $_GET['customer']>0)$q="WHERE s.customer='$_GET[customer]'";
					$result=mysqli_query($con,"SELECT s.*,c.fname,c.lname,c.mobile,c.whatsapp,c.type,c.plat mem,u.first_name,
					(SELECT SUM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id) total,
					(SELECT SUM(amount) FROM sales_payments WHERE type IN(5,6) AND sales=s.id) cost,
					(SELECT SUM(amount*0.025) FROM sales_payments WHERE type=4 AND sales=s.id) comm,
					(SELECT SUM(amount) FROM sales_payments WHERE sales=s.id) paid,SUM((l.selling_price-l.purchased)*l.qty) profit,SUM((l.mrp-l.selling_price)*l.qty) dis
					FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_points p ON p.sale=s.id 
					LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type='0')
					$q GROUP BY s.id ORDER BY s.added DESC LIMIT 0,10") or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_assoc($result)){
						$class='';
						$row['profit']-=$row['comm'];
						$row['profit']-=$row['cost'];
						$row['total']+=$row['charge'];
						if($row['profit']<0)$class='table-warning';
						if($row['plat']>0)$class='plat_'.$row['plat'];
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.($row['closed']==0?'pending_invoice':'').'">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['id'].'</td>
							<td class="center">'.((isset($_GET['customer']) AND $_GET['customer']>0)?date('d-m-Y',strtotime($row['date'])).', ':'').date('h:i A',strtotime($row['added'])).'</td>
							<td>'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].'</td>
							<td class="'.$class.'">'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
							<td class="right">'.Number_format($row['total'],2).'</td>
							<td class="right">'.Number_format($row['dis'],2).'</td>
							<td class="right">'.($row['total']>0?Number_format(($row['dis']/($row['total']+$row['dis']))*100,2):'').'</td>
							<td class="right">'.Number_format($row['paid'],2).'</td>
							<td class="right">'.Number_format($row['total']-$row['paid'],2).'</td>
							<td>'.$row['first_name'].'</td>
							<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light print_invoice_btn">Print</button></td>
							<td class="center">'.
							((substr($row['mobile'], 0,2)=='07' AND $row['whatsapp']==1)?'<button type="button" class="btn btn-primary waves-effect waves-light whatsapp_invoice_btn">Whatsapp</button>':'')
							.'</td>
						</tr>';
					}
				echo '</tbody></table>
				</div>
			</div>';
				break;
			case 'bill_payments_summary' :
				echo '<div class="card col-lg-10 mx-auto">
				<div class="card-body">
					<table class="table table-sm">
					<thead><tr><th>#</th><th>Till</th><th>'.language('Time').'</th><th>'.language('Customer').'</th><th>'.language('Type').'</th><th>'.language('Amount').'</th>
					<th>'.language('Number').'</th><th>'.language('Reference').'</th><th>'.language('Status').'</th>
					<th>'.language('Staff').'</th><th>'.language('Action').'</th>
					</tr></thead><tbody>';
					$q="WHERE b.branch='$_SESSION[branch]'";
					if(isset($_GET['id']) AND $_GET['id']>0)$q.=" AND s.customer='$_GET[id]'";
					$result=mysqli_query($con,"SELECT b.*,d.title,s.till,IF(b.type=0,CONCAT(c.mobile,'<br/>',c.fname,' ',c.lname),IF(b.type=2,CONCAT(c1.mobile,'<br/>',c1.fname,' ',c1.lname),CONCAT('Staff<br/>',u.first_name))) name,
					IF(b.type=2,c1.fname,u.first_name) user
					FROM bill_payments b LEFT JOIN products_logs l ON (b.log=l.id AND b.type=0) LEFT JOIN sales s ON l.referrer=s.id LEFT JOIN customers c ON c.id=s.customer 
					LEFT JOIN customers c1 ON (c1.id=b.user AND b.type=2) LEFT JOIN users u ON (u.id=b.user AND b.type<2) LEFT JOIN bill_payments_domain d ON d.id=b.service
					$q ORDER BY b.id DESC LIMIT 0,25") or die(mysqli_error($con));
					$counter=1;
					$status=array(0=>'Pending',1=>'Processing',2=>'Success',3=>'Error',4=>'Via AgoySoft');
					$status_class=array(0=>'',1=>'success',2=>'success',3=>'warning',4=>'pending_invoice');
					while($row=mysqli_fetch_assoc($result)){
						$response=json_decode($row['response'],true);
						$description='';
						if(isset($response['description']))$description=$response['description'];
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.$status_class[$row['status']].'">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['till'].'</td>
							<td class="center">'.date('d-m-Y, h:i A',strtotime($row['added'])).'</td>
							<td>'.$row['name'].'</td>
							<td>'.$row['title'].'</td>
							<td class="right">'.Number_format($row['amount'],2).'</td>
							<td class="center">'.$row['number'].'</td>
							<td class="center">'.$row['reference'].'</td>
							<td title="'.($row['status']==3?$description:'').'">'.($row['status']==3?$description:$status[$row['status']]).'</td>
							<td>'.$row['user'].'</td>
							<td class="center">'.($row['status']==3?'<button type="button" class="btn btn-primary waves-effect waves-light bill_payments_retry">Retry</button>':'').'</td>
						</tr>';
					}
				echo '</tbody></table>
				</div>
			</div>';
				break;
			case 'product':
				echo view_a_product($_GET['id'],str_replace('.php','',str_replace($dir,'',__FILE__)),str_replace($dir,'',dirname(__FILE__)),$_GET['type']);
				break;
			case 'customer':
				echo view_a_customer($_GET['id'],str_replace('.php','',str_replace($dir,'',__FILE__)),str_replace($dir,'',dirname(__FILE__)));
				break;
			case 'bill_payments':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Payee</div>
								<div class="form-group col-lg-10"><select class="form-control select" id="domain"><option></option>';
									$result=mysqli_query($con,"SELECT * FROM bill_payments_domain WHERE status='1'");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" reference="'.$row['reference'].'" length="'.$row['length'].'">'.$row['title'].'</option>';
								echo '</select></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Account No</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control" id="no" required/></div>
							</div>
							<div class="row reference">
								<div class="form-group col-lg-2">Reference No</div>
								<div class="form-group col-lg-10"><input type="text" data-mask="0999999999" class="form-control" id="reference"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Amount</div>
								<div class="form-group col-lg-10"><input type="number" class="form-control" id="amount" min="25" placeholder="Minimum Rs25/=" required/></div>
							</div><input type="hidden" name="form_submit" value="bill_payments"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block add_bill_payments">Add</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'delivery':
				if(!isset($_GET['did']))$_GET['did']='';
				if(!isset($_GET['rider']))$_GET['rider']='';
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Delivery ID</div>
								<div class="form-group col-lg-10"><select class="form-control select" id="did" required><option></option><option value="New Delivery" '.($_GET['did']=='New Delivery'?'selected':'').'>Create New Delivery</option>';
									$result=mysqli_query($con,"SELECT d.*,c.mobile,c.fname FROM sales_delivery d LEFT JOIN sales s ON s.id=d.sales LEFT JOIN customers c ON c.id=s.customer 
									WHERE d.status='0' AND s.id is NOT NULL");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" '.($_GET['did']==$row['id']?'selected':'').'>'.$row['id'].' ('.$row['mobile'].' - '.$row['fname'].')</option>';
								echo '</select></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Rider</div>
								<div class="form-group col-lg-10"><select class="form-control select" id="rider" required><option></option>';
									$result=mysqli_query($con,"SELECT * FROM users WHERE delivery='1'");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" '.($_GET['rider']==$row['id']?'selected':'').'>'.$row['first_name'].'</option>';
								echo '</select></div>
							</div><input type="hidden" name="form_submit" value="bill_payments"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block delivery_data">Select</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'delete_product':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="password_verify" enctype="multipart/form-data">
							<div class="row">
								<div class="form-group col-lg-4">Admin Password</div>
								<div class="form-group col-lg-8"><input type="password" class="form-control" name="password" required/></div>
							</div><input type="hidden" name="form_submit" value="password_verify"/>
							<div class="row"><div class="form-group col-lg-12"><button type="button" id="password_verify" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Grant Permission</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'attendance':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<table class="table table-sm" table="users_log" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Name').'</th><th>'.language('NIC').'</th><th></th></tr></thead><tbody>';
							$result=mysqli_query($con,"SELECT * FROM users WHERE id>0 AND status='1'") or die(mysqli_error($con));
							$counter=1;
							while($row=mysqli_fetch_assoc($result)){
								$branches=json_decode($row['branch'],true);
								if(in_array($_SESSION['branch'],$branches)){
									$result1=mysqli_query($con,"SELECT * FROM users_log WHERE user='$row[id]' AND DATE(logged)='$date'") or die(mysqli_error($con));
									$rows=mysqli_affected_rows($con);
									echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td>
										<td>'.$row['first_name'].' '.$row['last_name'].'</td>
										<td>'.$row['nic'].'</td>
										<td><button type="button" class="btn btn-primary waves-effect waves-light attendance">'.($rows%2==0?'IN':'OUT').'</button></td>
									</tr>';
								}
							}
						echo '</tbody></table>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_GET['product'])){
		$result=mysqli_query($con,"SELECT id FROM products WHERE ui_code='$_GET[product]'");
		if(mysqli_affected_rows($con)==1){
			$row = $result->fetch_assoc();
			update_qty($row['id']);
			$result=mysqli_query($con,"SELECT * FROM products_logs_temp WHERE product='$row[id]' AND type='2' AND branch='$_SESSION[branch]' AND date(added)='$today'");
			if(mysqli_affected_rows($con)==1){
				$row1 = $result->fetch_assoc();
				$result=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[id]' AND branch='$_SESSION[branch]' AND added>'$row1[added]'");
				$balance=0;
				while($row2 = $result->fetch_assoc()){
					$qty=($row2['uic']>0?($row2['qty']*$row2['uic']):$row2['qty'])+$row2['free'];
					if($row2['type']%2==0)$balance-=$qty;
					else $balance+=$qty;
				}
				$row1['qty']+=$balance;
				$row1['qty']+=$_GET['qty'];
				mysqli_query($con,"UPDATE products_logs_temp SET qty='$row1[qty]',balance='$row1[qty]',added='$current_time' WHERE id='$row1[id]'");
			}
			else mysqli_query($con,"INSERT INTO products_logs_temp(product,type,branch,qty,balance,added,user) VALUES('$row[id]',2,'$_SESSION[branch]','$_GET[qty]','$_GET[qty]','$current_time','$_SESSION[user_id]')");
		}
	}
	else if(isset($_GET['counter'])){
		// $result = mysqli_query($con, "SELECT * FROM settings WHERE title='sales_counters' AND branch='$_SESSION[branch]'");
		// if(mysqli_affected_rows($con)==1)$sales_counters=json_decode(mysqli_fetch_assoc($result)['description']);
		// else {
			// mysqli_query($con, "INSERT INTO settings(title,branch) VALUES('sales_counters','$_SESSION[branch]')");
			// $sales_counters=array();
		// }
		// if(in_array($_GET['counter'],$sales_counters)){
			// echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				// <i class="mdi mdi-block-helper mr-2"></i>Counter Already Registered
				// <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			// </div>';
		// }
		// else {
			// $sales_counters[]=$_GET['counter'];
			// $counters=json_encode($sales_counters);
			// mysqli_query($con, "UPDATE settings SET description='$counters' WHERE title='sales_counters' AND branch='$_SESSION[branch]'");
			// setcookie('sales_counter',$_GET['counter'], time() + (86400 * 365), "/");
			// $_COOKIE['sales_counter']=$_GET['counter'];
		// }
		$result = mysqli_query($con, "SELECT * FROM sales_counters WHERE title='$_GET[counter]' AND branch='$_SESSION[branch]'");
		if(mysqli_affected_rows($con)==1){
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				<i class="mdi mdi-block-helper mr-2"></i>Counter Already Registered
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>';
		}
		else {
			mysqli_query($con, "INSERT INTO sales_counters(title,branch,added,user) VALUES('$_GET[counter]','$_SESSION[branch]','$current_time','$_SESSION[user_id]')");
			$counter=mysqli_insert_id($con);
			setcookie('sales_counter',$counter, time() + (86400 * 7), "/", $domain, true);
			$_COOKIE['sales_counter']=$counter;
		}
	}
	else if(isset($_GET['term'])){
		$result=mysqli_query($con,"SELECT c.*,(SELECT COUNT(*) FROM sales WHERE customer=c.id) visits FROM customers c 
		WHERE fname LIKE '%$_GET[term]%' OR lname LIKE '%$_GET[term]%' OR mobile LIKE '%$_GET[term]%' OR nic LIKE '%$_GET[term]%' 
		OR street LIKE '%$_GET[term]%' OR address LIKE '%$_GET[term]%' OR loyalty_card LIKE '%$_GET[term]%' OR platinum_card LIKE '%$_GET[term]%' 
		OR remind LIKE '%$_GET[term]%' 
		ORDER BY visits DESC LIMIT 0,10")or die(mysqli_error($con));
		$customers=array();
		while($row = $result->fetch_assoc()){
			if($row['nic']=='')$row['nic']='';
			array_push($customers,$row);
		}
		echo json_encode($customers);
		return;
	}
	// if(!isset($_COOKIE['sales_counter']) OR $_SESSION['soft']['settings']['stock_count_at_till']==1){
		echo '<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body stock_count">
						<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-4">
									<label>Product</label>
									<div><input type="text" name="product" class="form-control product_stock" required placeholder="Code"/>
									<ul class="parsley-errors-list filled" id="product_title"></ul></div>
								</div>
								<div class="form-group col-lg-2">
									<label>Qty</label>
									<div><input type="text" name="qty" class="form-control qty" required placeholder="Qty"/></div>
								</div>
								<div class="form-group col-lg-1">
									<label></label>
									<div><button type="button" class="btn btn-primary waves-effect waves-light form_submit_click">Add</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>';
	// }
	if(!isset($_COOKIE['sales_counter']) AND $_SESSION['soft']['user_data']['user_level']==0){
		echo '<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-3">
									<label></label><div><input type="text" name="counter" class="form-control" placeholder="Counter#"/></div>
								</div>
								<div class="form-group col-lg-3"><label></label>
									<div><button type="button" class="btn btn-primary waves-effect waves-light form_submit_click">Register Sales Counter</button></div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>';
	}
	$result=mysqli_query($con,"SELECT p.*,(SELECT COUNT(*) FROM products_logs WHERE product=p.id) so FROM products p WHERE p.status='1' AND (category<>98 OR category is NULL) ORDER BY so DESC");
	$products=array();
	while($row = $result->fetch_assoc()){
		// $row['remind']='';
		array_push($products,$row);
	}
	echo '<script>
	var products='.json_encode($products).';
	</script>';
	unset($products);
	$editable=1;
	$delivery=0;
	if(isset($_GET['id']) AND $_GET['id']>0){
		$result=mysqli_query($con,"SELECT s.*,c.mobile FROM sales s LEFT JOIN customers c ON c.id=s.customer WHERE s.id='$_GET[id]'");
		$sales = $result->fetch_assoc();
		if($sales['closed']==1)$editable=0;
		else if($sales['closed']==-1)$delivery=0;
	}
	
	echo '<div id="stock_count"></div>';
	{
	echo '<div id="pending_invoice_list"></div>';
	$loader = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col-lg-3" id="customer">
							<label></label><div><input type="text" id="customer_field" name="customer" class="form-control customer" placeholder="Customer #" value="'.((isset($sales['mobile']) AND $sales['customer']>0)?$sales['mobile']:'').'"/></div>
						</div>
						<div class="form-group col-lg-12 hide" id="customer_data"></div>
					</div>
				</div>
			</div>
		</div>
	</div>';
	echo '<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div id="invoice" class="card-body">
                    <form id="invoice-header" onsubmit="return false;">';
					// if($_SESSION['soft']['user_data']['user_level']==1){
						// echo '<div class="container invoice_header" changed="0">
							// <div class="row">
								// <div class="col-md-4">
									// <div class="form-group row">
										// <label class="col-lg-4 col-form-label">Invoice Date</label>
										// <div class="col-lg-8">
											// <input name="date" type="text" class="form-control date" value="'.date('d/m/Y').'">
										// </div>
									// </div>
								// </div>
							// </div>
						// </div>';
					// }
                        echo '<fieldset class="container">
							<div class="row">
								<div class="col-12">
									<table class="table mb-0 form-group">
										<thead><tr><th id="selectable">#</th><th>Product</th><th>Qty</th><th class="right">Price</th><th class="right">Discount</th><th class="right">Total</th><th></th></tr></thead>
                                        <tbody id="invoice-item-container">';
                                            $counter=0;
											$total=0;
											$warning='';
											
											if(isset($sales['id'])){
												$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code,p.category,p.brand,p.price_vary FROM products_logs_temp l LEFT JOIN products p on l.product=p.id 
												WHERE l.referrer='$sales[id]' AND l.type='0'");
												while($row=$result->fetch_assoc()){
													$result1=mysqli_query($con,"SELECT mrp,price,max_qty,min_bill,exclude_promo,limit_variety,cash_only FROM products_offer 
													WHERE start_date<='$date' AND end_date>='$date' AND product='$row[id]'");
													$promo=array();
													while($row1 = $result1->fetch_assoc()){
														array_push($promo,$row1);
													}
													$prices = array();
													$q="";
													if($row['price_vary']==0)$q=" ORDER BY id DESC LIMIT 0,1";
													$result1 = mysqli_query($con, "SELECT *,(SELECT discount FROM products WHERE id=product) discount,(SELECT category FROM products WHERE id=product) category 
													FROM products_logs WHERE product='$row[product]' and type IN (1,3) AND status='1' and 
													id in(SELECT max(id) FROM products_logs WHERE product='$row[product]' AND mrp>0 AND purchased>0 AND qty>0 AND status='1' and type IN (1,3) group by mrp,mrpc) $q") or die(mysqli_error($con));
													$margin=0;
													while($row1 = $result1->fetch_assoc()){
														$qty=($row1['uic']>0?($row1['qty']*$row1['uic']):$row1['qty'])+$row1['free'];
														$row1['cost']=($row1['purchased']*$row1['qty'])/$qty;
														if($row1['cost']==$row1['mrp']){
															$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.total,po.discount FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row1[referrer]'") or die(mysqli_error($con));
															$ro=mysqli_fetch_assoc($re);
															$diff=$ro['total']+$ro['discount']-$ro['tot'];
															if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
																$discounted_price=$ro['total']/$ro['tot'];
																$row1['cost']=$row1['mrp']*$discounted_price;
																$row1['cost']=($row1['cost']*$row1['qty'])/$qty;
															}
														}
														$row1['cost']=($row1['cost']<$row1['mrp']?number_format($row1['cost'],2, '.', ''):0);
														$row1['selling_price']=(($row1['mrpc']>0 AND ($row1['mrpc']<=$row1['selling_price'] OR $row1['selling_price']==null) AND $row1['mrpc']<$row1['mrp'])?$row1['mrpc']:(($row1['selling_price']<$row1['mrp'] AND $row1['selling_price']>0 AND $row1['discount']==1)?$row1['selling_price']:$row1['mrp']));
														if($row1['cost']>0)$margin=(($row1['mrp']-$row1['cost'])/$row1['mrp'])*100;
														array_push($prices,array('id'=>$row1['id'],'mrp'=>$row1['mrp'],'selling_price'=>$row1['selling_price'],'cost'=>$row1['cost']));
													}
													echo '<tr id="product_tr_'.$counter++.'" class="product_tr" changed="1" product_id="'.$row['product'].'" mrp="'.$row['mrp'].'" 
													purchased="'.$row['purchased'].'" log_id="'.$row['status'].'" selling_price="'.$row['selling_price'].'" category="'.$row['category'].'" 
													brand="'.$row['brand'].'" prices_length="'.sizeof($prices).'" prices="'.htmlspecialchars(json_encode($prices), ENT_QUOTES, 'UTF-8').'" promo="'.htmlspecialchars(json_encode($promo), ENT_QUOTES, 'UTF-8').'" temp="'.$row['id'].'">
													<td class="product_count selectable">'.$counter.'</td>
													<td>'.$row['ui_code'].' - '.$row['title'].'</td>
													<td class="qty">'.$row['qty'].'</td>
													<td class="mrp right" '.(sizeof($prices)>0?'data-toggle="modal" data-target=".price_vary"':'').'>'.$row['mrp'].'</td>
													<td class="discount right">'.number_format(($row['mrp']-$row['selling_price']),2).'</td>
													<td class="product_total right">'.number_format($row['selling_price']*$row['qty'],2).'</td>
													<td class="product_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>
													</tr>';
												}
											}
                                        echo '</tbody>';
										if($editable==1){
											echo '<tbody><tr>
												<th scope="row"></th>
												<td class="title" style="position: relative;">
													<input type="text" id="form-autocomplete" class="search_input x-large form-control product">'.$loader.'
												</td><td colspan="5"></td>
											</tr></tbody>';
										}
										echo '<tfoot><tr><td></td><td>Total</td><td colspan="3"></td><td class="product-total right">0.00</td><td></td></tr></tfoot>
                                    </table>
								</div>
							</div>
							<div class="row card-body">';
                                $btn='<button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block right_bar_click" id="pay_button" data="#pay">Pay <spn class="pay_button_amount"></span></button>';
								$cash_register_text='Open';
								if(isset($_COOKIE['Counter_login'])){
									$Counter_login=json_decode($_COOKIE['Counter_login']);
									if(date('Y-m-d',$Counter_login->time)<>date('Y-m-d')){
										$counter_id=$Counter_login->id;
										mysqli_query($con,"UPDATE sales_session SET close='$current_time',close_staff='0',close_amount='0',close_denomination='0' WHERE id='$counter_id'") or die(mysqli_error($con));
										setcookie('Counter_login', null, -1, '/');
										unset($_COOKIE['Counter_login']);
									}
									else {
										echo $btn;
										$cash_register_text='Close';
									}
								}
                                if(isset($_COOKIE['sales_counter'])){
									echo '<button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block right_bar_click cash_register_btn" data="#cash_register">Cash Register</button>';
									if($delivery==1)echo '<button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block delivery_invoice">Delivery</button>';
								}
							echo '</div>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>';
	// if($_GET['width']<1024){
		$buttons=array('F3','F4','F6','F12');
		if($_SESSION['branch_data']['finger_print']==0)$buttons[]='Insert';
		echo'<div class="row"><div class="form-group col-lg-12">';
			foreach($buttons as $button){
			echo '
			<button type="button" class="btn btn-primary waves-effect waves-light f_buttons">'.$button.'</button>';
			}
		echo '</div></div>';
	// }
	}
	echo '<div class="row hide popup delivery" o-href="'.$remote_file.'/pos&form=delivery">
		<div class="col-lg-12">
			<div class="card">
				<div class="card-body">
					<h4 class="mt-0 header-title">Delivery Note : </h4>
					<p class="text-muted m-b-30">Delivery ID <code class="highlighter-rouge did"></code>
					Rider : <code class="highlighter-rouge rider"></code></p>
				</div>
			</div>
		</div>
	</div>';
	echo '<script>
	plugin="'.$plugin.'";
	btn=\''.$btn.'\';
    remote_file="'.$config_url.'remote.php?file='.$plugin.'/";
	invoice_id="'.((isset($_GET['id']) AND $_GET['id']>0)?$_GET['id']:0).'";
	back_to_main="'.((isset($_GET['id']) AND $_GET['id']>0)?1:0).'";
	count="'.$counter.'";
	client="'.$client.'";
	</script>';
?>
<script>
	var function_calls='';
	function pad (str, max) {
	  str = str.toString();
	  return str.length < max ? pad("0" + str, max) : str;
	}
	function count_down(time,ele){
		ele.prop( "disabled",true);
		text=ele.text();
		text=text.replace(' ('+ (time+1) +'s)','');
		if(time>0){
			ele.text(text+' ('+ time +'s)');
			setTimeout(function () {count_down((time-1),ele);},1000);
		}
		else {
			ele.text(text);
			ele.prop( "disabled",false);
		}
	}
	function after_ajax(){
		$('.right_bar_click').unbind("click").click(function(){
			$('.right-bar').removeClass('right-bar').hide();
			ele=$(this).attr('data');
			$(ele).addClass('right-bar').show();
			$(document).click("body",function(e){
				if(0<$(e.target).closest(".right-bar-toggle").length){
					$("body").removeClass("right-bar-enabled");
				}
			});
			$("body").toggleClass("right-bar-enabled");
			$('.first_field').focus();
			$('.payment_method').trigger('change');
		});
		$('.inner_right_bar_click').unbind("click").click(function(){
			$('.right-bar').removeClass('right-bar').hide();
			ele=$(this).attr('data');
			$(ele).addClass('right-bar').show();
			if(ele=='#cash_history')list_payments();
		});
		$('.bill_payments_retry').unbind("click").click(function(){
			tr=$(this).parents('tr');
			$(this).remove();
			$.post(remote_file+"pos",{form_submit:'bill_payments_retry',id:tr.attr('id')},function(data){
				
			});
		});
		$('.print_invoice_btn').unbind('click').click(function(){
			$.post(remote_file+"pos",{print:'invoice_old',id:$(this).parents('tr').attr('id')},function(data){
				$('#printableArea').html(data);
				window.print();
			});
		});
		$('.whatsapp_invoice_btn').unbind('click').click(function(){
			$.post(remote_file+"pos",{print:'whatsapp_invoice_old',id:$(this).parents('tr').attr('id')},function(data){
				code=jQuery.parseJSON(data);
				window.open('https://web.whatsapp.com/send?phone='+code.mobile+'&text='+code.text, '_blank');
			});
		});
		$('.add_bill_payments').unbind('click').click(function(){
			if($('#amount').val()>24 && ($('#domain').children("option:selected").attr('reference')==0 || ($('#domain').children("option:selected").attr('reference')==1 && $('#reference').val().length==10))){
				tr=$('tr[product_id="2968"]');
				tr.attr('log_id',$('#domain').val()+'|'+$('#no').val()+'|'+$('#reference').val());
				tr.attr('mrp',$('#amount').val());
				tr.attr('selling_price',$('#amount').val());
				tr.attr('prices','[{"id":"","mrp":"'+$('#amount').val()+'","selling_price":"'+$('#amount').val()+'","cost":""}]');
				tr.find('.mrp').html($('#amount').val());
				update(tr);
			}
		});
		$('#domain').unbind('change').bind('change',function(){
			// reference
			if($(this).children("option:selected").attr('reference')==1){
				$('#reference').prop('required',true);
				$('.reference').show();
			}
			else $('.reference').hide();
			if($(this).children("option:selected").attr('length')>0){
				$('#no').attr('data-parsley-length','['+$(this).children("option:selected").attr('length')+','+$(this).children("option:selected").attr('length')+']');
			}
		});
		$('.delivery_data').unbind('click').click(function(){
			$('.did').text($('#did').val());
			$('.rider').text($('#rider').children("option:selected").text()).attr('id',$('#rider').val());
			$(".delivery").attr('href',$(".delivery").attr('o-href')+'&did='+$('.did').text()+'&rider='+$('#rider').val()).removeClass('hide');
		});
		$('.attendance').unbind('click').click(function(){
			tr=$(this).parents('tr');
			$(this).hide();
			$.post(remote_file+"pos",{form_submit:'attendance',id:tr.attr('id')},function(data){
				
			});
		});
		$(".product_tr").on('classChange', function() {
			delivery_check();
		});
		 // $(".right-bar-toggle").on("click",function(e){$("body").toggleClass("right-bar-enabled")});
		 // $(document).on("click","body",function(e){0<$(e.target).closest(".right-bar-toggle, .right-bar").length||$("body").removeClass("right-bar-enabled")});
	}
	function list_payments(){
		$.post(remote_file+"pos",{select:'list_payments'},function(data){//alert(data);
			$('#list_payments').html(data);
		});
	}
	function keydown_act(key){
		switch(key){
			case '+':
				if($.isNumeric($('.product').val()) || $('.product').val()=='')$(".plus").attr('href',$(".plus").attr('o-href')+'&code='+$('.product').val()).trigger('click');
				break;
			case 'Ctrl++':
				if($.isNumeric($('.product').val()) || $('.product').val()=='')$(".plus").attr('href',$(".plus").attr('o-href')+'&type=yura&code='+$('.product').val()).trigger('click');
				break;
			case 'alt':
				$('.product_tr:last-child').find('.qty').trigger('click');
				break;
			case 'esc':
				$(".right-bar-toggle").trigger('click');
				$(".product").focus();
				break;
			case 'left':
				if($('#pay_button').attr('hide')==0){
					$(".right-bar-toggle").trigger('click');
					$("#pay_button").trigger('click');
					$(".payment_method").val(1).trigger('change');
				}
				break;
			case 'right':
				if($('#pay_button').attr('hide')==0){
					$(".right-bar-toggle").trigger('click');
					$("#pay_button").trigger('click');
					$(".payment_method").val(4).trigger('change');
				}
				break;
			case 'f1':
				$(".right-bar-toggle").trigger('click');
				$(".product").focus();
				break;
			case 'f2':
				// $(".right-bar-toggle").trigger('click');
				// $('#customer_data').trigger('click');
				$(".customer").focus();
				break;
			case 'f3':
				if($('#customer_data').attr('customer_id')>0)$(".f3").attr('href',$(".f3").attr('o-href')+'&id='+$('#customer_data').attr('customer_id')).trigger('click');
				break;
			case 'f4':
				$(".recent_invoices").attr('href',$(".recent_invoices").attr('o-href')+'&type=0').trigger('click');
				break;
			case 'shift+f4':
				$(".recent_invoices").attr('href',$(".recent_invoices").attr('o-href')+'&type=1').trigger('click');
				break;
			case 'alt+shift+f4':
				if($('#customer_data').attr('customer_id')>0)$(".recent_invoices").attr('href',$(".recent_invoices").attr('o-href')+'&type=1&customer='+$('#customer_data').attr('customer_id')).trigger('click');
				break;
			case 'f6':
				$(".bill_payments_summary").attr('href',$(".bill_payments_summary").attr('o-href')+'&id='+$('#customer_data').attr('customer_id')).trigger('click');
				break;
			case 'f12':
				if($('.product_tr:last-child').attr('product_id')>0)$(".f12").attr('href',$(".f12").attr('o-href')+'&type=0&id='+$('.product_tr:last-child').attr('product_id')).trigger('click');
				break;
			case 'Ctrl+f12':
				$(".f12").attr('href',$(".f12").attr('o-href')+'&type=1&id='+$('.product_tr:last-child').attr('product_id')).trigger('click');
				break;
			case 'Ctrl+shift+f12':
				$(".f12").attr('href',$(".f12").attr('o-href')+'&type=2&id='+$('.product_tr:last-child').attr('product_id')).trigger('click');
				break;
			case 'insert':
				$(".insert").trigger('click');
				break;
		}
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"esc", function(e) {
		keydown_act('esc');
		return false;
	});
	$(document).bind('keydown',"left", function(e) {
		keydown_act('left');
		return false;
	});
	$(document).bind('keydown',"right", function(e) {
		keydown_act('right');
		return false;
	});
	$(document).bind('keydown',"f1", function(e) {
		keydown_act('f1');
		return false;
	});
	$(document).bind('keydown',"f2", function(e) {
		keydown_act('f2');
		return false;
	});
	$(document).bind('keydown',"f3", function(e) {
		keydown_act('f3');
		return false;
	});
	$(document).bind('keydown',"f4", function(e) {
		keydown_act('f4');
		return false;
	});
	$(document).bind('keydown',"f6", function(e) {
		keydown_act('f6');
		return false;
	});
	$(document).bind('keydown',"f12", function(e) {
		keydown_act('f12');
		return false;
	});
	$(document).bind('keydown',"shift+f4", function(e) {
		keydown_act('shift+f4');
		return false;
	});
	$(document).bind('keydown',"alt+shift+f4", function(e) {
		keydown_act('alt+shift+f4');
		return false;
	});
	$(document).bind('keydown',"Ctrl+f12", function(e) {
		keydown_act('Ctrl+f12');
		return false;
	});
	$(document).bind('keydown',"Ctrl+shift+f12", function(e) {
		keydown_act('Ctrl+f12');
		return false;
	});
	$(document).bind('keydown',"alt", function(e) {//alert(1);
		keydown_act('alt');
		return false;
	});
	$(document).bind('keydown',"+", function(e) {
		if(user_level==0)keydown_act('+');
		return false;
	});
	$(document).bind('keydown',"Ctrl++", function(e) {
		keydown_act('Ctrl++');
		return false;
	});
	$(document).bind('keydown',"insert", function(e) {
		keydown_act('insert');
		return false;
	});
	$('.f_buttons').click(function(){
		keydown_act($(this).text().toLowerCase());
	});
	$('.delivery_invoice').click(function(){
		delivery_invoice(invoice_id,1);
	});
	$('#customer_data').click(function(){
		$('#customer_data').attr('customer_id','').attr('plat',0).html('').hide();
		$('#customer').show();
		$('#customer_field').val('');
		$('.payment_method').html('<option value="1">Cash</option><option value="4">Card</option>').trigger('change');
		$('.product_tr').each(function(id){
			update($(this));
		});
	});
	$('#cash_count').click(function(){
		$('#denomination_list').toggleClass("show");
	});
	$('.payment_method').bind('change',function(){
		$('input[name="amount"]').prop( "disabled",false);
		$('.payment_add_button').show();
		$('#payment_code').unbind('keyup');
		if($(this).val()==1)$('input[name="amount"]').val('');
		else if($(this).val()==0){
			if(Number($('#customer_data').attr('credit_limit'))>Number($('.to_pay').text())){
				$('.payment_add_button').hide();
				$('.sms_code').show();
				$('.sms_code').unbind('click').click(function(){
					code=$('#payment_code').attr('code');
					$.post(remote_file+"pos",{select:'credit_code',id:$('#customer_data').attr('customer_id'),code:code,amount:$('.to_pay').text()},function(data){
						$('#payment_code').prop( "disabled",false).attr('code',data);
						count_down(60,$('.sms_code button'));
						$('#payment_code').keyup(function(e){
							if($(this).val()==$(this).attr('code')){
								$('.payment_add_button').show();
								$('.sms_code').hide();
							}
							else {
								$('.payment_add_button').hide();
								$('.sms_code').show();
							}
						});
					});
				});
				$('input[name="amount"]').val($('.to_pay').text()).prop( "disabled",true);
			}
			else $('.payment_add_button').hide();
		}
		else if($(this).val()==5){
			if(Number($('#customer_data').find('.points').text())>0){
				points=(Number($('.net_total').text())*0.1).toFixed(2);
				if($('#customer_data').attr('plat')==4)points=(Number($('.net_total').text())).toFixed(2);;
				if(Number($('#customer_data').find('.points').text())<points)points=Number($('#customer_data').find('.points').text());
				$('input[name="amount"]').bind('change',function(){
					if(Number($(this).attr('points'))<$(this).val())$(this).val($(this).attr('points'));
					if($(this).val()>30){
						$('.payment_add_button').hide();
						$('.sms_code').show();
					}
					else {
						$('.payment_add_button').show();
						$('.sms_code').hide();
					}
				});
				$('.sms_code').unbind('click').click(function(){
					code=$('#payment_code').attr('code');
					$.post(remote_file+"pos",{select:'points',id:$('#customer_data').attr('customer_id'),code:code},function(data){
						$('#payment_code').prop( "disabled",false).attr('code',data);
						$('input[name="amount"]').prop( "disabled",true);
						count_down(60,$('.sms_code button'));
						$('#payment_code').keyup(function(e){
							if($(this).val()==$(this).attr('code')){
								$('.payment_add_button').show();
								$('.sms_code').hide();
							}
							else {
								$('.payment_add_button').hide();
								$('.sms_code').show();
							}
						});
					});
				});
				$('input[name="amount"]').val(points).attr('points',points).trigger('change');
			}
			else $('input[name="amount"]').val(0);
		}
		else if($(this).val()==6){
			$('.payment_add_button').hide();
			$('#payment_code').prop( "disabled",false);
			$('input[name="amount"]').prop( "disabled",true);
			$('#payment_code').bind('change',function(){
				code=$(this).val();
				$.post(remote_file+"pos",{select:'voucher',net_total:Number($('.net_total').text()),customer:$('#customer_data').attr('customer_id'),code:code},function(data){//alert(data);
					voucher=jQuery.parseJSON(data);
					if(voucher.usable==1){
						$('.payment_add_button').show();
						$('#payment_code').prop( "disabled",true);
						
						if(voucher.type==1){
							$('input[name="amount"]').val(0.01);
							$('.product_tr').each(function(id){
								$(this).attr('selling_price',($(this).attr('mrp')*((100-voucher.amount)/100)));
								update($(this));
							});
						}
						else $('input[name="amount"]').val(voucher.amount);
					}
					else {
						$('.payment_add_button').hide();
						$('#payment_code').prop( "disabled",false);
						$('input[name="amount"]').val('');
						Swal.fire({title: voucher.message,confirmButtonColor: '#4090cb',});
					}
				});
			});
		}
		else if($(this).val()==8){
			$('input[name="amount"]').val($('.to_pay').text()).prop( "disabled",true);
			$('.payment_add_button').hide();
			$.post(remote_file+"pos",{select:'upay',amount:Number($('.to_pay').text())},function(data){
				$('#upay_list').html(data);
				$('.upay').unbind('click').click(function(){
					$('.upay').not(this).removeClass('selected');
					$(this).toggleClass('selected');
					$('input[name="upay"]').val($(this).attr('id'));
					add_payment();
				});
			});
		}
		else $('input[name="amount"]').val($('.to_pay').text());
		$('.payment_methods').hide();
		// $('.method_'+$(this).val()).attr('style','display: flex;');
		$('.method_'+$(this).val()).show();
		setTimeout(function () {$('.second_field').focus();},500);
	});
	$('.cash_register').click(function(){
		form_ele=$(this).parents('form');
		if(form_ele.find('input[name="cash_register"]').val()!=''){
			$.post(remote_file+"pos", form_ele.serializeArray(),function(data){
				btn_text='Close';
				if(data=='close'){
					btn_text='Open';
					$('button[data="#pay"]').remove();
					$('.cash_in_out_form').hide();
				}
				else {
					$('.cash_register_btn').before(btn);
					$('.cash_in_out_form').show();
				}
				$('.cash_register').text(btn_text);
				$('#cash_count').trigger('click');
				$('.right_bar_click').trigger('click');
				form_ele.trigger("reset");
				$.agoy_init();
			});
		}
	});
	$('.cash_out').click(function(){
		form_ele=$(this).parents('form');
		$(this).hide();
		if(form_ele.find('input[name="amount"]').val()>0 && form_ele.find('#type').val()>0){
			$.post(remote_file+"pos", form_ele.serializeArray(),function(data){//alert(data);
				if(data!='')alert(data);
				else {
					form_ele.trigger("reset");
					$('#type').val('').trigger('change');
					$('#p_type').val('').trigger('change');
					$('select[name="staff"]').val('').trigger('change');
					$('select[name="supplier"]').val('').trigger('change');
					$('select[name="creditor"]').val('').trigger('change');
					$('.right_bar_click').trigger('click');
				}
			});
		}
	});
	$('.cash_in').click(function(){
		form_ele=$(this).parents('form');
		if(form_ele.find('input[name="amount"]').val()>0){
			$.post(remote_file+"pos", form_ele.serializeArray(),function(data){//alert(data);
				if(data!='')alert(data);
				else {
					$('select[name="source"]').val('').trigger('change');
					form_ele.trigger("reset");
					$('.right_bar_click').trigger('click');
				}
			});
		}
	});
	$('.print_last_invoice').click(function(){
		window.print();
	});
	$('.no_whatsapp').click(function(){
		$.post(remote_file+"pos",{form_submit:'no_whatsapp',id:$(this).attr('id')},function(data){});
	});
	
	function add_payment(){
		function_calls=function_calls+'add_payment,';
		if($('input[name="amount"]').val()!=0 && $('input[name="amount"]').val()!=''){
			var data = {};
			$('#payment_method').find(':input:disabled').removeAttr('disabled');
			$.map($('#payment_method').serializeArray(), function(n, i){
				data[n['name']] = n['value'];
			});
			var data =JSON.stringify(data);
			pay_amount=$('input[name="amount"]').val();
			$('#payment_list').append($('<tr class="payments"></tr>').attr({data:data}).html(
			'<td>'+$('.payment_method option:selected').text()+'</td><td class="right pay_amount">'+pay_amount+'</td>'+
			'<td class="payment_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>') );
			$('#payment_list').find('.payment_delete').unbind('click').click(delete_payment);
			payment_total();
			if($('.payment_method option:selected').val()==5)$(".payment_method option[value='5']").remove();
			else if($('.payment_method option:selected').val()==6)$(".payment_method option[value='6']").remove();
			$(".payment_method").val(1).trigger('change');
			$('input[name="amount"]').attr('points',0).unbind('change').val('').prop( "disabled",false);;
		}
		$('.first_field').focus();
	}
	$('.payment_press_enter').keyup(function(e){
		if(e.which == 13){
			if($(this).val()<1000000)add_payment();
			else {
				$(".product").val($(this).val()).trigger('keydown');
				$(this).val('');
				keydown_act('f1');
			}
		}
		else if(e.which == 27)keydown_act('esc');
		else if(e.which == 112)keydown_act('f1');
		else if(e.which == 113)keydown_act('f2');
	});
	$('.payment_add').unbind('click').click(function(){
		add_payment();
	});
	function payment_total(){
		function_calls=function_calls+'p,';
		var sum=0;
		$('.pay_amount').each(function(id){
			sum+=Number($(this).text());
		});
		var charges=0;
		$('.charge_amount').each(function(id){
			charges+=Number($(this).text());
		});
		balance=(Number($('.net_total').text())+charges-sum);
		if(balance!=0 && balance!='')$('.to_pay').text( balance.toFixed(2) );
		if($('.product_tr').length>0){
			if(Number($('.net_total').text())>0 && balance<=0)submit_invoice();
			else if(Number($('.net_total').text())<=0 && balance==0)submit_invoice();
		}
	}
	function delivery_check(){
		if($('.product_tr.selected').length>0){
			if($(".delivery").hasClass('hide')){
				$(".delivery").attr('href',$(".delivery").attr('o-href')).trigger('click');
				$('#charges').append($('<tr class="charges delivery_charges table" file="sales/pos" table="test"></tr>').html('<td>Delivery Charge</td><td class="right charge_amount editable">100.00</td><td></td>') );
				net_total=Number($('.net_total').text())+100;
				$('.pay_button_amount').text('Rs.'+net_total.toFixed(2) );
				payment_total();
				$('.charge_amount').unbind('change').bind('change',function(){
					setTimeout(payment_total,500);
				});
			}
		}
		else {
			$(".delivery").attr('href',$(".delivery").attr('o-href')).addClass('hide');
			$(".delivery_charges").remove();
		}
	}
	function delivery_invoice(id){
		$('.payments').remove();
		$('.product_tr').remove();
		$('#payment_method').trigger("reset");
		$(".payment_method").val(1).trigger('change');
		$('#customer_data').trigger('click');
		// $(".right-bar-toggle").trigger('click');
		invoice_total();
		payment_total();
		count=0;
		$.post(remote_file+"pos",{form_submit:'delivery_invoice',id:id},function(data){
			function_calls='';
			// location.reload();
		});
	}
	function submit_invoice(){
		function_calls=function_calls+'submit,';
		id=invoice_id;
		invoice_id=0;
		var data = new Array();
		data.push({name: "print", value: 'invoice'});
		data.push({name: "id", value: id});
		data.push({name: "customer", value: $('#customer_data').attr('customer_id')});
		$('.no_whatsapp').attr('id',$('#customer_data').attr('customer_id'));
		$('#payment_list .payments').each(function(i){
			data.push({name: "data[]", value: $(this).attr('data')});
		});
		$('#invoice-item-container .product_tr').each(function(i){
			tr=$(this);
			var item = new Array();
			item.push({id: tr.attr('product_id'), mrp: tr.attr('mrp'), purchased: tr.attr('purchased'), selling_price: (Number(tr.attr('mrp'))-Number(tr.find('.discount').text())), log_id: tr.attr('log_id'), qty: Number(tr.find('.qty').text())});
			data.push({name: "items[]", value: JSON.stringify(item)});
		});
		// $('#charges .charges').each(function(i){
			data.push({name: "delivery_charge", value: $('.charge_amount').text()});
		// });
		data.push({name: "delivery_rider", value: $('.rider').attr('id')});
		data.push({name: "delivery_id", value: $('.did').text()});
		//id=913035;
		function IsJsonString(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		}
		$.post(remote_file+"pos",data,function(data){
			if(back_to_main==1)location.hash='sales/pos';//window.history.back();
			// to_print=data;
			// whatsapp='';
			// if(IsJsonString(data)==true){
				code=jQuery.parseJSON(data);//if(user_id==1)alert(data);
				to_print=code.html;
				whatsapp=code.mobile;
			// }
			$('#printableArea').html(to_print);
			window.print();
			$('.print_last_invoice').show();
			$('.payments').remove();
			$('.charges').remove();
			$(".delivery").attr('href',$(".delivery").attr('o-href')).addClass('hide');
			$('.product_tr').remove();
			$('#payment_method').trigger("reset");
			$(".payment_method").val(1).trigger('change');
			$('#customer_data').trigger('click');
			// $(".right-bar-toggle").trigger('click');
			function_calls='';
			invoice_total();
			payment_total();
			if(whatsapp!='')window.open('https://web.whatsapp.com/send?phone='+code.mobile+'&text='+code.text, '_blank');
		});
	}
	function delete_payment(e){
		function_calls=function_calls+'delete_payment,';
		ele=$(e.currentTarget).parents('tr');
		ajax_indicate('Item deleted');
		$(ele).remove();
	}
	$('.denomination').bind('change',function(){
		function_calls=function_calls+'denomination,';
		var cash=0;
		$('.denomination').each(function (){
			value=$(this).val()*$(this).attr('amount');
			cash=cash+value;
			$(this).parent().parent().find('.total').text(value.toFixed(2));
		});
		$('#denomination').val(cash.toFixed(2));
	});
	$('#type').unbind('change').bind('change',function(){
		function_calls=function_calls+'type,';
		form=$(this).parents('form');
		form.find('.hide').hide();
		form.find('.type_'+$(this).val()).attr('style','display:flex;');
	});
    $('#p_type').unbind('change').bind('change',function(){
		function_calls=function_calls+'p_type,';
		$('.p_type').hide();
		$('.p_type_'+$(this).val()).attr('style','display:flex;');
	});
    function selling_price_customer_eligiblity(){
		function_calls=function_calls+'eligiblity,';
		customer.plat=$('#customer_data').attr('plat');
		margin=customer.plat;
		if(customer.plat==1 && client=='00002')margin=0;
		if(customer.plat==3)margin=0;
		if(customer.plat==8)margin=3;
		return Number(margin);
	}
	if(typeof products!='undefined' && $( ".product" ).length >0){
		function select_product(product,qty=1){
			if(client=='00002'){
				if(product.id==1009 && $('.to_pay').text()<1000)return;
				else if(product.id==1083 && $('.to_pay').text()<3000)return;
			}
			function_calls=function_calls+'1,';
			$('.product').val('');
            $('.lds-ellipsis').css('display', 'inline-block');
			if($('tr[product_id="'+product.id+'"]').length==1 && $('tr[product_id="'+product.id+'"]').attr('prices_length')<2){//
				$('tr[product_id="'+product.id+'"] .qty').html( Number($('tr[product_id="'+product.id+'"] .qty').html()) + Number(qty) );
				update($('tr[product_id="'+product.id+'"]'));
				$('.lds-ellipsis').css('display', 'none');
				return;
			}
			$.post(remote_file+"pos",{select:'product',id:product.id,price_vary:product.price_vary},function(data){
				// alert(data);
				pi=jQuery.parseJSON(data);
				prices=pi.prices;
				promo=pi.promo;
				product.purchased=0;
				product.pid=0;
				product.selling_price=0;
				product.discount=0;
				var mrp='';
				var modal='';
				if(prices.length==1){
					mrp=prices[0].mrp;
					product.selling_price=prices[0].selling_price;
					product.purchased=prices[0].cost;
					product.pid=prices[0].id;
					product.discount=prices[0].discount;
				}
				else if(prices.length>1)modal='data-toggle="modal" data-target=".price_vary"';
				$('.lds-ellipsis').css('display', 'none');
				bill_payments='';
				if(product.id==2968)bill_payments='class="popup bill_payments" href="'+remote_file+'pos&form=bill_payments"';
				$('#invoice-item-container').append($('<tr></tr>').attr({id : "product_tr_"+count, class: "product_tr", changed: 0, product_id: product.id, mrp : mrp, purchased : product.purchased, log_id : product.pid,selling_price:product.selling_price,discount:product.discount,category:product.category,company:product.company,brand:product.brand,prices_length:prices.length,prices:JSON.stringify(prices),promo:JSON.stringify(promo)}).html(
					'<td class="product_count selectable"></td><td '+bill_payments+'>'+product.ui_code+' - '+product.title+'</td>'+
					'<td class="qty">'+qty+'</td>'+
					'<td class="mrp right" '+modal+'>'+mrp+'</td><td class="discount right">0</td>'+
					'<td class="product_total right">'+mrp+'</td><td class="product_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>') );
				count_products();
				if(product.id==2968){
					$.agoy_init();
					$('#product_tr_'+count).find('.bill_payments').trigger('click');
				}
				$('#product_tr_'+count).find('.product_delete').click(delete_product);
				if(mrp==''){
					if(prices.length==0 && product.id!=2968)bind_price_click($('#product_tr_'+count).find('.mrp'));
					else $('#product_tr_'+count).find('.mrp').click(price_vary).trigger('click');
					invoice_total();
				}
				else update($('#product_tr_'+count));
				//$('#product_tr_'+count).trigger('change');
				bind_qty_click($('#product_tr_'+count).find('.qty'));
				$('#product_tr_'+count).find('.selectable').click(function(){
					$(this).parents('tr').toggleClass('selected');
					delivery_check();
				});
				count++;
			});
		}
		function bind_price_click(ele){
			function_calls=function_calls+'bind_price_click,';
			ele.click(function(){
				$(this).html('<input type="number" value="'+$(this).text()+'" class="edit_value form-control" style="width: 100px;"/>');
				$(".edit_value").focus().select();
				$(this).unbind("click");
				$('.edit_value').on('focusout keypress', function(e) {
					if(e.which == 13 || e.which == 0){
						bind_price_click($(this).parents('.mrp'));
						tr=$(this).parents('tr');
						tr.attr('mrp',$(this).val()).attr('selling_price',$(this).val());
						$(this).parent().html($(this).val());
						update(tr);
					}
				});
			});
		}
		function qty_update_enable(){
			function_calls=function_calls+'qty_update_enable,';
			$('.edit_value').on('focusout keypress', function(e) {
				if(e.which == 13 || e.which == 0){
					// tr=$(e.currentTarget).parents('tr');
					tr=$(this).parents('tr');
					if($(this).val() < 10000){
						if($(this).val()!=0 && $(this).val()!='')$(this).parent().html($(this).val());
						else $(this).parent().html($(this).attr('o-val'));
						if(Number($(this).attr('o-val'))!=$(this).val())update(tr);
					}
					else {
						$(".product").val($(this).val()).trigger('keydown');
						$(this).parent().html($(this).attr('o-val'));
						// update(tr);
					}
					bind_qty_click(tr.find('.qty'));
				}
			});
		}
		function bind_qty_click(ele){///alert(0);
			function_calls=function_calls+'5,';
			ele.click(function(){
				$(this).html('<input type="number" value="'+$(this).text()+'" o-val="'+$(this).text()+'" class="edit_value form-control" style="width: 100px;"/>');
				$(".edit_value").focus().select();
				$(this).unbind("click");
				qty_update_enable();
			});
			
		}
		function update(tr){//if(user_id==1)alert(0);
			function_calls=function_calls+'3,';
			if(tr.attr('mrp')=='')return;
			selling_price_init=Number(tr.attr('mrp'))-Number(tr.find('.discount').text());
			margin=0;
			selling_price=0;
			if($('#customer_data').attr('customer_id')>0){
				margin=selling_price_customer_eligiblity();
				purchased=Number(tr.attr('purchased'));
				mrp=Number(tr.attr('mrp'));
				if(margin>0 && purchased>0){
					customer.plat=Number($('#customer_data').attr('plat'));
					selling_price=Number(tr.attr('selling_price'));
					if(purchased>0 && purchased<mrp)selling_price=purchased*(1+(margin/100));
					profit=((mrp-purchased)/mrp)*100;
					if(profit>20 && customer.plat>1 && customer.plat<5)selling_price=((purchased+mrp)/2);
					else if(profit>(10+margin) && customer.plat!=1)selling_price=mrp*0.9;
					if(Number(tr.attr('company'))==1 && (selling_price-purchased)<7){
						selling_price=purchased+7;
						if(selling_price>mrp)selling_price=mrp;
					}
					selling_price=Math.ceil( selling_price ).toFixed( 2 );
					// if(selling_price<mrp && selling_price<Number(tr.attr('selling_price'))){
						// tr.attr('selling_price', Math.ceil( selling_price ).toFixed( 2 ));
					// }
				}
				promo=jQuery.parseJSON( tr.attr('promo') );//if(user_id==1)alert(promo.length);
				if(promo.length>0){
					qty=0;
					$('.product_tr[product_id="'+tr.attr('product_id')+'"]').each(function(){
						qty = qty + Number($(this).find('.qty').text());
					});
					cash=$('.payment_method').val();
					$('.payments').each(function(){
						payment=jQuery.parseJSON( $(this).attr('data') );
						if(payment.method!=1)cash=0;
					});
					$.each( promo, function( key, value ) {
						if(Number($(".net_total").text())>value.min_bill && mrp==value.mrp){
							if(value.max_qty==0 || value.max_qty>=qty){//if(user_id==1)alert(value.max_qty);
								if(value.cash_only==0 || cash==1){
									if(selling_price==0 || value.price<selling_price){
										selling_price=value.price;
									}
								}
							}
						}
					});
				}
			}
			if(selling_price>Number(tr.attr('mrp')) || selling_price>Number(tr.attr('selling_price')) || selling_price==0)selling_price=Number(tr.attr('selling_price'));
			// if(tr.find(".product_delete").length==1)tr.find(".product_delete").replaceWith( "<td></td>" );
			if((selling_price/Number(tr.attr('mrp')))<=0.9)tr.addClass('discount_color_15');
			else tr.removeClass('discount_color_15');
			if($('#customer_data').html()!='' && $('#customer_data').attr('fname').toLowerCase()=='expired')selling_price=0;
			tr.find('.discount').text((Number(tr.attr('mrp'))-selling_price).toFixed(2));
			tr.find(".product_total").text( (selling_price * Number(tr.find('.qty').text()) ).toFixed(2) );
			
			invoice_total();
			var data = $('#invoice-header').serializeArray();
			data.push({name: "invoice", value: invoice_id});
			if($('#customer_data').attr('update')==0){
				data.push({name: "customer", value: $('#customer_data').attr('customer_id')});
				data.push({name: "plat", value: $('#customer_data').attr('plat')});
				$('#customer_data').attr('update',1);
			}
			data.push({name: "form_submit", value: 'add_sales'});
			data.push({name: "log", value: tr.attr('temp')});
			data.push({name: "id", value: tr.attr('product_id')});
			data.push({name: "mrp", value: tr.attr('mrp')});
			data.push({name: "purchased", value: tr.attr('purchased')});
			data.push({name: "selling_price", value: (Number(tr.attr('mrp'))-Number(tr.find('.discount').text()))});
			data.push({name: "log_id", value: tr.attr('log_id')});
			data.push({name: "qty", value: Number(tr.find('.qty').text())});
			// if(selling_price_init!=selling_price){
				$.post(remote_file+"pos",data,function(data){
					if(tr.attr('temp')>0)ajax_indicate('Qty And MRP Updated!');
					else ajax_indicate('Product Added!');
					p=jQuery.parseJSON(data);
					if(invoice_id==0)invoice_id=p.invoice;
					tr.attr('temp',p.log).attr('changed', '1');
					// tr.find('.product_delete').remove();
				});
			// }
			$(".product").focus();
			
			
			// ele=e.currentTarget;
			// setTimeout(function () { // needed because nothing has focus during 'focusout'
				// if ( ($(':focus').closest(ele).length <= 0)) {//if(user_id==1)alert($(e.currentTarget).html());
					
                // }
            // }, 0);
			
        }
		function count_products(){
			function_calls=function_calls+'2,';
			$('.product_count').each(function(id){
                $(this).text(id+1);
            });
			if(back_to_main==1 && $('.product_tr').length==0)location.hash='sales/pos';
        }
		function invoice_total(){
            function_calls=function_calls+'i,';
			var sum=0;
			$('#pay_button').attr('hide',0).show();
            $('.product_total').each(function(id){
                if($(this).text()!=='')sum+=Number($(this).text().replace(',',''));
				else $('#pay_button').attr('hide',1).hide();
            });
            var charges=0;
			$('.charge_amount').each(function(id){
				charges+=Number($(this).text());
			});
			$('.product-total').text( sum.toFixed(2) );
            if(sum!=0 && sum!=''){
				$('.pay_button_amount').text('Rs.'+(sum+charges).toFixed(2) );
				$('.to_pay').text( (sum+charges).toFixed(2));
			}
			else $('.pay_button_amount').text('');
			card=1;
			$('.product_tr').each(function(id){
				mrp=Number($(this).attr('mrp'));
				prices=jQuery.parseJSON( $(this).attr('prices') );
				var warning='warning';
				$.each( prices, function( key, value ) {
					if(value.mrp==mrp)warning='';
				});//alert(warning);
				if(warning=='' && $(this).hasClass('warning'))$(this).removeClass('warning');
				else if(warning!='' && !$(this).hasClass('warning'))$(this).addClass('warning');
				if($(this).attr('product_id')==2968)card=0;
			});
			if(card==0)$("option[value='4']").attr("disabled", "disabled");
        }
		function delete_product(e){
            function_calls=function_calls+'delete_product,';
			ele=$(e.currentTarget).parents('tr');
			if(ele.attr('temp')>0){
				// $('.delete_product').trigger('click');
				// setTimeout(function(){$('#password_verify').unbind('click').click(function(){
					// form_ele=$(this).parents('form');
					// $.post(remote_file+"pos", form_ele.serializeArray(),function(data){//if(data!=='')alert(data);
						// if(Number(data)>0){
							$.post(remote_file+'pos',{id:ele.attr('temp'), form_submit: "delete_product"}, function(data){
								ele.remove();
								ajax_indicate('Item deleted');
								count_products();
								invoice_total();
							});
						// }
						// else  Swal.fire("Password Error.", "You have no permission to delete this. contact sales admin or manager.", "warning");
					// });
					// $('.mfp-close').trigger('click');
				// });},500);
				
			}
			else {
				ele.remove();
				ajax_indicate('Item deleted');
				count_products();
				invoice_total();
			}
        }
		function price_vary(e){
            function_calls=function_calls+'price_vary,';
			ele=$(e.currentTarget).parents('tr');
			prices=jQuery.parseJSON( ele.attr('prices') );
			var htm='<table class="table mb-0 form-group"><tbody>';
			$.each( prices, function( key, value ) {
				var selling_price='';
				if(value.selling_price>0)selling_price=value.selling_price;
				htm=htm+'<tr class="pointer select_price" id="' + ele.attr('id') + '" data="' +JSON.stringify(value).replaceAll('"', "'")+ '"><td>' + value.mrp + '</td><td>' + selling_price + '</td></tr>';
			});
			htm=htm+'</tbody></table>';
			$('#price_vary').html(htm);
			$('.select_price').click(select_price);
			// $(document).unbind('keyup').keyup(function(e){
                // alert(e.which);
            // });
			/*$(document).unbind('keyup').keyup(function(e){
				// e.preventDefault();
				// $('body').css('overflow','hidden');
				if(e.which == 40){
					if($('.select_price.selected').length==1){
						if($('.selected').next().length==1)$('.selected').removeClass('selected').next().addClass('selected');
						else {
							$('.selected').removeClass('selected');
							$('.select_price:first-child').addClass('selected');
						}
					}
					else $('.select_price:first-child').addClass('selected');
				}
				else if(e.which == 38){
					if($('.select_price.selected').length==1){
						if($('.selected').prev().length==1)$('.selected').removeClass('selected').prev().addClass('selected');
						else {
							$('.selected').removeClass('selected');
							$('.select_price:last-child').addClass('selected');
						}
					}
					else $('.select_price:last-child').addClass('selected');
				}
                else if(e.which == 13){
					$('.selected').trigger('click');
					// $('body').css('overflow','auto');
				}
			});*/
        }
		function select_price(e){
			function_calls=function_calls+'select_price,';
			ele=$(e.currentTarget);
			prices=jQuery.parseJSON( ele.attr('data').replaceAll("'",'"') );
			$('#'+ele.attr('id')).attr('mrp',prices.mrp);
			$('#'+ele.attr('id')).attr('selling_price',prices.selling_price);
			$('#'+ele.attr('id')).attr('purchased',prices.cost);
			$('#'+ele.attr('id')).attr('log_id',prices.id);
			$('#'+ele.attr('id')).find('.mrp').text(prices.mrp).trigger('change');
			update($('#'+ele.attr('id')));
			// $('#'+$(e.currentTarget).attr('id')).find('.edit_value').focus().select();alert();
			$(".price_vary .close").trigger('click');
        }
		$( ".product" ).autocomplete({
			minLength: 1,
			autoFocus: false,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( products, function( value ) {
					return matcher.test(value.title.replace(/\ /g, "")) ||  matcher.test(value.ui_code) ||  matcher.test(value.bulk_code) ||  matcher.test(value.remind) || matcher.test(value.mrp);
				}).slice(0, 15) );
			},
			focus: function(event,ui){
				event.preventDefault();
				return false;
			},
			response: function(event, ui) {
				$(this).val($(this).val().trim());
				if(($(this).val().length==7 && ($(this).val().substring(0,3)==273 || $(this).val().substring(0,3)==483)) || ($(this).val().length==10 && ($(this).val().toLowerCase().indexOf("v")==9 || $(this).val().toLowerCase().indexOf("x")==9))){
					$('#customer').val($(this).val()).trigger('keydown').focus();
					$(this).val('');
				}
				else if(($(this).val().trim().length==8 && $(this).val().substring(0,4)==1111)){
					$(this).val($(this).val().trim().replace('1111','DG')).trigger('keydown');
				}
				else if($(this).val().length==10 && $.isNumeric($(this).val())){
					code='DG'+pad(Number($(this).val().substring(0,5)),4);
					qty=Number((($(this).val().substring(5,10))/1000));
					var matches = $.grep(products, function(e) { return e.ui_code == code });
					select_product(matches[0],qty);
				}
				else if (ui.content.length == 1 && $(this).val()==ui.content[0].bulk_code){
					select_product(ui.content[0],ui.content[0].uic);
					$(this).autocomplete( "close" );
				}
				else if (ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].ui_code)){// || ui.content[0].remind.indexOf($(this).val()) != -1
					ui.item=ui.content[0];
					$(this).val(ui.content[0].ui_code);
					select_product(ui.item);
					$(this).autocomplete( "close" );
				}
				else if(ui.content.length ==0){//alert(1206);
					$.post(remote_file+'pos', {select:'product_disabled',code:$(this).val()},function(data){//alert(data);
						item=jQuery.parseJSON( data );//alert(item.id);
						select_product(item,1);
					});
				}
			},
			select: function(event,ui){
				select_product(ui.item);
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul,item){
			tam='';
			if(item.title_tamil!='')tam="&nbsp;("+item.title_tamil+")";
			return $("<li>").append("<a>"+item.title+tam+"&nbsp;["+item.mrp+"]<br>"+item.ui_code+"</a>").appendTo(ul);
		};
	}
	if(typeof products!='undefined' && $( ".product_stock" ).length >0){
		function select_product_stock(product,qty=1){
			$('.stock_count .product_stock').val(product.ui_code);
			$('#product_title').text(product.title);
			if(qty>1)$('.stock_count .qty').val(qty);
			$('.stock_count .qty').focus().select();
		}
		$( ".product_stock" ).autocomplete({
			minLength: 1,
			autoFocus: false,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( products, function( value ) {
					return matcher.test(value.title.replace(/\ /g, "")) ||  matcher.test(value.ui_code) ||  matcher.test(value.bulk_code) ||  matcher.test(value.remind) || matcher.test(value.mrp);
				}).slice(0, 15) );
			},
			focus: function(event,ui){
				event.preventDefault();
				return false;
			},
			response: function(event, ui) {
				$(this).val($(this).val().trim());
				if(($(this).val().trim().length==8 && $(this).val().substring(0,4)==1111)){
					$(this).val($(this).val().trim().replace('1111','DG')).trigger('keydown');
				}
				else if($(this).val().length==10 && $.isNumeric($(this).val())){
					code='DG'+pad(Number($(this).val().substring(0,5)),4);
					qty=Number((($(this).val().substring(5,10))/1000));
					var matches = $.grep(products, function(e) { return e.ui_code == code });
					select_product_stock(matches[0],qty);
				}
				else if (ui.content.length == 1 && $(this).val()==ui.content[0].bulk_code){
					select_product_stock(ui.content[0],ui.content[0].uic);
					$(this).autocomplete( "close" );
				}
				else if (ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].ui_code)){// || ui.content[0].remind.indexOf($(this).val()) != -1
					ui.item=ui.content[0];
					$(this).val(ui.content[0].ui_code);
					select_product_stock(ui.item);
					$(this).autocomplete( "close" );
				}
				else if(ui.content.length ==0){//alert(1206);
					$.post(remote_file+'pos', {select:'product_disabled',code:$(this).val()},function(data){//alert(data);
						item=jQuery.parseJSON( data );//alert(item.id);
						select_product_stock(item,1);
					});
				}
			},
			select: function(event,ui){
				select_product_stock(ui.item);
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul,item){
			tam='';
			if(item.title_tamil!='')tam="&nbsp;("+item.title_tamil+")";
			return $("<li>").append("<a>"+item.title+tam+"&nbsp;["+item.mrp+"]<br>"+item.ui_code+"</a>").appendTo(ul);
		};
	}
	$('.product_stock').on('focusout', function(e) {
		$.post(remote_file+"pos",{select:'stock_count',code:$(this).val()},function(data){
			$('#stock_count').html(data);
		});
	});
	function select_customer(id,new_customer=0){
		function_calls=function_calls+'customer,';
		$.post(remote_file+"pos",{select:'customer',id:id,new_customer:new_customer},function(data){
			customer=jQuery.parseJSON(data);
			$('#customer').hide();
			htm='<label></label><div><table class="table mb-0 form-group"><tbody><tr><td>'+customer.fname+' '+customer.lname+'</td>'
			+'<td>'+customer.name_ta+'</td><td>'+customer.mobile+'</td><td class="points">'+customer.points+'</td></tr></tbody></table></div>';
			$('#customer_data').attr('update',0).attr('customer_id',customer.id).attr('fname',customer.fname).attr('plat',customer.plat).attr('credit_limit',customer.credit_limit).html(htm).show();
			margin=selling_price_customer_eligiblity();
			// if(customer.plat>0){
				$('.product_tr').each(function(id){
					// $(this).trigger('change');
					update($(this));
				});
			// }
			$('.payment_method').html(customer.payment_methods).trigger('change');
			$(".right-bar-toggle").trigger('click');
			$(".product").focus();
			setTimeout(function(){select_customer_run=0;},5000);
			// if(invoice_id>0){
				// $('#customer_data').attr('update',1);
				// $.post(remote_file+"pos",{form_submit:'update_customer',id:invoice_id,customer:customer.id});
			// }
		});
	}
	select_customer_run=0;
	$("#customer_field").keyup(function(e){
		if(e.which == 13 && $(this).val().length==10 && $.isNumeric($(this).val()) && select_customer_run==0){
			select_customer_run=1;
			select_customer($(this).val(),1);
		}
	});
	$( ".customer" ).autocomplete({source: remote_file+"pos",minLength: 5,
		response: function(event, ui) {
			$(this).val($(this).val().trim());
			if(ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].nic.toUpperCase() || $(this).val()==ui.content[0].loyalty_card || $(this).val()==ui.content[0].platinum_card || $(this).val()==ui.content[0].mobile)){//alert($(this).val().toUpperCase());
				ui.item=ui.content[0];
				select_customer_run=1;
				select_customer(ui.item.id);
				$(this).autocomplete( "close" );
			}
		},
		select: function( event, ui ) {
			select_customer(ui.item.id);
		}
	}).data("ui-autocomplete")._renderItem = function(ul,item){
		tam='';
		if(item.name_ta!='' && item.name_ta!=null)tam="&nbsp;("+item.name_ta+")";
		return $("<li>").append("<a>"+item.fname+" "+item.lname+tam+"<br>"+item.nic+" "+item.mobile+"</a>").appendTo(ul);
	};
	function ajax_indicate(msg){
        console.log(msg)
        if( $('#ajax_indicator').hasClass('hide') ){
            show();
            setTimeout(hide,2000);
        }else{
            hide();
        }
        function show(){
            $('#ajax_indicator span.text').html(msg);
            $('#ajax_indicator').fadeIn();
            setTimeout(hide,2000);
        }
        function hide(){
            $('#ajax_indicator').fadeOut();
        }
    }
	function load(){
		$.post(remote_file+'pos',{select:'pending_invoice_list',id:invoice_id},function(d){
			$('#pending_invoice_list').html(d);
			setTimeout(load,180000);
			$(".print_delivery_list").unbind('click').click(function(){
				$.post(remote_file+"pos",{print_delivery_list:$(this).parents('tr').attr('id')},function(data){
					$('#printableArea').html(data);
					window.print();
				});
			});
			
		});
	}
	load();
	if(invoice_id>0){
		if($("#customer_field").val()>0)select_customer($("#customer_field").val());
		$(".product_tr").each(function(i){//if(i==0)alert($(this).find('.product_delete').html());
			$(this).find('.product_delete').click(delete_product);
			$(this).find('.mrp').click(price_vary);
			bind_qty_click($(this).find('.qty'));
			invoice_total();
			count_products();
			// tr=$(this);
			// $(this).change((e)=>{
				// update(tr);
			// });
		});
	}
	
</script>
<div class="hide" id="pay">
	<div data-simplebar class="h-100">
		<div class="rightbar-title px-3 py-4">
			<button type="button" class="btn btn-outline-dark waves-effect waves-light right-bar-toggle float-right"><i class="mdi mdi-close noti-icon"></i></button>
			<h5 class="m-0">Payment Options</h5>
		</div><hr class="mt-0" />
		<div class="p-4">
			<form class="form_submit" id="payment_method" enctype="multipart/form-data" autocomplete="off">
			<table class="table mb-0 form-group">
			<tbody id="charges"><tr><td>Sale Total</td><td class="product-total net_total right"></td><td style="width: 5%;"></td></tr></tbody>
			<tbody id="payment_list"></tbody>
			<tfoot><tr style="font-weight: bold;"><td>To Pay :</td><td class="to_pay right">0.00</td><td></td></tr></tfoot></table><hr class="mt-0" />
			<?php
				echo '<div class="row">
					<div class="form-group col-lg-4">
						<div><select class="form-control select payment_method" name="method"><option value="1">Cash</option><option value="4">Card</option>';
						// foreach($payment_methods as $val=>$title)echo '<option value="'.$val.'">'.$title.'</option>';
						echo '</select></div>
					</div>
					<div class="form-group col-lg-4"><input type="number" class="form-control payment_press_enter first_field" placeholder="Amount" name="amount"/></div>
					<div class="form-group col-lg-4 hide payment_methods method_5 method_6 method_0"><input type="number" id="payment_code" placeholder="Code" class="form-control next_input" name="code" code="" disabled/></div>
					<div class="form-group col-lg-4 payment_methods method_4 method_3">
						<div><select class="form-control select" name="pos">';
						$default=74;
						if(isset($_COOKIE['sales_counter'])){
							if($_COOKIE['sales_counter']==2)$default=28;
							elseif($_COOKIE['sales_counter']==3)$default=2;
						}
						foreach($pos_machines as $val=>$title)echo '<option value="'.$val.'" '.($val==$default?'selected':'').'>'.$title.'</option>';
						echo '</select></div>
					</div>
					<div class="form-group col-lg-4 hide payment_methods method_8"><input type="number" placeholder="ID" class="form-control next_input" name="upay" disabled/></div>
				</div>';
				echo '<div class="row hide payment_methods method_4 method_8 method_3">
					
					<!--div class="form-group col-lg-4"><input type="number" placeholder="First 6 Digits" class="form-control next_input second_field" name="digits_6"/></div>
					<div class="form-group col-lg-4"><input type="number" placeholder="Last 4 Digits" class="form-control payment_press_enter" name="digits_4"/></div-->
				</div>';
				echo '<div class="row hide payment_methods method_8">
					<div class="form-group col-lg-12" id="upay_list"></div>
				</div>';
			?>
			<div class="row hide sms_code"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Send SMS</button></div></div>
			<div class="row payment_add_button"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block payment_add">Add</button></div></div>
			<button type="button" class="btn btn-primary btn-icon print_last_invoice">
				<span class="btn-icon-label"><i class="mdi mdi-printer mr-2"></i></span> Print Last Invoice
			</button>
			<button type="button" class="btn btn-primary btn-icon no_whatsapp">No Whatsapp</button>
			</form>
		</div>
	</div>
</div>
<div class="hide" id="cash_register">
	<div data-simplebar class="h-100">
		<div class="rightbar-title px-3 py-4">
			<button type="button" class="btn btn-outline-dark waves-effect waves-light right-bar-toggle float-right"><i class="mdi mdi-close noti-icon"></i></button>
			<h5 class="m-0">Cash Register</h5>
		</div><hr class="mt-0" />
		<div class="p-4">
			<form class="form_submit" enctype="multipart/form-data">
			<div class="row"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary waves-effect waves-light" id="cash_count">Cash Count</button></div></div>
			<div class="collapse" id="denomination_list">
			<?php
				$result = mysqli_query($con, "SELECT * FROM settings WHERE title='denomination'");
				$denomination=json_decode(mysqli_fetch_assoc($result)['description']);
				foreach($denomination as $deno){
					echo '<div class="row"><div class="form-group col-lg-4">'.$deno.' x</div>
					<div class="form-group col-lg-4"><input type="text" name="denomination['.$deno.']" class="form-control next_input denomination" amount="'.$deno.'"/></div>
					<div class="form-group col-lg-1">=</div><div class="form-group col-lg-3 total"></div></div>';
				}
			?>
			</div>
			<div class="row"><div class="form-group col-lg-4"></div><div class="form-group col-lg-4">Total</div><div class="form-group col-lg-1">=</div><div class="form-group col-lg-3"><input type="number" class="form-control next_input" name="cash_register" id="denomination"/></div></div>
			<div class="row"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block cash_register"><?php echo $cash_register_text;?></button></div></div>
			<?php
			echo '<div class="row '.((isset($_COOKIE['Counter_login']) OR $_SESSION['soft']['user_data']['user_level']==0)?'':'hide').' cash_in_out_form"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block inner_right_bar_click" data="#cash_in">Cash IN</button></div></div>
				<div class="row '.((isset($_COOKIE['Counter_login']) OR $_SESSION['soft']['user_data']['user_level']==0)?'':'hide').' cash_in_out_form"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block inner_right_bar_click" data="#cash_out">Cash Out</button></div></div>';
			?>
			</form>
		</div>
	</div>
</div>
<div class="hide" id="cash_in">
	<div data-simplebar class="h-100">
		<div class="rightbar-title px-3 py-4">
			<button type="button" class="btn btn-outline-dark waves-effect waves-light float-right inner_right_bar_click" data="#cash_register"><i class="mdi mdi-close noti-icon"></i></button>
			<h5 class="m-0">Cash In</h5>
		</div><hr class="mt-0" />
		<div class="p-4">
			<form class="form_submit" enctype="multipart/form-data">
			<?php
				if(isset($_SESSION['soft']['menus']['accounts/accounts']['permission']) AND in_array('add',$_SESSION['soft']['menus']['accounts/accounts']['permission'])){
					echo '<div class="row"><div class="form-group col-lg-6">Date</div>
					<div class="form-group col-lg-6"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'"/></div></div>';
					echo '<div class="row"><div class="form-group col-lg-6">Session</div>
					<div class="form-group col-lg-6"><input type="number" class="form-control" name="session"/></div></div>';
				}
				echo '<div class="row"><div class="form-group col-lg-6">Source</div>
				<div class="form-group col-lg-6">
					<div><select class="form-control select" name="source"><option></option>';
						$result=mysqli_query($con,"SELECT ba.id,ba.account,b.short FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank WHERE ba.status='1'");
						while($row=mysqli_fetch_assoc($result)){
							echo '<option value="'.$row['id'].'">'.$row['account'].'</option>';
						}
					echo '</select></div>
				</div></div>';
			?>
			<div class="row"><div class="form-group col-lg-6">Amount : </div>
				<div class="form-group col-lg-6"><input type="number" class="form-control next_input" name="amount"/><input type="hidden" name="type" value="in"/></div></div>
			<div class="row"><div class="form-group col-lg-6">Description : </div>
			<div class="form-group col-lg-6"><input type="text" class="form-control next_input" name="description"/></div></div>
			<div class="row"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block cash_in">Submit</button></div></div>
			</form>
		</div>
	</div>
</div>
<div class="hide" id="cash_out">
	<div data-simplebar class="h-100">
		<div class="rightbar-title px-3 py-4">
			<button type="button" class="btn btn-outline-dark waves-effect waves-light float-right inner_right_bar_click" data="#cash_register"><i class="mdi mdi-close noti-icon"></i></button>
			<h5 class="m-0">Cash Out</h5>
		</div><hr class="mt-0" />
		<div class="p-4">
			<form class="form_submit" enctype="multipart/form-data">
			<?php
				if(isset($_SESSION['soft']['menus']['accounts/accounts']['permission']) AND in_array('add',$_SESSION['soft']['menus']['accounts/accounts']['permission'])){
					echo '<div class="row"><div class="form-group col-lg-6">Date</div>
					<div class="form-group col-lg-6"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'"/></div></div>';
					echo '<div class="row"><div class="form-group col-lg-6">Session</div>
					<div class="form-group col-lg-6"><input type="number" class="form-control" name="session"/></div></div>';
				}
				echo '<div class="row"><div class="form-group col-lg-6">Type</div>
				<div class="form-group col-lg-6">
					<select class="form-control select" name="payment_type" id="type"><option></option>';
						$types=array('1'=>'Payments','2'=>'Account');
						foreach($types as $id=>$type){
							echo '<option value="'.$id.'">'.$type.'</option>';
						}
					echo '</select>
				</div></div>
				<div class="row hide type_1"><div class="form-group col-lg-6">Payment Type</div>
					<div class="form-group col-lg-6">
						<select class="form-control select" name="p_type" id="p_type">
							<option></option>';
							foreach($payments_type as $id=>$title){
								echo '<option value="'.$id.'">'.$title.'</option>';
							}
						echo '</select>
					</div>
				</div>
				<div class="row hide p_type p_type_2"><div class="form-group col-lg-6">Staff</div>
				<div class="form-group col-lg-6">
					<select class="form-control select" name="staff">
						<option></option>';
						$result=mysqli_query($con,"SELECT * FROM users WHERE status='1'");
						while($row=mysqli_fetch_assoc($result)){
							echo '<option value="'.$row['id'].'">'.$row['first_name'].'</option>';
						}
				echo '</select>
				</div></div>
				<div class="row hide p_type p_type_1"><div class="form-group col-lg-6">Suppliers</div>
				<div class="form-group col-lg-6">
					<select class="form-control select" name="supplier">
						<option></option>';
						$result=mysqli_query($con,"SELECT * FROM suppliers WHERE status>0");
						while($row=mysqli_fetch_assoc($result)){
							echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
						}
				echo '</select>
				</div></div>
				<div class="row hide type_2"><div class="form-group col-lg-6">Account</div>
					<div class="form-group col-lg-6">
						<select class="form-control select" name="creditor">
							<option></option>';
							$result=mysqli_query($con,"SELECT ba.id,ba.account,b.short FROM bank_accounts ba 
							LEFT JOIN banks b ON b.id=ba.bank WHERE ba.status='1'");
							while($row=mysqli_fetch_assoc($result)){
								echo '<option value="'.$row['id'].'">'.$row['account'].'</option>';
							}
					echo '</select>
					</div>
				</div>';
			?>
			<div class="row"><div class="form-group col-lg-6">Amount : </div>
			<div class="form-group col-lg-6"><input type="number" class="form-control next_input" name="amount"/><input type="hidden" name="type" value="out"/></div></div>
			<div class="row"><div class="form-group col-lg-6">Description : </div>
			<div class="form-group col-lg-6"><input type="text" class="form-control next_input" name="description"/></div></div>
			<div class="row"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block cash_out">Submit</button></div></div>
			<div class="row"><div class="form-group col-lg-12"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light btn-block inner_right_bar_click" data="#cash_history">View History</button></div></div>
			</form>
		</div>
	</div>
</div>
<div class="hide" id="cash_history">
	<div data-simplebar class="h-100">
		<div class="rightbar-title px-3 py-4">
			<button type="button" class="btn btn-outline-dark waves-effect waves-light float-right inner_right_bar_click" data="#cash_out"><i class="mdi mdi-close noti-icon"></i></button>
			<h5 class="m-0">Cash Flow History</h5>
		</div><hr class="mt-0" />
		<div class="p-4">
			<table class="table mb-0 form-group">
			<tbody id="list_payments"></tbody></table>
		</div>
	</div>
</div>
<div class="modal fade price_vary" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	 <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="price_vary"></div>
			</div>
		</div>
	</div>
</div>
<span class="hide popup recent_invoices" o-href="<?php echo $remote_file.'/pos&form=recent_invoices';?>"></span>
<span class="hide popup f12" o-href="<?php echo $remote_file.'/pos&form=product';?>"></span>
<span class="hide popup f3" o-href="<?php echo $remote_file.'/pos&form=customer';?>"></span>
<span class="hide popup bill_payments_summary f6" o-href="<?php echo $remote_file.'/pos&form=bill_payments_summary';?>"></span>
<?php
	if(isset($_SESSION['soft']['menus']['products/products']['permission']) AND in_array('add',$_SESSION['soft']['menus']['products/products']['permission']))echo '<span class="hide popup plus" o-href="'.$config_url.'remote.php?file=products/products&form=add_product"></span>';
	if($_SESSION['branch_data']['finger_print']==0)echo '<span class="hide popup insert" href="'.$remote_file.'/pos&form=attendance"></span>';
?>
<span class="hide popup delete_product" href="<?php echo $remote_file.'/pos&form=delete_product';?>"></span>
<style>
#cash_register .form-group{margin-bottom: 0.2rem;}
.ui-menu-item{cursor: pointer;}

#cash_history .table > tbody > tr > td {
    padding: 0;
}
</style>
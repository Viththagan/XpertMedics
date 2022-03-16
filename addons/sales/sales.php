<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// echo '<pre>';print_r($_SESSION);
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if($_POST['edit']=='sales_payments'){
			$result=mysqli_query($con,"SELECT * FROM sales_payments WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			if($_POST['field']=='type'){
				$status=0;
				if($value==1)$status=-1;
				if(($row['child']==0 OR $row['child']=='') AND $value==4){
					mysqli_query($con,"INSERT INTO sales_card(amount) VALUES('$row[amount]')");
					$row['child']=mysqli_insert_id($con);
					mysqli_query($con,"UPDATE sales_payments SET child='$row[child]' WHERE id='$_POST[id]'");
				}
				mysqli_query($con,"UPDATE sales_card SET status='$status' WHERE id='$row[child]'");
			}
			else if($_POST['field']=='first_6_digit' OR $_POST['field']=='no' OR $_POST['field']=='bank'){
				mysqli_query($con,"UPDATE sales_card SET $_POST[field]='$value' WHERE id='$row[child]'");
				return;
			}
		}
		if($_POST['edit']=='products_logs' AND in_array($_POST['field'],array('qty','free','uic'))){
			$result=mysqli_query($con,"SELECT product FROM $_POST[edit] WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			update_qty($row['product']);
		}
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'view_invoice' :
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
			case 'move_invoice' :
				$date=date('Y-m-d');
				$result=mysqli_query($con,"SELECT * FROM products_logs_temp WHERE type='0' AND referrer='$_POST[id]'");
				while($row=mysqli_fetch_assoc($result)){
					$result1=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[product]' ORDER BY id DESC LIMIT 0,1")or die(mysqli_error($con));
					$balance=0;
					if(mysqli_affected_rows($con)==1){
						$row1=mysqli_fetch_assoc($result1);
						if(is_numeric($row1['balance']))$balance=$row1['balance'];
					}
					$balance-=$row['qty'];
					mysqli_query($con,"INSERT INTO products_logs(product,type,referrer,qty,balance,purchased,mrp,selling_price,added,user) 
						VALUES('$row[product]','$row[type]','$row[referrer]','$row[qty]','$balance','$row[purchased]','$row[mrp]','$row[selling_price]','$row[added]','$row[user]')")or die(mysqli_error($con));
					$log=mysqli_insert_id($con);
					
					if($row['status']>0){
						$ids=implode(',',$partners);
						$result1=mysqli_query($con,"SELECT po.id FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type='1') 
						WHERE l.id='$row[status]' AND po.supplier IN($ids)")or die(mysqli_error($con));
						if(mysqli_affected_rows($con)>0){
							$row1=mysqli_fetch_assoc($result1);
							mysqli_query($con,"INSERT INTO suppliers_partners(product_log,po) VALUES('$log','$row1[id]')")or die(mysqli_error($con));
						}
					}
				}
				// mysqli_query($con,"UPDATE products_logs_temp SET checked='1' WHERE type='0' AND referrer='$_POST[id]'")or die(mysqli_error($con));
				mysqli_query($con,"DELETE FROM products_logs_temp WHERE type='0' AND referrer='$_POST[id]'")or die(mysqli_error($con));
				mysqli_query($con,"UPDATE sales SET closed='1' WHERE id='$_POST[id]'");
				break;
			case 'customers' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Customer</label><div><input type="text" class="x-large form-control customer" name="mobile"></div></div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="add_customer_to_invoice"/>
												<input type="hidden" name="id" value="'.$_GET['id'].'"/>
												<input type="hidden" id="callback" value="'.$_GET['callback'].'"/>
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
			case 'select_customer':
				$q = strtolower($_GET["term"]);
				if (get_magic_quotes_gpc()) $q = stripslashes($q);
				$result1=mysqli_query($con,"SELECT * FROM customers WHERE fname LIKE '%$q%' OR lname LIKE '%$q%' OR mobile LIKE '%$q%' OR remind LIKE '%$q%' OR street LIKE '%$q%' LIMIT 0,10");
				$result = array();
				while($row=mysqli_fetch_array($result1)){
					array_push($result, array("id"=>$row['id'], "label"=>$row['mobile'].' : '.$row['fname'].' '.$row['lname'], "value" =>$row['mobile']));
				}
				echo json_encode($result);
				break;
			case 'invoice_data':
				$title="Products Logs";
				$table="products_logs";
				$result=mysqli_query($con,"SELECT l.*,p.title FROM products_logs l LEFT JOIN products p ON p.id=l.product WHERE l.referrer='$_GET[id]' AND l.type='0'");
				if(mysqli_affected_rows($con)==0){
					$result=mysqli_query($con,"SELECT l.*,p.title FROM products_logs_temp l LEFT JOIN products p ON p.id=l.product WHERE l.referrer='$_GET[id]' AND l.type='0'");
					$title="Products Logs Temporary";
					$table="products_logs_temp";
				}
				echo $title.'<table table="'.$table.'" style="width:100%;" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-sm">
					<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('MRP').'</th><th>'.language('Sold').'</th><th>'.language('Purchased').'</th>
					<th>'.language('Qty').'</th><th>'.language('Sum').'</th><th>'.language('Profit').'</th><th></th>
					</tr></thead><tbody>';
				$profit=0;
				$total=0;
				$counter=1;
				$products=array();
				while($row=mysqli_fetch_assoc($result)){
					$sum=$row['qty']*$row['selling_price'];
					$profit1=($row['selling_price']-$row['purchased'])*$row['qty'];
					echo '<tr id="'.$row['id'].'">
						<td class="center '.($_SESSION['user_id']==1?'update_available_qty':'').'" id="'.$row['product'].'">'.$counter++.'</td>
						<td>'.$row['title'].'</td>
						<td class="right">'.$row['mrp'].'</td>
						<td class="right editable" field="selling_price">'.$row['selling_price'].'</td>
						<td class="right editable" field="purchased">'.number_format($row['purchased'],2).'</td>
						<td class="right editable" field="qty">'.($row['qty']+0).'</td>
						<td class="right">'.number_format($sum,2).'</td>
						<td class="right">'.number_format($profit1,2).'</td>
						<td class="center">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete" style="cursor:pointer;"></i>':'').'</td>
					</tr>';
					$products[$row['product']]=$row['title'];
					$total+=$sum;
					$profit+=$profit1;
				}
				echo '<tfoot><tr><td></td><td colspan="5">Total</td><td class="right">'.number_format($total,2).'</td>
				<td class="right">'.number_format($profit,2).'</td><td></td></tr></tfoot></tbody></table>';
				
				$result=mysqli_query($con,"SELECT p.*,sc.first_6_digit,sc.no,sc.bank FROM sales_payments p LEFT JOIN sales_card sc ON (sc.id=p.child AND p.type=4) WHERE p.sales='$_GET[id]'");
				echo '<table table="sales_payments" style="width:100%;" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-sm">
					<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Type').'</th><th>'.language('Bank').'</th><th>'.language('Amount').'</th>
					<th></th></tr></thead><tbody>';
				$total=0;
				$counter=1;
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr class="pointer" id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td class="right editable" field="date">'.date('d-m-Y',strtotime($row['date'])).'</td>
						<td class="editable_select" field="type" data="{\'1\':\'Cash\',\'4\':\'Card\'}" value="'.$row['type'].'">'.$payment_methods[$row['type']].'</td>
						'.($row['type']==4?'<td class="editable_select" field="bank" data="'.str_replace('"',"'",json_encode($pos_machines)).'" value="'.$row['bank'].'">'.$pos_machines[$row['bank']].'</td>':'<td></td>').'
						<td class="right editable" field="amount">'.number_format($row['amount'],2).'</td>
						<td class="center">'.((in_array('delete',$permission) AND $row['type']<5)?'<i class="mdi mdi-delete agoy_delete" style="cursor:pointer;"></i>':'').'</td>
					</tr>';
					$total+=$row['amount'];
				}
				echo '<tfoot><tr><td></td><td colspan="3">Total</td><td class="right">'.number_format($total,2).'</td><td></td></tr></tfoot></tbody></table>';
				if($table=="products_logs_temp")echo '<button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block move_invoice" id="'.$_GET['id'].'">Move Invoice</button>';
				// echo '<style>.verti-timeline .event-list .event-content::before {height: 10px;top: -12px;}</style>
				// <ul class="verti-timeline list-unstyled">';
				$result=mysqli_query($con,"SELECT id,device FROM post_log WHERE data LIKE '{\"post\":{\"print\":\"invoice\",\"id\":\"$_GET[id]\"%' LIMIT 0,1");
				if(mysqli_affected_rows($con)>0){
					$row=mysqli_fetch_assoc($result);
					$result=mysqli_query($con,"SELECT * FROM post_log WHERE id>(SELECT MAX(id) id FROM post_log WHERE data LIKE '{\"post\":{\"print\":\"invoice\",\"id\"%' AND id<$row[id] AND device='$row[device]') 
					AND id<=(SELECT MAX(id) FROM post_log WHERE data LIKE '{\"post\":{\"print\":\"invoice\",\"id\":\"$_GET[id]\",%') AND device='$row[device]'")or die(mysqli_error($con));
					// echo $row['id'];	{"post":{"print":"invoice","id"
					$price_type=array(0=>'Price Fixed',1=>'Price Vary');
					while($row=mysqli_fetch_assoc($result)){
						if (strpos($row['data'], '[') !== false){
							$row['data']=str_replace('["','',$row['data']);
							$row['data']=str_replace('"]','',$row['data']);
						}
						$data=json_decode($row['data'],true);
						if(isset($data['post'])){
							$data=$data['post'];
							$title='';
							$product='';
							if(isset($data['id']) AND isset($products[$data['id']]))$product=$products[$data['id']];
							if(isset($data['select'])){
								if($data['select']=='product'){
									$title='Ping to Get data of '.$product.' ('.$price_type[$data['price_vary']].')';
									unset($data);
								}
								else if($data['select']=='customer'){
									$title='Ping to get Customer data';
									unset($data['select']);
								}
								$row['data']='';
							}
							else if(isset($data['form_submit'])){
								if(!isset($data['invoice']) OR $data['invoice']==0)$title.='Creating New Invoice And ';
								if(isset($data['log']) AND $data['log']>0)$title.='Update product '.$product;
								else $title.='Adding product '.$product;
								$row['data']='';
								unset($data['form_submit']);
								unset($data['invoice']);
								unset($data['id']);
								// unset($data['log']);
							}
							else if(isset($data['print'])){
								$title.='Print invoice';
								unset($data['print']);
								unset($data['id']);
								unset($data['customer']);
								$row['data']='';
								if(isset($data['data']) AND sizeof($data['data'])>0){
									$title.=' [Payment : ';
									$dat=array();
									if($data['data']['method']!=4)unset($data['data']['pos']);
									foreach($data['data'] as $key=>$value){
										if($key=='method')$dat[]=$key.' -> '.$payment_methods[$value];
										else if($value<>'')$dat[]=$key.' -> '.$value;
									}
									$title.=' ('.implode(',',$dat).')]';
									unset($data['data']);
								}
							}
							
							echo date('H:i:s',strtotime($row['added'])).' : '.$title;
							if(isset($data) AND sizeof($data)>0){
								$dat=array();
								foreach($data as $key=>$value)$dat[]=$key.' -> '.$value;
								echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ('.implode(', ',$dat).')';
							}
							if($row['data']<>'')echo ' ('.$row['data'].')';
							echo '<br/>';
							// echo '<li class="event-list" style="padding: 10px 10px 10px 10px;">
								// <div class="timeline-icon"><i class="mdi mdi-adjust bg-'.$icon.'"></i></div>
								// <div class="event-content p-4" style="padding: 0rem!important;"><h5 class="mt-0 mb-3 font-18">'.$title.'</h5>
									// <div class="text-muted"><p class="mb-2">'.$row['data'].'</p></div>
								// </div>
							// </li>';//danger,primary,warning
						}
						if(isset($data['get'])){
							$data=$data['get'];
							$title='';
							echo '-->'.$title;
							if(isset($data) AND sizeof($data)>0){
								$dat=array();
								foreach($data as $key=>$value)$dat[]=$key.'=>'.$value;
								echo ' ('.implode(', ',$dat).')';
							}
							$row['data']='';
							if($row['data']<>'')echo ' ('.$row['data'].')';
							echo '<br/>';
						}
						if($row['data']<>''){
							echo $row['data'].'<br/>';
						}
					}
					// echo '</ul>';
				}
				break;
			case 'add_payment':
				$result=mysqli_query($con,"SELECT * FROM sales WHERE id='$_GET[id]'");
				$row=mysqli_fetch_assoc($result);
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">';
				echo '<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="form-group col-lg-2">Date</div>
							<div class="form-group col-lg-10"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'"/></div>
						</div>
						<div class="row">
							<div class="form-group col-lg-2">Session</div>
							<div class="form-group col-lg-10"><input type="number" class="form-control" name="session" value="'.$row['session'].'" required/></div>
						</div>
						<div class="row">
							<div class="form-group col-lg-4">
								<div><select class="form-control select payment_method" name="method">';
								foreach($payment_methods as $val=>$title)echo '<option value="'.$val.'">'.$title.'</option>';
								echo '</select></div>
							</div>
							<div class="form-group col-lg-4"><input type="number" class="form-control payment_press_enter first_field" placeholder="Amount" name="amount" value="'.$_GET['amount'].'"/></div>
						</div>';
						echo '<div class="form-group col-lg-4 hide payment_methods method_8"><input type="number" placeholder="ID" class="form-control next_input" name="upay"/></div>
							<div class="row hide payment_methods method_4">
							<div class="form-group col-lg-4">
								<div><select class="form-control select" name="pos">';
								$default=74;
								foreach($pos_machines as $val=>$title)echo '<option value="'.$val.'" '.($val==$default?'selected':'').'>'.$title.'</option>';
								echo '</select></div>
							</div>
							<!--div class="form-group col-lg-4"><input type="number" placeholder="First 6 Digits" class="form-control next_input second_field" name="digits_6"/></div>
							<div class="form-group col-lg-4"><input type="number" placeholder="Last 4 Digits" class="form-control payment_press_enter" name="digits_4"/></div-->
							</div>
							<input type="hidden" name="popup_form_submit" value="add_payment"/>
							<input type="hidden" name="id" value="'.$_GET['id'].'"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Payment</button></div></div>
						</form>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_POST['popup_form_submit'])){
		switch($_POST['popup_form_submit']){
			case 'add_customer_to_invoice':
				$result=mysqli_query($con,"SELECT id FROM customers WHERE mobile='$_POST[mobile]'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					mysqli_query($con,"UPDATE sales SET customer='$row[id]' WHERE id='$_POST[id]'");
				}
				break;
			case 'add_payment':
				$date=date('Y-m-d',strtotime($_POST['date']));
				$result=mysqli_query($con,"SELECT * FROM sales WHERE id='$_POST[id]'");
				$row=mysqli_fetch_assoc($result);
				$child=0;
				$cash=0;
				$paid=0;
				switch($_POST['method']){
					case 1:
						$cash=$_POST['amount'];
						break;
					case 2:
						$transfer=date('Y-m-d',strtotime($_POST['transfer']));
						mysqli_query($con,"INSERT INTO sales_cheque(date,customer,cheque,bank,transfer,amount,used,added,user) 
						VALUES('$date','$row[customer]','$_POST[cheque]','$_POST[bank]','$transfer','$_POST[amount]','$_POST[amount]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
						$child=mysqli_insert_id($con);
						break;
					case 4:
						mysqli_query($con,"INSERT INTO sales_card(amount,bank) VALUES('$_POST[amount]','$_POST[pos]')") or die(mysqli_error($con));
						$child=mysqli_insert_id($con);
						break;
					case 5:
						$amount=$_POST['amount'];
						$resul=mysqli_query($con,"SELECT * FROM sales_points WHERE (points-used)>0 AND customer='$row[customer]' ORDER BY expiry ASC")or die(mysqli_error($con));
						while($row1=mysqli_fetch_assoc($resul) AND $amount>0){
							$available=$row1['points']-$row1['used'];
							$use=$amount;
							if($available<$amount)$use=$available;
							$amount-=$use;
							mysqli_query($con,"UPDATE sales_points SET used=used+$use WHERE id='$row1[id]'") or die(mysqli_error($con));
						}
						break;
					case 6:
						$amount=$_POST['amount'];
						mysqli_query($con,"UPDATE sales_vouchers SET used=used+$amount WHERE code='$_POST[code]'");
						$result1=mysqli_query($con,"SELECT * FROM sales_vouchers WHERE code='$_POST[code]'");
						$row1=mysqli_fetch_assoc($result1);
						$child=$row1['id'];
						if($row1['used']==$row1['amount'] || $row1['type']==1)mysqli_query($con,"UPDATE sales_vouchers SET code='',code_used='$_POST[code]',used=amount WHERE code='$_POST[code]'") or die(mysqli_error($con));
						break;
				}
				if($_POST['method']>1){
					if($_POST['method']==8){
						$child=$_POST['upay'];
						$result=mysqli_query($con,"SELECT * FROM upay WHERE id='$child'");
						if(mysqli_affected_rows($con)==1){
							$row1=mysqli_fetch_assoc($result);
							if($row1['customer']>0)echo "$id1 : $row1[customer]";
							else mysqli_query($con,"UPDATE upay SET customer='$row[customer]',sales='$_POST[id]' WHERE id='$child'");
						}
					}
					$paid+=$_POST['amount'];
					mysqli_query($con,"INSERT INTO sales_payments(date,amount,cash,type,sales,child,session,added,user) 
					VALUES('$date','$_POST[amount]','0','$_POST[method]','$_POST[id]','$child','$_POST[session]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				}
				if($cash>0){
					$result=mysqli_query($con,"SELECT SUM(qty*selling_price) total FROM products_logs WHERE type='0' AND referrer='$_POST[id]'") or die(mysqli_error($con));
					$row1=mysqli_fetch_assoc($result);
					$payable=$row1['total']-$paid;
					if($cash<$payable)$payable=$cash;
					mysqli_query($con,"INSERT INTO sales_payments(date,amount,cash,type,sales,child,session,added,user) 
						VALUES('$date','$payable','$cash','1','$_POST[id]','$child','$_POST[session]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				}
				mysqli_query($con,"UPDATE sales SET closed='1' WHERE id='$_POST[id]'");
				break;
		}
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='sales'){
			delete_invoice($_POST['id']);
		}
		else if($_POST['todelete']=='sales_payments'){
			$result=mysqli_query($con,"SELECT * FROM sales_payments WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			switch($row['type']){
				case 1:
					mysqli_query($con,"DELETE FROM sales_payments WHERE id='$_POST[id]'");
					break;
				case 2:
					mysqli_query($con,"DELETE FROM sales_cheque WHERE id='$row[child]'");
					mysqli_query($con,"DELETE FROM sales_payments WHERE sales='$row[sales]' AND child='$row[child]'");
					break;
				case 3:
					mysqli_query($con,"DELETE FROM sales_payments WHERE id='$_POST[id]'");
					break;
				case 4:
					mysqli_query($con,"DELETE FROM sales_card WHERE id='$row[child]'");
					mysqli_query($con,"DELETE FROM sales_payments WHERE sales='$row[sales]' AND child='$row[child]'");
					break;
				case 5:
					// mysqli_query($con,"DELETE FROM sales_card WHERE id='$row[child]'");
					// mysqli_query($con,"DELETE FROM sales_payments WHERE sales='$row[sales]' AND child='$row[child]'");
					break;
				
			}
		}
		else if($_POST['todelete']=='products_logs'){
			mysqli_query($con,"DELETE FROM products_logs WHERE id='$_POST[id]'");
			update_qty($_POST['id']);
		}
		else if($_POST['todelete']=='products_logs_temp'){
			mysqli_query($con,"DELETE FROM products_logs_temp WHERE id='$_POST[id]'");
		}
		mysqli_query($con,"UPDATE sales SET checked='1' WHERE id='$_POST[id]'");
		return;
	}
	// if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	// else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
		if((!isset($_GET['search']) OR $_GET['search']=='') AND isset($_GET['id']))$_GET['search']=$_GET['id'];
		echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="col-xl-12">
                                <div class="card" style="margin-bottom: 0px;">
                                    <div class="card-body" style="padding: 0rem;">
										<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#query" aria-expanded="false" aria-controls="collapseExample">Query</a>
										<a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#extra" aria-expanded="false" aria-controls="collapseExample">By Amount</a>
										<a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#product" aria-expanded="false" aria-controls="collapseExample">By Product</a></p>
                                        <div class="collapse" id="query">
                                            <div class="card card-body mb-0">
                                                <textarea name="query" class="form-control" rows="3">'.(isset($_GET['query'])?$_GET['query']:'').'</textarea>
                                            </div>
										</div>
                                        <div class="collapse" id="extra">
											<div class="row">
												<div class="form-group col-lg-2">
													<label>Min.Bill</label>
													<div><input type="number" name="min_bill" class="form-control" value="'.(isset($_GET['min_bill'])?$_GET['min_bill']:'').'"/></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Min.Discount</label>
													<div><input type="number" name="min_discount" class="form-control" value="'.(isset($_GET['min_discount'])?$_GET['min_discount']:'').'"/></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Min.Profit</label>
													<div><input type="number" name="min_profit" class="form-control" value="'.(isset($_GET['min_profit'])?$_GET['min_profit']:'').'"/></div>
												</div>
											</div>
										</div>
                                        <div class="collapse" id="product">
											<div class="row">
												<div class="form-group col-lg-6">
													<label>Products (id OR code OR title Seperated by comma)</label>
													<div><textarea name="product" class="form-control" rows="3">'.(isset($_GET['product'])?$_GET['product']:'').'</textarea></div>
												</div>
												<div class="form-group col-lg-3">
													<label>Category(s)</label>
													<div><select class="form-control select" name="category[]" multiple="multiple" multiple>';
														$result=mysqli_query($con,"SELECT * FROM product_category");
														while($row=mysqli_fetch_assoc($result)){
															echo '<option value="'.$row['id'].'" '.((isset($_GET['category']) AND in_array($row['id'],$_GET['category']))?'selected':'').'>'.$row['title'].'</option>';
														}
													echo '</select>
													</div>
												</div>
												<div class="form-group col-lg-3">
													<label>Brand(s)</label>
													<div><select class="form-control select" name="brand[]" multiple="multiple" multiple>';
														$result=mysqli_query($con,"SELECT * FROM product_brand");
														while($row=mysqli_fetch_assoc($result)){
															echo '<option value="'.$row['id'].'" '.((isset($_GET['brand']) AND in_array($row['id'],$_GET['brand']))?'selected':'').'>'.$row['title'].'</option>';
														}
													echo '</select>
													</div>
												</div>
											</div>
										</div>
                                    </div>
                                </div>
                            </div>
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']) AND !isset($_GET['id']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Time</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['time']['from']))$_GET['time']['from']=date('00:00');
										if(!isset($_GET['time']['to']))$_GET['time']['to']=date('23:59');
										echo '<input type="text" class="form-control time" name="time[from]" value="'.date('H:i',strtotime($_GET['time']['from'])).'"/>
										<input type="text" class="form-control time" name="time[to]" value="'.date('H:i',strtotime($_GET['time']['to'])).'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Customer Type(s)</label>
								<div><select class="form-control select" name="type[]" multiple="multiple" multiple>';
									foreach($customer_types as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['type']) AND in_array($type,$_GET['type']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Membership</label>
								<div><select class="form-control select" name="member[]" multiple="multiple" multiple>';
									foreach($memberships as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['member']) AND in_array($type,$_GET['member']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Till</label>
								<div><select class="form-control select form_submit_change" name="till">';
									if(!isset($_GET['till']))$_GET['till']='';
									$result=mysqli_query($con,"SELECT * FROM sales_counters WHERE status='1' ORDER BY id ASC");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" '.($_GET['till']==$row['id']?'selected':'').'>'.$row['title'].'</option>';
									echo '<option value="" '.($_GET['till']==''?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000);
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
	$where="WHERE s.branch='$_SESSION[branch]'";
	$today=date('Y-m-d');
	$date=" AND s.date='$today'";
	if($_GET['from']<>''){
		$from=date('Y-m-d',strtotime($_GET['from']));
		$date=" AND s.date='$from'";
	}
	$view_date=0;
	if(isset($_GET['to']) AND $_GET['to']<>''){
		if($_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$date=" AND s.date='$to'";
		}
		else if(strtotime($_GET['from'])<strtotime($_GET['to'])){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$date=" AND s.date BETWEEN '$from' AND '$to'";
			$view_date=1;
		}
		else {
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$date=" AND s.date BETWEEN '$to' AND '$from'";
			$view_date=1;
		}
	}
	$where.=$date;
	$where.=" AND time(s.added) BETWEEN '".$_GET['time']['from']."' AND '".$_GET['time']['to']."' ";
	if(isset($_GET['type']) AND sizeof($_GET['type'])>0){
		$types=implode(',',$_GET['type']);
		$where.=" AND c.type IN($types)";
	}
	if(isset($_GET['till']) AND is_numeric($_GET['till']))$where.=" AND s.till='$_GET[till]'";
	
	if(isset($_GET['member']) AND sizeof($_GET['member'])>0){
		$member=implode(',',$_GET['member']);
		$where.=" AND c.plat IN($member)";
	}
	
	if(isset($_GET['query']) AND $_GET['query']<>''){
		if(0==count(array_intersect(array_map('strtoupper', explode(' ',$_GET['query'])),$query_avoid))){
			$result=mysqli_query($con,$_GET['query'])or die(mysqli_error($con));
			if(mysqli_affected_rows($con)>0){
				$id=array();
				while($row=mysqli_fetch_array($result))$id[]=$row['id'];
				$ids=implode(',',$id);
				$where.=" AND s.id IN($ids)";
			}
		}
	}
	
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (s.id='$_GET[search]' OR c.mobile LIKE '%$_GET[search]%' OR c.nic LIKE '%$_GET[search]%' OR c.fname LIKE '%$_GET[search]%' OR c.lname LIKE '%$_GET[search]%'";
		$keys=preg_split("/[\s,]+/",$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.="OR s.id='$_GET[search]' OR c.mobile LIKE '%$key%' OR c.nic LIKE '%$key%' c.fname LIKE '%$key%' OR c.lname LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	if(isset($_GET['product']) AND strlen($_GET['product'])>3){
		$keys=explode(',',$_GET['product']);
		$q="";
		foreach($keys as $key){
			if(strlen(trim($key))>3){
				if($q=='')$q="p.id='$key' OR p.ui_code='$key' OR p.bulk_code='$key' OR p.remind LIKE '%$key%' OR p.title LIKE '%$key%'";
				else $q.="AND p.id='$key' OR p.ui_code='$key' OR p.bulk_code='$key' OR p.remind LIKE '%$key%' OR p.title LIKE '%$key%'";
			}
		}
		$where.=" AND s.id IN(SELECT s.id FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) LEFT JOIN products p on p.id=l.product WHERE $q)";
	}
	if(isset($_GET['category']) AND sizeof($_GET['category'])>0){
		$category=implode(',',$_GET['category']);
		$where.=" AND s.id IN(SELECT s.id FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) LEFT JOIN products p ON p.id=l.product WHERE p.category IN($category))";
	}
	if(isset($_GET['brand']) AND sizeof($_GET['brand'])>0){
		$brand=implode(',',$_GET['brand']);
		$where.=" AND s.id IN(SELECT s.id FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) LEFT JOIN products p ON p.id=l.product WHERE p.brand IN($brand))";
	}
	$having="";
	if(isset($_GET['min_bill']) AND $_GET['min_bill']>0)$where.=" AND s.total>=$_GET[min_bill]";
	if(isset($_GET['min_discount']) AND $_GET['min_discount']>0)$having.=" HAVING dis>=$_GET[min_discount]";
	if(isset($_GET['min_profit']) AND $_GET['min_profit']>0){
		if($having=='')$having.=" HAVING profit>=$_GET[min_profit]";
		else $having.=" AND profit>=$_GET[min_profit]";
	}
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	{
	$q="SELECT s.*,c.fname,c.lname,c.mobile,c.type,c.plat mem,u.first_name,
	(SELECT SUM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id) total,
	(SELECT SUM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id AND product='2968') bill_total,
	(SELECT SUM(qty*selling_price) FROM products_logs_temp WHERE type='0' AND referrer=s.id) total_temp,
	(SELECT SUM(amount) FROM sales_payments WHERE type IN(5,6) AND sales=s.id) cost,
	(SELECT SUM(amount*0.025) FROM sales_payments WHERE type=4 AND sales=s.id) comm,
	(SELECT SUM(amount) FROM sales_payments WHERE sales=s.id) paid,SUM((if(l.purchased>0,l.selling_price-l.purchased,0))*l.qty) profit,SUM((l.mrp-l.selling_price)*l.qty) dis
	FROM sales s LEFT JOIN customers c ON c.id=s.customer LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_points p ON p.sale=s.id 
	LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type='0')
	$where GROUP BY s.id $having $limit";
	// echo $q;
	// echo '<pre>';print_r($_SESSION['soft']['menus']);echo '</pre>';
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
				<table table="sales" style="width:100%;" data-button="true" data-sum="7,8,10,11,12,13" data-hide-columns="ID,Session,Till,Time,Type,Discount %,Profit %,Staff" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="customized_table table table-sm">
					<thead><tr><th class="center">#</th><th class="center">'.language('ID').'</th><th class="center">'.language('Session').'</th>
					<th class="center">'.language('Till').'</th><th class="center">'.language('Time').'</th>
					<th>'.language('Customer').'</th><th>'.language('Type').'</th><th class="right">'.language('Total').'</th><th class="right">'.language('Reload/Bill').'</th>
					<th class="right">'.language('Discount').'</th><th class="right">Discount %</th><th class="right">'.language('Paid').'</th>
					<th class="right">'.language('Balance').'</th><th class="right">'.language('Profit').'</th><th class="right">Profit %</th>
					<th>'.language('Staff').'</th><th class="center">'.language('Action').'</th><th class="center">'.language('Payment').'</th>
					<th class="center">'.language('Print').'</th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,$q) or die(mysqli_error($con));
					$counter=1;
					// $color=array('light','success','info','warning','danger');
					$sum=array('total'=>0,'bill_total'=>0,'dis'=>0,'profit'=>0,'paid'=>0);
					while($row=mysqli_fetch_array($result)){
						$class='';
						$row['profit']-=$row['comm'];
						$row['profit']-=$row['cost'];
						$row['total']+=$row['charge'];
						if($row['profit']<0)$class='table-warning';
						if($row['plat']>0)$class='plat_'.$row['plat'];
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.($row['closed']==0?'pending_invoice':($row['closed']==-1?'warning':'')).'">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.($row['invoice']>0?$row['invoice']:$row['id']).'</td>
							<td class="center">'.$row['session'].'</td>
							<td class="center">'.$row['till'].'</td>
							<td class="center">'.($view_date==1?date('d-m-Y',strtotime($row['date'])).', ':'').date('h:i A',strtotime($row['added'])).'</td>
							<td class="popup '.$class.'" href="'.$remote_file.'/sales&form=customers&id='.$row['id'].'&callback=">'.$row['mobile'].'<br/>'.$row['fname'].' '.$row['lname'].($row['mem']>0?'<br/>'.$memberships[$row['mem']]:'').'</td>
							<td class="'.$class.'">'.($row['mem']>0?$memberships[$row['mem']]:'').'</td>
							<td class="right">'.($row['total']>0?Number_format($row['total'],2):Number_format($row['total_temp'],2)).'</td>
							<td class="right">'.Number_format($row['bill_total'],2).'</td>
							<td class="right">'.Number_format($row['dis'],2).'</td>
							<td class="right">'.($row['total']>0?Number_format(($row['dis']/($row['total']+$row['dis']))*100,2):'').'</td>
							<td class="right">'.Number_format($row['paid'],2).'</td>
							<td class="right">'.Number_format($row['total']-$row['paid'],2).'</td>
							<td class="right '.($row['profit']<0?'warning':'').'">'.Number_format($row['profit'],2).'</td>
							<td class="right">'.($row['total']>0?Number_format(($row['profit']/$row['total'])*100,2):'').'</td>
							<td>'.$row['first_name'].'</td>
							<td class="center"><i class="mdi mdi mdi-view-list invoice_view" data-toggle="modal" data-target=".invoice_data"></i>
							'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
							<td class="center">'.(($row['total']-$row['paid'])>1?'<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/sales&form=add_payment&id='.$row['id'].'&amount='.($row['total']-$row['paid']).'">Add Payment</button>':'').'</td>
							<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light print_invoice_btn">Print</button>
							<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/sales&form=view_invoice&id='.$row['id'].'">View</button></td>
						</tr>';
						$sum['total']+=$row['total'];
						$sum['bill_total']+=$row['bill_total'];
						$sum['dis']+=$row['dis'];
						$sum['profit']+=$row['profit'];
						$sum['paid']+=$row['paid'];
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="6">Page Total</td>
					<td class="right"></td>
					<td class="right"></td>
					<td class="right"></td><td></td>
					<td class="right"></td>
					<td class="right"></td>
					<td class="right"></td><td colspan="5"></td>
					</tr>
					<tr><td></td><td colspan="6">Total</td>
					<td class="right">'.Number_format($sum['total'],2).'</td>
					<td class="right">'.Number_format($sum['bill_total'],2).'</td>
					<td class="right">'.Number_format($sum['dis'],2).'</td><td></td>
					<td class="right">'.Number_format($sum['paid'],2).'</td>
					<td class="right">'.Number_format(($sum['total']-$sum['paid']),2).'</td>
					<td class="right">'.Number_format($sum['profit'],2).'</td><td colspan="5"></td>
					</tr></tfoot></table>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
    function after_ajax(){
		if($( ".customer" ).length >0){
			var cache = {};
			$( ".customer" ).autocomplete({minLength: 3,
			  source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
				  response( cache[ term ] );
				  return;
				}
		 
				$.getJSON(remote_file+"/sales&form=select_customer", request, function( data, status, xhr ) {
				  cache[ term ] = data;
				  response( data );
				});
			  }
			});
		}
		$('.payment_method').bind('change',function(){
			$('.payment_methods').hide();
			$('.method_'+$(this).val()).attr('style','display: flex;');
		});
	}
	$('.invoice_view').click(function(){
		$('#invoice_data').html('');
		$.get(remote_file+"/sales&form=invoice_data",{id:$(this).parents('tr').attr('id')},function(data){
			$('#invoice_data').html(data);
			$.agoy_init();
			$('.move_invoice').unbind('click').click(function(){
				but=$(this);
				$.post(remote_file+"/sales&form=move_invoice",{id:$(this).attr('id')},function(data){
					but.remove();
				});
			});
		});
	});
	$('.print_invoice_btn').click(function(){
		$('#printableArea').html('');
		$.post(remote_file+"/pos",{print:'invoice_old',id:$(this).parents('tr').attr('id')},function(data){
			$('#printableArea').html(data);
			window.print();
		});
	});
	
	
</script>
<style>
.table > tbody > tr > td, .table > tfoot > tr > td, .table > thead > tr > td {
    padding: 0px 5px;
}
.white-popup {
  position: relative;
  background: #FFF;
  padding: 20px;
  width: auto;
  max-width: 500px;
  margin: 20px auto;
}
</style>
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
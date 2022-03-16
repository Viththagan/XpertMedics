<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	$date=date('Y-m-d');
	if(isset($_POST['form_submit'])){//print_r($_POST);
		switch($_POST['form_submit']){
			case 'add_purchase'://print_r($_POST);
				if($_POST['invoice_id']==0){
					if(!isset($_POST['date']))$_POST['date']=date('Y-m-d');
					$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
					if($_POST['discount']=='')$_POST['discount']='NULL';
					mysqli_query($con,"INSERT INTO po(date,company,supplier,invoice,discount,branch,device,added,user) 
					VALUES('$_POST[date]','$_POST[company]','$_POST[supplier]','$_POST[invoice]',$_POST[discount],'$_SESSION[branch]','$_COOKIE[device_id]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					$_POST['invoice_id']=mysqli_insert_id($con);
				}
				if($_POST['invoice_id']>0){
					if($_POST['qty']=='')$_POST['qty']='NULL';
					if($_POST['uic']=='')$_POST['uic']='NULL';
					if($_POST['free']=='')$_POST['free']='NULL';
					if($_POST['mrpc']=='')$_POST['mrpc']='NULL';
					if($_POST['selling_price']=='')$_POST['selling_price']='NULL';
					if($_POST['purchased']=='')$_POST['purchased']='NULL';
					$expiry="";
					if($_POST['expiry']!=''){
						$_POST['expiry']=str_replace('.','-',$_POST['expiry']);
						if(strlen($_POST['expiry'])==5){
							$_POST['expiry']=$_POST['expiry'].'-'.date('Y');
						}
						$_POST['expiry']=date('Y-m-d',strtotime($_POST['expiry']));
						$expiry="expiry='$_POST[expiry]'";
					}
					if($_POST['log']>0){
						if($expiry<>'')$expiry=",".$expiry;
						mysqli_query($con,"UPDATE products_logs_temp SET qty=$_POST[qty],uic=$_POST[uic],free=$_POST[free],mrp='$_POST[mrp]',mrpc=$_POST[mrpc],selling_price=$_POST[selling_price],purchased=$_POST[purchased]$expiry WHERE id='$_POST[log]'")or die(mysqli_error($con));
					}
					else {
						mysqli_query($con,"INSERT INTO products_logs_temp(product,type,referrer,qty,uic,free,purchased,mrp,mrpc,selling_price,status,added,user) 
						VALUES('$_POST[id]',1,'$_POST[invoice_id]',$_POST[qty],$_POST[uic],$_POST[free],$_POST[purchased],'$_POST[mrp]',$_POST[mrpc],$_POST[selling_price],'1','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
						$_POST['log']=mysqli_insert_id($con);
						if($expiry<>'')mysqli_query($con,"UPDATE products_logs_temp SET $expiry WHERE id='$_POST[log]'")or die(mysqli_error($con));
					}
				}
				echo json_encode(array('invoice_id'=>$_POST['invoice_id'],'log'=>$_POST['log']));
				break;
			case 'delete_pur_product' :
				if(isset($_POST['id']) AND $_POST['id']>0)mysqli_query($con,"DELETE FROM products_logs_temp WHERE id='$_POST[id]'")or die(mysqli_error($con));
				break;
			case 'finish' :
				if($_POST['value']==0){
					mysqli_query($con,"UPDATE po SET status='0' WHERE id='$_POST[id]'")or die(mysqli_error($con));
					return;
				}
				$result=mysqli_query($con,"SELECT * FROM po WHERE id='$_POST[id]'");
				$po=mysqli_fetch_assoc($result);
				$result=mysqli_query($con,"SELECT * FROM products_logs_temp WHERE type='1' AND referrer='$_POST[id]'");
				while($row=mysqli_fetch_assoc($result)){
					// $result1=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[product]' AND branch='$po[branch]' ORDER BY id DESC LIMIT 0,1")or die(mysqli_error($con));
					$balance=0;
					// if(mysqli_affected_rows($con)==1){
						// $row1=mysqli_fetch_assoc($result1);
						// if(is_numeric($row1['balance']))$balance=$row1['balance'];
					// }
					// $qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
					// $balance+=$qty;
					if($row['qty']=='' OR $row['qty']==0)$row['qty']='NULL';
					if($row['uic']=='' OR $row['uic']==0)$row['uic']='NULL';
					if($row['free']=='' OR $row['free']==0)$row['free']='NULL';
					if($row['mrpc']=='' OR $row['mrpc']==0)$row['mrpc']='NULL';
					if($row['selling_price']=='' OR $row['selling_price']==0)$row['selling_price']='NULL';
					if($row['purchased']=='' OR $row['purchased']==0)$row['purchased']='NULL';
					mysqli_query($con,"INSERT INTO products_logs(branch,product,type,referrer,qty,uic,free,balance,purchased,mrp,mrpc,selling_price,status,added,user) 
						VALUES('$po[branch]','$row[product]','$row[type]','$row[referrer]',$row[qty],$row[uic],$row[free],'$balance',$row[purchased],'$row[mrp]',$row[mrpc],$row[selling_price],1,'$row[added]','$row[user]')")or die(mysqli_error($con));
					$products_logs=mysqli_insert_id($con);
					if($row['expiry']!='')mysqli_query($con,"UPDATE products_logs SET expiry='$row[expiry]' WHERE id='$products_logs'")or die(mysqli_error($con));
					$q="";
					if($row['purchased']>0 AND $row['qty']>0){
						if($row['free']=='NULL')$row['free']=0;
						$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
						$row['purchased']=($row['purchased']*$row['qty'])/$qty;
						$q=",purchased='$row[purchased]'";
					}
					mysqli_query($con,"UPDATE products SET mrp='$row[mrp]'$q WHERE id='$row[product]'")or die(mysqli_error($con));
					update_qty($row['product']);
				}
				mysqli_query($con,"DELETE FROM products_logs_temp WHERE type='1' AND referrer='$_POST[id]'")or die(mysqli_error($con));
				mysqli_query($con,"UPDATE po SET status='1' WHERE id='$_POST[id]'")or die(mysqli_error($con));
				break;
			case 'delete' :
				$result=mysqli_query($con,"SELECT * FROM products_logs WHERE type='1' AND referrer='$_POST[id]'");
				while($row=mysqli_fetch_assoc($result)){
					if($row['qty']=='' OR $row['qty']==0)$row['qty']='NULL';
					if($row['uic']=='' OR $row['uic']==0)$row['uic']='NULL';
					if($row['free']=='' OR $row['free']==0)$row['free']='NULL';
					if($row['mrpc']=='' OR $row['mrpc']==0)$row['mrpc']='NULL';
					if($row['selling_price']=='' OR $row['selling_price']==0)$row['selling_price']='NULL';
					if($row['purchased']=='' OR $row['purchased']==0)$row['purchased']='NULL';
					update_qty($row['product']);
					mysqli_query($con,"INSERT INTO products_logs_temp(product,type,referrer,qty,uic,free,balance,purchased,mrp,mrpc,selling_price,checked,status,added,user) 
						VALUES('$row[product]','$row[type]','$row[referrer]',$row[qty],$row[uic],$row[free],NULL,$row[purchased],'$row[mrp]',$row[mrpc],$row[selling_price],1,1,'$row[added]','$row[user]')")or die(mysqli_error($con));
				}
				mysqli_query($con,"DELETE FROM products_logs WHERE type='1' AND referrer='$_POST[id]'")or die(mysqli_error($con));
				mysqli_query($con,"UPDATE po SET status='-1' WHERE id='$_POST[id]'")or die(mysqli_error($con));
				break;
			// case 'payment' ://print_r($_POST);
				// $date=date('Y-m-d',strtotime($_POST['date']));
				// mysqli_query($con,"INSERT INTO payments(date,category,type,amount,status,added,user) VALUES('$date',1,'$_POST[method]','$_POST[amount]',1,'$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
				// $payment_id=mysqli_insert_id($con);
				// mysqli_query($con,"INSERT INTO po_payments(amount,payment,po) VALUES('$_POST[amount]','$payment_id','$_POST[po]')");
				// if($_POST['method']==2){
					// $transfer=date('Y-m-d',strtotime($_POST['transfer']));
					// mysqli_query($con,"INSERT INTO payments_cheques(date,payment,number,bank,transfer,amount,added,user) 
					// VALUES('$date',$payment_id,'$_POST[no]','$_POST[bank]','$transfer','$_POST[amount]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
					// echo print_cheque(mysqli_insert_id($con));
				// }
				// break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'merge_to':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Parent PO ID</label>
											<div><input type="text" class="x-large form-control" name="parent"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="id" value="'.$_GET['id'].'"/>
												<input type="hidden" name="popup_form_submit" value="merge_to"/>
												<input type="hidden" id="callback" value="'.$_GET['callback'].'"/>
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
			case 'move_branch':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Branch</label>
											<div>
												<select class="form-control select" name="branch">';
													$branches=mysqli_query($con,"SELECT * FROM branches WHERE repack='0'");
													while($row=mysqli_fetch_assoc($branches)){
														echo '<option value="'.$row['id'].'" '.($_GET['branch']==$row['id']?'selected':'').'>'.ucwords($row['title']).'</option>';
													}
												echo '</select>
											</div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="id" value="'.$_GET['id'].'"/>
												<input type="hidden" name="popup_form_submit" value="move_branch"/>
												<input type="hidden" id="callback" value="'.$_GET['callback'].'"/>
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
			case 'product':
				echo view_a_product($_GET['id'],str_replace('.php','',str_replace($dir,'',__FILE__)),str_replace($dir,'',dirname(__FILE__)));
				break;
			case 'select_payment':
				echo '<div class="card col-lg-9 mx-auto">
					<div class="card-body"><div class="row">
						<div class="col-12"><div class="card">
						<div class="card-body">
							<table class="table table-sm" table="po_payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th class="right">Amount</th><th class="right">Payment 2</th><th class="right">Payment</th><th class="right">Voucher</th><th></th></tr></thead><tbody>';
							$counter=1;
							$result=mysqli_query($con,"SELECT * FROM po_payments WHERE po='$_GET[id]'") or die(mysqli_error($con));
							while($row=mysqli_fetch_array($result)){
								echo '<tr id="'.$row['id'].'" class="pointer">
									<td class="center">'.$counter++.'</td>
									<td class="right">'.number_format($row['amount'],2).'</td>
									<td class="right editable" field="payment2">'.$row['payment2'].'</td>
									<td class="right editable" field="payment">'.$row['payment'].'</td>
									<td class="right editable" field="voucher">'.$row['voucher'].'</td>
									<td class="center"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block">Select</button></td>
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
			case 'merge_to':
				$result=mysqli_query($con,"SELECT * FROM po WHERE id='$_POST[id]'");
				$row=mysqli_fetch_array($result);
				$result=mysqli_query($con,"SELECT * FROM po WHERE id='$_POST[parent]'");
				$parent=mysqli_fetch_array($result);
				if($parent['img']=='' AND $row['img']<>'')mysqli_query($con,"UPDATE po SET img='$row[img]' WHERE id='$_POST[parent]'");
				mysqli_query($con,"UPDATE products_logs SET referrer='$_POST[parent]' WHERE referrer='$_POST[id]' AND type='1'");
				mysqli_query($con,"UPDATE products_logs_temp SET referrer='$_POST[parent]' WHERE referrer='$_POST[id]' AND type='1'");
				mysqli_query($con,"UPDATE po_payments SET po='$_POST[parent]' WHERE po='$_POST[id]'");
				mysqli_query($con,"UPDATE po SET status='-1' WHERE id='$_POST[id]'");
				break;
			case 'move_branch':
				mysqli_query($con,"UPDATE po SET branch='$_POST[branch]' WHERE id='$_POST[id]'");
				mysqli_query($con,"UPDATE products_logs SET branch='$_POST[branch]' WHERE referrer='$_POST[id]' AND type='1'");
				break;
		}
		return;
	}
	else if(isset($_POST['select'])){//if($_SESSION['user_id'])print_r($_POST);
		switch($_POST['select']){
			case 'product':
				$prices = array();
				$result = mysqli_query($con, "SELECT *,(SELECT discount FROM products WHERE id=product) discount 
				FROM products_logs WHERE product='$_POST[id]' and type='1' AND mrp>0 AND purchased>0 AND qty>0  ORDER BY id DESC LIMIT 0,1") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					$row = $result->fetch_assoc();
					echo json_encode($row);
				}
				break;
			case 'product_disabled' ://print_R($_POST);
				$result=mysqli_query($con,"SELECT * FROM products WHERE ui_code='$_POST[code]' AND (status=0 OR company<2)");
				if(mysqli_affected_rows($con)>0){
					$row=mysqli_fetch_array($result);
					mysqli_query($con,"UPDATE products SET status='1' WHERE id='$row[id]'");
					echo json_encode($row);
				}
				break;
		}
		return;
	}
	else if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false OR $_POST['field']=='expiry' OR $_POST['field']=='transfer'){
			if($value=='')$value='NULL';
			else $value=date('Y-m-d',strtotime($value));
		}
		if($value=='NULL')mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]=NULL WHERE id='$_POST[id]'");
		else mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		if($_POST['edit']=='products_logs' AND in_array($_POST['field'],array('qty','free','uic'))){
			$result=mysqli_query($con,"SELECT product FROM $_POST[edit] WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			update_qty($row['product']);
		}
		if($_POST['edit']=='products_logs')mysqli_query($con,"UPDATE products_logs SET checked='0' WHERE id='$_POST[id]'");
		return;
	}
	$result=mysqli_query($con,"SELECT p.*,(SELECT COUNT(*) FROM products_logs WHERE product=p.id) so FROM products p WHERE p.status='1' ORDER BY so DESC");
	$products=array();
	while($row = $result->fetch_assoc()){
		$row['remind']='';
		array_push($products,$row);
	}
	echo '<script>
	var products='.json_encode($products).';
	</script>';
	unset($products);
	{
		if(isset($_GET['id']) AND $_GET['id']>0){
			$result=mysqli_query($con,"SELECT * FROM po WHERE id='$_GET[id]'");
			$po = $result->fetch_assoc();
		}
	$loader = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
	echo '<div class="row">
		<div class="col-sm-12">
			<div class="card">
			<div id="ajax_indicator" class="hide alert alert-success alert-dismissible fade show" role="alert"><i class="mdi mdi-check-all mr-2"></i><span class="text"></span>
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
				<div id="invoice" class="card-body">
					<form id="invoice-header" action="" validate autocomplete="off">
						<div class="container invoice_header" changed="0">
							<div class="row">
								<div class="col-md-2">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Date</label>
										<div class="col-lg-8">
											<input name="date" type="text" class="form-control date" value="'.date('d-m-Y',(isset($po['date'])?strtotime($po['date']):time())).'" '.((isset($po['status']) AND $po['status']>0)?'disabled':'').'>
										</div>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group row">
										<label class="col-lg-2 col-form-label">#</label>
										<div class="col-lg-10">
											<input name="invoice" id="invoice_no" type="text" class="form-control" value="'.(isset($po['invoice'])?$po['invoice']:'').'" '.((isset($po['status']) AND $po['status']>0)?'disabled':'').'>
										</div>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group row">
										<label class="col-lg-5 col-form-label">Discount</label>
										<div class="col-lg-7">
											<input name="discount" id="discount" type="number" class="form-control" value="'.(isset($po['discount'])?$po['discount']:'').'" '.((isset($po['status']) AND $po['status']>0)?'disabled':'').'>
										</div>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Supplier</label>
										<div class="col-lg-8">';
											$result=mysqli_query($con,"SELECT * FROM suppliers");
											$htm='';
											$disabled='';
											while($supplier=mysqli_fetch_array($result)){
												if(isset($po['supplier']) AND $po['supplier']==$supplier['id'])$disabled='disabled';
												$htm.='<option value="'.$supplier['id'].'" '.((isset($po['supplier']) AND $po['supplier']==$supplier['id'])?'selected':'').'>'.($supplier['title']<>''?$supplier['title']:$supplier['company']).'</option>';
											}
											echo '<select class="form-control select" name="supplier" id="supplier" '.$disabled.'>
												<option></option>'.$htm.'</select>
										</div>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Company</label>
										<div class="col-lg-8">';
											$result=mysqli_query($con,"SELECT * FROM products_company");
											$htm='';
											$disabled='';
											while($company=mysqli_fetch_array($result)){
												if(isset($po['company']) AND $po['company']==$company['id'])$disabled='disabled';
												$htm.='<option value="'.$company['id'].'" '.((isset($po['company']) AND $po['company']==$company['id'])?'selected':'').'>'.$company['title'].'</option>';
											}
											echo '<select class="form-control select" name="company" id="company" '.$disabled.'>
												<option></option>'.$htm.'</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<fieldset class="container">
							<div class="row">
								<div class="col-12">
									<table class="table table-striped table-bordered" data-page-length="500" id="datatable-buttons" table="products_logs_temp" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
										<thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Free</th><th>UIC</th><th class="right">Cost</th><th class="right">MRP</th>
										<th class="right">MRPC</th><th class="right">Selling Price</th><th class="right">Unit Price</th><th class="right">%</th><th class="right">Total</th><th></th><th></th></tr></thead>
										<tbody id="invoice-item-container" style="font-size: 12px;">';
											$counter=0;
											$total=0;
											$warning='';
											if(isset($po['id'])){
												$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code FROM products_logs l LEFT JOIN products p on l.product=p.id WHERE l.referrer='$po[id]' AND l.type='1'");
												$rows=array();
												while($row=$result->fetch_assoc()){
													$row['table']='products_logs';
													$row['editable']=($po['status']==0?'editable':'editable');
													array_push($rows,$row);
												}
												$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code FROM products_logs_temp l LEFT JOIN products p on l.product=p.id WHERE l.referrer='$po[id]' AND l.type='1'");
												while($row=$result->fetch_assoc()){
													$row['table']='products_logs_temp';
													$row['editable']='editable';
													array_push($rows,$row);
												}
												foreach($rows as $row){
													$q="";
													$htm="";
													$counter++;
													if($row['table']=='products_logs')$q=" AND id<>'$row[id]'";
													$result = mysqli_query($con, "SELECT * FROM products_logs WHERE product='$row[product]' AND type='1' AND qty>0 $q ORDER BY id DESC LIMIT 0,1") or die(mysqli_error($con));
													if(mysqli_affected_rows($con)==1){
														$old=$result->fetch_assoc();
														$unit_price_old=($old['purchased']*$old['qty'])/(($old['uic']>0?($old['qty']*$old['uic']):$old['qty'])+$old['free']);
														$margin_old=number_format((($old['mrp']-$unit_price_old)/$old['mrp'])*100,2);
														$htm.='<tr class="text-muted">
															<td>'.$counter.'</td><td></td>
															<td>'.($old['qty']+0).'</td><td>'.$old['free'].'</td><td>'.($old['uic']+0).'</td>
															<td class="right">'.($old['purchased']+0).'</td>
															<td class="right">'.$old['mrp'].'</td><td class="right">'.$old['mrpc'].'</td>
															<td class="right">'.$old['selling_price'].'</td><td class="right">'.number_format($unit_price_old,2).'</td>
															<td class="right">'.$margin_old.'%</td>
															<td class="right">'.number_format($old['purchased']*$old['qty'],2).'</td><td></td><td></td>
														</tr>';
													}
													$unit_price=($row['purchased']*$row['qty'])/(($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free']);
													$margin=number_format((($row['mrp']-$unit_price)/$row['mrp'])*100,2);
													$warning=($unit_price>$row['mrp']?'warning':'');
													if($row['qty']>0 AND isset($margin_old)){
														// ($margin<$margin_old?$warning='warning':'');
														($margin>$margin_old?$warning='plat_1':'');
														if($margin>0){
															((($margin-$margin_old)/$margin)*100>=5?$warning='warning':'');
															((($margin_old-$margin)/$margin)*100>1?$warning='warning':'');
														}
													}
													if($po['status']==2)$row['editable']='';
													echo '<tr id="product_tr_'.$counter.'" class="product_tr '.$warning.'" changed="0" product_id="'.$row['product'].'" mrp="'.$row['mrp'].'" 
													purchased="'.$row['purchased'].'" selling_price="'.$row['selling_price'].'" qty="'.$row['qty'].'" free="'.$row['free'].'" 
													uic="'.$row['uic'].'" mrpc="'.$row['mrpc'].'" temp="'.$row['id'].'">
														<td class="product_count selectable">'.$counter.'</td>
														<td class="popup" href="'.$remote_file.'/new_purchase&form=product&id='.$row['product'].'&type=1">'.$row['ui_code'].'<br/>'.$row['title'].'</td>
														<td class="'.$row['editable'].'" field="qty" id="'.$row['id'].'" table="'.$row['table'].'">'.($row['qty']+0).'</td>
														<td class="'.$row['editable'].'" field="free" id="'.$row['id'].'" table="'.$row['table'].'">'.$row['free'].'</td>
														<td class="'.$row['editable'].'" field="uic" id="'.$row['id'].'" table="'.$row['table'].'">'.($row['uic']+0).'</td>
														<td class="'.$row['editable'].' right" field="purchased" id="'.$row['id'].'" table="'.$row['table'].'">'.($row['purchased']+0).'</td>
														<td class="'.$row['editable'].' right" field="mrp" id="'.$row['id'].'" table="'.$row['table'].'">'.$row['mrp'].'</td>
														<td class="'.$row['editable'].' right" field="mrpc" id="'.$row['id'].'" table="'.$row['table'].'">'.$row['mrpc'].'</td>
														<td class="'.$row['editable'].' right" field="selling_price" id="'.$row['id'].'" table="'.$row['table'].'">'.$row['selling_price'].'</td>
														<td class="unit_price right">'.number_format($unit_price,2).'</td>
														<td class="margin right">'.$margin.'%</td>
														<td class="product_total right">'.number_format($row['purchased']*$row['qty'],2).'</td>
														<td class="'.$row['editable'].' right" field="expiry" id="'.$row['id'].'" table="'.$row['table'].'">'.$row['expiry'].'</td>
														'.($row['table']=='products_logs_temp'?'<td class="product_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>':'<td></td>').'
													</tr>';
													echo $htm;
													$total+=$row['purchased']*$row['qty'];
												}
											}
										$net_total=(isset($po['discount'])?($total-$po['discount']):$total);
										echo '</tbody>
										'.((!isset($po['status']) OR $po['status']==0)?'<tbody><tr>
												<th scope="row"></th>
												<td class="title" style="position: relative;">
													<input type="text" id="form-autocomplete" class="search_input x-large form-control product"/>'.$loader.'
												</td><td colspan="11"></td>
											</tr>
										</tbody>':'').'
										<tfoot>
										<tr><td></td><td>Total</td><td colspan="9"></td><td class="product-total right sub_total">'.number_format($total,2).'</td><td></td></tr>
										<tr><td></td><td>Discount/Cost</td><td colspan="9"></td><td class="product-total right discount">'.(isset($po['discount'])?($po['discount']<0?number_format($po['discount'],2):number_format($po['discount'],2)):'').'</td><td></td></tr>
										<tr><td></td><td>Net Total</td><td colspan="9"></td><td class="product-total right net_total">'.number_format($net_total,2).'</td><td></td></tr>
										</tfoot>
                                    </table>
								</div>
							</div>';//echo '<pre>';print_r($_SESSION);$_SESSION['soft']['user_data']['user_level']
							
						echo'</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>';
	echo '<div class="row card-body">';
	// if(($_SESSION['soft']['user_data']['user_level']==0 OR $warning!='warning') AND isset($po['status'])){
	// if($_SESSION['user_id']==1){
	if(isset($po['status']) AND $po['status']<2){
		if($po['status']==0)echo'<div class="col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="finish" value="1">Finish</button></div>';
		else echo'<div class="col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="finish" value="0">Edit</button></div>';
	}
	if(($_SESSION['soft']['user_data']['user_level']==0 OR in_array('delete',$permission)) AND isset($po['status']) AND $po['status']<2){
		echo'<div class="col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="delete">Delete</button></div>';
		if(isset($_GET['id']) AND $_GET['id']>0)echo'<div class="col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block popup" href="'.$remote_file.'/new_purchase&form=merge_to&id='.$_GET['id'].'&callback=back">Merge To</button></div>';
	}
	if($_SESSION['branches']>1 AND isset($po['id']) AND ($_SESSION['soft']['user_data']['user_level']==0 OR in_array('edit',$permission) OR $po['user']==$_SESSION['user_id'])){
		echo'<div class="col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block popup" href="'.$remote_file.'/new_purchase&form=move_branch&id='.$_GET['id'].'&branch='.$po['branch'].'&callback=back">Move Branch</button></div>';
	}
	echo '</div>';
	if(isset($po['id'])){
		$result = mysqli_query($con,"SELECT pp.*,p.date,p.id pid,p.type,pc.number,pc.transfer,pc.status,b.short,ba.account FROM po_payments pp LEFT JOIN payments p ON p.id=pp.payment 
		LEFT JOIN payments_cheques pc ON pc.payment=p.id LEFT JOIN bank_accounts ba ON ba.id=pc.bank LEFT JOIN banks b ON b.id=ba.bank WHERE pp.po='$po[id]'") or die(mysqli_error($con));
		$total=0;
		if(mysqli_affected_rows($con)>0){
			echo '<div class="row">
				<div class="col-12">
					<div class="card">
						<div id="invoice" class="card-body">
							<form id="invoice-header" action="" validate autocomplete="off">
								<fieldset class="container">
									<div class="row">
										<div class="col-12">
											<table class="table mb-0 form-group" table="po_payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
												<thead><tr><th>#</th><th>Date</th><th>Method</th><th>Amount</th></tr></thead>
												<tbody id="invoice-item-container">';
													$counter=0;
													$rows=array();
													$payment_methods['']='Unknown';
													while($row=$result->fetch_assoc()){
														echo '<tr id="'.$row['pid'].'">
															<td>'.++$counter.'</td>
															<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
															<td class="popup pointer" href="'.$remote_file.'/new_purchase&form=select_payment&id='.$po['id'].'">'.$payment_methods[$row['type']].'</td>
															<td class="right">'.$row['amount'].'</td>
														</tr>';
														if($row['type']==2){
															echo '<tr><td></td>
																<td colspan="3">'.$row['number'].' | '.date('d-m-Y',strtotime($row['transfer'])).' ('.$row['short'].', '.$row['account'].') - '.$cheque_status[$row['status']].'</td>
															</tr>';
														}
														$total+=$row['amount'];
														$net_total-=$row['amount'];
													}
												echo '</tbody>
												<tfoot><tr><td></td><td colspan="2">Total</td><td class="right">'.number_format($total,2).'</td></tr></tfoot>
											</table>
										</div>
									</div>
								</fieldset>
							</form>
						</div>
					</div>
				</div>
			</div>';
		}
		echo '<div class="row collapse" id="payment">
			<div class="col-sm-12">
				<div class="card">
				<div id="ajax_indicator" class="hide alert alert-success alert-dismissible fade show" role="alert"><i class="mdi mdi-check-all mr-2"></i><span class="text"></span>
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
					<div id="invoice" class="card-body">
						<form id="payment-header" action="" validate autocomplete="off">
							<div class="container">
								<div class="row">
									<div class="col-md-2">
										<div class="form-group row">
											<label class="col-lg-4 col-form-label">Date</label>
											<div class="col-lg-8">
												<input name="date" type="text" class="form-control date" value="'.date('d-m-Y').'">
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group row">
											<div class="col-lg-12">
												<select class="form-control select" name="method" id="method">
													<option value="1">Cash</option><option value="2">Cheque</option>
												</select>
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group row">
											<label class="col-lg-5 col-form-label">Amount</label>
											<div class="col-lg-7">
												<input name="data['.$po['id'].']" type="number" class="form-control" value="'.$net_total.'">
											</div>
										</div>
									</div>
									<div class="col-md-2 method_2 hide">
										<div class="form-group row">
											<div class="col-lg-12">
												<select class="form-control select" name="bank">';
													$result1=mysqli_query($con,"SELECT ba.id,b.short bank,ba.account FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank 
													WHERE ba.status='1' AND ba.bank>0");
													while($row1=$result1->fetch_assoc())echo '<option value="'.$row1['id'].'">'.$row1['bank'].' ('.$row1['account'].')</option>';
												echo '</select>
											</div>
										</div>
									</div>
									<div class="col-md-2 method_2 hide">
										<div class="form-group row">
											<label class="col-lg-2 col-form-label">#</label>
											<div class="col-lg-10">
												<input name="no" type="text" class="form-control">
											</div>
										</div>
									</div>
									<div class="col-md-2 method_2 hide">
										<div class="form-group row">
											<label class="col-lg-6 col-form-label">Cq Date</label>
											<div class="col-lg-6">
												<input name="transfer" type="text" class="form-control date">
											</div>
										</div>
									</div>
								</div>
							</div>
							<input type="hidden" name="paid_total" id="paid_total" value="'.$total.'"/>
							<div class="row card-body col-3"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="pay">Pay</button></div>
						</form>
					</div>
				</div>
			</div>
		</div>';
	}
	}
	if (strpos(dirname(__FILE__),'/') !== false)$folders=explode('/',dirname(__FILE__));
	else $folders=explode('\\',dirname(__FILE__));
	$plugin=end($folders);
	echo '<script>
	plugin="'.$plugin.'";
	count="'.$counter.'";
	invoice_id="'.((isset($_GET['id']) AND $_GET['id']>0)?$_GET['id']:0).'";
    remote_file="'.$config_url.'remote.php?file='.$plugin.'/";
	</script>';
?>
<script>
	function pad (str, max) {
	  str = str.toString();
	  return str.length < max ? pad("0" + str, max) : str;
	}
	function after_ajax(){
		
	}
	
	$('#company,#supplier').bind('change',function(){
		if($("#supplier").val()>0 && $("#company").val()!=''){
			$("#form-autocomplete").focus();
			company=$("#company").val();
			$("#company").prop( "disabled",true);
			$("#supplier").prop( "disabled",true);
		}
		if($(this).attr('id')=='supplier')$('#company').select2('open');
	});
	$('#invoice_no').keydown(function(e){
		if(e.which == 13){
			if($("#supplier").prop( "disabled")==false)$('#supplier').select2('open');
			else $("#form-autocomplete").focus();
		}
		else if(e.which == 39)$('#discount').focus();
	});
	$('#discount').keydown(function(e){
		if(e.which == 13 || e.which == 39){
			if($("#supplier").prop( "disabled")==false)$('#supplier').select2('open');
			else $("#form-autocomplete").focus();
		}
	});
	$('.invoice_header input').bind('change',function(){
		if(invoice_id>0 && $(this).attr('name')!=''){
			if($(this).attr('name')=='discount'){
				$('.discount').text( $(this).val() );
				invoice_total();
			}
			$.post(remote_file+"new_purchase",{edit:'po',id:invoice_id,field:$(this).attr('name'),value:$(this).val()},function(data){
				
			});
		}
	});
	if(invoice_id>0){
		$(".product_tr").each(function(i){//if(i==0)alert($(this).find('.product_delete').html());
			$(this).find('.product_delete').click(delete_product);
			tr=$(this);
			$(this).change((e)=>{
				update(tr);
			});
		});
	}
	company=$("#company").val();
	function delete_product(e){
		ele=$(e.currentTarget).parents('tr');//alert(ele.html());
		$.post(remote_file+'new_purchase', {id:ele.attr('temp'), form_submit: "delete_pur_product"}, function(data){
			ajax_indicate('Item deleted');
		});
		$(ele).remove();
		count_products();
	}
	$('#finish').click(function(){
		value=$(this).val();
		$(this).remove();
		$.post(remote_file+"new_purchase",{id:invoice_id,form_submit: "finish",value:value},function(data){
			if(value==1)window.history.back();
			else location.reload();
		});
	});
	$('#delete').click(function(){
		$(this).remove();
		$.post(remote_file+"new_purchase",{id:invoice_id,form_submit: "delete"},function(data){
			window.history.back();
		});
	});
	$('#pay').click(function(){
		var data = $('#payment-header').serializeArray();
		data.push({name: "form_submit", value: 'payment'});
		$(this).hide();
		$.post(remote_file+"payment",data,function(data){
			if(data!=''){
				$('#printableArea').html(data);
				window.print();
			}
			else window.history.back();
			$('#payment').remove();
		});
	});
	$('#method').change(function(){
		form=$(this).parents('form');
		form.find('.hide').hide();
		form.find('.method_'+$(this).val()).attr('style','display:flex;');
	});
	function invoice_total(){
		var sum=0;
		$('#pay_button').show();
		$('.product_total').each(function(id){
			if($(this).text()!=='')sum+=Number($(this).text().replace(",",""));
			else $('#pay_button').hide();
		});
		$('.sub_total').text( sum.toFixed(2) );
		sum=sum-Number($('.discount').text().replace(",",""));
		$('.net_total').text( sum.toFixed(2) );
		if((sum-$('#paid_total').val())>10){
			if($('#payment').hasClass('show')==false)$('#payment').addClass('show');
		}
	}
	if(typeof products!='undefined' && $( ".product" ).length >0){
		function select_product(product){
			$('.product').val('');
            $('.lds-ellipsis').css('display', 'inline-block');
			$.post(remote_file+"new_purchase",{select:'product',id:product.id},function(data){
				var mrp='';
				var selling_price='';
				var purchased='';
				if(data!=''){
					pi=jQuery.parseJSON(data);
					mrp=pi.mrp;
					selling_price=pi.selling_price;
					purchased=pi.purchased;
				}
				$('.lds-ellipsis').css('display', 'none');
				$('#invoice-item-container').append($('<tr></tr>').attr({id : "product_tr_"+count, class: "product_tr", changed: 0, product_id: product.id, mrp : mrp, purchased : purchased,selling_price:selling_price}).html(
					'<td class="product_count"></td><td>'+product.ui_code+'<br/>'+product.title+'</td>'+
					'<td class="editable" field="qty"><input type="number" class="form-control next_input" index="0"/></td>'+
					'<td class="editable" field="free"><input type="number" class="form-control" index="0"/></td>'+
					'<td class="editable" field="uic"><input type="number" class="form-control next_input" index="1"/></td>'+
					'<td class="editable" field="purchased"><input type="number" class="form-control next_input" index="2" value="'+purchased+'"/></td>'+
					'<td class="editable" field="mrp"><input type="number" class="form-control next_input" index="3" value="'+mrp+'"/></td>'+
					'<td class="editable" field="mrpc"><input type="number" class="form-control next_input" index="4"/></td>'+
					'<td class="editable" field="selling_price"><input type="number" class="form-control next_input '+(product.expiry>0? '' :'finish')+'" index="5" value="'+selling_price+'"/></td>'+
					'<td class="unit_price right">'+mrp+'</td><td></td><td class="product_total right">'+purchased+'</td>'+
					'<td class="editable" field="expiry">'+(product.expiry>0? '<input type="text" class="form-control next_input finish" index="6"/>' :'')+'</td>'+
					'<td class="product_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>') );
				count_products();
				$(".next_input").first().focus();
				$('#product_tr_'+count).find('.product_delete').click(delete_product);
				$('#product_tr_'+count).find('.editable').bind('click');
				$('#product_tr_'+count+' input').keydown(function(e){
					if(e.which == 13){
						if($(this).hasClass('finish')){
							tr_finish($(this).parents('tr'));
						}
						else {
							input=$(this).attr('index');
							$(this).parents('tr').find('.next_input').each(function(e){
								if(input<e){
									$(this).focus().select();
									return false;
								}
							});
						}
						// $(this).parents('tr').find('.next_input').not(this).first().focus();
					}
					else if(e.which == 37){
						$(this).parent().prev().find('input').focus().select();
						return false;
					}
					else if(e.which == 39){
						$(this).parent().next().find('input').focus().select();
						return false;
					}
					else if(e.which == 27){
						$(this).parents('tr').find('.product_delete').trigger('click');
					}
					else if(e.which == 107 || e.which == 18){
						tr_finish($(this).parents('tr'));
					}
				});
				// $('#product_tr_'+count+' .finish').keydown(function(e){
					// if(e.which == 13)tr_finish($(this).parents('tr'));
				// });
				count++;
			});
		}
		function tr_finish(tr){
			if((tr.find('td[field="qty"] input').val()!='' || tr.find('td[field="free"] input').val()>0) && tr.find('td[field="mrp"] input').val()>0){
				$('.editable').each(function(e){
					tr.attr($(this).attr('field'),$(this).find('input').val());
					$(this).html($(this).find('input').val());
				});
				update(tr);
			}
		}
		function update(tr){
			tr.attr('changed', '1');
			purchased=Number(tr.attr('purchased'));
			qty=Number(tr.attr('qty'));
			uic=Number(tr.attr('uic'));
			free=Number(tr.attr('free'));
			unit_price=(( purchased * qty )/(( uic > 0 ? ( qty * uic ) : qty ) + free ));
			tr.find(".product_total").text( (purchased * qty ).toFixed( 2 ) );
			tr.find(".unit_price").text( unit_price.toFixed( 2 ) );
			invoice_total();
			var data = $('#invoice-header').serializeArray();
			data.push({name: "form_submit", value: 'add_purchase'});
			data.push({name: "invoice_id", value: invoice_id});
			data.push({name: "company", value: $('#company').val()});
			data.push({name: "supplier", value: $('#supplier').val()});
			data.push({name: "log", value: tr.attr('temp')});
			data.push({name: "id", value: tr.attr('product_id')});
			data.push({name: "mrp", value: tr.attr('mrp')});
			data.push({name: "mrpc", value: tr.attr('mrpc')});
			data.push({name: "purchased", value: purchased});
			data.push({name: "selling_price", value: tr.attr('selling_price')});
			data.push({name: "expiry", value: tr.attr('expiry')});
			data.push({name: "qty", value: qty});
			data.push({name: "free", value: free});
			data.push({name: "uic", value: tr.attr('uic')});
			$.post(remote_file+"new_purchase",data,function(data){
				if(tr.attr('temp')>0)ajax_indicate('Qty And MRP Updated!');
				else ajax_indicate('Product Added!');
				p=jQuery.parseJSON(data);
				if(invoice_id==0){
					invoice_id=p.invoice_id;
					location.hash=location.hash + '\\' + invoice_id;
				}
				tr.attr('temp',p.log);
			});
			$(".product").focus();
        }
		function count_products(){
            $('.product_count').each(function(id){
                $(this).text(id+1);
            });
			invoice_total();
        }
		
		
		$( ".product" ).autocomplete({
			minLength: 1,
			autoFocus: true,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( products, function( value ) {
					return value.company==company && (matcher.test(value.title.replace(/\ /g, "")) ||  matcher.test(value.ui_code) ||  matcher.test(value.bulk_code) ||  matcher.test(value.remind) || matcher.test(value.mrp));
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
				else if (ui.content.length == 1 && $(this).val()==ui.content[0].bulk_code){
					select_product(ui.content[0],ui.content[0].uic);
					$(this).autocomplete( "close" );
				}
				else if (ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].ui_code || ui.content[0].remind.indexOf($(this).val()) != -1)){
					ui.item=ui.content[0];
					$(this).val(ui.content[0].ui_code);
					select_product(ui.item);
					$(this).autocomplete( "close" );
				}
				else if(ui.content.length ==0){//alert(1206);
					$.post(remote_file+'new_purchase', {select:'product_disabled',code:$(this).val()},function(data){//alert(data);
						item=jQuery.parseJSON( data );//alert(item.id);
						select_product(item);
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
			return $("<li>").append("<a>"+item.title+tam+"&nbsp;[Rs."+item.mrp+"]<br>"+item.ui_code+"</a>").appendTo(ul);
		};
	}
	invoice_total();
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
</script>
<span class="hide popup recent_invoices" o-href="<?php echo $remote_file.'/new_purchase&form=recent_invoices';?>"></span>
<span class="hide popup f12" o-href="<?php echo $remote_file.'/new_purchase&form=product';?>"></span>
<style>
.ui-menu-item{cursor: pointer;}
#ajax_indicator{position: fixed;min-width: 50%;left: 10px;bottom: 10px;z-index: 100;}
#invoice-item-container .editable{width: 9%;}
input.form-control { padding: .2rem .2rem;font-size: 12px;}
.text-muted td{border-top: 0px;line-height: 1.0;}
.product_tr td,.text-muted td{padding: 0px 12px !important;}
.text-muted {color: #9ca8b396 !important;}
</style>
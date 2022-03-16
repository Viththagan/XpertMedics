<?php
	if(isset($_POST['form_submit'])){//print_r($_POST);
		switch($_POST['form_submit']){//print_r($_POST);
			case 'payment' :
				$date=date('Y-m-d',strtotime($_POST['date']));
				mysqli_query($con,"INSERT INTO payments(branch,date,category,type,status,added,user) VALUES('$_SESSION[branch]','$date',1,'$_POST[method]',1,'$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
				$payment_id=mysqli_insert_id($con);
				$total=0;
				$po=0;
				foreach($_POST['data'] as $id=>$value){
					if($value>0 AND $id>0){
						$total+=$value;
						mysqli_query($con,"INSERT INTO po_payments(amount,payment,po) VALUES('$value','$payment_id','$id')");
						$po=$id;
					}
				}
				mysqli_query($con,"UPDATE payments SET amount='$total' WHERE id='$payment_id'");
				$result=mysqli_query($con,"SELECT s.cheque_name FROM po LEFT JOIN suppliers s ON s.id=po.supplier WHERE po.id='$po'") or die (mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				if($_POST['method']==2){
					$transfer=date('Y-m-d',strtotime($_POST['transfer']));
					mysqli_query($con,"INSERT INTO payments_cheques(date,payment,number,bank,transfer,amount,payee,added,user) 
					VALUES('$date',$payment_id,'$_POST[no]','$_POST[bank]','$transfer','$total','$row[cheque_name]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
					echo print_cheque(mysqli_insert_id($con));
				}
				
				break;
			case 'print_cheque' ://print_r($_POST);
				echo print_cheque($_POST['id']);
				break;
			case 'cash_payment' ://print_r($_POST);
				foreach($_POST['data'] as $id=>$value){
					// $result = mysqli_query($con, "SELECT *,
					// ((SELECT SUM(qty*purchased) FROM products_logs WHERE referrer=p.id AND type=1)-IFNULL(discount,0)-(IFNULL((SELECT SUM(amount) FROM po_payments WHERE po=p.id),0))) payable 
					// FROM po p WHERE p.id='$id'")or die(mysqli_error($con));
					// $row=$result->fetch_assoc();
					mysqli_query($con,"INSERT INTO po_payments(amount,payment,po) VALUES('$value','$_POST[payment]','$id')")or die(mysqli_error($con));
				}
				mysqli_query($con,"UPDATE payments SET status='1' WHERE id='$_POST[payment]'");
				break;
			case 'create_po' :
				$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
				$_POST['discount']='NULL';
				mysqli_query($con,"INSERT INTO po(date,company,supplier,invoice,discount,branch,device,added,user) 
					VALUES('$_POST[date]','$_POST[company]','$_POST[supplier]','$_POST[invoice]',$_POST[discount],'$_SESSION[branch]','$_COOKIE[device_id]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				echo mysqli_insert_id($con);
				break;
			case 'add_cheque' :
				$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
				$_POST['transfer']=date('Y-m-d',strtotime($_POST['transfer']));
				$_POST['number']=trim($_POST['number']);
				$_POST['payee']=trim($_POST['payee']);
				$_POST['name']=trim($_POST['name']);
				$_POST['amount']=trim($_POST['amount']);
				mysqli_query($con,"INSERT INTO cheque_buffer(ac,no,date,transfer,payee,amount,added,user) 
					VALUES('$_POST[bank]','$_POST[number]','$_POST[date]','$_POST[transfer]','$_POST[payee]','$_POST[amount]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				$date=date('dmY',strtotime($_POST['transfer']));
			if($_POST['bank']==74){
				$date=date('dm',strtotime($_POST['transfer'])).'&nbsp;&nbsp;'.date('y',strtotime($_POST['transfer']));
			}
				echo '<style>
			.cheque .cross {content: "";width: 100px;height: 50px;position: absolute;transform: rotateZ(45deg);transform-origin: bottom right;width: 10px;border-left: solid;border-right: solid;top: -5px;Left: -5px;}
			.cheque .date {padding-left: 118mm;padding-top: 5.5mm;position: fixed;letter-spacing: 13px;font-size: 23px;}
			.cheque .payee {padding-left: 15mm;padding-top: 20mm;position: fixed;}
			.cheque .amount_text {width:90mm;line-height: 8mm;padding-left: 10mm;padding-top: 29mm;position: fixed;}
			.cheque .amount {padding-left: 130mm;padding-top: 35mm;position: fixed;font-size: 23px;}
			.cheque .ac_pay {padding-left: 50mm;padding-bottom: 55mm;position: fixed;}
			.cheque .ac_border {position: fixed;border-top: solid;border-bottom: solid;border-width: 1px;padding: 5px;}
			.cheque .ac_text {font-size: 12px;margin: 10px;}
			</style>
			<div class="cheque">
				<span class="date">'.$date.'</span>
				<span class="payee">'.($_POST['name']<>''?$_POST['name']:'Cash').'</span>
				<span class="amount_text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ucwords(convertNumber($_POST['amount'])).' Only</span>
				<span class="amount">'.Number_format($_POST['amount'],2).'</span>
				'.($_POST['name']<>''?'<span class="ac_pay"><span class="ac_border"><span class="ac_text">A/C PAYEE ONLY</span></span></span>':'').'
			</div>';
				break;
		}
		return;
	}
	else if(isset($_GET['form'])){//print_r($_POST);
		switch($_GET['form']){
			case 'add_cheque':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Date</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control date" name="date" required value="'.date('d-m-Y').'"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Bank</div>
								<div class="form-group col-lg-10"><select class="form-control select" name="bank">';
									$result=mysqli_query($con,"SELECT ba.id,b.short bank,ba.account FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank 
									WHERE ba.status='1' AND ba.bank>0");
									while($row=$result->fetch_assoc())echo '<option value="'.$row['id'].'">'.$row['bank'].' ('.$row['account'].')</option>';
								echo '</select></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Cheque Name</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control" name="name"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Payee</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control" name="payee"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Cheque#</div>
								<div class="form-group col-lg-10"><in<td class="center">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>put type="number" class="form-control" name="number"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Transfer</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control date" name="transfer" required value="'.date('d-m-Y').'"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Amount</div>
								<div class="form-group col-lg-10"><input type="number" class="form-control" name="amount" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_cheque"/>
							<input type="hidden" id="callback" value="print"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add & Print</button></div></div>
						</form>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_POST['select'])){//print_r($_POST);
		$result = mysqli_query($con, "SELECT p.*,c.title company,
		((SELECT SUM(qty*purchased) FROM products_logs WHERE referrer=p.id AND type=1)-IFNULL(discount,0)-(IFNULL((SELECT SUM(amount) FROM po_payments WHERE po=p.id),0))) payable 
		FROM po p LEFT JOIN products_company c ON c.id=p.company WHERE p.branch='$_SESSION[branch]' AND p.supplier='$_POST[id]' AND p.date>'2021-01-01' HAVING payable>10")or die(mysqli_error($con));
		$po=array();
		while($row=$result->fetch_assoc()){
			$row['date']=date('d-m-Y',strtotime($row['date']));
			$row['payable']=number_format($row['payable'],2);
			array_push($po,$row);
		}
		echo json_encode($po);
		return;
	}
	else if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='payments'){
			delete_payment($_POST['id']);
		}
		return;
	}
	if(isset($_GET['from']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-3">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('01-m-Y');
										if(!isset($_GET['to']))$_GET['to']=date('d-m-Y');
										echo '<input type="text" class="form-control form_submit_change date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.$_GET['to'].'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Supplier(s)</label>
								<div><select class="form-control select" name="supplier[]" multiple="multiple" multiple>';
									$supplier=mysqli_query($con,"SELECT * FROM suppliers");
									while($row=mysqli_fetch_assoc($supplier)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['supplier']) AND in_array($row['id'],$_GET['supplier']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>Company(s)</label>
								<div><select class="form-control select" name="company[]" multiple="multiple" multiple>';
									$company=mysqli_query($con,"SELECT * FROM products_company");
									while($row=mysqli_fetch_assoc($company)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['company']) AND in_array($row['id'],$_GET['company']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=100;
									foreach($values as $value)echo '<option value="'.$value.'" '.($_GET['limit']==$value?'selected':'').'>'.$value.'</option>';
									echo '<option value="0" '.($_GET['limit']==0?'selected':'').'>All</option>
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
	if(!isset($_GET['supplier']) OR sizeof($_GET['supplier'])==0){
		$result=mysqli_query($con,"SELECT p.*,s.id sid,s.title,ss.till FROM payments p LEFT JOIN suppliers s ON s.id=p.referrer LEFT JOIN sales_session ss ON ss.id=p.session 
		WHERE p.status='0' AND p.category='1' AND p.type='1' AND p.branch='$_SESSION[branch]'") or die(mysqli_error($con));
		echo '<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#cash_payments" aria-expanded="false" aria-controls="collapseExample">Cash Payments At till</a></p>';
		if(mysqli_affected_rows($con)>0){
			echo '<div class="row collapse" id="cash_payments" style="font-size: 13px;">
				<div class="col-12">
					<div class="card">
						<div class="card-body">
							<table class="customized_table table table-sm" table="payments" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
								<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Date').'</th><th>'.language('Till').'</th><th>'.language('Method').'</th>
								<th>'.language('Supplier').'</th><th>'.language('Amount').'</th><th></th><th></th>
								</tr></thead><tbody>';
								$counter=1;
								$amount=0;
								while($row=mysqli_fetch_assoc($result)){
									echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td>
										<td class="center">'.$row['id'].'</td>
										<td>'.date('d-m-Y',strtotime($row['date'])).', '.date('h:i A',strtotime($row['added'])).'</td>
										<td class="center">'.$row['till'].'</td>
										<td class="popup_select" value="'.$row['category'].'" data-toggle="modal" data-target=".category">'.$payment_methods[$row['type']].($row['description']!=''?' ('.$row['description'].')':'').'</td>
										<td class="popup_select" value="'.$row['sid'].'" field="referrer" data-toggle="modal" data-target=".suppliers">'.$row['title'].' ('.$row['sid'].')</td>
										<td class="right editable paid_amount" field="amount">'.number_format($row['amount'],2).'</td>
										<td class="center"><a class="btn btn-primary mb-2 mb-sm-0 supplier" id="'.$row['sid'].'" data-toggle="modal" data-target=".pending_po">Invoices</a></td>
										<td class="right">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
									</tr>';
									$amount+=$row['amount'];
								}
								echo '</tbody><tfoot><tr><td colspan="2"></td><td colspan="4">Total</td>
								<td class="right">'.number_format($amount,2).'</td><td></td><td></td>
								</tr></tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>';
		}
	}
	
	
	$where="WHERE s.payment>0 AND p.category=1 AND p.branch='$_SESSION[branch]'";// AND p.referrer is NULL
	if($_GET['from']<>'' OR $_GET['to']<>''){
		if($_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where.=" AND p.date='$to'";
		}
		else if($_GET['to']==''){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND p.date='$from'";
		}
		else if(strtotime($_GET['from'])<strtotime($_GET['to'])){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND p.date BETWEEN '$from' AND '$to'";
			$view_date=1;
		}
		else {
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND p.date BETWEEN '$to' AND '$from'";
			$view_date=1;
		}
	}
	if(isset($_GET['supplier']) AND sizeof($_GET['supplier'])>0){
		$suppliers=implode(',',$_GET['supplier']);
		$where.=" AND s.sid IN($suppliers)";
	}
	if(isset($_GET['company']) AND sizeof($_GET['company'])>0){
		$suppliers=implode(',',$_GET['company']);
		$where.=" AND s.cid IN($suppliers)";
	}
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	$result=mysqli_query($con,"SELECT p.*,c.id cid,c.transfer,c.number,c.transferred,b.short,ba.id bid,ba.account,s.sid,s.supplier,s.company
	FROM payments p LEFT JOIN payments_cheques c ON c.payment=p.id LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank 
	LEFT JOIN (SELECT pp.payment,s.id sid,s.title supplier,c.id cid,c.title company FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier LEFT JOIN products_company c ON c.id=po.company GROUP BY pp.payment) s ON s.payment=p.id 
	$where ORDER BY p.date ASC $limit") or die(mysqli_error($con));
	$counter=1;
	$amount=0;
	$htm='';
	$supplier=0;
	$class="";
	if(in_array('edit',$permission))$class="editable";
	while($row=mysqli_fetch_assoc($result)){
		$htm.='<tr id="'.$row['id'].'" style="cursor:pointer;">
			<td class="center">'.$counter++.'</td>
			<td class="center">'.$row['id'].'</td>
			<td>'.date('d-m-Y',strtotime($row['date'])).'</td>
			<td>'.$payment_methods[$row['type']].'</td>
			<td>'.$row['description'].'</td>
			<td class="center '.($row['type']==2?$class:'').'" field="number" table="payments_cheques" id="'.$row['cid'].'">'.$row['number'].'</td>
			<td class="center '.($row['type']==2?$class:'').'" field="transfer" table="payments_cheques" id="'.$row['cid'].'">'.$row['transfer'].'</td>
			<td class="center '.($row['type']==2?$class:'').'" field="transferred" table="payments_cheques" id="'.$row['cid'].'">'.$row['transferred'].'</td>
			<td>'.($row['type']==2?$row['short']:'').'</td>
			<td class="popup_select" value="'.$row['bid'].'" id="'.$row['cid'].'" table="payments_cheques" data-toggle="modal" data-target=".bank">'.($row['type']==2?$row['account']:'').'</td>
			<td>'.$row['supplier'].'<br/>'.$row['company'].'</td>
			<td class="right" field="total" id="'.$row['cid'].'">'.number_format($row['amount'],2).'</td>
			<td class="center">'.($row['type']==2?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 print_cheque" id="'.$row['cid'].'">Re-Print</button>':'').'</td>
			<td class="center">'.($row['print']==0?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 print_voucher">Print</button>':'').'</td>
			<td class="right">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
		</tr>';
		$amount+=$row['amount'];
		if($supplier==0)$supplier=$row['sid'];
		else if($supplier!=$row['sid'])$supplier=-1;
	}
	if($supplier==0 AND isset($_GET['supplier']) AND sizeof($_GET['supplier'])==1){
		$supplier=implode(',',$_GET['supplier']);
	}
	if($supplier>0){
		echo '<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#payment" aria-expanded="false" aria-controls="collapseExample">Payment</a></p>';
		echo '<div class="row collapse" id="payment">
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
									<div class="col-md-3 method_2 hide">
										<div class="form-group row">
											<label class="col-lg-4 col-form-label">Bank</label>
											<div class="col-lg-8">
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
									<div class="col-md-3 method_2 hide">
										<div class="form-group row">
											<label class="col-lg-6 col-form-label">Effect Date</label>
											<div class="col-lg-6">
												<input name="transfer" type="text" class="form-control date">
											</div>
										</div>
									</div>
								</div>
							</div>
							<fieldset class="container">
								<div class="row">
									<div class="col-12">
										<table class="table mb-0 form-group" table="products_logs_temp" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
											<thead><tr><th>#</th><th>ID</th><th style="width: 10%;">Date</th><th style="width: 15%;">Invoice</th><th>Company</th><th class="right">Payable</th><th class="right" style="width: 10%;">Total</th><th></th></tr></thead>
											<tbody id="invoice-item-container" style="font-size: 12px;">';
		$result = mysqli_query($con, "SELECT p.*,c.title company,
		((SELECT SUM(qty*purchased) FROM products_logs WHERE referrer=p.id AND type=1)-IFNULL(discount,0)-(IFNULL((SELECT SUM(amount) FROM po_payments WHERE po=p.id),0))) payable 
		FROM po p LEFT JOIN products_company c ON c.id=p.company WHERE p.branch='$_SESSION[branch]' AND p.supplier='$supplier' AND p.date>'2021-01-01' HAVING payable>10")or die(mysqli_error($con));
												$counter=0;
												$total=0;
												while($row=$result->fetch_assoc()){
													echo '<tr class="po_payment" id="'.$row['id'].'">
														<td class="count">'.++$counter.'</td>
														<td>'.$row['id'].'</td>
														<td>'.$row['date'].'</td>
														<td>'.$row['invoice'].'</td>
														<td>'.$row['company'].'</td>
														<td class="right">'.number_format($row['payable'],2).'</td>
														<td class="po_total right">'.number_format($row['payable'],2).'</td>
														<td class="po_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>
													</tr>';
													$total+=$row['payable'];
												}
											echo '</tbody>
											<tbody id="invoice-new"><tr>
												<td></td>
												<td></td>
												<td><input type="text" class="form-control" id="date" value="'.date('d-m-Y').'"/></td>
												<td><input type="text" class="form-control" id="invoice_no"/></td>
												<td><select class="form-control select" id="po_company">';
													$company=mysqli_query($con,"SELECT * FROM products_company");
													while($row=mysqli_fetch_assoc($company)){
														echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
													}
												echo '</select></td>
												<td></td>
												<td><input type="number" class="form-control create_po"/></td>
											</tr>
										</tbody>
											<tfoot>
											<tr><td></td><td>Total</td><td colspan="4"></td><td class="right total">'.number_format($total,2).'</td><td></td></tr>
											</tfoot>
										</table>
									</div>
								</div>
								<div class="row card-body"><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="pay">Pay</button></div>
							</fieldset>
							<input type="hidden" name="supplier" id="supplier" value="'.$supplier.'"/>
						</form>
					</div>
				</div>
			</div>
		</div>';
	}
	{
	echo '<div class="row" style="font-size: 13px;">
		<div class="col-12">
			<div class="card">
				<div class="card-body table-responsive">
					<table class="customized_table table table-sm" table="payments" style="width:100%;" data-button="true" data-sum="9" data-hide-columns="Description,Transferred,Account" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Date').'</th><th>'.language('Method').'</th>
						<th>'.language('Description').'</th><th>'.language('Cheque#').'</th><th>'.language('Transfer').'</th><th>'.language('Transferred').'</th>
						<th>'.language('Bank').'</th><th>'.language('Account').'</th><th>'.language('Supplier').'</th><th>'.language('Amount').'</th>
						<th>'.language('Cheque').'</th><th>'.language('Voucher').'</th><th></th>
						</tr></thead><tbody>'.$htm.'</tbody>
						<tfoot>
							<tr><td colspan="4"></td><td colspan="7">Page Total</td><td class="right"></td><td colspan="3"></td></tr>
							<tr><td colspan="4"></td><td colspan="7">Total</td><td class="right">'.number_format($amount,2).'</td><td colspan="3"></td></tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>';
	echo '<div class="modal fade bank" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Account</label>
						<div><select class="form-control select" id="bank" required>';
							$result1=mysqli_query($con,"SELECT ba.id,b.short bank,ba.account FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank 
							WHERE ba.status='1' AND ba.bank>0");
							while($row1=$result1->fetch_assoc())echo '<option value="'.$row1['id'].'">'.$row1['bank'].' ('.$row1['account'].')</option>';
							echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade suppliers" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Suppliers</label>
						<div><select class="form-control select" id="suppliers" required>
							<option>Select</option>';
							$supplier=mysqli_query($con,"SELECT * FROM suppliers");
							while($row=mysqli_fetch_assoc($supplier)){
								echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
							}
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade category" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Move to</label>
						<div><select class="form-control select" id="category" required>
							<option>Select</option>';
							foreach($payments_type as $id=>$title){
								if($id==1 OR $id==2)continue;
								echo '<option value="'.$id.'">'.$title.'</option>';
							}
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	}
	if (strpos(dirname(__FILE__),'/') !== false)$folders=explode('/',dirname(__FILE__));
	else $folders=explode('\\',dirname(__FILE__));
	$plugin=end($folders);
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	echo '<script>
	plugin="'.$plugin.'";
    remote_file="'.$config_url.'remote.php?file='.$plugin.'/";
	</script>';
	
?>
<script>
	$(".po_payment").each(function(i){//if(i==0)alert($(this).find('.product_delete').html());
		$(this).find('.po_delete').click(delete_po);
		bind_price_click($(this).find('.po_total'));
	});
	function delete_po(e){
		ele=$(e.currentTarget).parents('tr');//alert(ele.html());
		$(ele).remove();
		ajax_indicate('Item deleted');
		count_po();
	}
	function count_po(){
		$('.count').each(function(id){
			$(this).text(id+1);
		});
		po_total();
	}
	function po_total(){
		var sum=0;
		$('.po_total').each(function(id){
			if($(this).text()!=='')sum+=Number($(this).text().replace(",",""));
		});
		$('.total').text( sum.toFixed(2) );
	}
	$('#pay').click(function(){
		$(this).hide();
		var data = $('#invoice-header').serializeArray();
		data.push({name: "form_submit", value: 'payment'});
		$('.po_payment').each(function(i){
			data.push({name: "data["+$(this).attr('id')+"]", value: $(this).find('.po_total').text().replace(",","")});
		});
		$.post(remote_file+"payment",data,function(data){
			if(data!=''){
				$('#printableArea').html(data);
				window.print();
				$('.po_payment').remove();
				$('#invoice-header').trigger("reset");
				po_total();
			}
		});
	});
	$('.print_cheque').click(function(){
		$.post(remote_file+"payment",{form_submit:'print_cheque',id:$(this).attr('id')},function(data){
			if(data!=''){
				$('#printableArea').html(data);
				window.print();
			}
		});
	});
	$('#method').change(function(){
		form=$(this).parents('form');
		form.find('.hide').hide();
		form.find('.method_'+$(this).val()).attr('style','display:flex;');
	});
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
	$('.create_po').keyup(function(e){
		if(e.which == 13){
			var data = new Array();
			data.push({name: "date", value: $('#date').val()});
			data.push({name: "invoice", value: $('#invoice_no').val()});
			data.push({name: "company", value: $('#po_company').val()});
			data.push({name: "supplier", value: $('#supplier').val()});
			data.push({name: "amount", value: $(this).val()});
			data.push({name: "form_submit", value: 'create_po'});
			$.post(remote_file+"payment",data,function(data){
				$('#invoice-item-container').append($('<tr></tr>').attr({id : data, class: "po_payment"}).html(
					'<td class="count"></td>'+
					'<td>'+ data +'</td>'+
					'<td>' + $('#date').val() + '</td>'+
					'<td>' + $('#invoice_no').val() + '</td>'+
					'<td>' + $('#po_company').children("option:selected").text() + '</td>'+
					'<td class="right">' + $('.create_po').val() + '</td>'+
					'<td class="po_total right">' + $('.create_po').val() + '</td>'+
					'<td class="po_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>') );
				count_po();
				$('#'+data).find('.po_delete').click(delete_po);
				bind_price_click($('#'+data).find('.po_total'));
				$('.create_po').val('');
				$('#invoice_no').val('');
			});
		}
	});
	function bind_price_click(ele){
		ele.click(function(){
			ele.html('<input type="number" value="'+ele.text().replace(',','')+'" class="edit_value form-control" style="width: 100px;"/>');
			$(".edit_value").focus().select();
			$(this).unbind("click");
			$('.edit_value').on('focusout keypress', function(e) {
				if(e.which == 13 || e.which == 0){
					bind_price_click($(this).parents('.po_total'));
					tr=$(this).parents('tr');
					$(this).parent().html($(this).val());
					po_total();
				}
			});
		});
	}
	$('.supplier').click(function(){
		payment=$(this).parents('tr').attr('id');
		$.post(remote_file+'payment', {select:'pending_po',id:$(this).attr('id')},function(data){
			item=jQuery.parseJSON( data );
			var htm='<table class="table mb-0 form-group"><tbody>';
			$.each( item, function( key, value ) {
				htm=htm+'<tr class="pointer" id="' + value.id + '"><td class="count po">'+(key+1)+'</td><td class="po">' + value.date + '</td><td class="po">' + value.invoice + '</td>'+
				'<td class="po">' + value.company + '</td><td class="right po">' + value.payable + '</td><td class="right payable po_total">' + value.payable + '</td>'+
				'<td class="right"><a class="btn btn-primary waves-effect waves-light" target="_blank" href="'+config_url+'#purchases/new%20purchase\\' + value.id + '" role="button">PO</a></td></tr>';
			});
			if(item.length==0)htm=htm+'No Pending Invoices!';
			htm=htm+'</tbody></table><br/><button type="button" class="btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block add_po_payment hide" payment="' + payment + '">Add Payment</button>';
			$('#pending_po').html(htm);
			$('#pending_po').find('.po_total').each(function (){
				bind_price_click($(this));
			});
			$('.po').click(function(){
				$(this).parents('tr').toggleClass('selected');
				calculate_total(payment);
			});
			$('.add_po_payment').click(select_po);
		});
	});
	function calculate_total(payment){
		sum=0;
		$('.add_po_payment').hide();
		$('.selected').each(function (){
			sum+=Number($(this).find('.payable').text().replaceAll(',',''));
		});
		total=Number($('#'+payment).find('.paid_amount').text().replaceAll(',',''));
		if(sum==total)$('.add_po_payment').show();
	}
	function select_po(e){
		var data = new Array();
		data.push({name: "form_submit", value: 'cash_payment'});
		payment=$(this).attr('payment');
		data.push({name: "payment", value: $(this).attr('payment')});
		$('.selected').each(function (){
			data.push({name: "data["+$(this).attr('id')+"]", value: $(this).find('.payable').text().replaceAll(',','')});
		});
		$.post(remote_file+'payment',data,function(data){});
		$("#"+$(e.currentTarget).attr('payment')).remove();
		$(".pending_po .close").trigger('click');
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$(".plus").trigger('click');
		return false;
	});
	
</script>
<div class="modal fade pending_po" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	 <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="pending_po"></div>
			</div>
		</div>
	</div>
</div>
<span class="hide popup plus" href="<?php echo $remote_file.'/payment&form=add_cheque';?>"></span>
<style>
input.form-control { padding: .2rem .2rem;font-size: 12px;}
</style>
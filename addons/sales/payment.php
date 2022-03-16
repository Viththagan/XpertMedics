<?php
	if(isset($_POST['form_submit'])){//print_r($_POST);
		switch($_POST['form_submit']){//print_r($_POST);
			case 'payment' :
				
				break;
		}
		return;
	}
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
	if(!isset($_GET['search']))$_GET['search']='';
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control" placeholder="Search" value="'.$_GET['search'].'"/>
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
	$customer=0;
	if(strlen($_GET['search'])>3){
		$result=mysqli_query($con,"SELECT * FROM customers WHERE fname LIKE '%$_GET[search]%' OR lname LIKE '%$_GET[search]%' OR nic LIKE '%$_GET[search]%' OR mobile LIKE '%$_GET[search]%'");
		if(mysqli_affected_rows($con)==1){
			$customer=1;
			$row=mysqli_fetch_assoc($result);
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
													<input name="date" type="text" class="form-control date" value="'.date('d-m-Y').'">
												</div>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group row">
												<div class="col-lg-12">
													<select class="form-control select" name="method" id="method">
														<option value="1">Cash</option><option value="2">Card</option>
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
				$result=mysqli_query($con,"SELECT s.*,c.mobile,c.fname,c.lname,SUM(l.qty*l.selling_price) si_total,(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid 
				FROM sales s LEFT JOIN products_logs l ON (s.id=l.referrer AND l.type=0) LEFT JOIN customers c ON c.id=s.customer 
				WHERE s.closed=1 AND s.date>'2021-01-01' AND s.customer='$row[id]' GROUP BY s.id ORDER BY s.added ASC")or die(mysqli_error($con));
													$total=0;
													while($row=$result->fetch_assoc()){
														echo '<tr class="sales_payments" id="'.$row['id'].'">
															<td class="count">'.++$counter.'</td>
															<td>'.$row['id'].'</td>
															<td>'.$row['date'].'</td>
															<td>'.$row['invoice'].'</td>
															<td>'.$row['customer'].'</td>
															<td class="right">'.number_format($row['si_total']-$row['paid'],2).'</td>
															<td class="po_total right">'.number_format($row['si_total']-$row['paid'],2).'</td>
															<td class="po_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>
														</tr>';
														$total+=$row['si_total']-$row['paid'];
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
								<input type="hidden" name="customer" id="customer" value="'.$customer.'"/>
							</form>
						</div>
					</div>
				</div>
			</div>';
		}
	} 
	if($customer==0){
		echo '<div class="col-xs-1 col-lg-12"><div class="card">
			<div class="card-body">
				<table class="customized_table table-sm" table="sales" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
				<thead><tr><th>#</th><th>ID</th><th>'.language('Date').'</th><th>'.language('Time').'</th><th>'.language('Customer').'</th>
				<th>'.language('Total').'</th><th>'.language('Paid').'</th><th>'.language('Receivable').'</th>
				</tr></thead><tbody>';
				$result=mysqli_query($con,"SELECT s.*,c.mobile,c.fname,c.lname,SUM(l.qty*l.selling_price) si_total,(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid 
				FROM sales s LEFT JOIN products_logs l ON (s.id=l.referrer AND l.type=0) LEFT JOIN customers c ON c.id=s.customer 
				WHERE s.closed=1 AND s.date>'2021-01-01' GROUP BY s.id ORDER BY s.added ASC")or die(mysqli_error($con));
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
						</tr>';
					}
					else mysqli_query($con,"UPDATE sales SET closed=2 WHERE id='$row[id]'");
				}
			echo '</tbody></table>
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
	function select_customer(e){
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
	$('#pay').click(function(){
		$(this).hide();
		var data = $('#invoice-header').serializeArray();
		data.push({name: "form_submit", value: 'payment'});
		$('.sales_payments').each(function(i){
			data.push({name: "data["+$(this).attr('id')+"]", value: $(this).find('.payable').text().replace(",","")});
		});
		$.post(remote_file+"payment",data,function(data){
			if(data!=''){
				$('#printableArea').html(data);
				window.print();
				$('.sales_payments').remove();
				$('#invoice-header').trigger("reset");
				payable();
			}
		});
	});






</script>
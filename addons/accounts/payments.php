<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false OR strpos($_POST['field'],'transfer')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['print'])){
		$result=mysqli_query($con,"SELECT p.*,c.transfer,c.number,b.short,ba.account,c.id cid,s.sid,s.supplier,s.company
	FROM payments p LEFT JOIN payments_cheques c ON c.payment=p.id LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank 
	LEFT JOIN (SELECT pp.payment,s.id sid,s.title supplier,c.id cid,c.title company FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier LEFT JOIN products_company c ON c.id=po.company GROUP BY pp.payment) s ON s.payment=p.id 
	WHERE p.id='$_POST[id]'") or die(mysqli_error($con));
		$row=mysqli_fetch_assoc($result);
		mysqli_query($con,"UPDATE payments SET print='1' WHERE id='$_POST[id]'");
		echo '<div class="col-xs-1 col-lg-6">
			<div class="card">
				<div class="card-body"><img src="https://dfc.lk/app/icons/icon-325x200.png" style="visibility: visible; width:15%;" alt="Daily Food City"/>
		<table id="meta3" class="meta po">
			<tbody>
				<tr><td class="meta-head">Address:</td><td class="right">Aadiyapatham Road, Kokuvil East, Kokuvil</td></tr>
				<tr><td class="meta-head">Phone:</td><td class="right">0774308010</td></tr>
			</tbody>
		</table>
		<table id="meta4" class="meta po" table="po" plugin="purchase"><tbody><tr><td>'.$row['id'].'</td></tbody></table>
		<div id="header">Purchase Payment Voucher</div><div style="clear:both"></div>
			<div class="supplier">
			<table id="meta1" class="meta po"><tbody>
			<tr><td class="meta-head">Payee :</td><td class="right">'.$row['company'].'</td></tr>
			<tr><td class="meta-head">Cheque No :</td><td class="right">'.(isset($row['number'])?$row['number']:'').'</td></tr>
			<tr><td class="meta-head">Bank :</td><td class="right">'.(isset($row['short'])?$row['short'].' ('.$row['account'].')':'').'</td></tr>
			</tbody></table>
			<table id="meta2" class="meta po" table="po" plugin="purchase"><tbody>
				<tr><td class="meta-head">Date</td><td>'.date("d-m-Y",strtotime($row['date'])).'</td></tr>
					<tr><td class="meta-head">Transfer Date</td><td>'.(isset($row['transfer'])?date("d-m-Y",strtotime($row['transfer'])):'').'</td></tr>
					<tr><td class="meta-head">Method</td><td>'.$payment_methods[$row['type']].'</td></tr>
					</tbody></table></div>
				<div style="clear:both"></div>
					<table id="items" table="po_items" plugin="purchase" class="po po_table">
						<thead><th class="center">#</th><th class="center">Date</th><th class="center">PO</th><th class="center">Invoice</th><th class="center">Amount</th></thead><tbody>';
						$result=mysqli_query($con,"SELECT po.date,po.invoice,po.id,pp.amount FROM po_payments pp LEFT JOIN po ON po.id=pp.po WHERE pp.payment='$_POST[id]'")or die(mysqli_error($con));
						$counter=1;
						while($data=mysqli_fetch_assoc($result)){
							echo'<tr><td class="center">'.$counter++.'</td>
							<td class="center">'.$data['date'].'</td>
							<td class="center">'.$data['id'].'</td>
							<td class="center">'.$data['invoice'].'</td>
							<td class="right" >'.$data['amount'].'</td>
							</tr>';
						}
						echo'<tr><td class="center"></td>
							<td class="center" colspan="3">Total </td>
							<td class="center" >'.$row['amount'].'</td>
							</tr><br><table id="meta3" class="meta po">
						<tbody><ul><span class="amount_text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ucwords(convertNumber($row['amount'])).' Only</span></ul>
				<ul><span class="by"> Prepared by :........................................&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Authorized by:..........................................</span></ul>
				</tbody></table>
			<table id="meta3" class="meta po"><tbody><ul><span class="by">Received by</span></ul>
				        <ul><span class="by">&nbsp;&nbsp;&nbsp;Name :........................................&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NIC  :........................................&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Signature :........................................</span></ul>
			</tbody></table></div></div></div>
			<style>
@page {size: 6.8in 8.3in;}
	#terms{width:100%;position: fixed;bottom: 0;page-break-after: always;}
body { font: 16px/1.4 Arial !important; color: #000;background: #fff;}
table.po { border-collapse: collapse; }
table.po td,table.po th { border: 1px solid black; padding: 3px; }
#header { clear: both;width: 100%; margin: 20px 0;text-align: center; color: black; font: bold 15px Helvetica, Sans-Serif;text-transform: uppercase; letter-spacing: 20px; padding: 8px 0px; }
#supplier { overflow: hidden; }
#meta1 { margin-top: 1px; width: 300px; float: left;}
#meta2 { margin-top: 1px; width: 300px; float:right}
#meta3 { margin-top: 1px; width: 200px; float:right;font-size:12px;border:0px;}
#meta4 { margin-top: 1px; width: 200px; float:right; font: bold 18px Helvetica, Sans-Serif;border:0px;clear: both;}
.meta td { text-align: right;  }
.right{ text-align:right}
.center{ text-align:center;}
.meta td.meta-head { text-align: left;}
#items { clear: both; width: 100%; margin: 30px 0 0 0; border: 1px solid black; }
#terms { text-align: center;}
.no_border td{border-top:0px;border-bottom:0px;}
h6 { font: 17px Helvetica, Sans-Serif;-webkit-margin-before: 0em;-webkit-margin-after: 0em;-webkit-margin-start: 0px;-webkit-margin-end: 0px;}
#terms h5{ text-transform: uppercase; font: 13px Helvetica, Sans-Serif; letter-spacing: 10px; border-bottom: 1px solid black; padding: 0 0 8px 0; margin: 0 0 8px 0; }
</style>';
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='payments'){
			delete_payment($_POST['id']);
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'add_payment':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
					<form class="popup_form_submit repeater" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
							<div class="row">
								<div class="form-group col-lg-3">Date</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'"/></div>
							</div>
							<div class="row"><div class="form-group col-lg-3">Payment Type</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="type" id="type">';
										unset($payments_type[1]);
										foreach($payments_type as $id=>$title){
											echo '<option value="'.$id.'">'.$title.'</option>';
										}
									echo '</select>
								</div>
							</div>
							<div class="row p_type p_type_2"><div class="form-group col-lg-3">Staff</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="staff">
										<option></option>';
										$result=mysqli_query($con,"SELECT * FROM users WHERE status='1'");
										while($row=mysqli_fetch_assoc($result)){
											echo '<option value="'.$row['id'].'">'.$row['first_name'].'</option>';
										}
								echo '</select>
								</div>
							</div>
							<div class="row"><div class="form-group col-lg-3">Method</div>
								<div class="form-group col-lg-9">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer active">
											<input type="radio" name="method" value="1" checked>Cash
										</label>
										<label class="btn btn-secondary pointer">
											<input type="radio" name="method" value="2">Cheque
										</label>
									</div>
								</div>
							</div>
							<!--div class="row method method_1"><div class="form-group col-lg-3">Source</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="source">';
										$result=mysqli_query($con,"SELECT ba.id,ba.account,b.short FROM bank_accounts ba 
										LEFT JOIN banks b ON b.id=ba.bank WHERE ba.status='1'");
										while($row=mysqli_fetch_assoc($result)){
											echo '<option value="'.$row['id'].'">'.$row['account'].'</option>';
										}
								echo '</select>
								</div>
							</div-->
							<div class="row method method_2 hide">
								<div class="form-group col-lg-3">Bank</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="bank">';
										$result1=mysqli_query($con,"SELECT ba.id,b.short bank,ba.account FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank 
										WHERE ba.status='1' AND ba.bank>0");
										while($row1=$result1->fetch_assoc())echo '<option value="'.$row1['id'].'">'.$row1['bank'].' ('.$row1['account'].')</option>';
									echo '</select>
								</div>
							</div>
							<div class="row method method_2 hide">
								<div class="form-group col-lg-3">Payee : </div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input" name="payee"/></div>
							</div>
							<div class="row method method_2 hide">
								<div class="form-group col-lg-3">Cheque #</div>
								<div class="form-group col-lg-9">
									<input name="no" type="text" data-mask="999999" class="form-control">
								</div>
							</div>
							<div class="row method method_2 hide">
								<div class="form-group col-lg-3">Transfer Date</div>
								<div class="form-group col-lg-9">
									<input name="transfer" type="text" class="form-control date">
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Amount : </div>
								<div class="form-group col-lg-9"><input type="number" class="form-control next_input" name="amount"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Description : </div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input" name="description"/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_payment"/>
							<input type="hidden" id="callback" value="print"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add</button></div></div>
						</form>
					</div>
				</div>';
				break;
		}
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'add_payment':
				$date=date('Y-m-d',strtotime($_POST['date']));
				if($_POST['type']==2){
					pay_salary($_POST['staff'],$_POST['amount'],array('description'=>$_POST['description'],'counter_id'=>0,'date'=>$date));
				}
				else {
					mysqli_query($con,"INSERT INTO payments(branch,date,amount,description,category,type,status,added,user) 
					VALUES('$_SESSION[branch]','$date','$_POST[amount]','$_POST[description]','$_POST[type]','$_POST[method]',1,'$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
					$payment_id=mysqli_insert_id($con);
					if($_POST['method']==2){
						$transfer=date('Y-m-d',strtotime($_POST['transfer']));
						mysqli_query($con,"INSERT INTO payments_cheques(date,payment,number,bank,transfer,amount,payee,added,user) 
						VALUES('$date',$payment_id,'$_POST[no]','$_POST[bank]','$transfer','$_POST[amount]','$_POST[payee]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
						echo print_cheque(mysqli_insert_id($con));
					}
				}
				break;
		}
		return;
	}
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div><input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/></div>
							</div>
							<div class="form-group col-lg-3">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('01-m-Y');
										if(!isset($_GET['to']))$_GET['to']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.$_GET['to'].'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-3"><label>Category</label>
								<div><select class="form-control select form_submit_change" name="category"><option></option>';
									foreach($payments_type as $value=>$title)echo '<option value="'.$value.'" '.((isset($_GET['category']) AND $_GET['category']==$value)?'selected':'').'>'.$title.'</option>';
									echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=1000;
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
	$where="WHERE (s.payment>0 OR p.category<>1) AND p.branch='$_SESSION[branch]'";
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
	if(isset($_GET['search']) AND strlen(trim($_GET['search']))>3){
		$_GET['search']=trim($_GET['search']);
		if($_GET['search']>0){
			$min=$_GET['search']-5;
			$max=$_GET['search']+5;
			$where.=" AND p.amount BETWEEN '$min' AND '$max' OR pp.po='$_GET[search]'";
		}
		else {
			$keys=explode(',',trim($_GET['search']));
			$where.=" AND (pp.po='%$_GET[search]%'";
			foreach($keys as $key){
				$key=trim($key);
				$where.=" OR pp.po='%$key%'";
			}
			$where.=")";
		}
	}
	if(isset($_GET['category']))$where.=" AND p.category='$_GET[category]'";
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	$q="SELECT p.*,c.id cid,c.transfer,c.number,c.transferred,b.short,ba.id bid,ba.account,IF(p.category=1,CONCAT(s.supplier,'<br/>',s.company),
	IF(p.category=2,CONCAT(u.first_name,' (',s.month,'-',s.year,')'),p.description)) as descrip
	FROM payments p LEFT JOIN payments_cheques c ON c.payment=p.id LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank 
	LEFT JOIN (SELECT pp.payment,s.title supplier,c.title company FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier LEFT JOIN products_company c ON c.id=po.company GROUP BY pp.payment) s ON s.payment=p.id 
	LEFT JOIN salary_staff ss ON (ss.id=p.referrer AND p.category=2) LEFT JOIN salary s ON s.id=ss.salary LEFT JOIN users u ON u.id=ss.staff 
	$where ORDER BY p.date ASC $limit";
	echo '<span id="po">
		<div class="row" style="font-size: 13px;">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
					<table class="customized_table table table-sm" table="payments" style="width:100%;" data-button="true" data-buts="copy,excel,pdf,colvis,add_payment" data-sum="9" data-hide-columns="Cheque#,Transfer,Transferred" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th>#</th><th>ID</th><th>'.language('Date').'</th><th>'.language('Category').'</th><th>'.language('Type').'</th>
						<th>'.language('Cheque#').'</th><th>'.language('Transfer').'</th><th>'.language('Transferred').'</th><th>'.language('Bank').'</th><th>'.language('Account').'</th>
						<th>'.language('Description').'</th><th>'.language('Amount').'</th><th></th><th></th><th></th>
						</tr></thead><tbody>';
									$result=mysqli_query($con,$q) or die(mysqli_error($con));
									$counter=1;
									$total=0;
									$class="";
									if(in_array('edit',$permission))$class="editable";
									while($row=mysqli_fetch_assoc($result)){
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="center">'.$row['id'].'</td>
											<td class="editable" field="date">'.date('d-m-Y',strtotime($row['date'])).'</td>
											<td>'.$payments_type[$row['category']].'</td>
											<td>'.$payment_methods[$row['type']].'</td>
											<td class="center '.($row['type']==2?$class:'').'" field="number" table="payments_cheques" id="'.$row['cid'].'">'.$row['number'].'</td>
											<td class="center '.($row['type']==2?$class:'').'" field="transfer" table="payments_cheques" id="'.$row['cid'].'">'.$row['transfer'].'</td>
											<td class="center '.($row['type']==2?$class:'').'" field="transferred" table="payments_cheques" id="'.$row['cid'].'">'.$row['transferred'].'</td>
											<td>'.($row['type']==2?$row['short']:'').'</td>
											<td class="'.($row['type']==2?'popup_select':'').'" value="'.$row['bid'].'" id="'.$row['cid'].'" table="payments_cheques" '.($row['type']==2?'data-toggle="modal" data-target=".bank"':'').'>'.($row['type']==2?$row['account']:'').'</td>
											<td>'.$row['descrip'].'</td>
											<td class="right editable" field="amount">'.number_format($row['amount'],2).'</td>
											<td><div class="zoom-gallery">'.list_images($row['img'],'uploads/payments/','').'</div>
											<div path="../dfc/uploads/payments/" style="float: right;" class="dropzone" query="UPDATE payments SET img=CONCAT(IFNULL(img,\'\'),\',[image]\') WHERE id=\''.$row['id'].'\'"></div>
											</td>
											<td class="center">'.($row['print']==0?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 print_voucher">Print</button>':'').'</td>
											<td class="right">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
										</tr>';
										$total+=$row['amount'];
									}
							echo '</tbody><tfoot>
							<tr><td colspan="3"></td><td colspan="7">Page Total</td><td class="right"></td><td colspan="3"></td></tr>
							<tr><td colspan="3"></td><td colspan="7">Total</td><td class="right">'.number_format($total,2).'</td><td colspan="3"></td></tr>
							</tfoot>
						</table>
					</div>
					</div>
				</div>
			</div>
		</div>
	</span>';
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
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<style>
.dropzone .dz-message{
	margin:0px !important;
	content:'' !important;
}
.dropzone {
    height: 50px;
    border: 2px solid rgba(0, 0, 0, 0.3);
	width: 20px !important;
    min-height: 50px !important;
	padding: 2px 2px;
}
</style>
<script>
	$('.print_voucher').click(function(){
		tr=$(this).parents('tr');
		button=$(this);
		$.post(remote_file+"/payments",{print:'print_voucher',id:tr.attr('id')},function(data){
			button.remove();
			$('#printableArea').html(data);
			window.print();
		});
	});
	function after_ajax(){
		$('#type').unbind('change').bind('change',function(){
			$('.p_type').hide();
			$('.p_type_'+$(this).val()).attr('style','display:flex;');
		});
		$('input[name="method"]').unbind('change').bind('change',function(){
			$('.method').hide();
			$('.method_'+$(this).val()).attr('style','display:flex;');
		});
	}
</script>
<?php
	if(in_array('add',$permission)){
		echo '<span class="hide popup add_payment" href="'.$remote_file.'/payments&form=add_payment"></span>';
?>		<script>
		$.fn.dataTable.ext.buttons.add_payment = {text: 'Add Payment',action: function ( e, dt, node, config ) {$('.add_payment').trigger('click');}};
		</script>
<?php
	}
?>

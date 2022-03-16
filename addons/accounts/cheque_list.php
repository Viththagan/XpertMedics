<?php
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'transferred':
				mysqli_query($con,"UPDATE payments_cheques SET status='3' WHERE id='$_POST[id]'") or die(mysqli_error($con));
				break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'sms':
				echo '<div class="card col-lg-10 mx-auto">
				<div class="card-body">
					<table class="table table-sm">
					<thead><tr><th>#</th><th>'.language('Sender').'</th><th>'.language('Date').'</th><th>'.language('Message').'</th></tr></thead><tbody>';
				$counter=1;
				$result=mysqli_query($con,"SELECT * FROM payments_cheques WHERE id='$_GET[id]'");
				$cheque=mysqli_fetch_assoc($result);
				$result=mysqli_query($con,"SELECT * FROM sms_sent WHERE message LIKE '%$cheque[amount]%' AND type='1'");
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td>'.$row['number'].'</td>
						<td class="center">'.$row['messaged'].'</td>
						<td>'.$row['message'].'</td>
					</tr>';
				}
				echo '</tbody></table></div></div>';
				break;
		}
		return;
	}
	
	if(isset($_GET['from']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
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
									<div class="input-daterange input-group">
										<input type="text" class="form-control form_submit_change date" name="from" value="'.(isset($_GET['from'])?$_GET['from']:'').'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Supplier</label>
								<div><select class="form-control select" name="suppliers[]" multiple>';
									$suppliers=mysqli_query($con,"SELECT * FROM suppliers");
									while($row=mysqli_fetch_assoc($suppliers)){
										echo '<option value="'.$row['title'].'" '.((isset($_GET['suppliers']) AND in_array($row['title'],$_GET['suppliers']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Company</label>
								<div><select class="form-control select" name="companies[]" multiple>';
									$companies=mysqli_query($con,"SELECT * FROM products_company");
									while($row=mysqli_fetch_assoc($companies)){
										echo '<option value="'.$row['title'].'" '.((isset($_GET['companies']) AND in_array($row['title'],$_GET['companies']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Bank</label>
								<div><select class="form-control select" name="bank[]" multiple>';
									$bank_accounts=mysqli_query($con,"SELECT ba.*,b.short FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank WHERE ba.status='1' AND ba.type='1'");
									while($row=mysqli_fetch_assoc($bank_accounts)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['bank']) AND in_array($row['id'],$_GET['bank']))?'selected':'').'>'.$row['short'].' ('.$row['account'].')</option>';
									}
								echo '</select>
								</div>
							</div>';
							if(!isset($_GET['status']))$_GET['status']=1;
							echo '<div class="form-group col-lg-2">
								<label>Status</label>
								<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
									<label class="btn btn-secondary pointer '.($_GET['status']==1?'active':'').'">
										<input type="radio" name="status" value="1" '.($_GET['status']==1?'checked':'').'>Panding
									</label>
									<label class="btn btn-secondary pointer '.($_GET['status']==0?'active':'').'">
										<input type="radio" name="status" value="0" '.($_GET['status']==0?'checked':'').'>All
									</label>
								</div>
							</div>';
							if(!isset($_GET['none']))$_GET['none']=0;
							echo '<div class="form-group col-lg-1">
								<label>Not Paid</label>
								<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
									<label class="btn btn-secondary pointer '.($_GET['none']==1?'active':'').'">
										<input type="radio" name="none" value="1" '.($_GET['none']==1?'checked':'').'>On
									</label>
									<label class="btn btn-secondary pointer '.($_GET['none']==0?'active':'').'">
										<input type="radio" name="none" value="0" '.($_GET['none']==0?'checked':'').'>OFF
									</label>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>&nbsp;</label>
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
	$where="WHERE p.branch='$_SESSION[branch]'";
	if($_GET['status']==1)$where.=" AND c.status=0";
	$view_date=1;
	if(isset($_GET['from']) AND ($_GET['from']<>'' OR $_GET['to']<>'')){
		if($_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where.=" AND c.transfer='$to'";
			$view_date=0;
		}
		else if($_GET['to']==''){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND c.transfer='$from'";
			$view_date=0;
		}
		else if(strtotime($_GET['from'])<strtotime($_GET['to'])){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND c.transfer BETWEEN '$from' AND '$to'";
		}
		else {
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$where.=" AND c.transfer BETWEEN '$to' AND '$from'";
		}
	}
	if(isset($_GET['bank']) AND sizeof($_GET['bank'])>0){
		$bank=implode(',',$_GET['bank']);
		$where.=" AND c.bank IN($bank)";
	}
	echo'<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<table class="customized_table table table-sm" table="bill_payments" style="width:100%;" data-button="true" data-sum="6" data-hide-columns="'.($view_date==0?'Date':'').'" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th class="center">#</th><th>'.language('Account').'</th><th class="center">'.language('CQ#').'</th>
							<th class="center">'.language('Date').'</th><th>'.language('Transfer').'</th><th>'.language('Description').'</th>
							<th class="right">'.language('Amount').'</th><th class="center"></th><th class="right">'.language('Sum').'</th><th></th>
							</tr></thead><tbody>';
							$result=mysqli_query($con,"SELECT c.*,ba.account,b.short,p.date,
							(SELECT s.title FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN suppliers s ON s.id=po.supplier WHERE pp.payment=c.payment GROUP BY s.id) supplier,
							(SELECT GROUP_CONCAT(DISTINCT(pc.title)) FROM po_payments pp LEFT JOIN po ON po.id=pp.po LEFT JOIN products_company pc ON pc.id=po.company WHERE pp.payment=c.payment) company
							FROM payments_cheques c LEFT JOIN bank_accounts ba ON ba.id=c.bank LEFT JOIN banks b ON b.id=ba.bank 
							LEFT JOIN payments p ON p.id=c.payment
							$where ORDER BY c.transfer ASC") or die(mysqli_error($con));
							$counter=1;
							$date='';
							$sum=0;
							$total=0;
							while($row=mysqli_fetch_array($result)){
								if(isset($_GET['suppliers']) AND sizeof($_GET['suppliers'])>0 AND !in_array($row['supplier'],$_GET['suppliers']))continue;
								if(isset($_GET['companies']) AND sizeof($_GET['companies'])>0 AND !in_array($row['company'],$_GET['companies']))continue;
								if($counter>1){
									if($row['transfer']<>$date){
										echo '<td>'.date("d-m-Y <\b\\r>l",strtotime($date)).'</td>
										<td class="right '.($date==date('Y-m-d')?'sum':'').'">'.number_format($sum,2).'</td>';
										$sum=0;
									}
									else {
										echo '<td></td><td></td>';
									}
									echo '<td class="center">'.(($row['status']==0 AND $date<=date('Y-m-d'))?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 transferred">Transferred</button>':'').'</td>';
									echo '</tr>';
								}
								$date=$row['transfer'];
								similar_text($row['supplier'],$row['company'], $perc);
								$sms=0;
								if($row['transfer']<=$today){
									mysqli_query($con,"SELECT id FROM sms_sent WHERE message LIKE '%$row[amount]%' AND message LIKE '%debit%' AND type='1' AND promo=1");
									$sms=mysqli_affected_rows($con);
								}
								$supplier=$row['supplier'];
								if(strlen($row['supplier'])>30)$supplier=substr($row['supplier'],0,30).'...';
								$company=$row['company'];
								if(strlen($row['company'])>30)$company=substr($row['company'],0,30).'...';
								echo'<tr id="'.$row['id'].'" class="'.($sms>0?'success':'').'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['short'].'</td>
									<td class="center">'.$row['number'].'</td>
									<td class="center">'.date('d-m-Y',strtotime($row['date'])).'</td>
									<td class="center">'.date('d-m-Y',strtotime($row['transfer'])).'</td>
									<td class="popup" href="'.$remote_file.'&form=sms&id='.$row['id'].'">'.$supplier.($perc<70?'<br/>'.$company:'').'</td>
									<td class="right">'.number_format($row['amount'],2).'</td>';
								$sum+=$row['amount'];
								$total+=$row['amount'];
							}
							echo '<td>'.date('d-m-Y<\b\\r>l',strtotime($date)).'</td><td class="right">'.number_format($sum,2).'</td>
							<td class="center">'.((isset($row['status']) AND $row['status']==0 AND $date<=date('Y-m-d') AND in_array('edit',$permission))?'<button type="button" class="btn btn-primary waves-effect waves-light mr-1 transferred">Transferred</button>':'').'</td>
							</tr>
						</tbody><tfoot>
								<tr><td colspan="5"></td><td>Page Total</td><td class="right"></td><td colspan="3"></td></tr>
								<tr><td colspan="5"></td><td>Total</td><td class="right">'.number_format($total,2).'</td><td colspan="3"></td></tr>
							<tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>';
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
	$(function(){
		$('.sum').parents('tr').addClass('today');
	});
	$('.transferred').click(function(){
		tr=$(this).parents('tr');
		button=$(this);
		$.post(remote_file,{form_submit:'transferred',id:tr.attr('id')},function(data){
			tr.remove();
		});
	});
</script>
<style>
.today{background: aqua;}
<style>
<?php
	if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'transfer' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-3">Date</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">To</div>
								<div class="form-group col-lg-9"><select class="form-control select" name="to" required>';
									$result=mysqli_query($con,"SELECT * FROM bank_accounts WHERE status=1 AND id<>'$_GET[from]'") or die(mysqli_error($con));
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'">'.$row['account'].'</option>';
								echo '</select></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Description</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control" amount="description" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Amount</div>
								<div class="form-group col-lg-9"><input type="number" class="form-control" name="amount" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="transfer"/>
							<input type="hidden" name="from" value="'.$_GET['from'].'"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Transfer</button></div></div>
						</form>
					</div>
				</div>';
			break;
			case 'add_account':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-3">Title/Account Number</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input" name="title" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Bank</div>
								<div class="form-group col-lg-9"><select class="form-control select" name="bank">';
									$result=mysqli_query($con,"SELECT * FROM banks") or die(mysqli_error($con));
									while($row1=$result->fetch_assoc())echo '<option value="'.$row1['id'].'">'.$row1['title'].'</option>';
								echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Initial Balance</div>
								<div class="form-group col-lg-9"><input type="number" class="form-control next_input" name="balance" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Balance Date</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control date" name="date" value="'.date('d-m-Y').'" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_account"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Account</button></div></div>
						</form>
					</div>
				</div>';
				break;
			
		}
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'add_account':
				$date=date('Y-m-d',strtotime($_POST['date']));
				mysqli_query($con,"INSERT INTO bank_accounts(bank,account,amount,date,added,user) 
				VALUES('$_POST[bank]','$_POST[title]','$_POST[balance]','$date','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
				break;
			case 'transfer':
				$date=date('Y-m-d',strtotime($_POST['date']));
				mysqli_query($con,"INSERT INTO bank_transactions(title,date,amount,flow,f_bank,bank,added,user) 
				VALUES('$_POST[description]','$date','$_POST[amount]','0','$_POST[from]','$_POST[to]','$current_time','$_SESSION[user_id]')") or die(mysqli_error($con));
				break;
		}
		return;
	}
	if(isset($_GET['id']) AND $_GET['id']>0){
		echo'<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<table class="customized_table table table-sm" table="bill_payments" style="width:100%;" data-button="true" data-sum="3,4" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Description').'</th><th>'.language('Credit').'</th>
							<th>'.language('Debit').'</th>
							<th>'.language('Balance').'</th>
							</tr></thead><tbody>';
							$result=mysqli_query($con,"SELECT ba.*,b.title FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank WHERE ba.id='$_GET[id]'") or die(mysqli_error($con));
							$bank=mysqli_fetch_array($result);
							$result=mysqli_query($con,"SELECT * FROM bank_transactions WHERE (bank='$_GET[id]' OR f_bank='$_GET[id]') AND date>='$bank[date]'") or die(mysqli_error($con));
							$rows=array();
							while($row=mysqli_fetch_array($result)){
								$rows['bank_transactions'.$row['id']]['date']=$row['date'];
								$rows['bank_transactions'.$row['id']]['title']=$row['title'];
								if($row['flow']==0){
									$rows['bank_transactions'.$row['id']]['credit']=($row['bank']==$_GET['id']?$row['amount']:0);
									$rows['bank_transactions'.$row['id']]['debit']=($row['f_bank']==$_GET['id']?$row['amount']:0);
								}
								else if($row['flow']>0){
									$rows['bank_transactions'.$row['id']]['credit']=($row['flow']<=2?$row['amount']:0);
									$rows['bank_transactions'.$row['id']]['debit']=($row['flow']==3?$row['amount']:0);
								}
								else {
									$rows['bank_transactions'.$row['id']]['credit']=($row['amount']>0?$row['amount']:0);
									$rows['bank_transactions'.$row['id']]['debit']=($row['amount']<0?$row['amount']:0);
								}
							}
							$result=mysqli_query($con,"SELECT * FROM payments_cheques WHERE bank='$_GET[id]' AND status=3 AND transferred>='$bank[date]'") or die(mysqli_error($con));
							while($row=mysqli_fetch_array($result)){
								$rows['payments_cheques'.$row['id']]['date']=$row['transferred'];
								$rows['payments_cheques'.$row['id']]['title']=$row['payment'].' - '.$row['number'];
								$rows['payments_cheques'.$row['id']]['debit']=$row['amount'];
								$rows['payments_cheques'.$row['id']]['credit']=0;
							}
							if($bank['account']=='Agoysoft'){
								$result=mysqli_query($con,"SELECT * FROM bill_payments WHERE status=2 AND date>='$bank[date]'") or die(mysqli_error($con));
								while($row=mysqli_fetch_array($result)){
									$rows['bill_payments'.$row['id']]['date']=$row['date'];
									$rows['bill_payments'.$row['id']]['title']=$row['number'];
									$rows['bill_payments'.$row['id']]['debit']=($row['amount']+$row['charge']-$row['commission']);
									$rows['bill_payments'.$row['id']]['credit']=0;
								}
							}
							if($bank['account']=='Cash Book'){
								$result=mysqli_query($con,"SELECT * FROM payments WHERE type=1 AND date>='$bank[date]' AND session is NULL") or die(mysqli_error($con));
								while($row=mysqli_fetch_array($result)){
									$rows['bill_payments'.$row['id']]['date']=$row['date'];
									$rows['bill_payments'.$row['id']]['title']=$payments_type[$row['category']];
									$rows['bill_payments'.$row['id']]['debit']=$row['amount'];
									$rows['bill_payments'.$row['id']]['credit']=0;
								}
							}
							aasort($rows,'date');
							$balance=$bank['amount'];
							$counter=1;
							foreach($rows as $id=>$row){
								$balance+=$row['credit'];
								$balance-=$row['debit'];
								echo'<tr id="'.$id.'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td>'.$row['date'].'</td>
									<td>'.$row['title'].'</td>
									<td class="right">'.($row['credit']<>0?number_format($row['credit'],2):'').'</td>
									<td class="right">'.($row['debit']<>0?number_format($row['debit'],2):'').'</td>
									<td class="right">'.number_format($balance,2).'</td>
								</tr>';
							}
						echo '</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>';
	}
	else {
		echo'<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body table-responsive">
						<table class="customized_table table table-sm" table="bank_accounts" style="width:100%;" data-button="true" data-buts="copy,excel,pdf,colvis,add_account" data-sum="4" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Bank').'</th><th>'.language('Title').'</th><th>'.language('Cash Flow').'</th><th></th>
							</tr></thead><tbody>';
							$result=mysqli_query($con,"SELECT ba.*,b.title,
							(SELECT SUM(amount) FROM bank_transactions WHERE ba.id=bank AND flow IN(0,2) AND date>=ba.date) cash_out, 
							(SELECT SUM(amount) FROM bank_transactions WHERE ((ba.id=bank AND flow=3) OR (ba.id=f_bank AND flow=0)) AND date>=ba.date) cash_in, 
							(SELECT SUM(amount) FROM payments_cheques WHERE ba.id=bank and status=3 AND date>=ba.date) payment, 
							(SELECT SUM(amount+charge-commission) FROM bill_payments WHERE status=2 AND date>=ba.date) bill_payments,
							(SELECT SUM(amount) FROM payments WHERE type=1 AND session is NULL AND date>=ba.date) payments 
							FROM bank_accounts ba LEFT JOIN banks b ON b.id=ba.bank WHERE ba.status=1") or die(mysqli_error($con));
							$counter=1;
							while($row=mysqli_fetch_array($result)){
								$balance=$row['amount']+$row['cash_out']-$row['cash_in']-$row['payment'];
								if($row['account']=='Agoysoft')$balance-=$row['bill_payments'];
								if($row['account']=='Cash Book')$balance-=$row['payments'];
								echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td class="center">'.$row['id'].'</td>
									<td>'.$row['title'].'</td>
									<td>'.$row['account'].'</td>
									<td class="right">'.number_format($balance,2).'</td>
									<td class="right">
										<a href="'.$config_url.'#accounts/accounts\\'.$row['id'].'"><button type="button" class="btn btn-primary waves-effect waves-light">View</button></a>
										<a class="popup" href="'.$remote_file.'&form=transfer&from='.$row['id'].'"><button type="button" class="btn btn-primary waves-effect waves-light">Transfer</button></a>
									</td>
								</tr>';
							}
						echo '</tbody><tfoot><tr><td colspan="2"></td><td colspan="2">Total</td><td class="right"></td><td></td></tr></tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>';
	}
?>
</script>
<?php
	if(in_array('add_account',$permission)){
		echo '<span class="hide popup add_account" href="'.$remote_file.'&form=add_account"></span>';
?>		<script>
		$.fn.dataTable.ext.buttons.add_account = {text: 'Add an Account',action: function ( e, dt, node, config ) {$('.add_account').trigger('click');}};
		</script>
<?php
	}
?>
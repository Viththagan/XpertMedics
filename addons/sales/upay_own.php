<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	$today=date('Y-m-d');
	$devices=array(0=>'Saayinthan',1=>'Yogabalan',2=>'Pavalajah',3=>'Vilajini');
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['select'])){
		switch($_POST['select']){
			case '' :
				
				break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'add_holder' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Holder</label>
										<div><select class="form-control select" name="holder">';//id IN(5,10,12,30426,264,8913,1143,40833)
											$result=mysqli_query($con,"SELECT * FROM customers WHERE type=8 AND status='1'") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['fname'].' - '.$row['mobile'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Device</label>
										<div><select class="form-control select" name="device">';//id IN(5,10,12,30426,264,8913,1143,40833)
											foreach($devices as $id=>$device)echo '<option value="'.$id.'">'.$device.'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Bank</label>
										<div><select class="form-control select" name="bank">';
											$result=mysqli_query($con,"SELECT * FROM banks WHERE id>0");
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['title'].' ['.$row['short'].']</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Mobile</label>
											<div><input type="text" class="x-large form-control" name="mobile"></div>
										</div>
										<div class="form-group"><label>No</label>
											<div><input type="text" class="x-large form-control" name="no"></div>
										</div>
										<div class="form-group"><label>Balance</label>
											<div><input type="text" class="x-large form-control" name="balance"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="add_holder"/>
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
			case 'add_transfer' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Date</label><div><input type="text" class="x-large form-control date" name="date" value="'.date('d-m-Y').'"></div></div>
										<div class="form-group"><label>From</label>
											<div><select class="form-control select" name="bank_from"><option></option>';
											$result=mysqli_query($con,"SELECT u.*,c.fname,c.nic,b.short,b.title
											FROM upay_own u LEFT JOIN customers c ON c.id=u.holder LEFT JOIN banks b ON b.id=u.bank") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['fname'].' - '.$row['short'].' '.$row['description'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>To</label>
											<div><select class="form-control select" name="bank_to">';
											$result=mysqli_query($con,"SELECT u.*,c.fname,c.nic,b.short,b.title
											FROM upay_own u LEFT JOIN customers c ON c.id=u.holder LEFT JOIN banks b ON b.id=u.bank") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['fname'].' - '.$row['short'].' '.$row['description'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Amount</label>
											<div><input type="text" class="x-large form-control" name="amount"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="add_transfer"/>
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
			case 'deduct_transfer' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Date</label><div><input type="text" class="x-large form-control date" name="date" value="'.date('d-m-Y').'"></div></div>
										<div class="form-group"><label>From</label>
											<div><select class="form-control select" name="bank_from">';
											$result=mysqli_query($con,"SELECT u.*,c.fname,c.nic,b.short,b.title
											FROM upay_own u LEFT JOIN customers c ON c.id=u.holder LEFT JOIN banks b ON b.id=u.bank") or die(mysqli_error($con));
											while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['fname'].' - '.$row['short'].' '.$row['description'].'</option>';
											echo '</select></div>
										</div>
										<div class="form-group"><label>Amount</label>
											<div><input type="text" class="x-large form-control" name="amount"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="popup_form_submit" value="deduct_transfer"/>
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
			case 'add_payment' :
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>ID</label>
											<div><input type="text" class="x-large form-control" name="upay"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="id" value="'.$_GET['id'].'"/>
												<input type="hidden" name="popup_form_submit" value="add_payment"/>
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
		}
		return;
	}
	else if(isset($_POST['popup_form_submit'])){
		switch($_POST['popup_form_submit']){
			case 'add_holder':
				mysqli_query($con,"INSERT INTO upay_own(mobile,bank,holder,no,balance,device) 
					VALUES('$_POST[mobile]','$_POST[bank]','$_POST[holder]','$_POST[no]','$_POST[balance]','$_POST[device]')") or die(mysqli_error($con));
				break;
			case 'add_payment':
				$id=sprintf('%05d',$_POST['upay']);
				$result=mysqli_query($con,"SELECT * FROM upay WHERE transaction_id='$id'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_assoc($result);
					mysqli_query($con,"INSERT INTO upay_own_transfer_ids(upay,upay_own) VALUES('$row[id]','$_POST[id]')") or die(mysqli_error($con));
					mysqli_query($con,"UPDATE upay SET customer='5' WHERE id='$row[id]'");
				}
				else echo 'ID Not exists!';
				break;
			case 'add_transfer':
				$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
				if($_POST['bank_from']=='')$_POST['bank_from']='NULL';
				mysqli_query($con,"INSERT INTO upay_own_transfer(date,bank_from,bank_to,amount) 
					VALUES('$_POST[date]',$_POST[bank_from],'$_POST[bank_to]','$_POST[amount]')") or die(mysqli_error($con));
				break;
			case 'deduct_transfer':
				$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
				mysqli_query($con,"INSERT INTO upay_own_transfer(date,bank_from,bank_to,amount) 
					VALUES('$_POST[date]','$_POST[bank_from]',NULL,'$_POST[amount]')") or die(mysqli_error($con));
				break;
			case 'upay_cleared' :
				$result=mysqli_query($con,"SELECT * FROM upay_own WHERE id='$_POST[id]'");
				$row=mysqli_fetch_assoc($result);
				$status=1;
				$text='Hide';
				if($row['status']==1){
					$status=0;
					$text='Show';
				}
				mysqli_query($con,"UPDATE upay_own SET status=$status WHERE id='$_POST[id]'");
				echo $text;
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
							<div class="form-group col-lg-3">
								<label>Bank</label>
								<div><select class="form-control select" name="bank">';
									if(!isset($_GET['bank']))$_GET['bank']='All';
									$result=mysqli_query($con,"SELECT b.* FROM upay_own u LEFT JOIN banks b ON b.id=u.bank GROUP BY b.id") or die(mysqli_error($con));
									while($row=mysqli_fetch_array($result)){
										echo '<option value="'.$row['id'].'" '.($row['id']==$_GET['bank']?'selected':'').'>'.$row['title'].' ('.$row['short'].')</option>';
									}
									echo '<option value="All" '.($_GET['bank']=='All'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Device</label>
								<div><select class="form-control select form_submit_change" name="device">';
									if(!isset($_GET['device']))$_GET['device']='All';
									foreach($devices as $key=>$device)echo '<option value="'.$key.'" '.($_GET['device']==$key?'selected':'').'>'.$device.'</option>';
									echo '<option value="All" '.($_GET['device']=='All'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Status</label>
								<div><select class="form-control select form_submit_change" name="status">';
									$types=array(1=>'Pending',0=>'Cleared');
									if(!isset($_GET['status']))$_GET['status']='All';
									foreach($types as $key=>$title)echo '<option value="'.$key.'" '.($_GET['status']==$key?'selected':'').'>'.$title.'</option>';
									echo '<option value="All" '.($_GET['status']=='All'?'selected':'').'>All</option>
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
	$where="WHERE u.id>0 ";
	$having="";
	if(isset($_GET['status']) AND $_GET['status']<>'All')$where.=" AND u.status='$_GET[status]'";
	if(isset($_GET['device']) AND $_GET['device']<>'All')$where.=" AND u.device='$_GET[device]'";
	if(isset($_GET['bank']) AND $_GET['bank']<>'All')$where.=" AND u.bank='$_GET[bank]'";
	
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body table-responsive">
					<table table="upay_own" data-page-length="50" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" data-button="true" class="table customized_table" style="width: 100%;">
					<thead><tr><th>#</th><th>'.language('Holder').'</th><th>'.language('Mobile').'</th><th>'.language('Device').'</th><th>'.language('Bank').'</th>
					<th>'.language('Account').'</th><th>'.language('Description').'</th><th>'.language('Balance').'</th><th></th></tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT u.*,c.fname,c.nic,b.short,b.title,
					(SELECT SUM(amount) FROM upay_own_transfer WHERE bank_from=u.id) debit,
					(SELECT SUM(amount) FROM upay_own_transfer WHERE bank_to=u.id) credit,
					(SELECT SUM(u1.amount) FROM upay_own_transfer_ids ui LEFT JOIN upay u1 ON u1.id=ui.upay WHERE ui.upay_own=u.id) paid,
					(SELECT u1.amount FROM upay_own_transfer_ids ui LEFT JOIN upay u1 ON u1.id=ui.upay WHERE ui.upay_own=u.id AND u1.date='$today') today
					FROM upay_own u LEFT JOIN customers c ON c.id=u.holder LEFT JOIN banks b ON b.id=u.bank $where $having") or die(mysqli_error($con));
					$counter=1;
					$total=0;
					while($row=mysqli_fetch_array($result)){
						$balance=$row['balance']-$row['debit']-$row['paid']+$row['credit']-1000;
						echo'<tr style="cursor:pointer;" id="'.$row['id'].'" class="'.($row['today']>0?'plat_1':'').'">
							<td class="center selectable">'.$counter++.'</td>
							<td>'.$row['fname'].'<br/>'.$row['nic'].'</td>
							<td class="center editable" field="mobile">'.$row['mobile'].'</td>
							<td class="popup_select" value="'.$row['device'].'" data-toggle="modal" data-target=".device">'.$devices[$row['device']].'</td>
							<td title="'.$row['title'].'" class="popup_select" value="'.$row['bank'].'" data-toggle="modal" data-target=".bank">'.$row['short'].'</td>
							<td class="" field="no">'.$row['no'].'</td>
							<td class="editable" field="description">'.$row['description'].'</td>
							<td class="right popup" href="'.$remote_file.'/upay_own&form=add_payment&id='.$row['id'].'&callback=remove">'.Number_format($balance,2).'</td>
							<td><button type="button" payment_id="'.$row['id'].'" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in upay_cleared">'.($row['status']==1?'Hide':'Show').'</button></td>
						</tr>';
						$total+=$balance;
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="6">Total</td>
					<td class="right">'.Number_format($total,2).'</td><td></td>
					</tr></tfoot></table>
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
						<label>Bank</label>
						<div><select class="form-control select" id="bank" required>
							<option>Select</option>';
							$result=mysqli_query($con,"SELECT * FROM banks WHERE id>0");
							while($row=mysqli_fetch_array($result))echo '<option value="'.$row['id'].'">'.$row['title'].' ['.$row['short'].']</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade device" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Device</label>
						<div><select class="form-control select" id="device" required>
							<option>Select</option>';
							foreach($devices as $id=>$device)echo '<option value="'.$id.'">'.$device.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
    function after_ajax(){
		
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$(".plus").trigger('click');
		return false;
	});
	$(document).bind('keydown',"-", function(e) {
		$(".minus").trigger('click');
		return false;
	});
	$(document).bind('keydown',"Ctrl++", function(e) {
		$(".ctrl_plus").trigger('click');
		return false;
	});
	$('.upay_cleared').click(function(){
		but=$(this);
		$.post(remote_file+"/upay_own",{popup_form_submit:'upay_cleared',id:$(this).attr('payment_id')},function(data){
			but.text(data);
		});
	});
</script>
<span class="hide popup ctrl_plus" href="<?php echo $remote_file.'/upay_own&form=add_holder';?>"></span>
<span class="hide popup plus" href="<?php echo $remote_file.'/upay_own&form=add_transfer';?>"></span>
<span class="hide popup minus" href="<?php echo $remote_file.'/upay_own&form=deduct_transfer';?>"></span>
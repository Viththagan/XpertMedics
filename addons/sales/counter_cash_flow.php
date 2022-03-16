<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// echo '<pre>';print_r($_SESSION);
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'view_transfer':
				$result=mysqli_query($con,"SELECT bt.*,a.account,b.short FROM bank_transactions bt LEFT JOIN bank_accounts a ON a.id=bt.bank LEFT JOIN banks b ON b.id=a.bank 
				WHERE bt.f_bank='$_GET[id]'");
				echo '<div class="card col-lg-8 mx-auto">
					<div class="card-body">
					<table table="bank_transactions" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table" style="width: 100%;">
					<thead><tr><th>#</th><th>'.language('Time').'</th><th>'.language('Account').'</th><th>'.language('Amount').'</th></tr></thead><tbody>';
				$total=0;
				$counter=1;
				while($row=mysqli_fetch_assoc($result)){
					if($row['flow']==3)$row['amount']=-$row['amount'];
					echo '<tr id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td class="center">'.date('h:i A',strtotime($row['added'])).'</td>
						<td>'.$row['short'].' ('.$row['account'].')</td>
						<td class="right editable" field="amount">'.number_format($row['amount'],2).'</td>
					</tr>';
					$total+=$row['amount'];
				}
				echo '<tfoot><tr><td></td><td colspan="2">Total</td><td class="right">'.number_format($total,2).'</td></tr></tfoot></tbody></table></div></div>';
				break;
			case 'view_payments':
				$result=mysqli_query($con,"SELECT p.*,s.title,ss.till,u.first_name FROM payments p LEFT JOIN suppliers s ON (s.id=p.referrer AND p.category=1) 
				LEFT JOIN salary_staff sas ON (sas.id=p.referrer AND p.category=2) LEFT JOIN users u ON u.id=sas.staff
				LEFT JOIN sales_session ss ON ss.id=p.session WHERE p.session='$_GET[id]'")or die(mysqli_error($con));
				echo '<div class="card col-lg-8 mx-auto">
					<div class="card-body">
					<table table="payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('Time').'</th><th>'.language('Type').'</th><th>'.language('Description').'</th><th>'.language('Amount').'</th></tr></thead><tbody>';
				$total=0;
				$counter=1;
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td class="center">'.date('h:i A',strtotime($row['added'])).'</td>
						<td>'.$payments_type[$row['category']].($row['title']<>''?' ['.$row['title'].']':'').($row['first_name']<>''?' ['.$row['first_name'].']':'').'</td>
						<td>'.$row['description'].'</td>
						<td class="right editable" field="amount">'.number_format($row['amount'],2).'</td>
					</tr>';
					$total+=$row['amount'];
				}
				echo '<tfoot><tr><td></td><td colspan="3">Total</td><td class="right">'.number_format($total,2).'</td></tr></tfoot></tbody></table></div></div>';
				
				break;
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
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control form_submit_change date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Till</label>
								<div><select class="form-control select form_submit_change" name="till">';
									if(!isset($_GET['till']))$_GET['till']='';
									$result=mysqli_query($con,"SELECT * FROM sales_counters WHERE status='1'");
									while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'" '.($_GET['till']==$row['id']?'selected':'').'>'.$row['title'].'</option>';
									echo '<option value="" '.($_GET['till']==''?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Type</label>
								<div><select class="form-control select form_submit_change" name="type">';
									$types=array(0=>'Pending',1=>'Checked');
									if(!isset($_GET['type']))$_GET['type']=0;
									foreach($types as $key=>$title)echo '<option value="'.$key.'" '.($_GET['type']==$key?'selected':'').'>'.$title.'</option>';
									echo '<option value="All" '.($_GET['type']=='All'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(10,50,100,200,500,1000);
									if(!isset($_GET['limit']))$_GET['limit']=10;
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
	$date=date('Y-m-d',strtotime($_GET['from']));
	$where="AND ss.date='$date'";
	$view_date=0;
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="AND ss.date BETWEEN '$from' AND '$to' ";
			$view_date=1;
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="AND ss.date='$to' ";
			$view_date=0;
		}
	}
	if(isset($_GET['type']) AND $_GET['type']<>'All')$where.=" AND ss.status='$_GET[type]'";
	if(isset($_GET['till']) AND is_numeric($_GET['till']))$where.=" AND ss.till='$_GET[till]'";
	
	
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	{
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table class="customized_table table table-sm" table="sales_session" style="width:100%;" data-button="true" data-sum="5,6,7,9" data-hide-columns="ID,Session,Sales,Credit Paid,Opened by,Closed by" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th class="center">#</th><th class="center">'.language('ID').'</th><th class="center">'.language('Session').'</th>
					<th>'.language('Till').'</th><th class="right">'.language('Opened').'</th><th class="right">'.language('Sales').'</th><th class="right">'.language('Credit Paid').'</th>
					<th class="right">'.language('Accounts').'</th><th class="right">'.language('Payments').'</th><th class="right">'.language('Closed').'</th>
					<th class="right">'.language('Shortage').'</th><th>'.language('Opened by').'</th><th>'.language('Closed by').'</th><th></th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT ss.*,u.first_name opened,u2.first_name closed,
					(SELECT SUM(amount) FROM sales_payments sp LEFT JOIN sales s ON s.id=sp.sales WHERE sp.session=ss.id AND type='1' AND s.date=ss.date) sales,
                    (SELECT SUM(amount) FROM sales_payments sp LEFT JOIN sales s ON s.id=sp.sales WHERE sp.date=ss.date AND type='1' AND s.date<>ss.date) credit,
					(SELECT SUM(amount) FROM bank_transactions WHERE f_bank=ss.id AND flow=3) acc_in,
					(SELECT SUM(amount) FROM bank_transactions WHERE f_bank=ss.id AND flow=2) acc_out,
					(SELECT SUM(amount) FROM payments WHERE session=ss.id) payments
					FROM sales_session ss LEFT JOIN users u ON u.id=ss.open_staff LEFT JOIN users u2 ON u2.id=ss.close_staff WHERE ss.till>0 AND ss.branch='$_SESSION[branch]' $where $limit") or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['id'].'</td>
							<td class="center">'.date('h:i A',strtotime($row['open'])).' - '.date('h:i A',strtotime($row['close'])).'</td>
							<td class="">'.$row['till'].'</td>
							<td class="right '.($_SESSION['soft']['user_data']['user_level']==0?'editable':'').'" field="open_amount">'.Number_format($row['open_amount'],2).'</td>
							<td class="right">'.Number_format($row['sales'],2).'</td>
							<td class="right">'.Number_format($row['credit'],2).'</td>
							<td class="right popup" href="'.$remote_file.'/counter_cash_flow&form=view_transfer&id='.$row['id'].'">'.Number_format(($row['acc_out']-$row['acc_in']),2).'</td>
							<td class="right popup" href="'.$remote_file.'/counter_cash_flow&form=view_payments&id='.$row['id'].'">'.Number_format($row['payments'],2).'</td>
							<td class="right '.($_SESSION['soft']['user_data']['user_level']==0?'editable':'').'" field="close_amount">'.Number_format($row['close_amount'],2).'</td>
							<td class="right">'.Number_format(($row['open_amount']+$row['sales']+$row['credit']-$row['close_amount']+$row['acc_in']-$row['acc_out']-$row['payments']),2).'</td>
							<td>'.$row['opened'].'</td>
							<td>'.$row['closed'].'</td>
							<td class="center pointer">'.(($row['close_staff']>0 AND $row['status']==0)?'<i class="fa fa-check fa-1x boolean_check" field="status" value="1"></i>':'').'</td>
						</tr>';
					}
				echo '</tbody>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
	echo '<div class="modal fade bank" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>POS</label>
						<div><select class="form-control select" id="bank" required>
							<option>Select</option>';
							foreach($pos_machines as $val=>$title)echo '<option value="'.$val.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
?>
<script>
    
</script>
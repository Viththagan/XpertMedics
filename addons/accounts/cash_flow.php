<?php
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		return;
	}
	else if(isset($_POST['view'])){
		$result=mysqli_query($con,"SELECT SUM(s.amount) sales,ss.open,ss.close,ss.open_amount,ss.close_amount,u.first_name open_staff,u2.first_name close_staff,ss.till 
		FROM sales_payments s LEFT JOIN sales_session ss ON ss.id=s.session LEFT JOIN users u ON u.id=ss.open_staff LEFT JOIN users u2 ON u2.id=ss.close_staff
		WHERE s.type='1' AND s.date BETWEEN '$start' AND '$end' $q GROUP BY s.session") or die(mysqli_error($con));
		$closed=0;
		$htm="";
		while($row=mysqli_fetch_array($result)){
			$balance+=$row['open_amount'];
			if($closed==0){
				echo '<tr>
					<th scope="row">'.date('h:i A',strtotime($row['open'])).'</th>
					<th scope="row">Till '.$row['till'].' Opened ['.$row['open_staff'].']</th>
					<td class="right">'.number_format($row['open_amount'],2).'</td>
					<td></td>
				</tr>';
			}
			$balance+=$row['sales'];
			echo '<tr>
				<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
				<th scope="row">Till '.$row['till'].' Sales</th>
				<td class="right">'.number_format($row['sales'],2).'</td>
				<td></td>
			</tr>';
			
			if($closed<strtotime($row['close'])){
				$closed=strtotime($row['close']);
				$htm='<tr>
					<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
					<th scope="row">Till '.$row['till'].' Closed ['.$row['close_staff'].']</th>
					<td></td>
					<td class="right">'.number_format($row['close_amount'],2).'</td>
				</tr>';
			}
		}
	}
	if(isset($_GET['from']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="form-group col-lg-4">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control form_submit_change date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Till</label>
								<div><select class="form-control select form_submit_change" name="till">';
									$counters=json_decode($_SESSION['soft']['settings']['sales_counters'],true);
									asort($counters);
									if(!isset($_GET['till']))$_GET['till']='All';
									foreach($counters as $value)echo '<option value="'.$value.'" '.($_GET['till']==$value?'selected':'').'>'.$value.'</option>';
									echo '<option value="All" '.($_GET['till']=='All'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>View</label>
								<div><div class="btn-group btn-group-toggle mt-2 mt-xl-0" data-toggle="buttons">';
									$array=array('Detailed','Summary');
									if(!isset($_GET['view']))$_GET['view']='Summary';
									foreach($array AS $val)echo '<label class="btn btn-secondary pointer '.($_GET['view']==$val?'active':'').'"><input type="radio" name="view" value="'.$val.'" '.($_GET['view']==$val?'checked':'').'>'.$val.'</label>';
								echo '</div></div>
							</div>
							<div class="form-group col-lg-2">
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
	$start=date('Y-m-d',strtotime($_GET['from']));
	$end=date('Y-m-d',strtotime($_GET['from']));
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$start=date('Y-m-d',strtotime($_GET['from']));
			$end=date('Y-m-d',strtotime($_GET['to']));
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$start=date('Y-m-d',strtotime($_GET['to']));
			$end=date('Y-m-d',strtotime($_GET['to']));
		}
	}
	$q="";
	if(isset($_GET['till'])){
		if($_GET['till']=='All'){}
		else $q=" AND ss.till='$_GET[till]'";
	}
	
	echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="table-responsive">
					<table class="table table-striped mb-0">
						<thead><tr><th style="width:10%;">Time</th><th>Description</th><th class="right">Credit</th><th class="right">Debit</th></thead>
						<tbody>';
	if($_GET['view']=='Summary'){
		$result=mysqli_query($con,"SELECT ss.*,u.first_name FROM sales_session ss LEFT JOIN users u ON u.id=ss.open_staff WHERE ss.date BETWEEN '$start' AND '$end' $q ORDER BY open ASC LIMIT 0,1") or die(mysqli_error($con));
		$row=mysqli_fetch_array($result);
		$credit=$balance=$row['open_amount'];
		$debit=0;
		echo '<tr>
			<th scope="row">'.date('h:i A',strtotime($row['open'])).'</th>
			<th scope="row">Opening Balance ['.$row['first_name'].']</th>
			<td class="right">'.number_format($row['open_amount'],2).'</td>
			<td></td>
		</tr>';
		
		
		$result=mysqli_query($con,"SELECT SUM(s.amount) sales FROM sales_payments s LEFT JOIN sales_session ss ON ss.id=s.session 
		WHERE s.type='1' AND s.date BETWEEN '$start' AND '$end' $q") or die(mysqli_error($con));
		$row1=mysqli_fetch_array($result);
		$balance+=$row1['sales'];
		$credit+=$row1['sales'];
		echo '<tr>
			<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
			<th scope="row">Sales</th>
			<td class="right">'.number_format($row1['sales'],2).'</td>
			<td></td>
		</tr>';
		$result=mysqli_query($con,"SELECT SUM(s.amount) paid,s.added,s.category FROM payments s LEFT JOIN sales_session ss ON ss.id=s.session 
		WHERE s.type='1' AND s.date BETWEEN '$start' AND '$end' $q GROUP BY s.category") or die(mysqli_error($con));
		while($row1=mysqli_fetch_array($result)){
			$balance-=$row1['paid'];
			$debit+=$row1['paid'];
			echo '<tr>
				<th scope="row">'.date('h:i A',strtotime($row1['added'])).'</th>
				<th scope="row">'.$payments_type[$row1['category']].'</th>
				<td></td>
				<td class="right">'.number_format($row1['paid'],2).'</td>
			</tr>';
		}
		$result=mysqli_query($con,"SELECT  (SUM(if(t.flow=2,t.amount,0))-SUM(if(t.flow=3,t.amount,0))) paid,t.added,b.short,ba.account FROM bank_transactions t LEFT JOIN sales_session ss ON ss.id=t.f_bank 
		LEFT JOIN bank_accounts ba ON ba.id=t.bank LEFT JOIN banks b ON b.id=ba.bank
		WHERE t.flow IN(2,3) AND t.date BETWEEN '$start' AND '$end' $q GROUP BY t.bank") or die(mysqli_error($con));
		while($row1=mysqli_fetch_array($result)){
			$balance-=$row1['paid'];
			if($row1['paid']<0)$credit+=-$row1['paid'];
			else $debit+=$row1['paid'];
			echo '<tr>
				<th scope="row">'.date('h:i A',strtotime($row1['added'])).'</th>
				<th scope="row">'.$row1['account'].($row1['short']<>''?' ('.$row1['short'].')':'').'</th>
				<td class="right">'.($row1['paid']<0?number_format(-$row1['paid'],2):'').'</td>
				<td class="right">'.($row1['paid']>0?number_format($row1['paid'],2):'').'</td>
			</tr>';
		}
		$result=mysqli_query($con,"SELECT ss.*,u.first_name FROM sales_session ss LEFT JOIN users u ON u.id=ss.open_staff WHERE ss.date BETWEEN '$start' AND '$end' $q ORDER BY close DESC LIMIT 0,1") or die(mysqli_error($con));
		$row=mysqli_fetch_array($result);
		$balance-=$row['close_amount'];
		$debit+=$row['close_amount'];
		echo '<tr>
			<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
			<th scope="row">Closing Balance ['.$row['first_name'].']</th>
			<td></td>
			<td class="right">'.number_format($row['close_amount'],2).'</td>
		</tr>';
		if($balance>0){
			$debit+=$balance;
			echo '<tr>
				<th scope="row"></th>
				<th scope="row">Shortage</th>
				<td></td>
				<td class="right">'.number_format($balance,2).'</td>
			</tr>';
		}
		else {
			$credit+=-$balance;
			echo '<tr>
				<th scope="row"></th>
				<th scope="row">Excess</th>
				<td class="right">'.number_format(-$balance,2).'</td>
				<td></td>
			</tr>';
		}
		echo '<tr>
			<th scope="row"></th>
			<th scope="row"></th>
			<td class="right">'.number_format($credit,2).'</td>
			<td class="right">'.number_format($debit,2).'</td>
		</tr>';
	}
	else {
		$balance=0;
		$result=mysqli_query($con,"SELECT SUM(s.amount) sales,ss.open,ss.close,ss.open_amount,ss.close_amount,u.first_name open_staff,u2.first_name close_staff,ss.till 
		FROM sales_payments s LEFT JOIN sales_session ss ON ss.id=s.session LEFT JOIN users u ON u.id=ss.open_staff LEFT JOIN users u2 ON u2.id=ss.close_staff
		WHERE s.type='1' AND s.date BETWEEN '$start' AND '$end' $q GROUP BY s.session") or die(mysqli_error($con));
		$closed=0;
		$htm="";
		while($row=mysqli_fetch_array($result)){
			$balance+=$row['open_amount'];
			if($closed==0){
				echo '<tr>
					<th scope="row">'.date('h:i A',strtotime($row['open'])).'</th>
					<th scope="row">Till '.$row['till'].' Opened ['.$row['open_staff'].']</th>
					<td class="right">'.number_format($row['open_amount'],2).'</td>
					<td></td>
				</tr>';
			}
			$balance+=$row['sales'];
			echo '<tr>
				<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
				<th scope="row">Till '.$row['till'].' Sales</th>
				<td class="right">'.number_format($row['sales'],2).'</td>
				<td></td>
			</tr>';
			$result1=mysqli_query($con,"SELECT * FROM bank_transactions WHERE s.type='1' AND s.date BETWEEN '$start' AND '$end' $q GROUP BY s.session") or die(mysqli_error($con));
			if($closed<strtotime($row['close'])){
				$closed=strtotime($row['close']);
				$htm='<tr>
					<th scope="row">'.date('h:i A',strtotime($row['close'])).'</th>
					<th scope="row">Till '.$row['till'].' Closed ['.$row['close_staff'].']</th>
					<td></td>
					<td class="right">'.number_format($row['close_amount'],2).'</td>
				</tr>';
			}
		}
		echo $htm;
	}
	
						echo '</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>';
?>
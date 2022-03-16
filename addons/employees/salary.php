<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if(strpos($_POST['field'],'break_down_')!==false){
			$result=mysqli_query($con,"SELECT break_down FROM salary_staff WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			$break_down=json_decode($row['break_down'],true);
			$title=str_replace('break_down_','',$_POST['field']);
			$break_down[$title]=$value;
			$value=json_encode($break_down);
			$_POST['field']='break_down';
		}
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'user_meta':
				foreach($_POST['meta'] as $title=>$description){
					$result=mysqli_query($con,"SELECT * FROM users_meta WHERE user='$_POST[id]' AND title='$title'");
					if(mysqli_affected_rows($con)==0){
						mysqli_query($con,"INSERT INTO users_meta(user,title,description) VALUES('$_POST[id]','$title','$description')");
					}
					else mysqli_query($con,"UPDATE users_meta SET description='$description' WHERE user='$_POST[id]' AND title='$title'");
				}
				break;
			case 'add_hours':
				mysqli_query($con,"INSERT INTO salary(year,month,min_hours,added,user) VALUES('$_POST[year]','$_POST[month]','$_POST[hours]','$current_time','$_SESSION[user_id]')");
				break;
			case 'calculate_ot':
				$result=mysqli_query($con,"SELECT * FROM salary WHERE id='$_POST[salary]'");
				$row=mysqli_fetch_assoc($result);
				$return=get_attendance(date('Y-m-01',strtotime("$row[year]-$row[month]-01")),date('Y-m-t',strtotime("$row[year]-$row[month]-01")),$_POST['id']);
				$ratio=(number_format(($return['hours']/$row['min_hours']),2)-1);
				echo json_encode(array('hours'=>$return['hours'],'days'=>$return['days'],'ratio'=>$ratio));
				break;
			case 'add_salary':
				$break_down=array();
				$contribution=array();
				$result=mysqli_query($con,"SELECT * FROM users WHERE id='$_POST[id]'");
				$user=mysqli_fetch_assoc($result);
				$result=mysqli_query($con,"SELECT title,description FROM users_meta WHERE user='$_POST[id]'");
				while($row=mysqli_fetch_assoc($result)){
					extract($row);
					$$title=$description;
				}
				if($user['type']==1){
					$break_down['EPF 8%']=-($salary*0.08);
					$contribution['EPF 12%']=($salary*0.12);
					$contribution['ETF 3%']=($salary*0.03);
				}
				foreach($_POST['break_down'] as $title=>$amount){
					if($amount>0 || $amount<0)$break_down[$title]=$amount;
				}
				foreach($_POST['meta'] as $data){
					if($data['meta_value']>0 AND $data['meta_title']<>'')$break_down[$data['meta_title']]=$data['meta_value'];
				}
				$break_down=json_encode($break_down);
				$contribution=json_encode($contribution);
				$result=mysqli_query($con,"SELECT * FROM salary_staff WHERE salary='$_POST[salary]' AND staff='$_POST[id]'");
				$id=0;
				if(mysqli_affected_rows($con)==0){
					mysqli_query($con,"INSERT INTO salary_staff(staff,salary,days,hours,basic,break_down,contribution,added,user) 
					VALUES('$_POST[id]','$_POST[salary]','$_POST[days]','$_POST[hours]','$_POST[basic]','$break_down','$contribution','$current_time','$_SESSION[user_id]')");
					$id=mysqli_insert_id($con);
				}
				else {
					$row=mysqli_fetch_assoc($result);
					mysqli_query($con,"UPDATE salary_staff SET days='$_POST[days]',hours='$_POST[hours]',basic='$_POST[basic]',break_down='$break_down',
					contribution='$contribution',added='$current_time',user='$_SESSION[user_id]' WHERE id='$row[id]'");
					$id=$row['id'];
				}
				echo print_payslip($id);
				// echo print_payslip(289);
				break;
			case 'print_salary':
				echo print_payslip($_POST['id']);
				break;
		}
		return;
	}
	else if(isset($_GET['form']) AND $_GET['form']<>''){
		switch($_GET['form']){
			case 'data':
				if(isset($_GET['id'])){
					$result=mysqli_query($con,"SELECT * FROM users_meta WHERE user='$_GET[id]'");
					$meta=array();
					while($row=mysqli_fetch_assoc($result))$meta[$row['title']]=$row['description'];
				}
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
					<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
							<div class="row">
								<div class="form-group col-lg-3">Basic</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6" name="meta[salary]" value="'.$meta['salary'].'" required/></div>
							</div>
							<input type="hidden" name="id" value="'.$_GET['id'].'"/>
							<input type="hidden" name="form_submit" value="user_meta"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Update</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'add_salary':
				$result=mysqli_query($con,"SELECT * FROM users_meta WHERE user='$_GET[id]' AND title='salary'");
				$row=mysqli_fetch_array($result);
				$basic=$row['description'];
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
					<form class="popup_form_submit repeater" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
							<div class="row">
								<div class="form-group col-lg-3">Month</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="salary" id="salary">';
										$last_month=date('m',strtotime('-3 months'));
										$last_year=date('Y',strtotime('-3 months'));
										$result=mysqli_query($con,"SELECT s.* FROM salary s LEFT JOIN salary_staff ss ON (s.id=ss.salary AND ss.staff=$_GET[id]) LEFT JOIN users u ON u.id=ss.staff WHERE (ss.id is null OR ss.basic=0) AND s.month>='$last_month' AND s.year>='$last_year'");
										while($salary=mysqli_fetch_array($result))echo '<option value="'.$salary['id'].'">'.date('M-Y',strtotime('01-'.$salary['month'].'-'.$salary['year'])).'</option>';
									echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Basic</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6" name="basic" id="basic" value="'.$basic.'" required/></div>
							</div>';
							foreach($salary_fields as $title){
								$title_=ucwords($title);
								$class='';
								if($title=='ot'){
									$class='ot';
									$title_='Over Time/No Pay';
								}
								echo '<div class="row">
									<div class="form-group col-lg-3">'.$title_.'</div>
									<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6 '.$class.'" name="break_down['.$title.']"/></div>
								</div>';
							}
							echo '<div class="inner-repeater mb-4">
									<div data-repeater-list="meta" class="inner form-group">
										<label>Addition :</label>
										<div data-repeater-item class="inner mb-3 row">
											<div class="col-md-10 col-8 input-group">
												<input type="text" name="meta_title" class="inner form-control" placeholder="Title"/>
												<input type="text" name="meta_value" class="inner form-control" placeholder="Value"/>
											</div>
											<div class="col-md-2 col-4">
												<input data-repeater-delete type="button" class="btn btn-primary btn-block inner" value="Delete"/>
											</div>
										</div>
									</div>
									<input data-repeater-create type="button" class="btn btn-success inner" value="Add a Field"/>
								</div>';
							echo '<input type="hidden" name="id" id="id" value="'.$_GET['id'].'"/>
							<input type="hidden" name="hours" id="hours" value=""/>
							<input type="hidden" name="days" id="days" value=""/>
							<input type="hidden" name="form_submit" value="add_salary"/>
							<input type="hidden" id="callback" value="print"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'add_hours':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
					<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
							<div class="row">
								<div class="form-group col-lg-3">Year</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="year">';
										for($i=date('Y')-3; $i<=date('Y')+3; $i++)echo '<option value="'.$i.'" '.($i==date('Y')?'selected':'').'>'.$i.'</option>';
									echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Month</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="month">';
										for($i=1; $i<=12; $i++)echo '<option value="'.$i.'" '.($i==date('m')?'selected':'').'>'.$i.'</option>';
									echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Hours</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6" name="hours" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_hours"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'view_salary':
				$result=mysqli_query($con,"SELECT * FROM salary_staff WHERE id='$_GET[id]'");
				$row=mysqli_fetch_assoc($result);
				echo '<div class="card col-lg-8 mx-auto">
					<div class="card-body">
					<table table="salary_staff" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table" style="width: 100%;">
					<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('Amount').'</th></tr></thead><tbody>';
				$counter=1;
				echo '<tr id="'.$row['id'].'">
					<td class="center">'.$counter++.'</td>
					<td>Basic</td>
					<td class="right editable" field="basic">'.number_format($row['basic'],2).'</td>
				</tr>';
				$total=$row['basic'];
				$break_down=json_decode($row['break_down'],true);
				foreach($break_down as $title=>$amount){
					$title_=ucwords($title);
					if($title=='ot'){
						$title_='Over Time';
						if($amount<0)$title_='No Pay';
					}
					echo '<tr id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td>'.$title_.'</td>
						<td class="right editable" field="break_down_'.$title.'">'.number_format($amount,2).'</td>
					</tr>';
					$total+=$amount;
				}
				echo '<tfoot><tr><td></td><td>Total</td><td class="right">'.number_format($total,2).'</td></tr></tfoot></tbody></table></div></div>';
				
				break;
			case 'view_payment':
				$result=mysqli_query($con,"SELECT * FROM payments WHERE referrer='$_GET[id]' AND category='2'");
				echo '<div class="card col-lg-8 mx-auto">
					<div class="card-body">
					<table table="payments" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table" style="width: 100%;">
					<thead><tr><th>#</th><th>ID</th><th>'.language('Date').'</th><th>'.language('Amount').'</th></tr></thead><tbody>';
				$counter=1;
				$total=0;
				while($row=mysqli_fetch_assoc($result)){
					echo '<tr id="'.$row['id'].'">
						<td class="center">'.$counter++.'</td>
						<td class="center editable" field="referrer">'.$row['referrer'].'</td>
						<td class="center editable" field="date">'.date('d-m-Y',strtotime($row['date'])).'</td>
						<td class="right editable" field="amount">'.number_format($row['amount'],2).'</td>
					</tr>';
					$total+=$row['amount'];
				}
				echo '<tfoot><tr><td></td><td colspan="2">Total</td><td class="right">'.number_format($total,2).'</td></tr></tfoot></tbody></table></div></div>';
				
				break;
		}
		return;
	}
	
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	if(isset($_GET['id']) AND strpos($_GET['id'],'month-')!==false){
		$id=str_replace('month-','',$_GET['id']);
		$result=mysqli_query($con,"SELECT * FROM salary WHERE id='$id'");
		$row1=mysqli_fetch_array($result);
		$hours=$row1['min_hours'];
		echo '<script>title="'.date('M-Y',strtotime("01-$row1[month]-$row1[year]")).'";</script>';
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="customized_table table table-sm" table="users" style="width:100%;" data-button="true" data-sum="4,5,6" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th class="center">#</th><th class="center">ID</th><th>'.language('Month').'</th><th class="right">'.language('Attendnce').'</th>
						<th class="right">'.language('Salary').'</th><th class="right">'.language('Paid').'</th><th class="right">'.language('Payable').'</th>
						<th class="center"></th><th class="center">'.language('Attendnce').'</th></tr></thead><tbody>';
						$result=mysqli_query($con,"SELECT ss.*,u.first_name,u.last_name,(SELECT SUM(amount) FROM payments WHERE referrer=ss.id AND category=2) paid
						FROM salary_staff ss LEFT JOIN users u ON u.id=ss.staff WHERE ss.salary='$id' ORDER BY u.id ASC") or die(mysqli_error($con));
						$counter=1;
						$total=0;
						$paid=0;
						$payable=0;
						while($row=mysqli_fetch_array($result)){
							$break_down=json_decode($row['break_down'],true);
							$sum=0;
							if(is_array($break_down))$sum=($row['basic']+array_sum($break_down));//$row['transport']+$row['incentive']+$row['ot']
							echo'<tr id="'.$row['id'].'" staff="'.$row['staff'].'" year="'.$row1['year'].'" month="'.$row1['month'].'" style="cursor:pointer;">
								<td class="center">'.$counter++.'</td>
								<td class="center">'.$row['id'].'</td>
								<td>'.$row['last_name'].' '.$row['first_name'].'</td>
								<td class="right">'.number_format(($row['hours']/$hours)*100,2).'%</td>
								<td class="right popup" href="'.$remote_file.'/salary&form=view_salary&id='.$row['id'].'">'.number_format($sum,2).'</td>
								<td class="right popup" href="'.$remote_file.'/salary&form=view_payment&id='.$row['id'].'">'.number_format($row['paid'],2).'</td>
								<td class="right">'.number_format($sum-$row['paid'],2).'</td>
								<td class="center">'.($row['paid']<$sum?'<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/salary&form=add_payment&id='.$row['id'].'">Pay</button>':'').'</td>
								<td class="center">
									<button type="button" class="btn btn-primary waves-effect waves-light attendance_view_btn">View</button>
									<button type="button" class="btn btn-primary waves-effect waves-light attendance_print_btn">Print</button>
								</td>
							</tr>';
							$total+=$sum;
							$paid+=$row['paid'];
							$payable+=$sum-$row['paid'];
						}
					echo '</tbody><tfoot>
					<tr><td></td><td colspan="3">Page Total</td><td class="right"></td><td class="right"></td><td class="right"></td><td></td><td></td></tr>
					<tr><td></td><td colspan="3">Total</td><td class="right">'.number_format($total,2).'</td>
					<td class="right">'.number_format($paid,2).'</td><td class="right">'.number_format($payable,2).'</td><td></td><td></td></tr>
					</tfoot></table>
					</div>
				</div>
			</div>
		</div>';
	}
	if(isset($_GET['id']) AND strpos($_GET['id'],'a-')!==false){
		$data=explode('-',str_replace('a-','',$_GET['id']));
		$id=$data[0];
		$year=$data[1];
		$month=$data[2];
		$result=mysqli_query($con,"SELECT * FROM users WHERE id='$id'");
		$row=mysqli_fetch_array($result);
		$start_date="$year-$month-01";
		$end_date="$year-$month-31";
		echo '<script>title="'.$row['last_name'].' '.$row['first_name'].' - Attendance Report";</script>';
		if(isset($_GET['form'])){
			echo '<h4>Attendance Report for the Period '.$start_date.' to '.$end_date.'</h4>
			<h5>Staff : '.$row['last_name'].' '.$row['first_name'].'</h5>';
		}
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="customized_table table table-sm" table="payments" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th class="center">#</th><th class="center">Date</th><th class="center">Day</th>
						<th class="center">IN</th><th class="center">OUT</th><th class="center">Break</th><th class="center">Time</th>
						<th class="right">Sum</th></tr></thead><tbody>';
						$result=mysqli_query($con,"SELECT * FROM users_log WHERE user='$id' AND logged BETWEEN '$start_date' AND '$end_date' ORDER BY logged ASC") or die(mysqli_error($con));
						$days=array();
						while($row=mysqli_fetch_assoc($result)){
							// $days[date('d-m-Y',strtotime($row['logged']))][]=$row['logged'];
							$days[date('d-m-Y',strtotime($row['logged']))][]=strtotime($row['logged']);
						}
						$counter=1;
						// echo '<pre>';print_r($days);
						$total=0;
						foreach($days as $date=>$logs){
							$in='';
							$out='';
							$sub_total=0;
							asort($logs);
							foreach($logs as $time){
								if($in=='' OR $in>$time)$in=$time;
								if($out=='' OR $out<$time)$out=$time;
							}
							$break=0;
							if(sizeof($logs)>3){
								$temp='';
								$i=1;
								foreach($logs as $time){
									if($time!=$in AND $out!=$time){
										if($i%2==1)$temp=$time;
										else if($i%2==0)$break+=$time-$temp;
										$i++;
									}
								}
							}
							$sub_total=$out-$in-$break;
							$total+=$sub_total;
							
							echo '<tr>
								<td class="center">'.$counter++.'</td>
								<td class="center">'.$date.'</td>
								<td class="center">'.date('D',strtotime($date)).'</td>
								<td class="center">'.date('h:i A',$in).'</td>
								<td class="center">'.date('h:i A',$out).'</td>
								<td class="center">'.time_format($break).'</td>
								<td class="center">'.time_format($sub_total).'</td>
								<td class="right">'.time_format($total).'</td>
							</tr>';
						}
					echo '</tbody></table>
					</div>
				</div>
			</div>
		</div>';
		if(isset($_GET['form'])){
			echo '<table width="100%" style="border-width: 0px;">
			<tr>
				<td style="border-style: none; border-width: medium" width="50%"><p align="center">............................................</td>
				<td style="border-style: none; border-width: medium" width="50%"><p align="center">............................................</td>
			</tr>
			<tr>
				<td align="center" style="border-style: none; border-width: medium" width="50%">Prepared By</td>
				<td align="center" style="border-style: none; border-width: medium" width="50%">Staff Signature</td>
			</tr>
		</table>';
		}
		return;
	}
	else if(isset($_GET['id']) AND $_GET['id']>0){
		$result=mysqli_query($con,"SELECT * FROM users WHERE id='$_GET[id]'");
		$row=mysqli_fetch_array($result);
		echo '<script>title="'.$row['last_name'].' '.$row['first_name'].'";</script>';
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="customized_table table table-sm" table="payments" style="width:100%;" data-button="true" data-sum="4,5,6" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th class="center">#</th><th class="center">ID</th><th class="center">'.language('Month').'</th>
						<th class="right">'.language('Attendnce').'</th><th class="right">'.language('Salary').'</th><th class="right">'.language('Paid').'</th>
						<th class="right">'.language('Payable').'</th><th class="center">'.language('Pay').'</th><th class="center">'.language('Pay Slip').'</th>
						<th class="center">'.language('Attendance').'</th>
						</tr></thead><tbody>';
						$result=mysqli_query($con,"SELECT ss.*,s.month,s.year,s.min_hours,(SELECT SUM(amount) FROM payments WHERE referrer=ss.id AND category=2) paid
						FROM salary_staff ss LEFT JOIN salary s ON s.id=ss.salary WHERE ss.staff='$_GET[id]'") or die(mysqli_error($con));
						$counter=1;
						$total=0;
						$paid=0;
						$payable=0;
						while($row=mysqli_fetch_array($result)){
							$break_down=json_decode($row['break_down'],true);
							$sum=0;
							if(is_array($break_down))$sum=($row['basic']+array_sum($break_down));//$row['transport']+$row['incentive']+$row['ot']
							echo'<tr id="'.$row['id'].'" staff="'.$_GET['id'].'" year="'.$row['year'].'" month="'.$row['month'].'" style="cursor:pointer;">
								<td class="center">'.$counter++.'</td>
								<td class="center">'.$row['id'].'</td>
								<td class="center">'.date('Y-M',strtotime("01-$row[month]-$row[year]")).'</td>
								<td class="right">'.number_format(($row['hours']/$row['min_hours'])*100,2).'%</td>
								<td class="right popup" href="'.$remote_file.'/salary&form=view_salary&id='.$row['id'].'">'.number_format($sum,2).'</td>
								<td class="right popup" href="'.$remote_file.'/salary&form=view_payment&id='.$row['id'].'">'.number_format($row['paid'],2).'</td>
								<td class="right">'.number_format($sum-$row['paid'],2).'</td>
								<td class="center">'.($row['paid']<$sum?'<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/salary&form=add_payment&id='.$row['id'].'">Pay</button>':'').'</td>
								<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light print_salary_btn">Print</button></td>
								<td class="center">
									<button type="button" class="btn btn-primary waves-effect waves-light attendance_view_btn">View</button>
									<button type="button" class="btn btn-primary waves-effect waves-light attendance_print_btn">Print</button>
								</td>
							</tr>';
							if(($sum-$row['paid'])==0)mysqli_query($con,"UPDATE salary_staff SET status='1' WHERE id='$row[id]'");
							$total+=$sum;
							$paid+=$row['paid'];
							$payable+=$sum-$row['paid'];
						}
					echo '</tbody><tfoot>
					<tr><td></td><td colspan="3">Page Total</td><td class="right"></td><td class="right"></td><td class="right"></td><td colspan="4"></td></tr>
					<tr><td></td><td colspan="3">Total</td><td class="right">'.number_format($total,2).'</td>
					<td class="right">'.number_format($paid,2).'</td><td class="right">'.number_format($payable,2).'</td><td colspan="4"></td></tr>
					</tfoot></table>
					</div>
				</div>
			</div>
		</div>';
	}
	else {
		if($_SESSION['branch_data']['finger_print']==1){
			echo'<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-body">
							<div path="'.$upload_path.'/attedance/" name="attendance" class="dropzone" query="">Upload Attendance Log File. (xls Format)</div>
						</div>
					</div>
				</div>
			</div>';
		}
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<ul class="nav nav-pills nav-justified" role="tablist">
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link active" data-toggle="tab" href="#staff" role="tab">
								<span class="d-block"><i class="fas fa-align-left"></i></span>
								<span class="d-none d-sm-block">Active Staff List</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#monthly" role="tab">
								<span class="d-block"><i class="fas fa-history"></i></span>
								<span class="d-none d-sm-block">Monthly</span> 
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active p-3" id="staff" role="tabpanel">
							<table class="customized_table table table-sm" table="users" style="width:100%;" data-button="true" data-sum="3" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
								<thead><tr><th>#</th><th>ID</th><th>'.language('Name').'</th><th>'.language('Basic').'/'.language('Allowance').'</th><th></th><th></th><th></th>
								</tr></thead><tbody>';
								$last_month=date('m',strtotime('-3 months'));
								$last_year=date('Y',strtotime('-3 months'));
								$result=mysqli_query($con,"SELECT u.*,
								(SELECT description FROM users_meta WHERE user=u.id AND title='salary') basic,(SELECT COUNT(*) FROM salary s WHERE id NOT IN(SELECT salary FROM salary_staff WHERE staff=u.id) AND month>='$last_month' AND year>='$last_year') pending
								FROM users u WHERE u.status=1 AND u.type>=0") or die(mysqli_error($con));
								$counter=1;
								while($row=mysqli_fetch_array($result)){
									echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td>
										<td class="center">'.$row['id'].'</td>
										<td class="staff_view">'.$row['first_name'].' '.$row['last_name'].'</td>
										<td class="right popup" href="'.$remote_file.'/salary&form=data&id='.$row['id'].'">'.number_format($row['basic'],2).'</td>
										<td class="center">'.($row['pending']>0?'<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/salary&form=add_salary&id='.$row['id'].'">Add Salary</button>':'').'</td>
										<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$remote_file.'/salary&form=add_payment&id='.$row['id'].'">Pay</button></td>
										<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light staff_view">View</button></td>
									</tr>';
								}
							echo '</tbody><tfoot><tr><td colspan="2"></td><td>Total</td><td class="right"></td><td></td><td></td><td></td></tr></tfoot></table>
						</div>
						<div class="tab-pane p-3" id="monthly" role="tabpanel">
							<table class="customized_table table table-sm" table="salary" style="width:100%;" data-button="true" data-buts="copy,excel,pdf,colvis,add_hours" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
								<thead><tr><th>#</th><th>'.language('Month').'</th><th>'.language('Hours').'</th><th></th>
								</tr></thead><tbody>';
								$month=date('m');
								$year=date('Y');
								$result=mysqli_query($con,"SELECT * FROM salary WHERE month='$month' AND year='$year'") or die(mysqli_error($con));
								if(mysqli_affected_rows($con)==0)mysqli_query($con,"INSERT INTO salary(month,year) VALUES('$month','$year')") or die(mysqli_error($con));
								$result=mysqli_query($con,"SELECT * FROM salary") or die(mysqli_error($con));
								$counter=1;
								while($row=mysqli_fetch_array($result)){
									echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td>
										<td class="monthly_view">'.date('Y-m',strtotime("01-$row[month]-$row[year]")).'</td>
										<td class="right editable" field="min_hours">'.$row['min_hours'].'</td>
										<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light monthly_view">View</button></td>
									</tr>';
								}
							echo '</tbody></table>
						</div>
					</div>
					</div>
				</div>
			</div>
		</div>';
	}
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
    min-height: 50px !important;
	padding: 2px 2px;
}
</style>
<script>
	function after_ajax(){
		if($('.ot').length>0){
			$('#salary').change(function(){
				$.post(remote_file+"/salary", { form_submit: 'calculate_ot', salary: $('#salary').val(), id: $('#id').val()},function(d){
					// if(d!='')alert(d);
					data=jQuery.parseJSON(d);
					$('#hours').val(data.hours);
					$('#days').val(data.days);
					$('.ot').val((data.ratio*$('#basic').val()).toFixed(2));
				});
			});
			$('#salary').trigger('change');
		}
	}
	$('.staff_view').click(function(){
		location.hash='employees/salary\\' + $(this).parents('tr').attr('id');
	});
	$('.monthly_view').click(function(){
		location.hash='employees/salary\\month-' + $(this).parents('tr').attr('id');
	});
	$('.attendance_view_btn').click(function(){
		location.hash='employees/salary\\a-' + $(this).parents('tr').attr('staff')+'-'+$(this).parents('tr').attr('year')+'-'+$(this).parents('tr').attr('month');
	});
	$('.attendance_print_btn').click(function(){
		id='a-' + $(this).parents('tr').attr('staff')+'-'+$(this).parents('tr').attr('year')+'-'+$(this).parents('tr').attr('month');
		$.get(config_url+"remote.php?file=employees/salary", { id: id,form:''},function(d){
			$('#printableArea').html(d);
			window.print();
		});
	});
	$('.print_salary_btn').click(function(){
		$.post(remote_file+"/salary", { form_submit: 'print_salary', id: $(this).parents('tr').attr('id')},function(d){
			$('#printableArea').html(d);
			window.print();
		});
	});
</script>
<?php
	if(in_array('add',$permission)){
		echo '<span class="hide popup add_hours" href="'.$remote_file.'/salary&form=add_hours"></span>';
?>		<script>
		$.fn.dataTable.ext.buttons.add_hours = {text: 'Add Hours',action: function ( e, dt, node, config ) {$('.add_hours').trigger('click');}};
		</script>
<?php
	}
?>
<?php
	// print_r($_GET);
	if(isset($_GET['import'])){
		$added=$current_time;
		if($_GET['time']<>'')$added=date("Y-m-d H:i:s",strtotime("$_GET[time] $_GET[date]"));
		mysqli_query($con,"INSERT INTO electricity(import,export,start,added) VALUES('$_GET[import]','$_GET[export]','$_GET[start]','$added')") or die(mysqli_error($con));
	}
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" autocomplete="off" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div><input type="text" name="date" class="form-control date" required value="'.(isset($_GET['date'])?$_GET['date']:date('d-m-Y')).'"/></div>
							</div>
							<div class="form-group col-lg-2">
								<label>Time</label>
								<div><input type="time" name="time" class="form-control" required value="'.(isset($_GET['time'])?date('H:00',strtotime('+1 hour',strtotime($added))):date('H:00')).'"/></div>
							</div>
							<div class="form-group col-lg-2">
								<label>18.0</label>
								<div><input type="number" id="import" name="import" class="form-control" required value="'.(isset($_GET['import'])?substr($_GET['import'],0,3):'').'"/></div>
							</div>
							<div class="form-group col-lg-2">
								<label>28.0</label>
								<div><input type="number" name="export" class="form-control form_submit_return" required value="'.(isset($_GET['export'])?((date('H',strtotime($added))>5 AND date('H',strtotime($added))<18)?substr($_GET['export'],0,3):$_GET['export']):'').'"/></div>
							</div>
							<div class="form-group col-lg-2">
								<label>Type</label>
								<div><select class="form-control select" name="start">
									<option value="0" selected>Non</option>
									<option value="1">Start</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label></label>
								<div><button type="button" class="btn btn-primary waves-effect waves-light form_submit_click">Add</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
    </div>';
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table class="customized_table table table-sm" table="products" style="width:100%;" data-button="true" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th class="center">Date</th><th class="right">Import</th><th class="right">Export</th></tr></thead><tbody>';//
					$result=mysqli_query($con,"SELECT * FROM electricity WHERE added>=(SELECT max(added) FROM electricity WHERE start='1')  AND 
					(time(added)='00:00:00' OR added=(SELECT max(added) FROM electricity WHERE start='1')) ORDER BY added ASC");
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td class="center">'.date('d-m-Y',strtotime($row['added'])).'</td>
							<td class="right popup" href="'.$remote_file.'/products&form=product&id='.$row['id'].'">'.$row['import'].'</td>
							<td class="right">'.$row['export'].'</td>
						</tr>';
					}
				echo '</tbody>
				</table>
				</div>
			</div>
		</div>
	</div>';
	// echo '<div class="grid_12">
		// <div class="widget_wrap">
			// <div class="widget_top"><span class="h_icon list "></span><h6>Electricity Reading</h6></div>
			// <div class="widget_content">
				// <table class="display tbl" table="electricity" rows="100">
					// <thead><tr><th>#</th><th>Import</th><th></th><th>Export</th><th></th><th>Time</th><th>Ses.Unit</th><th>Solar</th><th>Usage</th><th>Ses.Time</th>
					// <th>Per.Hour</th><th>Units</th><th>Amount</th><th>Action</th></tr></thead><tbody>';
					// $result=mysqli_query($con,"SELECT id,max(added) start FROM electricity WHERE start='1'");
					// $row=mysqli_fetch_array($result);
					// $q="";
					// if($detailed==0)$q=" AND (time(added)='00:00:00' OR added='$row[start]')";
					// $result=mysqli_query($con,"SELECT * FROM electricity WHERE added>='$row[start]' AND start<>2 $q ORDER BY added ASC");
					// $counter=1;
					// $start=array();
					// $last=array();
					// $session=array();
					// $units=0;
					// $href=$config_url.'remote.php?plugin=&act=ajax';
					// while($row=mysqli_fetch_array($result)){
						// if($row['start']==1){
							// $start['export']=$row['export'];
							// $start['import']=$row['import'];
							// $start['time']=$row['added'];
							// $last['export']=$row['export'];
							// $last['import']=$row['import'];
							// $last['time']=$row['added'];
						// }
						// $export=$row['export']-$start['export'];
						// $import=$row['import']-$start['import'];
						// $session['export']=$row['export']-$last['export'];
						// $session['import']=$row['import']-$last['import'];
						// $mins=(strtotime($row['added'])-strtotime($last['time']))/60;
						// $units_per_hour=0;
						// if($mins>0)$units_per_hour=(($session['import']-$session['export'])/$mins)*60;
						// $date=date('d-m-Y',strtotime('-1 day',strtotime($row['added'])));
						// $solar='';
						// if($detailed==1)$date=date('d-m-Y H:i',strtotime($row['added']));
						// else {
							// $date1=date('Y-m-d',strtotime($date));
							// $result2=mysqli_query($con,"SELECT * FROM electricity WHERE date(added)='$date1' AND start=2");
							// if(mysqli_affected_rows($con)>0){
								// $row2=mysqli_fetch_array($result2);
								// $solar=$row2['import'];
							// }
						// }
						// echo'<tr class="grade'.($row['start']==1?'X':'A').'" id="'.$row['id'].'" style="cursor:pointer;">
							// <td class="center">'.$counter++.'</td>
							// <td class="'.($row['start']==1?'':'editable').' right" field="import">'.$row['import'].'</td>
							// <td class="right">'.number_format($session['import'],2).'</td>
							// <td class="'.($row['start']==1?'':'editable').' right" field="export">'.$row['export'].'</td>
							// <td class="right">'.number_format($session['export'],2).'</td>
							// <td class="center">'.$date.'</td>
							// <td class="pop right" href="'.$href.'&form=electricity_more&date='.date('Y-m-d',strtotime($date)).'">'.number_format($session['import']-$session['export'],2).'</td>
							// <td class="right">'.number_format($solar,2).'</td>
							// <td class="right">'.number_format(($solar+$session['import']-$session['export']),2).'</td>
							// <td class="right">'.time_elapsed_string($row['added'],$last['time'],1).'</td>
							// <td class="right">'.number_format($units_per_hour,2).'</td>
							// <td class="right">'.number_format($import-$export,2).'</td>
							// <td class="right">'.number_format((($session['import']-$session['export'])*22.85),2).'</td>
							// <td class="center">'.($_SESSION['user_id']==1?'<a class="action-icons c-delete" field="delete">Delete</a>':'').'</td>
						// </tr>';
						// $last['export']=$row['export'];
						// $last['import']=$row['import'];
						// $last['time']=$row['added'];
						// $units=($import-$export);
					// }
					// $days=ceil((time()-strtotime($start['time']))/86400);
					// $amount=(240+($units*22.85));
					// echo '</tbody>
					// <tfoot><tr><td></td><td colspan="10">Amount</td><td class="right">'.number_format($amount,2).'</td><td class="right">'.number_format(($amount/$days)*31,2).'</td></tr></tfoot>
				// </table>
			// </div>
		// </div>
	// </div><span class="clear"></span>';
?>
<script>
$('#import').focus();
</script>
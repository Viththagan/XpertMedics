<?php
	if(isset($_GET['search']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	// print_r($_GET);
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Status</label>
								<div><select class="form-control select form_submit_change" name="status">';
									if(!isset($_GET['status']))$_GET['status']=-1;
									foreach($bill_payment_status as $value=>$title)echo '<option value="'.$value.'" '.($_GET['status']==$value?'selected':'').'>'.$title.'</option>';
									echo '<option '.($_GET['status']==-1?'selected':'').' value="-1" >All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Type</label>
								<div><select class="form-control select form_submit_change" name="type">';
								if(!isset($_GET['type']))$_GET['type']=-1;
								$result=mysqli_query($con,"SELECT * FROM bill_payments_domain WHERE status='1'");
								while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
								echo '<option '.($_GET['type']==-1?'selected':'').' value="-1" >All</option>title
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
	$q='';
	if($_GET['from']<>'' OR $_GET['to']<>''){
		if($_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$q.=" AND b.date='$to'";
		}
		else if($_GET['to']==''){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$q.=" AND b.date='$from'";
		}
		else{
			$to=date('Y-m-d',strtotime($_GET['to']));
			$from=date('Y-m-d',strtotime($_GET['from']));
			$q.=" AND b.date BETWEEN '$from' AND '$to'";
		}
	}
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$q.=" AND (b.number LIKE '%$_GET[search]%'";
		$arr=explode(',',$_GET['search']);
		foreach($arr as $key){
			$q.=" OR b.number LIKE '%$key%'";
		}
		$q.=")";
	
	}
	if(isset($_GET['status']) AND $_GET['status']>=0){
		$q.=" AND b.status='$_GET[status]'";
	}
	if(isset($_GET['type']) AND $_GET['type']>=0){
		$q.=" AND b.type='$_GET[type]'";
	}
	
	echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
				<table class="customized_table table table-sm" table="bill_payments" style="width:100%;" data-button="true" data-sum="9,10,11,12" data-hide-columns="Till,Time,Customer,Type,Reference,Status,Staff" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th>Till</th><th>'.language('Date').'</th><th>'.language('Time').'</th>
					<th>'.language('Customer').'</th><th>'.language('Type').'</th><th>'.language('Number').'</th><th>'.language('Reference').'</th>
					<th>'.language('Status').'</th><th>'.language('Amount').'</th><th>'.language('Charge').'</th><th>'.language('Profit').'</th>
					<th>'.language('Cost').'</th><th>'.language('Balance').'</th><th>'.language('Staff').'</th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT b.*,d.title,s.till,IF(b.type=0,CONCAT(c.mobile,'<br/>',c.fname,' ',c.lname),IF(b.type=2,CONCAT(c1.mobile,'<br/>',c1.fname,' ',c1.lname),CONCAT('Staff<br/>',u.first_name))) name,
					IF(b.type=2,c1.fname,u.first_name) user,br.title branch
					FROM bill_payments b LEFT JOIN products_logs l ON (b.log=l.id AND b.type=0) LEFT JOIN sales s ON l.referrer=s.id 
					LEFT JOIN customers c ON c.id=s.customer LEFT JOIN customers c1 ON (c1.id=b.user AND b.type=2) 
					LEFT JOIN users u ON (u.id=b.user AND b.type<2) LEFT JOIN bill_payments_domain d ON d.id=b.service LEFT JOIN branches br ON b.branch=br.id
					WHERE b.branch='$_SESSION[branch]' $q ORDER BY b.id ASC ") or die(mysqli_error($con));
					$counter=1;
					$status_class=array(0=>'',1=>'pending_invoice',2=>'',3=>'warning',4=>'pending_invoice');
					$sum=array('total'=>0,'charge'=>0,'commission'=>0,'cost'=>0);
					while($row=mysqli_fetch_array($result)){
						$cost=($row['amount']+$row['charge']-$row['commission']);
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.$status_class[$row['status']].'">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['till'].'</td>
							<td class="center">'.date('d-m-Y',strtotime($row['added'])).'</td>
							<td class="center">'.date('h:i A',strtotime($row['added'])).'</td>
							<td>'.$row['name'].'</td>
							<td>'.$row['title'].'</td>
							<td>'.$row['number'].'</td>
							<td class="center">'.$row['reference'].'</td>
							<td>'.$bill_payment_status[$row['status']].'</td>
							<td class="right">'.Number_format($row['amount'],2).'</td>
							<td class="right">'.Number_format($row['charge'],2).'</td>
							<td class="right">'.Number_format($row['commission'],2).'</td>
							<td class="right">'.($row['status']==2?Number_format($cost,2):'').'</td>
							<td class="right">'.Number_format($row['balance'],2).'</td>
							<td>'.$row['user'].'</td>
						</tr>';
						$sum['total']+=($row['status']==2?$row['amount']:0);
						$sum['charge']+=$row['charge'];
						$sum['commission']+=$row['commission'];
						$sum['cost']+=($row['status']==2?$cost:0);
					}
				echo '</tbody><tfoot>
            <tr>
                <th></th><th></th>
                <th colspan="7">Page Total</th><th class="right"></th>
                <th class="right"></th><th class="right"></th><th class="right"></th><th></th><th></th>
            </tr>
           <tr>
                <th></th><th></th>
                 <th colspan="7">Total</th><th class="right">'.Number_format($sum['total'],2).'</th>
                <th class="right">'.Number_format($sum['charge'],2).'</th><th class="right">'.Number_format($sum['commission'],2).'</th>
				<th class="right">'.Number_format($sum['cost'],2).'</th><th></th><th></th>
            </tr>
        </tfoot></table>
				</div>
			</div>
		</div>
	</div>';
?>
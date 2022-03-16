<?php
	 if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
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
							<div class="form-group col-lg-2">
								<label>Membership</label>
								<div><select class="form-control select" name="member[]" multiple="multiple" multiple>';
									foreach($memberships as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['member']) AND in_array($type,$_GET['member']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=100;
									foreach($values as $value)echo '<option value="'.$value.'" '.((isset($_GET['limit']) AND $_GET['limit']==$value)?'selected':'').'>'.$value.'</option>';
									echo '<option value="0">All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Mask</label>
								<div><select class="form-control select form_submit_change" name="mask">';
									if(!isset($_GET['mask']))$_GET['mask']='-1';
									$sms_mask_temp=$sms_mask;
									foreach($sms_mask_temp as $i=>$title)echo '<option value="'.$i.'" '.($_GET['mask']==$i?'selected':'').'>'.$title.'</option>';
									echo '<option value="-1" '.($_GET['mask']==-1?'selected':'').'>All</option>
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
	$date=date('Y-m-d',strtotime($_GET['from']));
	$where="WHERE date(s.messaged)='$date' ";
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE date(s.messaged) BETWEEN '$from' AND '$to' ";
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE date(s.messaged)='$to' ";
		}
	}
	
	
	if(isset($_GET['mask']) AND $_GET['mask']>=0){
		// if($_GET['mask']>3){
			// $where.=" AND s.method=1";
			// if($_GET['mask']==4)$where.=" AND s.mask='0'";
			// else $where.=" AND s.mask>0";
		// }
		// if($_GET['mask']==4)$where.=" AND s.mask='0' AND s.method=1";
		// else if($_GET['mask']==5)$where.=" AND s.mask>0 AND s.method=1";
		// else $where.=" AND s.mask='$_GET[mask]' AND s.method=0";
		switch($_GET['mask']){
			case 4:
				$where.=" AND s.mask='0' AND s.method=1";
				break;
			case 5:
				$where.=" AND s.mask>0 AND s.method=1";
				break;
			default :
				$where.=" AND s.mask='$_GET[mask]' AND s.method=0";
				break;
		}
	}
	if(isset($_GET['member']) AND sizeof($_GET['member'])>0){
		$member=implode(',',$_GET['member']);
		$where.=" AND c.plat IN($member)";
	}
	
	
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (s.message LIKE '%$_GET[search]%' OR c.mobile LIKE '%$_GET[search]%' OR c.nic LIKE '%$_GET[search]%' OR c.fname LIKE '%$_GET[search]%' OR c.lname LIKE '%$_GET[search]%' OR c.name_ta LIKE '%$_GET[search]%' OR c.street LIKE '%$_GET[search]%' 
		OR c.address LIKE '%$_GET[search]%' OR c.remind LIKE '%$_GET[search]%'";
		$keys=explode(' ',$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.="s.message LIKE '%$_GET[search]%' OR c.mobile LIKE '%$key%' OR c.nic LIKE '%$key%' c.fname LIKE '%$key%' OR c.lname LIKE '%$key%' OR c.name_ta LIKE '%$key%' OR c.street LIKE '%$key%' OR c.address LIKE '%$key%' OR c.remind LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	
	$limit="LIMIT 0,100";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	$q="SELECT s.*,c.fname FROM sms_sent s LEFT JOIN customers c ON s.number=c.mobile $where ORDER BY s.messaged DESC $limit";
	echo'<div class="row"  style="font-size: 12px;">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table table="sms_sent" style="width:100%;" data-button="true" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" class="table table-sm">
					<thead><tr><th>#</th><th class="center">Date</th><th>Customer</th><th>Message</th><th>ID</th><th class="right">Cost</th><th class="right">Balance</th></tr></thead><tbody>';
					$result=mysqli_query($con,$q) or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_assoc($result)){
						$type='0774308010';
						if($row['method']==0)$type=$sms_mask[$row['mask']];
						else if($row['mask']>0)$type='0764308010';
						echo'<tr class="'.($row['promo']==1?'warning2':'').' '.($row['status']!=1?'warning':'').'"><td>'.$counter++.'</td>
						<td class="center">'.$row['messaged'].'</td>
						<td>'.$row['number'].'<br/>'.$row['fname'].'</td>
						<td class="'.($row['type']==1?'right':'').'">'.$row['message'].'</td>
						<td>'.$type.'</td>
						<td class="right">'.$row['cost'].'</td>
						<td class="right">'.$row['balance'].'</td>
						</tr>';
					}
				echo'</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>';
?>
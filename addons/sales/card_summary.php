<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// echo '<pre>';print_r($_SESSION);
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		return;
	}
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
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
										echo '<input type="text" class="form-control form_submit_change date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control form_submit_change date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label>POS Machine</label>
								<div><select class="form-control select" name="pos_machine[]" multiple="multiple" multiple>';
									foreach($pos_machines as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['pos_machine']) AND in_array($type,$_GET['pos_machine']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Till</label>
								<div><select class="form-control select form_submit_change" name="till">';
									if(!isset($_GET['till']))$_GET['till']='';
									$result=mysqli_query($con,"SELECT * FROM sales_counters WHERE status='1' ORDER BY id ASC");
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
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=1000;
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
	$where="WHERE sp.date='$date' AND s.branch='$_SESSION[branch]'";
	$view_date=0;
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE sp.date BETWEEN '$from' AND '$to' ";
			$view_date=1;
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE sp.date='$to' ";
			$view_date=0;
		}
	}
	if(isset($_GET['pos_machine']) AND sizeof($_GET['pos_machine'])>0){
		$types=implode(',',$_GET['pos_machine']);
		$where.=" AND sc.bank IN($types)";
	}
	if(isset($_GET['type']) AND $_GET['type']<>'All')$where.=" AND sc.status='$_GET[type]'";
	if(isset($_GET['till']) AND is_numeric($_GET['till']))$where.=" AND s.till='$_GET[till]'";
	
	
	
	// if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		// $where.=" AND (c.mobile LIKE '%$_GET[search]%' OR c.nic LIKE '%$_GET[search]%' OR c.fname LIKE '%$_GET[search]%' OR c.lname LIKE '%$_GET[search]%' OR c.name_ta LIKE '%$_GET[search]%' OR c.street LIKE '%$_GET[search]%' 
		// OR c.address LIKE '%$_GET[search]%' OR c.remind LIKE '%$_GET[search]%'";
		// $keys=explode(' ',$_GET['search']);
		// foreach($keys as $key){
			// if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				// $where.="c.mobile LIKE '%$key%' OR c.nic LIKE '%$key%' c.fname LIKE '%$key%' OR c.lname LIKE '%$key%' OR c.name_ta LIKE '%$key%' OR c.street LIKE '%$key%' OR c.address LIKE '%$key%' OR c.remind LIKE '%$key%'";
			// }
		// }
		// $where.=") ";
	// }
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	{
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table class="customized_table table-sm" table="sales_card" style="width:100%;" data-button="true" data-sum="4" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Till').'</th><th>'.language('POS').'</th><th>'.language('Amount').'</th><th>'.language('Staff').'</th><th></th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT sp.date,sp.added,ss.till,sc.*,u.first_name FROM sales_card sc LEFT JOIN sales_payments sp ON (sp.child=sc.id AND sp.type=4) 
					LEFT JOIN sales s ON s.id=sp.sales LEFT JOIN customers c ON c.id=s.customer LEFT JOIN sales_session ss ON ss.id=sp.session LEFT JOIN users u ON u.id=sp.user $where $limit") or die(mysqli_error($con));
					$counter=1;
					$total=0;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td class="center">'.$row['date'].', '.date('h:i A',strtotime($row['added'])).'</td>
							<td class="">'.$row['till'].'</td>
							<td class="popup_select" value="'.$row['bank'].'" data-toggle="modal" data-target=".bank">'.$pos_machines[$row['bank']].'</td>
							<td class="right editable" field="amount">'.Number_format($row['amount'],2).'</td>
							<td>'.$row['first_name'].'</td>
							<td class="center pointer">'.($row['status']==0?'<i class="fa fa-check fa-1x boolean_check" field="status" value="1"></i>':'').'</td>
						</tr>';
						$total+=$row['amount'];
					}
				echo '</tbody><tfoot><tr><td></td><td colspan="3">Page Total</td>
					<td class="right"></td><td colspan="2"></td>
					</tr><tr><td></td><td colspan="3">Total</td>
					<td class="right">'.Number_format($total,2).'</td><td colspan="2"></td>
					</tr></tfoot></table>
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
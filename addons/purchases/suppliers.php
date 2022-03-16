<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if($_POST['edit']=='products_company' AND $value<>'' AND $value<>'None' AND $_POST['id']==0){
			mysqli_query($con,"INSERT INTO products_company(title) VALUES('$value')");
			$id=mysqli_insert_id($con);
			mysqli_query($con,"UPDATE suppliers SET company='$id' WHERE id='$_POST[field]'");
		}
		else if($_POST['id']>0)mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['form_submit'])){
		mysqli_query($con,"INSERT INTO suppliers(title) VALUES('$_POST[title]')");
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'add_supplier':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Title</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="title" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_supplier"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Supplier</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'supplier':
				$supplier['id']=0;
				if(isset($_GET['id']) AND $_GET['id']>0){
					$result=mysqli_query($con,"SELECT * FROM suppliers WHERE id='$_GET[id]'");
					$supplier=$result->fetch_assoc();
				}
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<div class="row">
							<div class="col-12 table" table="suppliers" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
								<div class="card">
									<div class="card-body">';//print_r($_GET);
										echo '<h4 class="mt-0 header-title">'.$supplier['title'].'</h4>
										<p class="text-muted mb-4"></p>';
										echo '<div class="form-group row">
													<label class="col-sm-2 col-form-label">Title</label>
													<div class="col-sm-10"><span class="editable form-control" field="title" id="'.$supplier['id'].'">'.$supplier['title'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Address:</label>
														<div class="col-sm-10"><span class="editable form-control" field="address" id="'.$supplier['id'].'">'.$supplier['address'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Phone:</label>
														<div class="col-sm-10"><span class="editable form-control" field="phone" id="'.$supplier['id'].'">'.$supplier['phone'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Cash Collector:</label>
														<div class="col-sm-10"><span class="editable form-control" field="cash_collector" id="'.$supplier['id'].'">'.$supplier['cash_collector'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Cheque Name:</label>
														<div class="col-sm-10"><span class="editable form-control" field="cheque_name" id="'.$supplier['id'].'">'.$supplier['cheque_name'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Credit :</label>
														<div class="col-sm-10"><span class="editable form-control" field="credit_period" id="'.$supplier['id'].'">'.$supplier['credit_period'].'</span></div>
												</div>
												<div class="form-group row">
													<label class="col-sm-2 col-form-label">Visits :</label>
														<div class="col-sm-10"><span class="editable form-control" field="visit_interval" id="'.$supplier['id'].'">'.$supplier['visit_interval'].'</span></div>
												</div>
											
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';
				break;
		}
		return;
	}
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-4">
								<label>Search</label>
								<div><input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/></div>
							</div>
							<div class="form-group col-lg-2">
								<label>Status</label>
								<div><select class="form-control select form_submit_change" name="status">';
									if(!isset($_GET['status']))$_GET['status']=-1;
									foreach($supplier_status as $value=>$title)echo '<option value="'.$value.'" '.($_GET['status']==$value?'selected':'').'>'.$title.'</option>';
									echo '<option value="-1" '.($_GET['status']==-1?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=0;
									foreach($values as $value)echo '<option value="'.$value.'" '.($_GET['limit']==$value?'selected':'').'>'.$value.'</option>';
									echo '<option value="0" '.($_GET['limit']==0?'selected':'').'>All</option>
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
	$where="WHERE id>0 ";
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (title LIKE '%$_GET[search]%'";
		$keys=explode(' ',$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.=" OR title LIKE '%$key%' ";
			}
		}
		$where.=") ";
	}
	if($_GET['status']>=0)$where.=" AND status='$_GET[status]'";
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	echo '<span id="po">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<table class="customized_table table-sm" table="suppliers" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('Credit').'</th><th>'.language('Visits').'</th></tr></thead><tbody>';
									$result=mysqli_query($con,"SELECT * FROM suppliers $where $limit") or die(mysqli_error($con));
									$counter=1;
									while($row=mysqli_fetch_assoc($result)){
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="popup" href="'.$remote_file.'/suppliers&form=supplier&id='.$row['id'].'&callback=">'.($row['title']==''?$row['company']:$row['title'].(!is_numeric($row['company'])?' - '.$row['company']:'')).'</td>
											<td class="center">'.$row['credit_period'].'</td>
											<td class="center">'.$row['visit_interval'].'</td>
										</tr>';
									}
							echo '</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</span>';
?>
<script>
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$(".plus").attr('href',$(".plus").attr('o-href')+'&code='+$('.product').val()).trigger('click');
		return false;
	});
	$.add_table_button('purchases','suppliers','Add a Supplier','suppliers', 'add_suppliers','', '&form=add_supplier');
</script>
<span class="hide popup plus" o-href="<?php echo $remote_file.'/suppliers&form=add_supplier';?>"></span>
<style>
.table > tbody > tr > td, .table > tfoot > tr > td, .table > thead > tr > td {padding: 0px 5px;}
</style>
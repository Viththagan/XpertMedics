<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='po'){
			mysqli_query($con,"DELETE FROM products_logs_temp WHERE referrer='$_POST[id]' AND type='1'")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM po WHERE id='$_POST[id]'")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM products_logs WHERE referrer='$_POST[id]' AND type='1'")or die(mysqli_error($con));
			$result=mysqli_query($con,"SELECT * FROM po_payments WHERE po='$_POST[id]'")or die(mysqli_error($con));
			while($row=mysqli_fetch_assoc($result)){
				mysqli_query($con,"DELETE FROM payments WHERE id='$row[payment]'")or die(mysqli_error($con));
				mysqli_query($con,"DELETE FROM payments_cheques WHERE payment='$row[payment]'")or die(mysqli_error($con));
				mysqli_query($con,"DELETE FROM po_payments WHERE payment='$_POST[payment]'")or die(mysqli_error($con));
			}
			
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
							<div class="form-group col-lg-2">
								<label>PO#</label>
								<div><input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/></div>
							</div>
							<div class="form-group col-lg-3">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										// if(!isset($_GET['to']))$_GET['to']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Supplier(s)</label>
								<div><select class="form-control select" name="supplier[]" multiple="multiple" multiple>';
									$supplier=mysqli_query($con,"SELECT * FROM suppliers");
									while($row=mysqli_fetch_assoc($supplier)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['supplier']) AND in_array($row['id'],$_GET['supplier']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Company(s)</label>
								<div><select class="form-control select" name="company[]" multiple="multiple" multiple>';
									$company=mysqli_query($con,"SELECT * FROM products_company");
									while($row=mysqli_fetch_assoc($company)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['company']) AND in_array($row['id'],$_GET['company']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Status</label>
								<div><select class="form-control select" name="status">';
									if(!isset($_GET['status']))$_GET['status']=0;
									$po_status_temp=$po_status;
									unset($po_status_temp[3]);
									unset($po_status_temp[4]);
									foreach($po_status_temp as $value=>$title)echo '<option value="'.$value.'" '.($_GET['status']==$value?'selected':'').'>'.$title.'</option>';
									echo '<option value="All" '.($_GET['status']=='All'?'selected':'').'>All</option>
									</select>
								</div>
							</div>
							<div class="form-group col-lg-1"><label>Payment</label>
								<div><select class="form-control select form_submit_change" name="payment">';
									if(!isset($_GET['payment']))$_GET['payment']=0;
									$payment=array(0=>'Non',1=>'Paid','All'=>'All');
									foreach($payment as $value=>$title)echo '<option value="'.$value.'" '.($_GET['payment']==$value?'selected':'').'>'.$title.'</option>';
									echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=100;
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
	$where="WHERE p.branch='$_SESSION[branch]' ";
	if(isset($_GET['from'])){
		if($_GET['from']<>'' AND isset($_GET['to']) AND $_GET['to']<>''){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where.="AND p.date BETWEEN '$from' AND '$to' ";
		}
		else if($_GET['from']<>'' OR (isset($_GET['to']) AND $_GET['to']<>'')){
			if($_GET['from']<>''){
				$from=date('Y-m-d',strtotime($_GET['from']));
				$where.="AND p.date='$from' ";
			}
			else if(isset($_GET['to']) AND $_GET['to']<>''){
				$to=date('Y-m-d',strtotime($_GET['to']));
				$where.="AND p.date='$to' ";
			}
		}
	}
	if(isset($_GET['search']) AND strlen(trim($_GET['search']))>3){
		$keys=explode(',',trim($_GET['search']));
		$where.=" AND (p.id='$_GET[search]'";
		foreach($keys as $key){
			$key=trim($key);
			if(is_numeric($key)){
				$where.=" OR p.id='$key'";
			}
		}
		$where.=")";
	}
	if(isset($_GET['supplier']) AND sizeof($_GET['supplier'])>0){
		$suppliers=implode(',',$_GET['supplier']);
		$where.=" AND p.supplier IN($suppliers)";
	}
	if(isset($_GET['company']) AND sizeof($_GET['company'])>0){
		$suppliers=implode(',',$_GET['company']);
		$where.=" AND p.company IN($suppliers)";
	}
	if($_GET['status']!='All')$where.=" AND p.status='$_GET[status]'";
	$having='';
	if($_GET['payment']!='All'){
		if($_GET['payment']==0)$having=" HAVING paid is NULL";
		else $having=" HAVING paid is NOT NULL";
	}
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	echo '<span id="po">
		<div class="row" style="font-size: 13px;">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
					<div class="table-rep-plugin">
						<div class="table-responsive mb-0" data-pattern="priority-columns">
						<table class="customized_table table table-sm" table="po" style="width:100%;" data-button="true" data-sum="4,5,6,7" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th>#</th><th data-priority="1">'.language('ID').'</th>
						<th data-priority="1">'.language('Date').'</th><th data-priority="1">'.language('Supplier').'</th>
							<th data-priority="1">'.language('Total').'</th><th data-priority="3">'.language('Disc').'</th><th data-priority="1">'.language('Paid').'</th>
							<th data-priority="3">'.language('Bal').'</th><th data-priority="6">'.language('Status').'</th>
							<th data-priority="3"></th>'.(in_array('delete',$permission)?'<th data-priority="6"></th>':'').($_SESSION['soft']['user_data']['user_level']==0?'<th data-priority="6"></th>':'').'
							</tr></thead><tbody>';
									if($_SESSION['soft']['user_data']['mobile']=='0776193405')$where.=" AND p.company='179'";
									$result=mysqli_query($con,"SELECT p.*,s.title supplier,c.title company,
									(SELECT SUM(qty*purchased) FROM products_logs WHERE type='1' AND referrer=p.id) total,
									(SELECT SUM(amount) FROM po_payments WHERE po=p.id) paid
									FROM po p LEFT JOIN suppliers s ON s.id=p.supplier LEFT JOIN products_company c ON c.id=p.company $where $having ORDER BY p.date ASC $limit") or die(mysqli_error($con));
									$counter=1;
									$sum=array('total'=>0,'discount'=>0,'cost'=>0,'paid'=>0,'balance'=>0,);
									while($row=mysqli_fetch_assoc($result)){
										$class='';
										if($row['status']==0)$class='editable';
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="center" title="'.$row['invoice'].'"><a href="#purchases/new%20purchase\\'.$row['id'].'">'.$row['id'].'</a></td>
											<td class="'.$class.'" field="date">'.date('d-m-Y',strtotime($row['date'])).'</td>
											<td>'.$row['supplier'].'<br/>'.$row['company'].'</td>
											<td class="right" field="total">'.number_format($row['total'],2).'</td>
											<td class="right '.$class.'" field="discount">'.number_format($row['discount'],2).'</td>
											<td class="right">'.number_format($row['paid'],2).'</td>
											<td class="right">'.number_format($row['total']-$row['discount']-$row['paid'],2).'</td>
											<td class="right">'.$po_status[$row['status']].'</td>
											<td><div class="zoom-gallery">'.list_images($row['img'],$upload_path.'/purchases/',($row['company']<>''?$row['company']:$row['supplier']).' ['.$row['id'].']').'</div>
											'.(($row['status']==0 || $row['status']==1)?'<div path="'.$upload_path.'/purchases/" style="float: right;" class="dropzone" query="UPDATE po SET img=CONCAT(IFNULL(img,\'\'),\',[image]\') WHERE id=\''.$row['id'].'\'"></div>':'').'
											</td>
											'.(in_array('delete',$permission)?'<td>'.($row['status']==-1?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>':'').'
											'.($_SESSION['soft']['user_data']['user_level']==0?'<td class="center pointer">'.($row['status']==1?'<i class="fa fa-check fa-1x boolean_check" field="status" value="2"></i>':'').'</td>':'').'
										</tr>';
										$sum['total']+=$row['total'];
										$sum['discount']+=$row['discount'];
										$sum['paid']+=$row['paid'];
									}
							echo '</tbody><tfoot><tr><td colspan="3"></td><td>Page Total</td>
							<td class="right"></td>
							<td class="right"></td>
							<td class="right"></td>
							<td class="right"></td>
							<td colspan="3"></td>
							</tr><tr><td colspan="3"></td><td>Total</td>
							<td class="right">'.number_format($sum['total'],2).'</td>
							<td class="right">'.number_format($sum['discount'],2).'</td>
							<td class="right">'.number_format($sum['paid'],2).'</td>
							<td class="right">'.number_format($sum['total']-$sum['paid'],2).'</td>
							<td colspan="3"></td>
							</tr></tfoot>
						</table>
					</div>
					</div>
					</div>
				</div>
			</div>
		</div>
	</span>';
?>
<style>
.dropzone .dz-message{
	margin:0px !important;
	content:'' !important;
}
.dropzone {
    height: 50px;
    border: 2px solid rgba(0, 0, 0, 0.3);
	width: 20px !important;
    min-height: 50px !important;
	padding: 2px 2px;
}
</style>
<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_GET['form'])){
		if($_GET['form']=='history'){
			echo '<div class="card col-lg-4 mx-auto">
				<div class="card-body">
					<table class="table mb-0 form-group"><thead><tr><th>Title</th><th>Qty</th><th>MRP</th><th>Selling</th><th>Purchased</th><th>Sticker</th></tr></thead><tbody>';
			$result=mysqli_query($con,"SELECT l.*,p.title FROM products p LEFT JOIN products_logs l ON l.product=p.id WHERE l.type=3 AND l.referrer='$_GET[id]'")or die(mysqli_error($con));
			while($row=mysqli_fetch_array($result)){
				echo '<tr class="pointer print_barcode" id="'.$row['id'].'"><td>'.$row['title'].'</td><td>'.($row['qty']+0).'</td><td>'.$row['mrp'].'</td><td>'.$row['selling_price'].'</td><td>'.$row['purchased'].'</td><td>'.ceil($row['qty']/2).'</td></tr>';
			}
			echo '</tbody></table>
				</div>
			</div>';
		}
		else if($_GET['form']=='raw'){
			echo '<div class="card col-lg-4 mx-auto">
				<div class="card-body">
					<table class="table mb-0 form-group"><thead><tr><th>Title</th><th>Qty</th><th>MRP</th><th>Selling</th><th>Purchased</th><th>Sticker</th></tr></thead><tbody>';
			$result=mysqli_query($con,"SELECT l.*,p.title FROM products p LEFT JOIN products_logs l ON l.product=p.id WHERE l.type=4 AND l.referrer='$_GET[id]'")or die(mysqli_error($con));
			while($row=mysqli_fetch_array($result)){
				echo '<tr class="pointer print_barcode" id="'.$row['id'].'"><td>'.$row['title'].'</td><td>'.($row['qty']+0).'</td><td>'.$row['mrp'].'</td><td>'.$row['selling_price'].'</td><td>'.$row['purchased'].'</td><td>'.ceil($row['qty']/2).'</td></tr>';
			}
			echo '</tbody></table>
				</div>
			</div>';
		}
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='yura_batch_history'){
			mysqli_query($con,"DELETE FROM products_logs WHERE referrer='$_POST[id]' AND type in(3,4)")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM yura_batch_history WHERE id='$_POST[id]'")or die(mysqli_error($con));
		}
		return;
	}
	if(isset($_GET['code']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Title Search</label>
								<div>
								<input type="text" name="title" class="form-control form_submit_change" required placeholder="Search" value="'.(isset($_GET['title'])?$_GET['title']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Code</label>
								<div>
								<input type="text" name="code" class="form-control form_submit_change" required placeholder="Search" value="'.(isset($_GET['code'])?$_GET['code']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									if(!isset($_GET['limit']))$_GET['limit']=100;
									$values=array(100,500,1000,2000,5000,10000);
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
	if($_SESSION['user_level']==0)$where="WHERE y.id>0 ";
	else $where="WHERE y.status=1 ";
	// if(isset($_GET['title']) AND strlen($_GET['title'])>2){
		// $where.=" AND (p.title LIKE '%$_GET[title]%' OR p.title_tamil LIKE '%$_GET[title]%' OR p.title_sinhala LIKE '%$_GET[title]%' OR p.remind LIKE '%$_GET[title]%'";
		// $keys=explode(' ',$_GET['title']);
		// foreach($keys as $key){
			// if(strlen(trim($key))>2 AND trim($_GET['title'])<>trim($key)){
				// $where.="OR p.title LIKE '%$key%' OR p.title_tamil LIKE '%$key%' OR p.title_sinhala LIKE '%$key%' OR p.remind LIKE '%$key%'";
			// }
		// }
		// $where.=") ";
	// }
	// if(isset($_GET['code']) AND strlen($_GET['code'])>2){
		// $where.=" AND (p.id LIKE '%$_GET[code]%' OR p.ui_code LIKE '%$_GET[code]%' OR p.bulk_code LIKE '%$_GET[code]%'";
		// $keys=explode(' ',$_GET['code']);
		// foreach($keys as $key){
			// if(strlen(trim($key))>2 AND trim($_GET['code'])<>trim($key)){
				// $where.="OR p.id LIKE '%$key%' OR p.ui_code LIKE '%$key%' OR p.bulk_code LIKE '%$key%'";
			// }
		// }
		// $where.=") ";
	// }
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table table="yura_batch_history" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'" id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Raw Products').'</th><th>'.language('Products').'</th><th>'.language('Action').'</th>
					</tr></thead><tbody>';//
					$this_start=date('Y-m-01');
					$this_end=date('Y-m-t');
					$last_start=date('Y-m-01',strtotime('-1 month'));
					$last_end=date('Y-m-t',strtotime('-1 month'));
					$result=mysqli_query($con,"SELECT y.*,
					(SELECT GROUP_CONCAT(p.title SEPARATOR ',<br/>') FROM products p LEFT JOIN products_logs l ON l.product=p.id WHERE l.type=3 AND l.referrer=y.id) products, 
					(SELECT GROUP_CONCAT(p.title SEPARATOR ',<br/>') FROM products p LEFT JOIN products_logs l ON l.product=p.id WHERE l.type=4 AND l.referrer=y.id) raw 
					FROM yura_batch_history y $where ORDER BY y.date DESC LIMIT 0,$_GET[limit]")or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" class="yura_batch_history" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['date'].'</td>
							<td class="popup" href="'.$remote_file.'/view%20history&form=raw&id='.$row['id'].'">'.$row['raw'].'</td>
							<td class="popup" href="'.$remote_file.'/view%20history&form=history&id='.$row['id'].'">'.$row['products'].'</td>
							<td class="center">'.(in_array('delete',$permission)?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
						</tr>';
					}
				echo '<tbody></table>
				</div>
			</div>
		</div>
	</div>';
	if (strpos(dirname(__FILE__),'/') !== false)$folders=explode('/',dirname(__FILE__));
	else $folders=explode('\\',dirname(__FILE__));
	$plugin=end($folders);
	echo '<script>
	plugin="'.$plugin.'";
    remote_file="'.$config_url.'remote.php?file='.$plugin.'/";
	</script>';
?>
<script>
	function after_ajax(){
		$('.print_barcode').click(function(){
			$.post(remote_file+"production",{print: 'print_barcode',id:$(this).attr('id')},function(data){
				$('#printableArea').html(data);
				$('.product_tr').remove();
				// invoice_total(ele)q
				setTimeout(function () {window.print();},50);
			});
		});
	}
</script>
<style>
.table > tbody > tr > td, .table > tfoot > tr > td, .table > thead > tr > td {
    padding: 0px 5px;
}
</style>
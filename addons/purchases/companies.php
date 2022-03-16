<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$value=str_replace("'","\'",$value);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['form_submit'])){
		if(trim($_POST['title'])<>'')mysqli_query($con,"INSERT INTO products_company(title) VALUES('$_POST[title]')");
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'add_company':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Title</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="title" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_company"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Company</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'products_logs':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<table class="table">
							<thead><tr><th>#</th><th>'.language('Product').'</th><th>'.language('Qty').'</th><th>'.language('Cost').'</th></tr></thead><tbody>';
									$result=mysqli_query($con,"SELECT s.date,SUM(l.qty*l.purchased) cost,SUM(l.qty) qty,p.title,p.ui_code 
									FROM `products_logs` l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) LEFT JOIN products p ON p.id=l.product 
									WHERE s.date='$_GET[date]' AND p.company='$_GET[id]' GROUP BY l.product") or die(mysqli_error($con));
									$counter=1;
									$total=0;
									while($row=mysqli_fetch_assoc($result)){
										$total+=$row['cost'];
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td>'.$row['title'].'<br/>'.$row['ui_code'].'</td>
											<td class="right">'.$row['qty'].'</td>
											<td class="right">'.number_format($row['cost'],2).'</td>
										</tr>';
									}
							echo '</tbody><tfoot><tr><td></td><td colspan="2"></td><td class="right">'.number_format($total,2).'</td></tr></tfoot>
						</table>
					</div>
				</div>';
				break;
		}
		return;
	}
	if(isset($_GET['id']) AND $_GET['id']>=0){
		$from=date('Y-m-d',strtotime('-1 year'));
		$to=date('Y-m-d');
		$q="SELECT s.date,SUM(l.qty*l.selling_price) sold,SUM(l.qty*l.purchased) cost,SUM(l.qty) qty 
		FROM `products_logs` l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) LEFT JOIN products p ON p.id=l.product 
		WHERE s.date  BETWEEN '$from' AND '$to' AND p.company='$_GET[id]' GROUP BY s.date";
		echo '<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<table class="customized_table table-sm" style="width:100%;" data-button="true" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Sold').'</th><th>'.language('Cost').'</th>
							<th>'.language('Profit').'</th><th>'.language('Qty').'</th><th>'.language('Balance').'</th></tr></thead><tbody>';
									$result=mysqli_query($con,$q) or die(mysqli_error($con));
									$counter=1;
									$total=0;
									while($row=mysqli_fetch_assoc($result)){
										$total+=$row['cost'];
										echo '<tr style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td>'.$row['date'].'</td>
											<td class="right">'.number_format($row['sold'],2).'</td>
											<td class="right">'.number_format($row['cost'],2).'</td>
											<td class="right">'.number_format($row['sold']-$row['cost'],2).'</td>
											<td class="right popup" href="'.$remote_file.'/companies&form=products_logs&date='.$row['date'].'&id='.$_GET['id'].'&callback=">'.($row['qty']+0).'</td>
											<td class="right">'.number_format($total,2).'</td>
										</tr>';
									}
							echo '</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>';
	}
	else {
		echo '<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body  table-responsive">
						<table class="customized_table table table-sm" table="products_company" style="width:100%;" data-button="true" data-sum="4,5" data-buts="copy,excel,pdf,colvis,add_company" data-hide-columns="Type" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Title').'</th><th>'.language('Credit').'</th><th>'.language('Visit').'</th>
							<th>'.language('Stock').'</th><th>'.language('Payable').'</th><th>'.language('Ratio').'</th><th>'.language('Type').'</th><th></th><th></th></tr></thead><tbody>';
	$result=mysqli_query($con,"SELECT (SELECT ifnull(SUM(ifnull((SELECT SUM(qty*purchased) FROM products_logs WHERE type='1' AND referrer=p2.id),0)-ifnull(p2.discount,0)-ifnull((SELECT SUM(amount) FROM po_payments WHERE po=p2.id),0)),0) FROM `po` p2 WHERE p2.company=c.id AND p2.branch='$_SESSION[branch]') payable,
	ifnull((SELECT SUM(pc.amount) FROM payments_cheques pc LEFT JOIN (SELECT payment,po FROM po_payments WHERE payment is not NULL GROUP BY payment) pp ON pc.payment=pp.payment LEFT JOIN `po` p3 on pp.po=p3.id WHERE p3.date>'2021-01-01' AND pc.status=0 AND p3.company=c.id AND p3.branch='$_SESSION[branch]'),0) cheque,
	c.*,s.stock FROM products_company c LEFT JOIN products_company_stock s ON (s.company=c.id AND s.branch=$_SESSION[branch])") or die(mysqli_error($con));
									$counter=1;
									$sum=array('stock'=>0,'payable'=>0);
									while($row=mysqli_fetch_assoc($result)){
										$ratio=0;
										if($row['stock']>0 AND ($row['payable']+$row['cheque'])>0)$ratio=$row['stock']/($row['payable']+$row['cheque']);
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="editable" field="title">'.$row['title'].'</td>
											<td class="editable" field="credit">'.$row['credit'].'</td>
											<td class="editable" field="visits">'.$row['visits'].'</td>
											<td class="right">'.number_format($row['stock'],2).'</td>
											<td class="right">'.number_format(($row['payable']+$row['cheque']),2).'</td>
											<td class="right">'.number_format($ratio,2).'</td>
											<td class="popup_select" value="'.$row['status'].'" data-toggle="modal" data-target=".status">'.$supplier_status[$row['status']].'</td>
											<td class="center view_history">History</td>
											<td class="center"><i class="fas fa-chart-line pointer company_analytics" style="color:green"></i></td>
										</tr>';
										$sum['stock']+=$row['stock'];
										$sum['payable']+=($row['payable']+$row['cheque']);
									}
							echo '</tbody><tfoot>
								<tr><td></td><td colspan="3"></td><td class="right"></td><td class="right"></td><td colspan="4"></td></tr>
								<tr><td></td><td colspan="3"></td><td class="right">'.number_format($sum['stock'],2).'</td><td class="right">'.number_format($sum['payable'],2).'</td><td colspan="4"></td></tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>';
	echo '<div class="modal fade status" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Status</label>
						<div><select class="form-control select" id="status" required>
							<option>Select</option>';
							foreach($supplier_status as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	}
?>
<script>
	$('.company_analytics').click(function(){
		location.hash='dashboard/analytics\\' + $(this).parents('tr').attr('id');
	});
	$('.view_history').click(function(){
		location.hash='purchases/companies\\' + $(this).parents('tr').attr('id');
	});
</script>

<?php
	if(in_array('add',$permission)){
		echo '<span class="hide popup add_company" href="'.$remote_file.'/companies&form=add_company"></span>';
?>		<script>
		$.fn.dataTable.ext.buttons.add_company = {text: 'Add a Company',action: function ( e, dt, node, config ) {$('.add_company').trigger('click');}};
		</script>
<?php
	}
?>
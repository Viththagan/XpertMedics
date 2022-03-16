<?php
	if(isset($_POST['select'])){
		$result=mysqli_query($con,"SELECT * FROM products_logs_temp WHERE id='$_POST[id]'");
		$row = $result->fetch_assoc();
		if($row['user']=='')$row['user']="NULL";
		
		$result=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$row[product]' AND branch='$_SESSION[branch]' AND added>'$row[added]'");
		while($row2 = $result->fetch_assoc()){
			$qty=($row2['uic']>0?($row2['qty']*$row2['uic']):$row2['qty'])+$row2['free'];
			if($row2['type']%2==0)$row['qty']-=$qty;
			else $row['qty']+=$qty;
		}
		mysqli_query($con,"INSERT INTO products_logs(product,type,branch,qty,balance,added,user) 
		VALUES('$row[product]','$row[type]','$row[branch]','$row[qty]','$row[qty]','$row[added]',$row[user])") or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM products_logs_temp WHERE id='$row[id]'") or die(mysqli_error($con));
		return;
	}
	echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
				<table class="customized_table table table-sm" table="bill_payments" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th>'.language('Date').'</th><th>'.language('Time').'</th><th>'.language('Product').'</th><th>'.language('MRP').'</th>
					<th>'.language('Physical Stock').'</th><th>'.language('System Stock').'</th><th>'.language('Missing').'</th><th>'.language('Stock Count by').'</th><th></th>
					</tr></thead><tbody>';
					$result = mysqli_query($con, "SELECT l.*,p.title,p.ui_code,p.mrp price,u.first_name,(SELECT balance FROM products_logs WHERE product=p.id ORDER BY id DESC LIMIT 0,1) balance
					FROM products_logs_temp l LEFT JOIN products p on l.product=p.id LEFT JOIN users u ON u.id=l.user
					WHERE l.type='2' AND l.branch='$_SESSION[branch]'") or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" product="'.$row['product'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.date('d-m-Y',strtotime($row['added'])).'</td>
							<td class="center">'.date('h:i A',strtotime($row['added'])).'</td>
							<td>'.$row['title'].'<br/>'.$row['ui_code'].'</td>
							<td class="right">'.$row['price'].'</td>
							<td class="right">'.$row['qty'].'</td>
							<td class="right">'.$row['balance'].'</td>
							<td class="right">'.($row['balance']-$row['qty']).'</td>
							<td>'.$row['first_name'].'</td>
							<td><button type="button" class="btn btn-primary waves-effect waves-light mr-1 stock_update">Admit</button></td>
						</tr>';
					}
				echo '</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>';
	echo '<script>
    remote_file="'.$remote_file.'";
	</script>';
?>
<script>
	$('.stock_update').click(function(){
		tr=$(this).parents('tr');
		$.post(remote_file,{select:'stock_count',product:tr.attr('product'),id:tr.attr('id')},function(data){
			tr.remove();
		});
	});
</script>
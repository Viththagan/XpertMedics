<?php
	if(isset($_GET['id']) AND $_GET['id']>0){
		$result=mysqli_query($con,"SELECT * FROM products WHERE id='$_GET[id]'")or die(mysqli_error($con));
		$product=mysqli_fetch_array($result);
		echo'<div class="row"  style="font-size: 10px;">
		<div class="col-12">
			<div class="card">
				<div class="card-body table-responsive">
					<table table="products" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'" id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
						<thead><tr><th>#</th><th>'.language('Code').'</th><th>'.language('Title').'</th><th>'.language('Category').'</th>
						<th>'.language('Brand').'</th><th>'.language('Company').'</th><th>'.language('Type').'</th><th>'.language('Discount').'</th>
						<th>'.language('Price').'</th><th>'.language('Status').'</th>
						'.($_SESSION['user_level']==0?'<th>'.language('Action').'</th>':'').'
						</tr></thead><tbody>';//
						$this_start=date('Y-m-01');
						$this_end=date('Y-m-t');
						$last_start=date('Y-m-01',strtotime('-1 month'));
						$last_end=date('Y-m-t',strtotime('-1 month'));
						$result=mysqli_query($con,"SELECT p.*,pb.title brand_title,pc.title category_title,c.title company_title 
						FROM products p LEFT JOIN product_category pc ON pc.id=p.category 
						LEFT JOIN product_brand pb ON pb.id=p.brand LEFT JOIN products_company c ON c.id=p.company
						WHERE p.parent='$_GET[id]'")or die(mysqli_error($con));
						$counter=1;
						while($row=mysqli_fetch_array($result)){
							echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
								<td class="center selectable">'.$counter++.'</td>
								<td class="editable" field="ui_code">'.$row['ui_code'].'</td>
								<td>'.$row['title'].'<br/><div style="font-size: 8px;">'.$row['title_tamil'].'</div><br/><div style="font-size: 8px;">'.$row['title_sinhala'].'</div></td>
								<td class="popup_select" value="'.$row['category'].'" data-toggle="modal" data-target=".category">'.$row['category_title'].'</td>
								<td class="popup_select" value="'.$row['brand'].'" data-toggle="modal" data-target=".brand">'.$row['brand_title'].'</td>
								<td class="popup_select" value="'.$row['company'].'" data-toggle="modal" data-target=".company">'.$row['company_title'].'</td>
								<td class="popup_select" value="'.$row['weighted'].'" data-toggle="modal" data-target=".weighted">'.$product['type'][$row['weighted']].'</td>
								<td class="popup_select" value="'.$row['price_g_enabled'].'" data-toggle="modal" data-target=".discount">'.$product['discount'][$row['price_g_enabled']].'</td>
								<td class="popup_select" value="'.$row['price_vary'].'" data-toggle="modal" data-target=".price">'.$product['price'][$row['price_vary']].'</td>
								<td class="popup_select" value="'.$row['status'].'" data-toggle="modal" data-target=".status">'.$product['status'][$row['status']].'</td>
								'.($_SESSION['user_level']==0?'<td class="center"></td>':'').'
							</tr>';
						}
					echo '<tbody></table>
					</div>
				</div>
			</div>
		</div>';
	}
	require_once 'addons/products/products_modal.php';
	echo '<script>
		$.page_title("'.$product['title'].' : '.$product['title_tamil'].'");
	</script>';
?>
<style>
.table > tbody > tr > td, .table > tfoot > tr > td, .table > thead > tr > td {
    padding: 0px 5px;
}
</style>
<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	$result=mysqli_query($con,"SELECT d.*,p.id pid FROM dfc_ui_codes d LEFT JOIN products p ON d.ui_code=p.ui_code")or die(mysqli_error($con));
	$counter=1;
	while($row=mysqli_fetch_array($result)){
		if($row['pid']>0){
			if($row['product']<>$row['pid'])mysqli_query($con,"UPDATE dfc_ui_codes SET product='$row[pid]' WHERE id='$row[id]'")or die(mysqli_error($con));
		}
		else mysqli_query($con,"UPDATE dfc_ui_codes SET product=NULL WHERE id='$row[id]'")or die(mysqli_error($con));
	}
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
			<div class="table-rep-plugin">
			 <div data-pattern="priority-columns">
				<table class="customized_table table-sm" table="dfc_ui_codes" style="width:100%;" data-button="true" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th>
					<th data-priority="1">'.language('Code').'</th><th data-priority="1">'.language('Title').'</th><th data-priority="1">'.language('Label').'</th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT d.*,p.title,p.title_tamil FROM dfc_ui_codes d LEFT JOIN products p ON d.product=p.id")or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td>'.$row['ui_code'].'</td>
							<td class="popup" href="'.$remote_file.'/products&form='.($row['product']>0?'product&id='.$row['product']:'add_product&code='.$row['ui_code']).'">'.$row['title'].'<br/>'.$row['title_tamil'].'</td>
							<td>'.$row['label'].'</td>
						</tr>';
					}
				echo '<tbody></table>
				</div>
				</div>
				</div>
			</div>
		</div>
	</div>';
?>
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
		switch($_POST['form_submit']){
			case 'add_category':
				$result=mysqli_query($con,"SELECT * FROM product_category WHERE id='$_POST[parent]'") or die(mysqli_error($con));
				$row=mysqli_fetch_assoc($result);
				$row['level']++;
				if(trim($_POST['title'])<>'')mysqli_query($con,"INSERT INTO product_category(title,parent,level) VALUES('$_POST[title]','$_POST[parent]','$row[level]')");
				break;
		}
		return;
	}
	else if(isset($_POST['todelete'])){
		if($_POST['todelete']=='product_category'){
			$result=mysqli_query($con,"SELECT id FROM product_category WHERE id='$_POST[id]' OR parent='$_POST[id]'")or die(mysqli_error($con));
			$ids=array();
			while($row=mysqli_fetch_assoc($result))$ids[]=$row['id'];
			$id=implode(',',$ids);
			mysqli_query($con,"UPDATE products SET category=NULL WHERE id IN($id)")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM product_category WHERE id='$_POST[id]' OR parent='$_POST[id]'")or die(mysqli_error($con));
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'add_category':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Title</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="title" required/></div>
							</div>
							<input type="hidden" name="form_submit" value="add_category"/>
							<input type="hidden" name="parent" value="'.$_GET['parent'].'"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Category</button></div></div>
						</form>
					</div>
				</div>';
				break;
		}
		return;
	}
	{
		echo '<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<table class="customized_table table-sm" table="product_category" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<thead><tr><th>#</th><th>'.language('Icon').'</th><th>'.language('Color').'</th><th>'.language('Title').'</th><th>'.language('Tamil').'</th><th>'.language('Parent').'</th><th></th><th></th></tr></thead><tbody>';
									if(isset($_GET['id']) and $_GET['id']>0){
										$q="c.parent=$_GET[id]";
									}
									else $q="c.level=0";
									$result=mysqli_query($con,"SELECT c.*,c1.title parent_title FROM product_category c LEFT JOIN product_category c1 ON c1.id=c.parent WHERE $q") or die(mysqli_error($con));
									$counter=1;
									while($row=mysqli_fetch_assoc($result)){
										echo '<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td><i class="fas fa-'.$row['icon'].' pointer" style="color:'.$row['color'].'"></i></td>
											<td class="editable" field="color">'.$row['color'].'</td>
											<td class="editable" field="title">'.$row['title'].'</td>
											<td class="editable" field="title_tamil">'.$row['title_tamil'].'</td>
											<td>'.$row['parent_title'].'</td>
											<td class="center">'.($row['level']<>1?'<button type="button" class="btn btn-primary waves-effect waves-light open_category">Open</button>':'').'</td>
											<td class="center"><i class="mdi mdi-delete agoy_delete"></i></td>
										</tr>';
									}
							echo '</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>';
	}
	if(!isset($_GET['id']))$_GET['id']='';
?>
<script>
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$(".plus").attr('href',$(".plus").attr('o-href')).trigger('click');
		return false;
	});
	$('.open_category').click(function(){
		location.hash='products/categories\\' + $(this).parents('tr').attr('id');
	});
</script>
<span class="hide popup plus" o-href="<?php echo $remote_file.'/categories&form=add_category&parent='.$_GET['id'];?>"></span>
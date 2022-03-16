<?php
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
	}
	else if(isset($_POST['todelete'])){
		mysqli_query($con,"DELETE FROM `$_POST[todelete]` WHERE id='$_POST[id]'");
	}
?>
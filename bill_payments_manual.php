<?php
	if(isset($_POST['id'])){
		require_once 'config.php';
		$result=mysqli_query($con,"SELECT * FROM bill_payments WHERE id='$_POST[id]'");
		$row=mysqli_fetch_assoc($result);
		mysqli_query($con,"UPDATE products_logs SET purchased='$_POST[purchased]' WHERE id='$row[log]'");
		mysqli_query($con,"UPDATE bill_payments SET status='$_POST[status]',charge='$_POST[charge]',commission='$_POST[commission]',balance='$_POST[balance]' WHERE id='$row[id]'");
		if(is_resource($con))mysqli_close($con);
	}
?>
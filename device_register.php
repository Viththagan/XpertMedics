<?php
	// if(!isset($_COOKIE['device'])){
	if(!isset($_COOKIE['device'])){
		echo '<div class="home-btn d-none d-sm-block"><a href="" class="text-white"><i class="fas fa-home h2"></i></a></div>';
		echo '<div class="accountbg"></div>';
		$logo='logo.png';
		if(isset($_COOKIE['logo']))$logo=$_COOKIE['logo'];
		$title='Request Permission';
		if(isset($_POST['device'])){
			$_SESSION['device']=$_POST['device'];
			$title='Register';
			$_SESSION['device_code']=mt_rand(100000,999999);
			$msg="Register Device\n";
			$msg.="Device : $_SESSION[device]\n";
			$msg.="Code : $_SESSION[device_code]\n";
			mysqli_query($con,"INSERT INTO sms_sent(number,method,mask,type,message,messaged,user) VALUES('$admin_mobile',0,0,'0','$msg','$current_time','0')")or die(mysqli_error($con));
		}
		if(isset($_SESSION['device']))$title='Register';
		echo '<div class="wrapper-page account-page-full">
			<div class="card">
				<div class="card-body">
					<div class="text-center"><a class="logo"><img src="'.$config_url.'/assets/images/'.$logo.'" height="80" alt="logo"></a></div>
					<div class="p-3">
						<h4 class="font-18 m-b-5 text-center">Register Device</h4>
						<form class="form-horizontal m-t-30" action="" method="post">
							'.((isset($_SESSION['device_code']) AND $_SESSION['device_code']<>'')?
							'<div class="form-group">
								<label for="username">Code </label>
								<input type="text" class="form-control" name="device_code" placeholder="Enter Code">
							</div>':
							'<div class="form-group">
								<label for="username">Device Name/Location</label>
								<input type="text" class="form-control" name="device" placeholder="Enter Device Name/Location">
							</div>').'
							<div class="form-group row m-t-20">
								<div class="col-sm-6 text-right">
									<button name="register" class="btn btn-primary w-md waves-effect waves-light" type="submit">'.$title.'</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<div class="m-t-40 text-center">
				<p>	© 2012 AgoySoft (Pvt) Ltd <span class="d-none d-sm-inline-block"> - Software <i class="mdi mdi-heart text-danger"></i> as A Service</span>.</p>
			</div>
		</div>';
		if(is_resource($con))mysqli_close($con);
		exit;
	}
?>
<?php
	if(isset($_GET['lang']) and $_GET['lang']<>''){
		$_SESSION['soft']['settings']['language']=$_GET['lang'];
		unset($_SESSION['soft']['language']);
		$user_id=$_SESSION['soft']['user_id'];
		if(isset($_SESSION['soft']['user_id']))mysqli_query($con,"INSERT INTO users_settings(user,title,description) VALUES('$user_id','language','$_GET[lang]') ON DUPLICATE KEY UPDATE description='$_GET[lang]'") or die(mysqli_error($con));
		echo "<meta http-equiv='refresh' content='0;URL=index.php'>";
	}
	if(!isset($_SESSION['soft']['user_data'])){
		echo '<div class="home-btn d-none d-sm-block"><a href="" class="text-white"><i class="fas fa-home h2"></i></a></div>';
		echo '<div class="accountbg"></div>';
		$logo='logo.png';
		if(isset($_COOKIE['logo']))$logo=$_COOKIE['logo'];
		echo '<div class="wrapper-page account-page-full">
			<div class="card">
				<div class="card-body">
					<div class="text-center"><a class="logo"><img src="'.$config_url.'/assets/images/'.$logo.'" height="80" alt="logo"></a></div>
					<div class="p-3">
						<h4 class="font-18 m-b-5 text-center">Sign In</h4>
						'.($alert<>''?$alert:'').'
						<form class="form-horizontal m-t-30" action="" method="post">
							<div class="form-group">
								<label for="username">Username</label>
								<input type="text" class="form-control" name="username" id="username" placeholder="Enter username">
							</div>
							<div class="form-group">
								<label for="userpassword">Password</label>
								<input type="password" class="form-control" name="password" id="userpassword" placeholder="Enter password">
							</div>
							<div class="form-group row m-t-20">
								<div class="col-sm-6">
									<div class="custom-control custom-checkbox">
										<input type="checkbox" name="remember" class="custom-control-input" id="customControlInline">
										<label class="custom-control-label" for="customControlInline">Remember me</label>
									</div>
								</div>
								<div class="col-sm-6 text-right">
									<button name="login" class="btn btn-primary w-md waves-effect waves-light" type="submit">Log In</button>
								</div>
							</div>
							<div class="form-group m-t-10 mb-0 row">
								<div class="col-12 m-t-20">
									<a href="?page=recovery"><i class="mdi mdi-lock"></i> Forgot your password?</a>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<div class="m-t-40 text-center">
				<!--p>Don\'t have an account ? <a href="" class="font-500 text-primary"> Signup now </a> </p-->
				<p>	© 2012 AgoySoft (Pvt) Ltd <span class="d-none d-sm-inline-block"> - Software <i class="mdi mdi-heart text-danger"></i> as A Service</span>.</p>
			</div>
		</div>';
		require_once "footer.php";
		echo '<script type="text/javascript" src="'.$config_url.'plugins/plugins.js"></script>
		<script type="text/javascript" src="'.$config_url.'plugins/custom.js" async></script>';
		echo '<!-- Be sure this are on your main visiting page, for example, the index.html page-->
    <!-- Install Prompt for Android -->
    <div id="menu-install-pwa-android" class="menu-box menu-box-detached round-medium" data-menu-type="menu-box-bottom" data-menu-height="340" data-menu-effect="menu-parallax">
        <div class="boxed-text-huge top-25">
            <img class="round-medium center-horizontal" src="<?php echo $config_url;?>app/icons/icon-325x200.png" alt="img" width="90">
            <h4 class="center-text bolder top-20 bottom-10"><span class="color-highlight">DFC Delivery</span> on your Home Screen</h4>
            <p>
                Install DFC Delivery on your home screen, and access it just like a regular app. It really is that simple!
            </p>
            <a href="#" class="pwa-install button button-xs button-round-medium button-center-large shadow-large bg-highlight bottom-0">Install</a><br>
            <a href="#" class="pwa-dismiss close-menu center-text color-gray2-light uppercase ultrabold opacity-80 under-heading">Maybe later</a>
            <div class="clear"></div>
        </div>
    </div>

    <!-- Install instructions for iOS -->
    <div id="menu-install-pwa-ios" class="menu-box menu-box-detached round-medium" data-menu-type="menu-box-bottom" data-menu-height="320" data-menu-effect="menu-parallax">
        <div class="boxed-text-huge top-25">
            <img class="round-medium center-horizontal" src="<?php echo $config_url;?>app/icons/icon-325x200.png" alt="img" width="90">
            <h4 class="center-text bolder top-20 bottom-10">Add <span class="color-highlight">DFC Delivery</span> on your Home Screen</h4>
            <p class="bottom-15">
                Install DFC Delivery on your home screen, and access it just like a regular app.  Open your Safari menu and tap "Add to Home Screen".
            </p>
            <div class="clear"></div>
            <a href="#" class="pwa-dismiss close-menu center-text color-red2-dark uppercase ultrabold opacity-80 top-25">Maybe later</a>
            <i class="fa-ios-arrow fa fa-caret-down font-40"></i>
        </div>
    </div>';
		if(is_resource($con))mysqli_close($con);
		exit;
	}
	else {
		$user_id=$_SESSION['soft']['user_id'];
		mysqli_query($con,"UPDATE users SET last_activity='$current_time' WHERE id='$user_id'");
		// mysqli_query($con,"UPDATE users_login SET access='$current_time' WHERE id='$_SESSION[login_id]'");
	}
?>
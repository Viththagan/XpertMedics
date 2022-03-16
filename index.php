<?php
	require_once "config.php";
	require_once 'php_image_magician.php';
	require_once 'functions.php';
    require_once 'basic_functions.php';
	if(!isset($_SESSION['branch']) OR !isset($_SESSION['user_id'])){
		$result=mysqli_query($con,"SELECT * FROM branches");
		$_SESSION['branches']=mysqli_affected_rows($con);
		if($_SESSION['branches']==0){
			mysqli_query($con,"INSERT INTO branches(title,short,main) VALUES('Sample','s',1)");
			$_SESSION['branches']=1;
		}
		$branch=0;
		if(isset($_GET['branch']) AND trim($_GET['branch'])<>''){
			$_GET['branch']=trim($_GET['branch']);
			$result1=mysqli_query($con,"SELECT * FROM branches WHERE title='$_GET[branch]' OR short='$_GET[branch]'");
			if(mysqli_affected_rows($con)==1){
				$result=$result1;
				$branch=1;
			}
			else $branch=0;
		}
		else $branch=0;
		// echo '<br/>';echo 'branch:'.$branch;
		if($branch==0)$result=mysqli_query($con,"SELECT * FROM branches WHERE main='1'");
		$row=mysqli_fetch_assoc($result);
		set_branch($row['id']);
	}
	else if(!isset($_GET['branch']) OR $_GET['branch']<>$_SESSION['branch_data']['short']){
		$refresh_url=$config_url;
		if(isset($_SESSION['branch']) AND $_SESSION['branch']<>'')$refresh_url.=$_SESSION['branch_data']['short'].'/';
		echo '<script type="text/javascript">
			var refresh_url="'.$refresh_url.'";
			window.location.href=refresh_url+ location.hash;
		</script>';
		// echo "<meta http-equiv='refresh' content='0;URL=$refresh_url'>";
		exit;
	}
	
	if(isset($_COOKIE['user_id']))setcookie('user_id',$_COOKIE['user_id'], time() + (86400 * 7), "/", $domain, true);
	if(isset($_COOKIE['device']))setcookie('device',$_COOKIE['device'], time() + (86400 * 7), "/", $domain, true);
	if(isset($_COOKIE['device_id']))setcookie('device_id',$_COOKIE['device_id'], time() + (86400 * 7), "/", $domain, true);
	if(isset($_COOKIE['sales_counter']))setcookie('sales_counter',$_COOKIE['sales_counter'], time() + (86400 * 7), "/", $domain, true);
    if(isset($_GET['act']) and $_GET['act']=='logout'){
		if(isset($_SESSION['soft']['user_id'])){
			$user_id=$_SESSION['soft']['user_id'];
			mysqli_query($con,"UPDATE users SET last_activity='$current_time' WHERE id='$user_id'") or die(mysqli_error($con));
		}
		session_destroy();
		setcookie('user_id',0, time(), "/", $domain, true);
		setcookie('user_id',0, time(), "/");
		unset($_COOKIE['user_id']);
		unset($_SESSION['soft']);
		$logout_url=$config_url;
		if(isset($_SESSION['branch']) AND $_SESSION['branch']<>'')$logout_url.=$_SESSION['branch_data']['short'].'/';
		echo "<meta http-equiv='refresh' content='0;URL=$logout_url'>";
		exit;
	}
	
	$date='';
	if(file_exists("open.txt"))$date=file_get_contents("open.txt");
	if(date('Y-m-d')<>$date AND date('G')>='06'){
		$date=date('Y-m-d');
		file_put_contents("open.txt",$date);
		$date_before_3months=date('Y-m-d',strtotime("-3months"));
		$result1=mysqli_query($con,"SELECT (SELECT s.date FROM products_logs l LEFT JOIN sales s on (s.id=l.referrer and l.type=0) WHERE p.id=l.product ORDER BY l.id DESC LIMIT 0,1) date,p.* FROM `products` p where p.status='0' HAVING date<'$date_before_3months'");
		while($row=mysqli_fetch_array($result1)){
			mysqli_query($con,"UPDATE products SET status='0' WHERE id='$row[id]'");
		}
		$tomorro=date('Y-m-d',strtotime("+1day"));
		mysqli_query($con,"SELECT * FROM sms_sent_summary WHERE date='$tomorro'");
		if(mysqli_affected_rows($con)==0)mysqli_query($con,"INSERT INTO sms_sent_summary(date) VALUES('$tomorro')");
	}
	
	require_once "login_create.php";
	require_once "head.php";
	require_once "device_register.php";
	require_once "login.php";
	echo '<div id="wrapper">';
	require_once "topbar.php";
	echo '<div class="left side-menu" id="leftbar">
		<div class="slimscroll-menu" id="remove-scroll">
			<div id="sidebar-menu">
				<ul class="metismenu" id="side-menu">
					<li class="menu-title">'.language('Components').'</li>';
					if(isset($_SESSION['soft']['menus'])){
						foreach($_SESSION['soft']['menus'] as $file=>$data){
							$permission=array();
							if(isset($data['permission']) AND is_array($data['permission']))$permission=$data['permission'];
							if(in_array('view',$permission) AND $data['type']==0){
								// if($_SESSION['branch_data']['repack']==1 AND strtolower($data['file'])=='products' AND isset($_SESSION['soft']['menus']['products/packing']['permission']) AND in_array('view',$_SESSION['soft']['menus']['products/packing']['permission'])){
									// $data=$_SESSION['soft']['menus']['products/packing'];
								// }
								echo '<li>
									<a href="#'.$data['plugin'].'/'.strtolower($data['file']).'">
									<i class="'.($data['icon']<>''?$data['icon']:'ion ion-md-arrow-round-forward').'"></i>
									<span>'.language($data['title']).'<span class="float-right menu-arrow">
									<i class="mdi mdi-chevron-right"></i></span></a>';
							}
							if(in_array('view',$permission))echo '</li>';
						}
					}
				echo '</ul>
			</div>
			<div class="clearfix"></div>
		</div>
	</div>';
	echo '<div class="content-page">';
	// if($_SESSION['user_id']==0){echo '<pre>';print_r($_SESSION['soft']['menus']);
	//print_r($_COOKIE);
	// }
	// echo '<pre>';print_r($_SESSION['soft']['menus']);
	echo '<div id="ajax_indicator" class="hide alert alert-success alert-dismissible fade show" role="alert"><i class="mdi mdi-check-all mr-2"></i><span class="text"></span>
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
	echo '<div class="content" id="grid_container">';//echo $_SERVER['REQUEST_URI'];
	echo "<script>
		if(location.hash=='')location.hash='$default_page';
	</script>";
	echo '</div>
		<footer class="footer">
			© 2012 AgoySoft (Pvt) Ltd <span class="d-none d-sm-inline-block"> - Software <i class="mdi mdi-heart text-danger"></i> as A Service</span>.
		</footer>
	</div>
	</div>';
	require_once "footer.php";
?>
<?php
	require_once "style.php";
	require_once "js_scripts.php";
	if(is_resource($con))mysqli_close($con);
?>
<div id="progress-bar" style="display:none;">
	<div class="page-title-box">
		<div class="row align-items-center"><div class="col-sm-6"><h4 class="page-title"><?php echo language('Loading');?></h4></div></div>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<div class="">
				<div class="progress m-b-10" style="height: 3px;">
					<div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
				</div>
				<div class="progress" style="height: 24px;">
					<div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="progress" style="display:none;">
	<div class="progress">
		<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
	</div>
</div>
<div id="printableArea" class="hide"><?php //echo print_label(3803731);//echo print_receipt(913089); ?></div>
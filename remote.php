<?php
	require_once "config.php";
	require_once "login_create.php";
	require_once 'php_image_magician.php'; 
	require_once "functions.php";
	require_once 'basic_functions.php';
	if(!isset($_SESSION['soft']['user_id'])){
		echo "<meta http-equiv='refresh' content='0;URL=$config_url#$_GET[file]'>";
		mysqli_close($con);
		exit;
	}
	if($_GET['file']=='branch'){
		set_branch($_POST['id']);
		echo $_SESSION['branch_data']['short'];
		mysqli_close($con);
		return;
	}
	$user_id=$_SESSION['soft']['user_id'];
	$branch_url=$config_url.$_SESSION['branch_data']['short'].'/';
	$_GET['file']=str_replace(' ','_',strtolower($_GET['file']));//print_r($_GET);
	$allowed=array('dashboard/home','dashboard/dashboard','dashboard/ajax','dashboard/ajax_home');
	if(in_array($_GET['file'],$allowed) || (isset($_SESSION['soft']['menus'][$_GET['file']]['permission']) AND in_array('view',$_SESSION['soft']['menus'][$_GET['file']]['permission'])) || isset($_COOKIE['analytics'])){
		
	}
	else {
		$file=explode('/',$_GET['file']);
		if(isset($file[1])){
			$menus = mysqli_query($con,"SELECT * FROM menu WHERE status=1 AND plugin='$file[0]' AND file='$file[1]'");
			if($_SESSION['soft']['user_data']['user_level']==1){
				$result=mysqli_query($con,"SELECT permissions FROM users WHERE id='$_SESSION[user_id]'");
				$row=mysqli_fetch_assoc($result);
				$row['permissions']=json_decode($row['permissions'],true);
			}
			while($menu=mysqli_fetch_assoc($menus)){
				$_SESSION['soft']['menus'][$file[0].'/'.$file[1]]=$menu;
				$_SESSION['soft']['menus'][$file[0].'/'.$file[1]]['permission']=($_SESSION['soft']['user_data']['user_level']==0?json_decode($menu['permissions'],true):(isset($row['permissions'][$menu['id']])?$row['permissions'][$menu['id']]:''));
			}
		}
		else {
			$get=$_GET;
			$post=$_POST;
			$serialize=array();
			if(sizeof($get)>0)$serialize['get']=$get;
			if(isset($post) AND sizeof($post)>0)$serialize['post']=$post;
			$serialize=str_replace("'","\'",json_encode($serialize));
			file_put_contents('remote',"$current_time : $_GET[file] | $_COOKIE[device_id] - $serialize\n", FILE_APPEND | LOCK_EX);
		}
	}
	if(in_array($_GET['file'],$allowed) || (isset($_SESSION['soft']['menus'][$_GET['file']]['permission']) AND in_array('view',$_SESSION['soft']['menus'][$_GET['file']]['permission'])) || isset($_COOKIE['analytics'])){
		$get=$_GET;
		unset($get['file']);
		$post=$_POST;
		if($_GET['file']=='sales/pos' AND isset($post['select']) AND $post['select']=="pending_invoice_list")unset($post);
		if($_GET['file']=='sales/pos' AND isset($post['form_submit']) AND $post['form_submit']=="function_calls")unset($post);
		if($_GET['file']=='sales/pos' AND isset($get['term']))unset($get['term']);
		if($_GET['file']=='dashboard/dashboard' OR $_GET['file']=='dashboard/ajax' OR $_GET['file']=='dashboard/ajax_home'){
			unset($post['i']);
			unset($post['width']);
			unset($post['height']);
		}
		if($_GET['file']=='dashboard/dashboard' AND isset($post['edit']) AND ($post['edit']=='sms_sent' OR $post['edit']=='sales_vouchers'))unset($post);
		$serialize=array();
		if(sizeof($get)>0)$serialize['get']=$get;
		if(isset($post) AND sizeof($post)>0)$serialize['post']=$post;
		if(sizeof($serialize)>0 AND $_GET['file']!='settings/logs' AND $user_id>0){
			$serialize=str_replace("'","\'",json_encode($serialize));
			mysqli_query($con,"INSERT INTO post_log(branch,file,data,device,added,user) VALUES('$_SESSION[branch]','$_GET[file]','$serialize','$_COOKIE[device_id]','$current_time','$user_id')")or die(mysqli_error($con));
		}
		if(file_exists("$plugin_path/$_GET[file].php")){
			$file=explode('/',$_GET['file']);
			if(sizeof($_POST)>0 || isset($_GET['form']) || isset($_GET['term'])){
				$permission=array();
				if(isset($_SESSION['soft']['menus'][$_GET['file']]['permission']))$permission=$_SESSION['soft']['menus'][$_GET['file']]['permission'];
				if($_GET['file']=='dashboard/dashboard' AND isset($_SESSION['soft']['menus']['dashboard/home']['permission']))$permission=$_SESSION['soft']['menus']['dashboard/home']['permission'];
				require_once "$plugin_path/$_GET[file].php";
			}
			else {
				echo '<div class="container-fluid">
					<div class="page-title-box">
						<div class="row align-items-center">
							<div class="col-sm-6"><h4 class="page-title" id="page_title">'.language('Dashboard').'</h4>
								<!--ol class="breadcrumb"></ol-->
							</div>';
							$sub_menus=mysqli_query($con,"SELECT * FROM menu WHERE parent='$file[1]' AND type='3' AND status='1' ORDER BY order_by,id asc")or die(mysqli_error($con));
							if(mysqli_affected_rows($con)>0){
								echo '<div class="col-sm-6">
									<div class="float-right d-none d-md-block">
										<div class="dropdown">
											<button class="btn btn-primary dropdown-toggle arrow-none waves-effect waves-light" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<i class="mdi mdi-view-comfy mr-2"></i> More
											</button>
											<div class="dropdown-menu dropdown-menu-right " x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(102px, 36px, 0px);">';
											while($row=mysqli_fetch_assoc($sub_menus)){
												echo '<a class="dropdown-item" href="#'.$row['plugin'].'/'.($row['file']<>''?strtolower($row['file']):strtolower($row['title'])).'">'.$row['title'].'</a>';
											}
										echo '</div></div>
									</div>
								</div>';
							}
						echo '</div>
					</div>
					<div class="grid_container">';
					// $result=mysqli_query($con,"SELECT * FROM menu WHERE parent='$file[1]' AND type=2 AND status='1'") or die(mysqli_error($con));
					// $type=array(2=>'menu',3=>'pop');
					// $vars='';
					// while($row=mysqli_fetch_assoc($result)){
						// create_table_button($_GET['plugin'],$file[1],$row['title'],$row['file'],$type[$row['type']]);
					// }
					// $remote_file=$config_url."remote.php?file=$_GET[file]";
					$permission=array();
					if(isset($_SESSION['soft']['menus'][$_GET['file']]['permission']))$permission=$_SESSION['soft']['menus'][$_GET['file']]['permission'];
					$remote_file=$config_url.'remote.php?file='.$_GET['file'];
					require_once "$plugin_path/$_GET[file].php";
					// echo '<script>
					// plugin="'.$plugin.'";
					// remote_file="'.$remote_file.'";
					// </script>';
					echo '</div>
				</div>';
			}
			//else echo "<h1>Access denied!</h1> ($_GET[plugin]/$_GET[act].php)";
		}
		else echo "<h1>The Page doesnot exists/Under Construction!</h1> ($_GET[file].php)";
	}
    else echo "<h1>Access Denied!</h1> ($_GET[file].php)";
	if(is_resource($con))mysqli_close($con);
?>
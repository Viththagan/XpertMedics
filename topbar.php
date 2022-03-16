<?php
	echo '<div class="topbar">
		<div class="topbar-left">
			<a class="logo agoy_menu"  href="#dashboard/home" plugin="dashboard" file="home">
				<span class="logo-light">
					<img src="'.$config_url.'uploads/'.$_SESSION['soft']['settings']['logo'].'" alt="" height="16">
				</span>
				<span class="logo-sm">
					<img src="https://dfc.lk/app/icons/icon-192x192.png" alt="" height="22">
				</span>
			</a>
		</div>
		<nav class="navbar-custom">
			<ul class="navbar-right list-inline float-right mb-0">
				<li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
					<form role="search" class="app-search">
						<div class="form-group mb-0">
							<input type="text" class="form-control" placeholder="'.language('Search').'.."><button type="submit"><i class="fa fa-search"></i></button>
						</div>
					</form>
				</li>
				<li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
					<a class="nav-link dropdown-toggle arrow-none waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
						<img src="'.$config_url.'assets/images/flags/'.$_SESSION['soft']['settings']['language'].'_flag.jpg" class="mr-2" height="12" alt=""/> '.language($languages[$_SESSION['soft']['settings']['language']]).' <span class="mdi mdi-chevron-down"></span>
					</a>';
					unset($languages[$_SESSION['soft']['settings']['language']]);
					foreach($languages as $lang=>$title){
						echo '<div class="dropdown-menu dropdown-menu-right language-switch">
							<a class="dropdown-item" href="?lang='.$lang.'"><img src="'.$config_url.'assets/images/flags/'.$lang.'_flag.jpg" alt="" height="16" /><span> '.language($title).' </span></a>
						</div>';
					}
				echo '</li>
				<li class="dropdown notification-list list-inline-item d-none d-md-inline-block"><a class="nav-link waves-effect" href="#" id="btn-fullscreen"><i class="mdi mdi-fullscreen noti-icon"></i></a></li>
				<!-- notification -->
				<!--li class="dropdown notification-list list-inline-item">
					<a class="nav-link dropdown-toggle arrow-none waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
						<i class="mdi mdi-bell-outline noti-icon"></i>
						<span class="badge badge-pill badge-danger noti-icon-badge">1</span>
					</a>
					<div class="dropdown-menu dropdown-menu-right dropdown-menu-lg">
						<h6 class="dropdown-item-text">'.language('Notifications').' (258)</h6>
						<div class="slimscroll notification-item-list">
							<a href="javascript:void(0);" class="dropdown-item notify-item active">
								<div class="notify-icon bg-success"><i class="mdi mdi-cart-outline"></i></div>
								<p class="notify-details">Sample 1<span class="text-muted">Dummy text of the printing and typesetting industry.</span></p>
							</a>
						</div>
						<a href="javascript:void(0);" class="dropdown-item text-center text-primary">'.language('View all').' <i class="fi-arrow-right"></i></a>
					</div>
				</li-->
				<li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
					<a class="nav-link dropdown-toggle arrow-none waves-effect" href="#settings/profile" role="button">
						'.(isset($_SESSION['soft']['user_data']['first_name'])?$_SESSION['soft']['user_data']['first_name']:'').'</a>
				</li>
				<li class="dropdown notification-list list-inline-item">
					<div class="dropdown notification-list nav-pro-img">
						<a class="dropdown-toggle nav-link arrow-none waves-effect nav-user" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
							<img src="'.((isset($_SESSION['soft']['img']) AND $_SESSION['soft']['img']<>'')?$config_url.'uploads/users/thumbnail/'.$_SESSION['soft']['img']:$config_url.'assets/images/photo_60x60.jpg').'" alt="user" class="rounded-circle">
						</a>
						<div class="dropdown-menu dropdown-menu-right profile-dropdown ">
							<a class="dropdown-item agoy_menu" href="#settings/profile" file="settings/profile"><i class="mdi mdi-account-circle m-r-5"></i> '.language('Profile').'</a>
							<!--a class="dropdown-item agoy_menu" href="#settings/plugins" file="settings/plugins"><i class="mdi mdi-wallet m-r-5"></i> '.language('Plugins').'</a-->
							<a class="dropdown-item d-block agoy_menu" href="#settings/general" file="settings/general"><i class="mdi mdi-settings m-r-5"></i> '.language('Settings').'</a>
							<!--a class="dropdown-item d-block agoy_menu" href="#settings/users" file="settings/users"><i class="mdi mdi-account-multiple-outline m-r-5"></i> '.language('Users').'</a-->
							<!--a class="dropdown-item d-block agoy_menu" href="#settings/language" file="settings/language"><i class="mdi mdi-google-adwords m-r-5"></i> '.language('Language').'</a-->
							<div class="dropdown-divider"></div>
							<a class="dropdown-item text-danger" href="?act=logout"><i class="mdi mdi-power text-danger"></i> '.language('Logout').'</a>
						</div>
					</div>
				</li>
			</ul>
			<ul class="list-inline menu-left mb-0">
				<li class="float-left"><button class="button-menu-mobile open-left waves-effect"><i class="mdi mdi-menu"></i></button></li>
				<li class="dropdown notification-list list-inline-item">
					<a class="nav-link dropdown-toggle arrow-none waves-effect agoy_menu"  href="#dashboard/home" plugin="dashboard" file="home" style="display: contents;">
						<i class="mdi mdi mdi-home noti-icon"></i>
					</a>';
				if(isset($_SESSION['soft']['user_data']['branch']) and sizeof($_SESSION['soft']['user_data']['branch'])>1){
					$branches=implode(',',$_SESSION['soft']['user_data']['branch']);
					$result=mysqli_query($con,"SELECT * FROM branches WHERE id IN($branches)");
					$htm='';
					while($row=mysqli_fetch_assoc($result)){
						$style='';
						if($_SESSION['branch']==$row['id']){
							$style='style="display:none;"';
							$title=$row['title'];
						}
						$htm.='<a class="dropdown-item branch_select pointer" id="'.$row['id'].'" '.$style.'>'.$row['title'].'</a>';
					}
					echo '<div class="dropdown pt-3 d-inline-block">
						<a class="btn btn-light dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$title.'</a>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuLink">'.$htm;
						echo '</div>
					</div>';
				}
				echo '</li>';
			echo '</ul>
		</nav>
	</div>';
?>
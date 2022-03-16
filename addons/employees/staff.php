<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// echo '<pre>';print_r($_POST);
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		if($_POST['field']=='permissions'){
			$data=explode('-',$_POST['value']);
			$result=mysqli_query($con,"SELECT * FROM users WHERE id=$_POST[id]") or die(mysqli_error($con));
			$staff=mysqli_fetch_array($result);
			$permissions=array();
			if($staff['permissions']<>'')$permissions=json_decode($staff['permissions'],true);
			if(!in_array($data[1],$permissions[$data[0]]) AND $data[2]==1)$permissions[$data[0]][]=$data[1];
			if($data[2]==0)unset($permissions[$data[0]][array_search($data[1],$permissions[$data[0]])]);
			$value=json_encode($permissions);
		}
		if($_POST['field']=='branch'){
			$data=explode('-',$_POST['value']);
			$result=mysqli_query($con,"SELECT * FROM users WHERE id=$_POST[id]") or die(mysqli_error($con));
			$staff=mysqli_fetch_array($result);
			$branch=array();
			if($staff['branch']<>'')$branch=json_decode($staff['branch'],true);
			if(!in_array($data[0],$branch) AND $data[1]==1)$branch[]=$data[0];
			if($data[1]==0)unset($branch[array_search($data[0],$branch)]);
			$value=json_encode($branch);
			
		}
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	else if(isset($_POST['select'])){
		switch($_POST['select']){
			case 'username':
				mysqli_query($con,"SELECT id FROM users WHERE username='$_POST[username]'") or die(mysqli_error($con));
				if(mysqli_affected_rows($con)==0)echo '<span style="color:green">Available</span>';
				else echo '<span style="color:red">Unavailable</span>';
				break;
		}
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'add_user'://print_r($_POST);
				$_POST['joined']=date('Y-m-d',strtotime($_POST['joined']));
				$q="'$_POST[username]'";
				$branches=json_encode($_POST['branch']);
				if(!isset($_POST['username']))$q='NULL';
				if(isset($_POST['id'])AND $_POST['id']>0){
					mysqli_query($con,"UPDATE users SET branch='$branches',first_name='$_POST[first_name]',last_name='$_POST[last_name]',nic='$_POST[nic]',mobile='$_POST[mobile]',joined='$_POST[joined]',user_level='$_POST[user_level]',type='$_POST[type]',login_auth='$_POST[login_auth]',status='$_POST[status]',username=$q WHERE id='$_POST[id]'") OR die(mysqli_error($con));
				}
				else {
					$password=rand(1000,9999);
					$msg="Dear $_POST[first_name], Your Login has been created.";
					$msg.="\nusername : $_POST[username]\n";
					$msg.="code : $password";
					mysqli_query($con,"INSERT INTO sms_sent(branch,number,type,message,messaged,user)VALUES('$_SESSION[branch]','$_POST[mobile]','0','$msg','$current_time','0')")or die(mysqli_error($con));
					$password=md5($password);
					mysqli_query($con,"INSERT INTO users(branch,first_name,last_name,nic,mobile,joined,user_level,type,login_auth,username,password,added,user) 
					VALUES('$branches','$_POST[first_name]','$_POST[last_name]','$_POST[nic]','$_POST[mobile]','$_POST[joined]','$_POST[user_level]','$_POST[type]','$_POST[login_auth]',$q,'$password','$current_time','$_SESSION[user_id]')") OR die(mysqli_error($con));
				}
				break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'user':
				if(isset($_GET['id'])){
					$result=mysqli_query($con,"SELECT * FROM users WHERE id='$_GET[id]'");
					$row=mysqli_fetch_assoc($result);
				}
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body table" table="users" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
							'.(isset($row['first_name'])?'<div class="row">
								<div class="form-group col-lg-3"></div>
								<div class="form-group col-lg-9">
								<img src="'.$config_url.($row['img']?$upload_path.'/users/'.$row['img']:'assets/images/photo_60x60.jpg').'" name="" style="height:50px;" path="'.$upload_path.'/users/" class="dropzone" query="UPDATE users SET img=\'[image]\' WHERE id=\''.$row['id'].'\'"/>
								</div>
							</div>':'').'
							<div class="row">
								<div class="form-group col-lg-3">First Name</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6" name="first_name" value="'.(isset($row['first_name'])?$row['first_name']:'').'" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Last Name</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-6" name="last_name" value="'.(isset($row['last_name'])?$row['last_name']:'').'" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">NIC</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-4" name="nic" value="'.(isset($row['nic'])?$row['nic']:'').'" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Mobile Number</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-4" data-mask="0999999999" name="mobile" value="'.(isset($row['mobile'])?$row['mobile']:'').'" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Date of Join</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input date col-lg-4" name="joined" value="'.(isset($row['joined'])?date('d-m-Y',strtotime($row['joined'])):'').'" required/></div>
							</div>';
							$user_level=1;
							if(isset($row['user_level']))$user_level=$row['user_level'];
							echo '<div class="row">
								<div class="form-group col-lg-3">User Type</div>
								<div class="form-group col-lg-9">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-2 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($user_level==0?'active':'').'"><input type="radio" name="user_level" value="0" '.($user_level==0?'checked':'').'>Administrator</label>
										<label class="btn btn-secondary pointer '.($user_level==1?'active':'').'"><input type="radio" name="user_level" value="1" '.($user_level==1?'checked':'').'>User</label>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Staff</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="type">';
										$type=0;
										if(isset($row['type']))$type=$row['type'];
										$types=array(1=>'Permanent Staff',0=>'Temporory Staff',-1=>'System Login');
										foreach($types as $key=>$title)echo '<option value="'.$key.'" '.($type==$key?'selected':'').'>'.$title.'</option>';
									echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-3">Branch(es)</div>
								<div class="form-group col-lg-9">
									<select class="form-control select" name="branch[]" multiple>';
										$branches=array();
										if(isset($row['branch']))$branches=json_decode($row['branch'],true);
										$result1=mysqli_query($con,"SELECT * FROM branches");
										while($row1=mysqli_fetch_assoc($result1))echo '<option value="'.$row1['id'].'" '.(in_array($row1['id'],$branches)?'selected':'').'>'.$row1['title'].'</option>';
									echo '</select>
								</div>
							</div>';
							$login_auth=0;
							if(isset($row['login_auth']))$login_auth=$row['login_auth'];
							echo '<div class="row">
								<div class="form-group col-lg-3">Login</div>
								<div class="form-group col-lg-9">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-2 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($login_auth==1?'active':'').'"><input type="radio" name="login_auth" value="1" '.($login_auth==1?'checked':'').'>Enabled</label>
										<label class="btn btn-secondary pointer '.($login_auth==0?'active':'').'"><input type="radio" name="login_auth" value="0" '.($login_auth==0?'checked':'').'>Diabled</label>
									</div>
								</div>
							</div>
							<div class="row '.((isset($row['login_auth']) AND $row['login_auth']==1)?'':'hide').' login_auth">
								<div class="form-group col-lg-3">User Name</div>
								<div class="form-group col-lg-9"><input type="text" class="form-control next_input col-lg-4 username" name="username" value="'.(isset($row['username'])?$row['username']:'').'"/><span class="font-13 text-muted username_availablity"></span></div>
							</div>
							'.(isset($_GET['id'])?'<div class="row">
								<div class="form-group col-lg-3">User Status</div>
								<div class="form-group col-lg-9">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-2 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($row['status']==1?'active':'').'"><input type="radio" name="status" value="1" '.($row['status']==1?'checked':'').'>Active</label>
										<label class="btn btn-secondary pointer '.($row['status']==0?'active':'').'"><input type="radio" name="status" value="0" '.($row['status']==0?'checked':'').'>Left</label>
									</div>
								</div>
							</div>':'').'
							
							'.(isset($_GET['id'])?'<input type="hidden" name="id" value="'.$_GET['id'].'"/>':'').'
							<input type="hidden" name="form_submit" value="add_user"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">'.(isset($_GET['id'])?'Update':'Add').'</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'permissions':
				$result=mysqli_query($con,"SELECT * FROM menu WHERE id='$_GET[id]'") or die(mysqli_error($con));
				$row=mysqli_fetch_array($result);
				$result=mysqli_query($con,"SELECT * FROM users WHERE id=$_GET[staff]") or die(mysqli_error($con));
				$staff=mysqli_fetch_array($result);
				$permissions='';
				if($staff['permissions']<>'')$permissions=json_decode($staff['permissions'],true);
				echo '<div class="card col-lg-6 mx-auto" id="permissions">
					<div class="card-body table" table="users" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<table><tbody>
						<tr id="'.$_GET['staff'].'" style="cursor:pointer;"><td>';
						$row['permissions']=json_decode($row['permissions'],true);
						foreach($row['permissions'] as $title){
							echo '<button type="button" class="btn '.((is_array($permissions) AND is_array($permissions[$row['id']]) AND in_array($title,$permissions[$row['id']]))?'btn-primary':'btn-outline-primary').' waves-effect waves-light permission_button" id="'.$row['id'].'">'.$title.'</button> ';
						}
					echo '</td></tr></tbody></table>
					</div>
				</div><style>#permissions .btn {margin-top: 10px;}</style>';
				break;
		}
		return;
	}
	if(isset($_GET['id']) AND $_GET['id']>=0){
		$result=mysqli_query($con,"SELECT * FROM users WHERE id=$_GET[id]") or die(mysqli_error($con));
		$staff=mysqli_fetch_array($result);
		$permissions='';
		if($staff['permissions']<>'')$permissions=json_decode($staff['permissions'],true);
		$branches=array();
		if($staff['branch']<>'')$branches=json_decode($staff['branch'],true);
		echo'<h1 class="mt-0 header-title">'.$staff['last_name'].' '.$staff['first_name'].'</h1>';
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table><tbody>
						<tr id="'.$_GET['staff'].'" style="cursor:pointer;"><td>';
						$result=mysqli_query($con,"SELECT * FROM branches") or die(mysqli_error($con));
						$counter=1;
						while($row=mysqli_fetch_array($result)){
							echo '<button type="button" class="btn '.((in_array($row['id'],$branches))?'btn-primary':'btn-outline-primary').' waves-effect waves-light branch_button" id="'.$row['id'].'">'.$row['title'].'</button> ';
						}
					echo '</td></tr></tbody></table>
					</div>
				</div>
			</div>
		</div>';
		echo'<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="customized_table table table-sm" table="users" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<thead><tr><th>#</th><th>'.language('Parent').'</th><th>'.language('Title').'</th><th>'.language('Permissions').'</th>
						</tr></thead><tbody>';
						$result=mysqli_query($con,"SELECT * FROM menu WHERE status=1 ORDER BY parent ASC") or die(mysqli_error($con));
						$counter=1;
						while($row=mysqli_fetch_array($result)){
							if($row['permissions']!=''){
								echo'<tr id="'.$staff['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td class="left">'.$row['parent'].'</td>
									<td class="left">'.$row['title'].'</td>';
									echo '<td>';
									$row['permissions']=json_decode($row['permissions'],true);
									$i=1;
									$view=4;
									$sum=sizeof($row['permissions']);
									foreach($row['permissions'] as $title){
										echo '<button type="button" class="btn '.((is_array($permissions[$row['id']]) AND is_array($permissions[$row['id']]) AND in_array($title,$permissions[$row['id']]))?'btn-primary':'btn-outline-primary').' waves-effect waves-light permission_button" id="'.$row['id'].'">'.$title.'</button> ';
										if($i==$view AND ($sum-$view)>0){
											echo '<button type="button" class="btn btn-outline-primary waves-effect waves-light popup" href="'.$remote_file.'/staff&form=permissions&staff='.$staff['id'].'&id='.$row['id'].'&callback=">+'.($sum-$view).'</button> ';
											break;
										}
										$i++;
									}
									echo '</td>';
								echo '</tr>';
							}
						}
					echo '</tbody></table>
					</div>
				</div>
			</div>
		</div>';
	}
	else{
		if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
		else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	
		echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Status</label>
								<div><select class="form-control select form_submit_change" name="status">';
									$user_status=array(1=>'Active',0=>'Inactive');
									if(!isset($_GET['status']))$_GET['status']=1;
									foreach($user_status as $id=>$value)echo '<option value="'.$id.'" '.($_GET['status']==$id?'selected':'').'>'.$value.'</option>';
									echo '<option value="-1" '.($_GET['status']=='-1'?'selected':'').'>All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label></label>
								<div><button type="button" class="btn btn-primary waves-effect waves-light form_submit_click">Filter</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
    </div>';
	$where='WHERE id>0';
	if($_GET['status']>=0)$where.=" AND status='$_GET[status]'";
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (mobile LIKE '%$_GET[search]%' OR nic LIKE '%$_GET[search]%' OR first_name LIKE '%$_GET[search]%' OR last_name LIKE '%$_GET[search]%' OR username LIKE '%$_GET[search]%' OR short LIKE '%$_GET[search]%'";
		$keys=explode(' ',$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.="mobile LIKE '%$key%' OR nic LIKE '%$key%' first_name LIKE '%$key%' OR last_name LIKE '%$key%' OR username LIKE '%$key%' OR short LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	
	{
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
				<table class="customized_table table table-sm" table="users" style="width:100%;" data-button="true" data-buts="copy,excel,pdf,colvis,add_user" data-sum="" data-hide-columns="Login,Joined" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th>'.language('ID').'</th><th></th><th>'.language('Name').'</th><th>'.language('NIC').'</th>
					<th class="center">'.language('Group').'</th><th class="right">'.language('Login').'</th><th class="center">'.language('Joined').'</th>
					<th class="center">'.language('Last Activity').'</th><th></th><th></th>
					</tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT * FROM users $where") or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td class="center">'.$row['id'].'</td>
							<td>
								<img src="'.$config_url.($row['img']?$upload_path.'/users/'.$row['img']:'assets/images/photo_60x60.jpg').'" name="" style="height:30px;" path="'.$upload_path.'/users/" class="dropzone" query="UPDATE users SET img=\'[image]\' WHERE id=\''.$row['id'].'\'" title="users"/>
							</td>
							<td class="popup" href="'.$remote_file.'/staff&form=user&id='.$row['id'].'">'.$row['first_name'].' '.$row['last_name'].'</td>
							<td>'.$row['nic'].'</td>
							<td class="center '.(($_SESSION['user_id']!=$row['id'] AND in_array('edit',$permission))?'change_user_level':'').'" value="'.$row['user_level'].'">'.($row['user_level']==0?'Administrator':'User').'</td>
							<td class="right '.(($_SESSION['user_id']!=$row['id'] AND in_array('edit',$permission))?'change_user_login':'').'" value="'.$row['login_auth'].'">'.($row['login_auth']==0?'Disabled':'Enabled').'</td>
							<td class="center">'.$row['joined'].'</td>
							<td class="center">'.$row['last_activity'].'</td>
							<td class="center '.((in_array('edit',$permission) AND $row['user_level']==1)?'View_Permission':'').'">'.($row['user_level']==1?'Permission':'').'</td>
							<td class="center View_log">log</td>
						</tr>';
					}
				echo '</tbody></table>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
	}
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<style>
.dropzone {
    min-height: 30px;
	padding: 0px;
	border: 0px;
}
</style>
<script>
	$('.View_Permission').click(function(){
		location.hash='employees/staff\\' + $(this).parents('tr').attr('id');
	});
	$('.View_log').click(function(){
		location.hash='settings/logs\\' + $(this).parents('tr').attr('id');
	});
	$('.change_user_level').click(function(){
		td=$(this);
		value=0;
		text='Administrator';
		if(td.attr('value')==0){
			value=1;
			text='User';
		}
		td.attr('value',value);
		td.text(text);
		$.post(remote_file+"/staff",{edit:'users',field:'user_level',id:$(this).parents('tr').attr('id'),value:value},function(data){
			
		});
	});
	$('.change_user_login').click(function(){
		td=$(this);
		value=0;
		text='Disabled';
		if(td.attr('value')==0){
			value=1;
			text='Enabled';
		}
		td.attr('value',value);
		td.text(text);
		$.post(remote_file+"/staff",{edit:'users',field:'login_auth',id:$(this).parents('tr').attr('id'),value:value},function(data){
			
		});
	});
	$('.branch_button').click(function(){
		td=$(this);
		value=td.attr('id');
		td.toggleClass('btn-primary btn-outline-primary');
		if(td.hasClass('btn-primary')==true)value=value+'-1';
		else value=value+'-0';
		$.post(remote_file+"/staff",{edit:'users',field:'branch',id:$(this).parents('tr').attr('id'),value:value},function(data){
			// alert(data);
		});
	});
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		$('.add_user').trigger('click');
		return false;
	});
	function after_ajax(){
		$('.username').keyup(function(){
			if($(this).val().length>=4){
				$.post(remote_file+"/staff",{select:'username',username:$(this).val()},function(data){
					$('.username_availablity').html(data);
				});
			}
		});
		$('.permission_button').unbind('click').click(function(){
			td=$(this);
			value=td.attr('id');
			value=value+'-'+td.text();
			td.toggleClass('btn-primary btn-outline-primary');
			if(td.hasClass('btn-primary')==true)value=value+'-1';
			else value=value+'-0';
			$.post(remote_file+"/staff",{edit:'users',field:'permissions',id:$(this).parents('tr').attr('id'),value:value},function(data){
				// alert(data);
			});
		});
	}
</script>
<?php
	if(in_array('add',$permission)){
		echo '<span class="hide popup add_user" href="'.$remote_file.'/staff&form=user"></span>';
?>		<script>
		$.fn.dataTable.ext.buttons.add_user = {text: 'Add a Staff',action: function ( e, dt, node, config ) {$('.add_user').trigger('click');}};
		</script>
<?php
	}
?>
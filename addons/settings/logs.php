<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	// print_r($_GET);
	if(isset($_GET['form'])){
		$result=mysqli_query($con,"SELECT * FROM post_log WHERE id='$_GET[id]'");
		$row=mysqli_fetch_assoc($result);
		$array=json_decode($row['data'],true);
		if(is_array($array['post']) AND sizeof($array['post'])>0){
			echo 'POST<table class="table">
			<thead><tr><th>#</th><th>'.language('Key').'</th><th>'.language('Value').'</th></tr></thead><tbody>';
			$counter=1;
			$type='';
			foreach($array['post'] as $key=>$value){
				if($value=='add_sales')$type='add_sales';
				else if($value=='customer')$type='customer';
				$more='';
				if($type=='add_sales' AND $key=='id'){
					$result=mysqli_query($con,"SELECT * FROM products WHERE id='$value'");
					$row=mysqli_fetch_assoc($result);
					$more=' ('.$row['ui_code'].' - '.$row['title'].')';
				}
				else if($type=='customer' AND $key=='id'){
					$result=mysqli_query($con,"SELECT * FROM customers WHERE id='$value'");
					$row=mysqli_fetch_assoc($result);
					$more=' ('.$row['mobile'].')';
				}
				echo '<tr class="pointer">
					<td class="center">'.$counter++.'</td>
					<td>'.$key.'</td>
					<td>'.(is_array($value)?json_encode($value):$value).$more.'</td>
				</tr>';
			}
			echo '</tbody></table>';
		}
		if(is_array($array['get']) AND sizeof($array['get'])>0){
			echo 'GET<table class="table">
			<thead><tr><th>#</th><th>'.language('Key').'</th><th>'.language('Value').'</th></tr></thead><tbody>';
			$counter=1;
			foreach($array['get'] as $key=>$value){
				echo '<tr class="pointer">
					<td class="center">'.$counter++.'</td>
					<td>'.$key.'</td>
					<td>'.(is_array($value)?json_encode($value):$value).'</td>
				</tr>';
			}
			echo '</tbody></table>';
		}
		return;
	}
	$user_id=0;
	if(isset($_GET['id']))$user_id=$_GET['id'];
	if(isset($_GET['search']))$_SESSION['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['get'][$_GET['file']]))$_GET=$_SESSION['get'][$_GET['file']];
	{
		echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off">
						<div class="row">
							<div class="col-xl-12">
                                <div class="card" style="margin-bottom: 0px;">
                                    <div class="card-body" style="padding: 0rem;">
										<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#query" aria-expanded="false" aria-controls="collapseExample">Query</a></p>
                                        <div class="collapse" id="query">
                                            <div class="card card-body mb-0">
                                                <textarea name="query" class="form-control" rows="3">'.(isset($_GET['query'])?$_GET['query']:'').'</textarea>
                                            </div>
										</div>
                                    </div>
                                </div>
                            </div>
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Date</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['from']))$_GET['from']=date('d-m-Y');
										echo '<input type="text" class="form-control date" name="from" value="'.$_GET['from'].'"/>
										<input type="text" class="form-control date" name="to" value="'.(isset($_GET['to'])?$_GET['to']:'').'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Time</label>
								<div>
									<div class="input-daterange input-group">';
										if(!isset($_GET['time']['from']))$_GET['time']['from']=date('00:00');
										if(!isset($_GET['time']['to']))$_GET['time']['to']=date('23:59');
										echo '<input type="text" class="form-control time" name="time[from]" value="'.date('H:i',strtotime($_GET['time']['from'])).'"/>
										<input type="text" class="form-control time" name="time[to]" value="'.date('H:i',strtotime($_GET['time']['to'])).'"/>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Devices</label>
								<div><select class="form-control select" name="device[]" multiple="multiple" multiple>';
									$result=mysqli_query($con,"SELECT * FROM registered_devices") or die(mysqli_error($con));
									while($row=mysqli_fetch_array($result)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['device']) AND in_array($row['id'],$_GET['device']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>POST</label>
								<div>
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0" data-toggle="buttons">';
										if(!isset($_GET['post']))$_GET['post']=0;
										echo '<label class="btn btn-secondary '.($_GET['post']==0?'active':'').' pointer"><input type="radio" name="post" value="0" class="form_submit_change" '.($_GET['post']==0?'checked':'').'>No</label>
										<label class="btn btn-secondary '.($_GET['post']==1?'active':'').' pointer"><input type="radio" name="post" value="1" class="form_submit_change" '.($_GET['post']==1?'checked':'').'>Yes</label>
									</div>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=1000;
									foreach($values as $value)echo '<option value="'.$value.'" '.((isset($_GET['limit']) AND $_GET['limit']==$value)?'selected':'').'>'.$value.'</option>';
									echo '<option value="0">All</option>
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
	}
	$date=date('Y-m-d',strtotime($_GET['from']));
	$where="WHERE date(l.added)='$date' ";
	if($user_id>0)$where.=" AND l.user='$user_id'";
	$view_date=0;
	if(isset($_GET['to'])){
		if($_GET['from']<>'' AND $_GET['to']<>'' AND strtotime($_GET['from'])<strtotime($_GET['to'])){
			$from=date('Y-m-d',strtotime($_GET['from']));
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE date(l.added) BETWEEN '$from' AND '$to' ";
			$view_date=1;
		}
		else if($_GET['to']<>'' AND $_GET['from']==''){
			$to=date('Y-m-d',strtotime($_GET['to']));
			$where="WHERE date(l.added)='$to' ";
			$view_date=0;
		}
	}
	$where.=" AND time(l.added) BETWEEN '".$_GET['time']['from']."' AND '".$_GET['time']['to']."' ";
	if(isset($_GET['device']) AND sizeof($_GET['device'])>0){
		$devices=implode("','",$_GET['device']);
		$where.=" AND l.device IN('$devices')";
	}
	if(isset($_GET['post']) AND $_GET['post']==1)$where.=" AND l.post='1'";
	
	if(isset($_GET['query']) AND $_GET['query']<>''){
		if(0==count(array_intersect(array_map('strtoupper', explode(' ',$_GET['query'])),$query_avoid))){
			$result=mysqli_query($con,$_GET['query'])or die(mysqli_error($con));
			if(mysqli_affected_rows($con)>0){
				$id=array();
				while($row=mysqli_fetch_array($result))$id[]=$row['id'];
				$ids=implode(',',$id);
				$where.=" AND l.id IN($ids)";
			}
		}
	}
	
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (l.file LIKE '%$_GET[search]%' OR l.data LIKE '%$_GET[search]%'";
		$keys=explode(' ',$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.=" OR l.file LIKE '%$key%' OR l.data LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	{
	echo'<div class="row"  style="font-size: 10px;">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table class="customized_table table-sm" table="post_log" style="width:100%;" data-button="true" data-sum="" data-hide-columns="" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th><th>'.language('ID').'</th><th>'.language('Time').'</th><th>'.language('File').'</th><th>'.language('Device').'</th><th>'.language('User').'</th><th>'.language('data').'</th></tr></thead><tbody>';
					$result=mysqli_query($con,"SELECT l.*,u.first_name,d.title FROM post_log l LEFT JOIN registered_devices d ON d.id=l.device LEFT JOIN users u ON u.id=l.user $where $limit") or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td>'.$row['id'].'</td>
							<td class="center">'.($view_date==1?date('d-m-Y',strtotime($row['added'])).', ':'').date('h:i A',strtotime($row['added'])).'</td>
							<td>'.$row['file'].'</td>
							<td>'.$row['title'].'</td>
							<td>'.$row['first_name'].'</td>
							<td class="log_view" data-toggle="modal" data-target=".log_data">'.$row['data'].'</td>
							
						</tr>';// class="popup" href="'.$remote_file.'/post_logs&form=log_view&id='.$row['id'].'&callback=" class="log_view" data-toggle="modal" data-target=".log_view"
					}
				echo '</tbody></table>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
	}
	echo '<script>
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
	function after_ajax(){
		
	}
	$('.log_view').click(function(){
		$.get(remote_file+"/logs&form=log_view",{id:$(this).parents('tr').attr('id')},function(data){
			$('#log_data').html(data);
		});
	});
</script>
<style>
.table > tbody > tr > td, .table > tfoot > tr > td, .table > thead > tr > td {
    padding: 0px 5px;
}
</style>
<div class="modal fade log_data" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	 <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="log_data"></div>
			</div>
		</div>
	</div>
</div>
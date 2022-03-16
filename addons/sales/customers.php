<?php
	 if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id='$_POST[id]'");
		return;
	}
	if(isset($_GET['active']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	// print_r($_GET);
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="col-xl-12">
                                <div class="card" style="margin-bottom: 0px;">
                                    <div class="card-body" style="padding: 0rem;">
										<p><a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#query" aria-expanded="false" aria-controls="collapseExample">Query</a>
										<a class="btn btn-primary mb-2 mb-sm-0" data-toggle="collapse" href="#extra" aria-expanded="false" aria-controls="collapseExample">By Purchased History</a></p>
                                        <div class="collapse" id="query">
                                            <div class="card card-body mb-0">
                                                <textarea name="query" class="form-control" rows="3">'.(isset($_GET['query'])?$_GET['query']:'').'</textarea>
                                            </div>
										</div>
                                        <div class="collapse" id="extra">
											<div class="row">
												<div class="form-group col-lg-1">
													<label></label>
													<div><div class="btn-group btn-group-toggle mt-2 mt-xl-0" data-toggle="buttons">';
														$array=array('>','<');
														if(!isset($_GET['this_month_options']))$_GET['this_month_options']='>';
														foreach($array AS $val)echo '<label class="btn btn-secondary pointer '.($_GET['this_month_options']==$val?'active':'').'"><input type="radio" name="this_month_options" value="'.$val.'" '.($_GET['this_month_options']==$val?'checked':'').'>'.$val.'</label>';
													echo '</div></div>
												</div>
												<div class="form-group col-lg-2">
													<label>This Month</label>
													<div><input type="number" name="this_month" class="form-control form_submit_change" value="'.(isset($_GET['this_month'])?$_GET['this_month']:'').'"/></div>
												</div>
												<div class="form-group col-lg-1">
													<label></label>
													<div><div class="btn-group btn-group-toggle mt-2 mt-xl-0" data-toggle="buttons">';
														$array=array('>','<');
														if(!isset($_GET['last_month_options']))$_GET['last_month_options']='>';
														foreach($array AS $val)echo '<label class="btn btn-secondary pointer '.($_GET['last_month_options']==$val?'active':'').'"><input type="radio" name="last_month_options" value="'.$val.'" '.($_GET['last_month_options']==$val?'checked':'').'>'.$val.'</label>';
													echo '</div></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Last Month</label>
													<div><input type="number" name="last_month" class="form-control form_submit_change" value="'.(isset($_GET['last_month'])?$_GET['last_month']:'').'"/></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Max in a Month</label>
													<div><input type="number" name="max_month" class="form-control form_submit_change" value="'.(isset($_GET['max_month'])?$_GET['max_month']:'').'"/></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Min in a Month</label>
													<div><input type="number" name="min_month" class="form-control form_submit_change" value="'.(isset($_GET['min_month'])?$_GET['min_month']:'').'"/></div>
												</div>
												<div class="form-group col-lg-2">
													<label>Total</label>
													<div><input type="number" name="total" class="form-control form_submit_change" value="'.(isset($_GET['total'])?$_GET['total']:'').'"/></div>
												</div>
											</div>
										</div>
                                    </div>
                                </div>
                            </div>
							<div class="form-group col-lg-2">
								<label>Search</label>
								<div>
								<input type="text" name="search" class="form-control form_submit_change" placeholder="Search" value="'.(isset($_GET['search'])?$_GET['search']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Customer Type(s)</label>
								<div><select class="form-control select" name="type[]" multiple="multiple" multiple>';
									foreach($customer_types as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['type']) AND in_array($type,$_GET['type']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Membership</label>
								<div><select class="form-control select" name="member[]" multiple="multiple" multiple>';
									foreach($memberships as $type=>$title){
										echo '<option value="'.$type.'" '.((isset($_GET['member']) AND in_array($type,$_GET['member']))?'selected':'').'>'.$title.'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-1">
								<label>Result</label>
								<div><select class="form-control select form_submit_change" name="limit">';
									$values=array(100,500,1000,2000,5000,10000);
									if(!isset($_GET['limit']))$_GET['limit']=100;
									foreach($values as $value)echo '<option value="'.$value.'" '.((isset($_GET['limit']) AND $_GET['limit']==$value)?'selected':'').'>'.$value.'</option>';
									echo '<option value="0">All</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Active</label>
								<div><select class="form-control select form_submit_change" name="active">';
									$values=array('-1 week'=>'Within a Week','-1 month'=>'Within a month',
									'-3 months'=>'Within 3 months','-6 months'=>'Within 6 months','-1 year'=>'Within a Year',);
									if(!isset($_GET['active']))$_GET['active']='-3 months';
									foreach($values as $value=>$text)echo '<option value="'.$value.'" '.($_GET['active']==$value?'selected':'').'>'.$text.'</option>';
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
	if($_GET['active']==0)$date="2016-01-01";
	else $date=date('Y-m-d',strtotime($_GET['active']));
	$having="HAVING visit>='$date' ";
	$where="WHERE id>0 ";
	
	if(isset($_GET['type']) AND sizeof($_GET['type'])>0){
		$types=implode(',',$_GET['type']);
		$where.=" AND c.type IN($types)";
	}
	
	if(isset($_GET['member']) AND sizeof($_GET['member'])>0){
		$member=implode(',',$_GET['member']);
		$where.=" AND c.plat IN($member)";
	}
	
	if(isset($_GET['query']) AND $_GET['query']<>''){
		if(0==count(array_intersect(array_map('strtoupper', explode(' ',$_GET['query'])),$query_avoid))){
			$result=mysqli_query($con,$_GET['query'])or die(mysqli_error($con));
			if(mysqli_affected_rows($con)>0){
				$id=array();
				while($row=mysqli_fetch_array($result))$id[]=$row['id'];
				$ids=implode(',',$id);
				$where.=" AND c.id IN($ids)";
			}
		}
	}
	
	if(isset($_GET['search']) AND strlen($_GET['search'])>2){
		$where.=" AND (c.mobile LIKE '%$_GET[search]%' OR c.nic LIKE '%$_GET[search]%' OR c.fname LIKE '%$_GET[search]%' OR c.lname LIKE '%$_GET[search]%' OR c.name_ta LIKE '%$_GET[search]%' OR c.street LIKE '%$_GET[search]%' 
		OR c.address LIKE '%$_GET[search]%' OR c.remind LIKE '%$_GET[search]%'";
		$keys=explode(' ',$_GET['search']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['search'])<>trim($key)){
				$where.="c.mobile LIKE '%$key%' OR c.nic LIKE '%$key%' c.fname LIKE '%$key%' OR c.lname LIKE '%$key%' OR c.name_ta LIKE '%$key%' OR c.street LIKE '%$key%' OR c.address LIKE '%$key%' OR c.remind LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	
	if(isset($_GET['this_month']) AND $_GET['this_month']>0)$having.=" AND s2.thi$_GET[this_month_options]=$_GET[this_month]";
	if(isset($_GET['last_month']) AND $_GET['last_month']>0)$having.=" AND s3.last$_GET[last_month_options]=$_GET[last_month]";
	if(isset($_GET['max_month']) AND $_GET['max_month']>0)$where.=" AND s4.max_month>=$_GET[max_month]";
	if(isset($_GET['min_month']) AND $_GET['min_month']>0)$where.=" AND s4.min_month>=$_GET[min_month]";
	if(isset($_GET['total']) AND $_GET['total']>0)$where.=" AND s1.total>=$_GET[total]";
	$limit="";
	if($_GET['limit']>0)$limit="LIMIT 0,$_GET[limit]";
	
	$this_start=date('Y-m-01');
	$this_end=date('Y-m-t');
	$last_start=date('Y-m-01',strtotime('-1 month'));
	$last_end=date('Y-m-t',strtotime('-1 month'));
	
	$query="SELECT c.*,s4.max_month,s4.min_month,
	(SELECT sum(l.selling_price*l.qty) total FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) WHERE s.customer=c.id AND s.date BETWEEN '$last_start' AND '$last_end') last,
	(SELECT sum(l.selling_price*l.qty) total FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) WHERE s.customer=c.id AND s.date BETWEEN '$this_start' AND '$this_end') thi,
	(SELECT sum(l.selling_price*l.qty) total FROM sales s LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type=0) WHERE s.customer=c.id) total,
	(SELECT max(date) FROM sales WHERE customer=c.id) visit
	 FROM customers c
	 LEFT JOIN (SELECT max(tot) max_month,min(tot) min_month,customer FROM (SELECT sum(total) tot,customer,year(date),month(date) FROM sales WHERE customer>0 GROUP BY customer,year(date),month(date)) s GROUP BY customer) s4 ON s4.customer=c.id
	$where $having $limit";
	echo'<div class="row"  style="font-size: 12px;">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table table="customers" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" plugin="'.str_replace($dir,'',dirname(__FILE__)).'" id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
					<thead><tr><th>#</th><th>'.language('Type').'</th><th>'.language('Member').'</th><th>'.language('Mobile/NIC').'</th>
					<th>'.language('Name').'</th><th>'.language('Visit').'</th>
					<th>'.language('This').'</th><th>'.language('Last').'</th><th>'.language('Max').'</th><th>'.language('Total').'</th><th>'.language('Capacity').'</th>
					</tr></thead><tbody>';//<th>'.language('Action').'</th>
					$result=mysqli_query($con,$query)or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center">'.$counter++.'</td>
							<td class="popup_select" value="'.$row['type'].'" data-toggle="modal" data-target=".type" title="'.$customer_types_info[$row['type']].'">'.$customer_types[$row['type']].'</td>
							<td class="popup_select" value="'.$row['plat'].'" data-toggle="modal" data-target=".plat">'.$memberships[$row['plat']].'</td>
							<td>'.$row['mobile'].'<br/>'.$row['nic'].'</td>
							<td>'.$row['fname'].' '.$row['lname'].'<br/><div style="font-size: 8px;">'.$row['name_ta'].'</div></td>
							<td>'.date('d-m-Y',strtotime($row['visit'])).'</td>
							<td class="right">'.number_format($row['thi'],2).'</td>
							<td class="right">'.number_format($row['last'],2).'</td>
							<td class="right">'.number_format($row['max_month'],2).'</td>
							<td class="right">'.number_format($row['total'],2).'</td>
							<td class="right">'.number_format($row['capacity'],2).'</td>
						</tr>';
					}
				echo '<tbody></table>
				</div>
			</div>
		</div>
	</div>';//<td class="center">'.(1==1?'<i class="mdi mdi-playlist-edit agoy_edit"></i>':'').
							//(1==1?'<i class="mdi mdi-delete agoy_delete"></i>':'').'</td>
	echo '<div class="modal fade type" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Type</label>
						<div><select class="form-control select" id="type" required>
							<option>Select</option>';
							foreach($customer_types as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade plat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Membership</label>
						<div><select class="form-control select" id="plat" required>
							<option>Select</option>';
							foreach($memberships as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
?>
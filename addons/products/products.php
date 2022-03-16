<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['edit'])){
		$value=$_POST['value'];
		if(strpos($_POST['field'],'date')!==false)$value=date('Y-m-d',strtotime($value));
		$id=$_POST['id'];
		if(is_array($_POST['id']))$id=implode(',',$_POST['id']);
		if($_POST['field']=='available_items'){
			stock_adj($_POST['id'],$value);
		}
		else mysqli_query($con,"UPDATE $_POST[edit] SET $_POST[field]='$value' WHERE id IN($id)");
		if($_POST['edit']=='products_logs' AND in_array($_POST['field'],array('qty','free','uic'))){
			$result=mysqli_query($con,"SELECT product FROM $_POST[edit] WHERE id='$_POST[id]'");
			$row=mysqli_fetch_assoc($result);
			update_qty($row['product']);
		}
		return;
	}
	else if(isset($_POST['print'])){
		switch($_POST['print']){
			case 'print_product_label'://print_r($_POST);
				$ids=implode(',',$_POST['id']);
				echo '<div>';
					$result=mysqli_query($con,"SELECT p.*,pb.title brand_title,pc.title category_title,
					(SELECT l.selling_price FROM products_logs l WHERE l.product=p.id AND l.type IN(1,3) ORDER BY l.id DESC LIMIT 0,1) price_g,
					(SELECT l.mrp FROM products_logs l WHERE l.product=p.id AND l.type IN(1,3) ORDER BY l.id DESC LIMIT 0,1) mrp_last
					FROM products p LEFT JOIN product_category pc ON pc.id=p.category 
					LEFT JOIN product_brand pb ON pb.id=p.brand WHERE p.id IN($ids)")or die(mysqli_error($con));
					$counter=1;
					while($row=mysqli_fetch_array($result)){
						$price='<span class="price">'.$row['mrp_last'].'/=</span>';
						if($row['price_g']>0 AND $row['price_g']<$row['mrp_last'] AND $row['discount']==1){
							$price='<span class="price cross">'.$row['mrp_last'].'/=</span><span class="price">'.$row['price_g'].'/=</span>';
						}
						echo '<div class="print_label">
							'.$row['title'].'<br/>
							<span class="tamil">'.$row['title_tamil'].'</span><br/>
							<span>'.$row['category_title'].'</span>
							'.$price.'
						</div>';
					}
				echo '</div>
				<style>
					.tamil{font-size:18px;}
					.price{font-size:24px;float: right;margin-bottom: 10px;position: static;margin-right: 10px;font-weight:bold;}
					.cross{text-decoration:line-through}
					.print_label{height: 40mm;width: 90mm;float: left;font-size: 22px;color:#000000;
					font-family: Verdana, Geneva, sans-serif;border-radius: 13px;border: 1px solid rgb(0, 0, 0);padding: 10px;}
				</style>';
			break;
		}
		return;
	}
	else if(isset($_GET['form'])){
		switch($_GET['form']){
			case 'product'://print_r($_POST);
				echo view_a_product($_GET['id'],str_replace('.php','',str_replace($dir,'',__FILE__)),str_replace($dir,'',dirname(__FILE__)));
				break;
			case 'merge_to'://print_r($_POST);
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Product</label>
											<div><input type="text" class="x-large form-control product" name="parent"></div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="child" value="'.$_GET['id'].'"/>
												<input type="hidden" name="popup_form_submit" value="product_merge"/>
												<input type="hidden" id="callback" value="'.$_GET['callback'].'"/>
												<button type="submit" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Merge</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>';
				break;
			case 'select_product':
				$q = strtolower($_GET["term"]);
				if (get_magic_quotes_gpc()) $q = stripslashes($q);
				$result1=mysqli_query($con,"SELECT * FROM products WHERE (title LIKE '%$q%' OR remind LIKE '%$q%' OR ui_code LIKE '%$q%') AND status='1' LIMIT 0,10");
				$result = array();
				while($row=mysqli_fetch_array($result1)){
					array_push($result, array("id"=>$row['id'], "label"=>$row['title']."\n".$row['ui_code'], "value" =>$row['ui_code']));
				}
				echo json_encode($result);
				break;
			case 'add_product':
				echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">';
				if(isset($_GET['company']) AND $_GET['company']==179)$uicode_prefix='sb';
				if($_GET['code']==''){
					if(isset($_GET['type'])){
						
					}
					else {
						$result=mysqli_query($con,"SELECT * FROM products WHERE ui_code like '$uicode_prefix%' ORDER BY ui_code ASC");
						$i=1;
						$prefix_=strtoupper($uicode_prefix);
						while($row = $result->fetch_assoc()){
							$code=str_replace($uicode_prefix,'',trim(strtolower($row['ui_code'])))+0;
							if($code<>$i){
								$code=sprintf("$prefix_%04d",$i);
								mysqli_query($con,"SELECT * FROM products WHERE ui_code='$code'");
								if(mysqli_affected_rows($con)==1)$code='';
								else break;
							}
							else $code='';
							$i++;
						}
						if($code=='')$_GET['code']=sprintf("$prefix_%04d",$i);
						else $_GET['code']=$code;
					}
				}
				echo '<form class="popup_form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
							<div class="row">
								<div class="form-group col-lg-2">Title</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="title" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Generic Name</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="generic_name"/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Code</div>
								<div class="form-group col-lg-10"><input type="text" class="form-control next_input" name="ui_code" value="'.$_GET['code'].'" required/></div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Category</div>
								<div class="form-group col-lg-10"><select class="form-control select" name="category"><option></option>';
									$result=mysqli_query($con,"SELECT c.*,c1.title parent_title 
									FROM product_category c LEFT JOIN product_category c1 ON c1.id=c.parent WHERE c.level=1") or die(mysqli_error($con));
									while($row1=$result->fetch_assoc())echo '<option value="'.$row1['id'].'">'.$row1['parent_title'].' -> '.$row1['title'].'</option>';
								echo '</select>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-lg-2">Company</div>
								<div class="form-group col-lg-10"><select class="form-control select" name="company"><option></option>';
									$result=mysqli_query($con,"SELECT * FROM products_company") or die(mysqli_error($con));
									while($row1=$result->fetch_assoc())echo '<option value="'.$row1['id'].'" '.((isset($_GET['company']) AND $_GET['company']==$row1['id'])?'selected':'').'>'.$row1['title'].'</option>';
								echo '</select>
								</div>
							</div>
							<input type="hidden" name="form_submit" value="add_product"/>
							<div class="row"><div class="form-group col-lg-12"><button type="submit" class="btn btn-primary btn-lg waves-effect waves-light btn-block">Add Product</button></div></div>
						</form>
					</div>
				</div>';
				break;
			case 'select_products_weigher':
				echo '<div class="card col-lg-4 mx-auto">
					<div class="card-body">
						<form class="repeater popup_form_submit" enctype="multipart/form-data" method="post" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'" autocomplete="off" validate>
							<div class="row">
								<div class="col-lg-12">
									<div class="p-20">
										<div class="form-group"><label>Product</label>
											<div><input type="text" class="x-large form-control product" name="product"></div>
										</div>
										<div class="row">
											<div class="form-group col-lg-2">Title</div>
											<div class="form-group col-lg-10"><input type="text" class="form-control" name="title"/></div>
										</div>
										<div class="row">
											<div class="form-group col-lg-2">Type</div>
											<div class="form-group col-lg-10"><select class="form-control select" name="type">
												<option value="0" selected>Image</option>
												<option value="1">Text</option>
												</select>
											</div>
										</div>
										<div class="form-group mb-0">
											<div>
												<input type="hidden" name="sort" value="'.$_GET['sort'].'"/>
												<input type="hidden" name="popup_form_submit" value="select_products_weigher"/>
												<button type="submit" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in">Add</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>';
				break;
			case 'products_weigher':
				if(!isset($_GET['type'])){
					echo '<div class="card col-lg-6 mx-auto">
					<div class="card-body">';
				}
					echo '<div id="main">';
					$result=mysqli_query($con,"SELECT w.*,p.img FROM products_weigher w LEFT JOIN products p ON p.id=w.product");
					$products=array();
					while($row=mysqli_fetch_assoc($result)){
						if(substr($row['img'], 0, 1)==',')$row['img']=substr($row['img'], 1);
						$img=explode(',',$row['img']);
						$products[$row['id']]['img']=$img[0];
						$products[$row['id']]['title']=$row['title'];
						$products[$row['id']]['type']=$row['type'];
					}
					
						for($i=1; $i<=72; $i++){
							$title='';// AND file_exists('../img/products/'.$products[$i]['img'])
							if(isset($products[$i]))$title=(($products[$i]['type']==0)?'<img src="https://dfc.lk/img/products/'.$products[$i]['img'].'">':'<span class="text" style="'.(strpos($products[$i]['title'], ' ')!==false?'margin-top: 0px;':'').'">'.str_replace(' ','<br/>',$products[$i]['title']).'</span>');
							echo '<div class="item popup" href="'.$config_url.'remote.php?file=products/products&form=select_products_weigher&sort='.$i.'">'.$title.'</div>';
						}
					echo '</div>
					<style>
					#main{
						width: 136mm;
						height: 144mm;
						-webkit-border-radius: 7px;
						-moz-border-radius: 7px;
						border-radius: 7px;
						border: 1px solid #000000;
					}
					.item{
						width: 16.1mm;
						height: 15.2mm;
						border-radius: 7px;
						border: 1px solid #000000;
						float: left;
						margin: 0.5px;box-sizing: unset;
					}
					.item img{max-width: 90%;
    margin: 3px;}.text{position: absolute;
    transform: rotate(-45deg);
    margin-top: 20px;}
					</style>';
				if(!isset($_GET['type'])){
					echo '</div>
					</div>';
				}
				break;
		}
		return;
	}
	else if(isset($_POST['form_submit'])){
		switch($_POST['form_submit']){
			case 'adj_stock'://print_r($_POST);
				update_qty($_POST['id']);
				break;
			case 'add_product'://print_r($_POST);
				$q="'$_POST[category]'";
				if($_POST['category']=='')$q="NULL";
				$q1="'$_POST[company]'";
				if($_POST['company']=='')$q1="NULL";
				mysqli_query($con,"INSERT INTO products(ui_code,title,generic_name,category,company,added,user) 
				VALUES('$_POST[ui_code]','$_POST[title]','$_POST[generic_name]',$q,$q1,'$current_time','$_SESSION[user_id]')");
				break;
			
		}
		return;
	}
	else if(isset($_POST['popup_form_submit'])){
		switch($_POST['popup_form_submit']){
			case 'product_merge':
				$result=mysqli_query($con,"SELECT * FROM products WHERE ui_code='$_POST[parent]'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_array($result);
					mysqli_query($con,"UPDATE products_logs SET product='$row[id]' WHERE product='$_POST[child]'");
					mysqli_query($con,"UPDATE products_logs_temp SET product='$row[id]' WHERE product='$_POST[child]'");
					mysqli_query($con,"UPDATE products_offer SET product='$row[id]' WHERE product='$_POST[child]'");
					mysqli_query($con,"UPDATE products_offer_customer SET product='$row[id]' WHERE product='$_POST[child]'");
					mysqli_query($con,"DELETE FROM products WHERE id='$_POST[child]'");
				}
				break;
			case 'select_products_weigher':
				$result=mysqli_query($con,"SELECT * FROM products WHERE ui_code='$_POST[product]'");
				if(mysqli_affected_rows($con)==1){
					$row=mysqli_fetch_array($result);
					mysqli_query($con,"SELECT * FROM products_weigher WHERE id='$_POST[sort]'");
					$q="";
					$title='NULL';
					if($_POST['title']<>'' AND $_POST['type']==1){
						$q=",title='$_POST[title]'";
						$title="'$_POST[title]'";
					}
					if(mysqli_affected_rows($con)==1)mysqli_query($con,"UPDATE products_weigher SET product='$row[id]',type='$_POST[type]'$q WHERE id='$_POST[sort]'")or die(mysqli_error($con));
					else mysqli_query($con,"INSERT INTO products_weigher(id,product,type,title) VALUES('$_POST[sort]','$row[id]','$_POST[type]',$title)")or die(mysqli_error($con));
				}
				break;
		}
		return;
	}
	if(isset($_GET['code']))$_SESSION['soft']['get'][$_GET['file']]=$_GET;
	else if(isset($_SESSION['soft']['get'][$_GET['file']]))$_GET=$_SESSION['soft']['get'][$_GET['file']];
	{
	echo '<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<form class="form_submit" enctype="multipart/form-data" action="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
						<div class="row">
							<div class="form-group col-lg-2">
								<label>Title Search</label>
								<div>
								<input type="text" name="title" class="form-control form_submit_change" required placeholder="Search" value="'.(isset($_GET['title'])?$_GET['title']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Code</label>
								<div>
								<input type="text" name="code" class="form-control form_submit_change" required placeholder="Search" value="'.(isset($_GET['code'])?$_GET['code']:'').'"/>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Category</label>
								<div><select class="form-control select" name="category[]" multiple="multiple" multiple>';
									$category=mysqli_query($con,"SELECT * FROM product_category");
									while($row=mysqli_fetch_assoc($category)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['category']) AND in_array($row['id'],$_GET['category']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '<option value="0" '.((isset($_GET['category']) AND in_array(0,$_GET['category']))?'selected':'').'>None</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Brand</label>
								<div><select class="form-control select" name="brand[]" multiple="multiple" multiple>';
									$brand=mysqli_query($con,"SELECT * FROM product_brand");
									while($row=mysqli_fetch_assoc($brand)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['brand']) AND in_array($row['id'],$_GET['brand']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '<option value="0" '.((isset($_GET['brand']) AND in_array(0,$_GET['brand']))?'selected':'').'>None</option>
								</select>
								</div>
							</div>
							<div class="form-group col-lg-1">';
								if(!isset($_GET['days']))$_GET['days']=7;
								echo '<label>Running</label><div><input type="number" name="days" class="form-control" value="'.$_GET['days'].'"/></div>
							</div>
							<div class="form-group col-lg-3">
								<label>Company</label>
								<div><select class="form-control select" id="company1" name="company[]" multiple>';
									$company=mysqli_query($con,"SELECT * FROM products_company");
									while($row=mysqli_fetch_assoc($company)){
										echo '<option value="'.$row['id'].'" '.((isset($_GET['company']) AND in_array($row['id'],$_GET['company']))?'selected':'').'>'.$row['title'].'</option>';
									}
								echo '</select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Status</label>
								<div><select class="form-control select" name="status">';
									if(!isset($_GET['status']))$_GET['status']=1;
									foreach($product['status'] as $id=>$title)echo '<option value="'.$id.'" '.($_GET['status']==$id?'selected':'').'>'.$title.'</option>';
								echo '<option '.($_GET['status']=='All'?'selected':'').'>All</option></select>
								</div>
							</div>
							<div class="form-group col-lg-2">
								<label>Result</label>
								<div><select class="form-control select" name="limit">';
									if(!isset($_GET['limit']))$_GET['limit']=100;
									$values=array(100,500,1000,2000,5000,10000);
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
	$where="WHERE p.id>0 ";
	if($_SESSION['branch_data']['repack']==1)$where="WHERE p.company='$packing_company_id' ";
	if(isset($_GET['title']) AND strlen(trim($_GET['title']))>=2){
		// $_GET['title']=trim($_GET['title']);
		$where.=" AND (p.title LIKE '%$_GET[title]%' OR p.title_tamil LIKE '%$_GET[title]%' OR p.title_sinhala LIKE '%$_GET[title]%' OR p.remind LIKE '%$_GET[title]%'";
		$keys=explode(',',$_GET['title']);
		foreach($keys as $key){
			if(strlen(trim($key))>2 AND trim($_GET['title'])<>trim($key)){
				$where.="OR p.title LIKE '%$key%' OR p.title_tamil LIKE '%$key%' OR p.title_sinhala LIKE '%$key%' OR p.remind LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	if(isset($_GET['code']) AND strlen(trim($_GET['code']))>2){
		$_GET['code']=trim($_GET['code']);
		$where.=" AND (p.id LIKE '%$_GET[code]%' OR p.ui_code LIKE '%$_GET[code]%' OR p.bulk_code LIKE '%$_GET[code]%' OR p.remind LIKE '%$_GET[code]%'";
		$keys=explode(',',$_GET['code']);
		foreach($keys as $key){
			$key=trim($key);
			if(strlen($key)>2 AND $_GET['code']<>$key){
				$where.="OR p.id LIKE '%$key%' OR p.ui_code LIKE '%$key%' OR p.bulk_code LIKE '%$key%' OR p.remind LIKE '%$key%'";
			}
		}
		$where.=") ";
	}
	if(isset($_GET['category']) AND sizeof($_GET['category'])>0){
		$types=implode(',',$_GET['category']);
		if(in_array(0,$_GET['category']))$where.=" AND (p.category=0 OR p.category is NULL)";
		else $where.=" AND p.category IN($types)";
	}
	if(isset($_GET['brand']) AND sizeof($_GET['brand'])>0){
		$types=implode(',',$_GET['brand']);
		if(in_array(0,$_GET['brand']))$where.=" AND (p.brand=0 OR p.brand is NULL)";
		else $where.=" AND p.brand IN($types)";
	}
	$q="";
	$th="";
	if(isset($_GET['company']) AND sizeof($_GET['company'])>0){
		$companies=implode(',',$_GET['company']);
		$where.=" AND p.company IN($companies)";
		// $q=",(SELECT SUM(if(l.uic>0,(l.qty*l.uic),l.qty)) FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type=1) WHERE l.product=p.id AND l.type=1 AND po.company IN($companies)) stock_in";
		// $th='<th data-priority="6">'.language('In').'</th>';
	}
	if(isset($_GET['status'])){
		if($_GET['status']=='All'){}
		else $where.=" AND p.status='$_GET[status]'";
	}
	$last=date('Y-m-d',strtotime('-7 days'));
	if(isset($_GET['days']) AND $_GET['days']>0)$last=date('Y-m-d',strtotime("-$_GET[days] days"));
	$yesterday=date('Y-m-d',strtotime('-1 days'));
	$running="AND s.date BETWEEN '$last' AND '$yesterday'";
	// echo '<pre>';print_r($_SESSION);
	if($_SESSION['soft']['user_data']['mobile']=='0776193405')$where.=" AND p.company='179'";
	$q="SELECT p.*,pb.title brand_title,pc.title category_title,c.title company_title,
	(SELECT l.balance FROM products_logs l WHERE l.product=p.id ORDER BY l.id DESC LIMIT 0,1) balance,
	(SELECT SUM(l.qty) FROM products_logs l LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) WHERE l.product=p.id AND l.type=0 $running) running
	$q
	FROM products p LEFT JOIN product_category pc ON pc.id=p.category 
	LEFT JOIN product_brand pb ON pb.id=p.brand LEFT JOIN products_company c ON c.id=p.company
	$where LIMIT 0,$_GET[limit]";
	echo'<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body table-responsive">
				<table class="customized_table table table-sm" table="products" style="width:100%;" data-button="true" data-sum="7" data-hide-columns="Category,Brand" data-page-length="100" file="'.str_replace('.php','',str_replace($dir,'',__FILE__)).'">
					<thead><tr><th>#</th>
					<th>'.language('Code').'</th><th>'.language('Title').'</th><th>'.language('Price').'</th><th>'.language('Cost').'</th>
					<th>'.language('%').'</th>'.$th.'<th>'.language('Qty').'</th><th>'.language('Value').'</th><th>('.$_GET['days'].')</th>
					<th>Re</th><th>'.language('Category').'</th>
					<th>'.language('Brand').'</th><th>'.language('Company').'</th><th>'.language('Merge').'</th>
					</tr></thead><tbody>';//
					$this_start=date('Y-m-01');
					$this_end=date('Y-m-t');
					$last_start=date('Y-m-01',strtotime('-1 month'));
					$last_end=date('Y-m-t',strtotime('-1 month'));
					$result=mysqli_query($con,$q)or die(mysqli_error($con));
					$counter=1;
					$sum=array('total_mrp'=>0,'total_purchase'=>0,);
					while($row=mysqli_fetch_array($result)){
						// if(substr(strtolower($row['title']), 0,3)=='dm ')update_qty($row['id']);
						echo'<tr id="'.$row['id'].'" style="cursor:pointer;">
							<td class="center selectable">'.$counter++.'</td>
							<td class="popup" href="'.$remote_file.'/products&form=product&id='.$row['id'].'">'.$row['ui_code'].'</td>
							<td class="popup" href="'.$remote_file.'/products&form=product&id='.$row['id'].'">
								'.$row['title'].(trim($row['title_tamil'])<>''?'<br/><div style="font-size: 8px;">'.$row['title_tamil'].'</div>':'').
								(trim($row['title_sinhala'])<>''?'<br/><div style="font-size: 8px;">'.$row['title_sinhala'].'</div>':'').
							'</td>
							<td class="right">'.$row['mrp'].'</td>
							<td class="right">'.number_format($row['purchased'],2).'</td>
							<td class="right">'.($row['mrp']>0?number_format((($row['mrp']-$row['purchased'])/$row['mrp'])*100,2):0).'</td>
							'.(isset($row['stock_in'])?'<td class="right">'.($row['stock_in']+0).'</td>':'').'
							<td class="right editable" field="available_items">'.($row['balance']+0).'</td>
							<td class="right">'.number_format(($row['balance']+0)*$row['mrp'],2).'</td>
							<td class="right">'.($row['running']+0).'</td>
							<td class="right">'.(($row['running']-$row['balance'])>0?($row['running']-$row['balance']):0).'</td>
							<td class="popup_select" value="'.$row['category'].'" data-toggle="modal" data-target=".category">'.$row['category_title'].'</td>
							<td class="popup_select" value="'.$row['brand'].'" data-toggle="modal" data-target=".brand">'.$row['brand_title'].'</td>
							<td class="popup_select" value="'.$row['company'].'" data-toggle="modal" data-target=".company">'.$row['company_title'].'</td>
							<td><button type="button" class="btn btn-primary waves-effect waves-light mr-1 mfp-close-btn-in popup" href="'.$remote_file.'/products&form=merge_to&id='.$row['id'].'&callback=remove">Merge to</button></td>
						</tr>';
						$sum['total_mrp']+=$row['mrp']*($row['balance']+0);
						// $sum['total_purchase']+=$row['purchased']*($row['balance']+0);
					}
				echo '</tbody><tfoot><tr><th colspan="2"></th><th colspan="4">Page Total</th>
				<th class="right"></th>
				<th colspan="7"></th>
				</tr><tr><th colspan="2"></th><th colspan="5">Total</th>
				<th class="right">'.number_format($sum['total_mrp'],2).'</th>
				<th colspan="7"></th>
				</tr></tfoot>';
				echo '</table>
				</div>
			</div>
		</div>
	</div>';
	require_once "$plugin_path/products/products_modal.php";
	echo '<script>
    remote_file="'.$remote_file.'/";
	</script>';
?>
<script>
	function after_ajax(){
		if($( ".product" ).length >0){
			var cache = {};
			$( ".product" ).autocomplete({minLength: 1,
			  source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
				  response( cache[ term ] );
				  return;
				}
		 
				$.getJSON(remote_file+"products&form=select_product", request, function( data, status, xhr ) {
				  cache[ term ] = data;
				  response( data );
				});
			  }
			});
		}
	}
	function keydown_act(key){
		switch(key){
			case 'f1':
				$(".f1").trigger('click');
				$.get(remote_file+"products",{form: 'products_weigher',type:'view'},function(data){
					$('#printableArea').html(data);
				});
				break;
			case '+':
				company='';
				if($("#company1").val().length==1)company=$("#company1").val();
				$(".plus").attr('href',$(".plus").attr('o-href')+'&code=&company='+company).trigger('click');
				break;
			case 'Ctrl++':
				if($('.selected').length>0){
					var id = [];
					$('.selected').each(function (){
						id.push(parseInt($(this).attr('id')));
					});
					$.post(remote_file+"products",{print: 'print_product_label',id:id},function(data){
						$('#printableArea').html(data);
						window.print();
					});
				}
				break;
		}
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"+", function(e) {
		keydown_act('+');
		return false;
	});
	$(document).bind('keydown',"Ctrl++", function(e) {
		keydown_act('Ctrl++');
		return false;
	});
	$(document).bind('keydown',"f1", function(e) {
		keydown_act('f1');
		return false;
	});
	
</script>
<?php
	if(in_array('add',$permission)){
		echo '<span class="hide popup plus" o-href="'.$config_url.'remote.php?file=products/products&form=add_product"></span>';
		echo "<script>$.add_table_button('products','products','Add a Product','products', 'add_products','', '&form=add_product');</script>";
	}
	echo '<span class="hide popup f1" href="'.$config_url.'remote.php?file=products/products&form=products_weigher"></span>';
?>
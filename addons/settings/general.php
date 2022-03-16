<?php
	$remote_file=$config_url.'remote.php?file='.str_replace($dir,'',dirname(__FILE__));
	if(isset($_POST['form_submit'])){
		mysqli_query($con,"SELECT * FROM settings WHERE title='$_POST[title]' AND branch='$_SESSION[branch]'") or die(mysqli_error($con));
		if(mysqli_affected_rows($con)==0)mysqli_query($con,"INSERT INTO settings(title,branch,description) VALUES('$_POST[title]','$_SESSION[branch]','$_POST[value]')") or die(mysqli_error($con));
		else mysqli_query($con,"UPDATE settings SET description='$_POST[value]' WHERE title='$_POST[title]' AND branch='$_SESSION[branch]'") or die(mysqli_error($con));
		
		$_SESSION['soft']['settings'][$_POST['title']]=$_POST['value'];
		echo ucwords(str_replace('_',' ',$_POST['title'])).' Updated!';
		return;
	}
	$tab='';
	$content='';
	// echo '<pre>';print_r($permission);
	if(in_array('general',$permission)){
		$tab.='<li class="nav-item">
			<a class="nav-link active" data-toggle="tab" href="#general" role="tab">
				<span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
				<span class="d-none d-sm-block">General</span> 
			</a>
		</li>';
		$content.='<div class="tab-pane active p-3" id="general" role="tabpanel">
			<p class="mb-0">
				<div class="col-12">
					<h4 class="mt-0 header-title">Invoice Print Settings</h4>
					<p class="text-muted m-b-30"></p>
					<dl class="row mb-0">
						
						<dt class="col-sm-3">Stock Count at Till</dt>
						<dd class="col-sm-9">';
						$stock_count_at_till=0;
						if(isset($_SESSION['soft']['settings']['stock_count_at_till']))$stock_count_at_till=$_SESSION['soft']['settings']['stock_count_at_till'];
							$content.='<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off_personalized" data-toggle="buttons">
								<label class="btn btn-secondary pointer '.($stock_count_at_till==1?'active':'').'">
									<input type="radio" class="settings_update" name="stock_count_at_till" value="1" '.($stock_count_at_till==1?'checked':'').'>On
								</label>
								<label class="btn btn-secondary pointer '.($stock_count_at_till==0?'active':'').'">
									<input type="radio" class="settings_update" name="stock_count_at_till" value="0" '.($stock_count_at_till==0?'checked':'').'>Off
								</label>
							</div>
						</dd>
						
					</dl>
				</div>
			</p>
		</div>';
	}
	if(in_array('invoice',$permission)){
		$tab.='<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#invoice" role="tab">
				<span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
				<span class="d-none d-sm-block">Invoice</span> 
			</a>
		</li>';
		$content.='<div class="tab-pane p-3" id="invoice" role="tabpanel">
			<p class="mb-0">
				<div class="col-12">
					<h4 class="mt-0 header-title">Invoice Print Settings</h4>
					<p class="text-muted m-b-30"></p>
					<dl class="row mb-0">
						<dt class="col-sm-3">Logo</dt>
						<dd class="col-sm-9"><img src="'.$config_url.'uploads/'.$_SESSION['soft']['settings']['logo'].'" name="logo_'.$_SESSION['branch'].'" style="height:50px;" path="uploads/" class="dropzone" query="UPDATE settings SET description=\'[image]\' WHERE title=\'logo\' AND branch=\''.$_SESSION['branch'].'\'" title="logo"/></dd>
						
						<dt class="col-sm-3">Company Name</dt>
						<dd class="col-sm-9"><input type="text" name="company_name" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['company_name'])?$_SESSION['soft']['settings']['company_name']:'').'"/></dd>
						
						<dt class="col-sm-3">Address</dt>
						<dd class="col-sm-9"><textarea name="address" rows="3" maxlength="256" class="settings_update form-control">'.(isset($_SESSION['soft']['settings']['address'])?$_SESSION['soft']['settings']['address']:'').'</textarea></dd>
						
						<dt class="col-sm-3">Phone Number</dt>
						<dd class="col-sm-9"><input type="text" name="phone_no" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['phone_no'])?$_SESSION['soft']['settings']['phone_no']:'').'"/></dd>
						
						<dt class="col-sm-3">Footer</dt>
						<dd class="col-sm-9"><textarea name="invoice_footer" rows="3" maxlength="256" class="settings_update form-control">'.(isset($_SESSION['soft']['settings']['invoice_footer'])?$_SESSION['soft']['settings']['invoice_footer']:'').'</textarea></dd>
						
						<dt class="col-sm-3">Cash Voucher</dt>
						<dd class="col-sm-9">';
						$cash_voucher=0;
						if(isset($_SESSION['soft']['settings']['cash_voucher']))$cash_voucher=$_SESSION['soft']['settings']['cash_voucher'];
							$content.='<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off_personalized" data-toggle="buttons">
								<label class="btn btn-secondary pointer '.($cash_voucher==1?'active':'').'">
									<input type="radio" class="settings_update" name="cash_voucher" value="1" '.($cash_voucher==1?'checked':'').'>Enable
								</label>
								<label class="btn btn-secondary pointer '.($cash_voucher==0?'active':'').'">
									<input type="radio" class="settings_update" name="cash_voucher" value="0" '.($cash_voucher==0?'checked':'').'>Disable
								</label>
							</div>
						</dd>
						
						<dt class="col-sm-3">Invoice Paper Width (mm)</dt>
						<dd class="col-sm-9"><input type="text" name="invoice_paper_width" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['invoice_paper_width'])?$_SESSION['soft']['settings']['invoice_paper_width']:'').'"/></dd>
						
						<dt class="col-sm-3">Font Size</dt>
						<dd class="col-sm-9"><input type="text" name="invoice_font_size" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['invoice_font_size'])?$_SESSION['soft']['settings']['invoice_font_size']:'').'"/></dd>
						
						<dt class="col-sm-3">Font Bold</dt>
						<dd class="col-sm-9">';
						$invoice_font_bold=0;
						if(isset($_SESSION['soft']['settings']['invoice_font_bold']))$invoice_font_bold=$_SESSION['soft']['settings']['invoice_font_bold'];
							$content.='<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off_personalized" data-toggle="buttons">
								<label class="btn btn-secondary pointer '.($invoice_font_bold==1?'active':'').'">
									<input type="radio" class="settings_update" name="invoice_font_bold" value="1" '.($invoice_font_bold==1?'checked':'').'>On
								</label>
								<label class="btn btn-secondary pointer '.($invoice_font_bold==0?'active':'').'">
									<input type="radio" class="settings_update" name="invoice_font_bold" value="0" '.($invoice_font_bold==0?'checked':'').'>Off
								</label>
							</div>
						</dd>
						
						<dt class="col-sm-3">Font</dt>
						<dd class="col-sm-9"><input type="text" name="invoice_font" class="settings_update form-control fonts" value="'.(isset($_SESSION['soft']['settings']['invoice_font'])?$_SESSION['soft']['settings']['invoice_font']:'').'"/></dd>
					</dl>
				</div>
			</p>
		</div>';
	}
	if(in_array('label',$permission)){
		$tab.='<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#label_print" role="tab">
				<span class="d-block d-sm-none"><i class="far fa-user"></i></span>
				<span class="d-none d-sm-block">Label</span> 
			</a>
		</li>';
		$content.='<div class="tab-pane p-3" id="label_print" role="tabpanel">
			<p class="mb-0">
				<div class="col-12">
					<h4 class="mt-0 header-title">Label Print Settings</h4>
					<p class="text-muted m-b-30"></p>
					<dl class="row mb-0">';
					if($_SESSION['soft']['user_data']['user_level']==0){
						$content.='<dt class="col-sm-3">Need Approval</dt>
						<dd class="col-sm-9">';
						$repack_approval=0;
						if(isset($_SESSION['soft']['settings']['repack_approval']))$repack_approval=$_SESSION['soft']['settings']['repack_approval'];
							$content.='<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off_personalized" data-toggle="buttons">
								<label class="btn btn-secondary pointer '.($repack_approval==1?'active':'').'">
									<input type="radio" class="settings_update" name="repack_approval" value="1" '.($repack_approval==1?'checked':'').'>On
								</label>
								<label class="btn btn-secondary pointer '.($repack_approval==0?'active':'').'">
									<input type="radio" class="settings_update" name="repack_approval" value="0" '.($repack_approval==0?'checked':'').'>Off
								</label>
							</div>
						</dd>';
					}
						$content.='<dt class="col-sm-3">Stickers per Row</dt>
						<dd class="col-sm-9"><input type="text" name="stickers_per_row" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['stickers_per_row'])?$_SESSION['soft']['settings']['stickers_per_row']:'').'"/></dd>
						
						<dt class="col-sm-3">Font</dt>
						<dd class="col-sm-9"><input type="text" name="label_font" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_font'])?$_SESSION['soft']['settings']['label_font']:'').'"/></dd>
						
						<dt class="col-sm-3">Font Size</dt>
						<dd class="col-sm-9"><input type="text" name="label_font_size" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_font_size'])?$_SESSION['soft']['settings']['label_font_size']:'').'"/></dd>
						
						<dt class="col-sm-3">Company Name</dt>
						<dd class="col-sm-9"><input type="text" name="label_company_name" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_company_name'])?$_SESSION['soft']['settings']['label_company_name']:'').'"/></dd>
						
						<dt class="col-sm-3">Address ON Label</dt>
						<dd class="col-sm-9"><input type="text" name="label_address" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_address'])?$_SESSION['soft']['settings']['label_address']:'').'"/></dd>
						
						<dt class="col-sm-3">Dimension</dt>
						<dd class="col-sm-9 mb-0">
							<dl class="row mb-0">
								<dt class="col-sm-2"><input type="text" name="label_width" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_width'])?$_SESSION['soft']['settings']['label_width']:'').'"/><ul class="parsley-errors-list filled"><li class="parsley-required">Width</li></ul></dt>
								<dd class="col-sm-2"><input type="text" name="label_height" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['label_height'])?$_SESSION['soft']['settings']['label_height']:'').'"/><ul class="parsley-errors-list filled"><li class="parsley-required">Height</li></ul></dd>
							</dl>
						</dd>
					</dl>
				</div>
			</p>
		</div>';
	}
	if(in_array('points',$permission)){
		$tab.='<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#points" role="tab">
				<span class="d-block d-sm-none"><i class="far fa-user"></i></span>
				<span class="d-none d-sm-block">Points</span> 
			</a>
		</li>';
		$content.='<div class="tab-pane p-3" id="points" role="tabpanel">
			<p class="mb-0">
				<div class="col-12">
					<h4 class="mt-0 header-title">Points and Points SMS Settings</h4>
					<p class="text-muted m-b-30"></p>
					<dl class="row mb-0">';
					if($_SESSION['soft']['user_data']['user_level']==0){
						$content.='<dt class="col-sm-3">Points SMS</dt>
						<dd class="col-sm-9">';
						$points_sms=0;
						if(isset($_SESSION['soft']['settings']['points_sms']))$points_sms=$_SESSION['soft']['settings']['points_sms'];
							$content.='<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off_personalized" data-toggle="buttons">
								<label class="btn btn-secondary pointer '.($points_sms==1?'active':'').'">
									<input type="radio" class="settings_update" name="points_sms" value="1" '.($points_sms==1?'checked':'').'>On
								</label>
								<label class="btn btn-secondary pointer '.($points_sms==0?'active':'').'">
									<input type="radio" class="settings_update" name="points_sms" value="0" '.($points_sms==0?'checked':'').'>Off
								</label>
							</div>
						</dd>';
					}
						$content.='<dt class="col-sm-3">Minimum Points</dt>
						<dd class="col-sm-9"><input type="text" name="sms_minimum_points" class="settings_update form-control" value="'.(isset($_SESSION['soft']['settings']['sms_minimum_points'])?$_SESSION['soft']['settings']['sms_minimum_points']:'').'"/></dd>
						
						<dt class="col-sm-3">SMS Format</dt>
						<dd class="col-sm-9"><textarea name="sms_format_points" rows="3" maxlength="600" class="settings_update text_count form-control">'.(isset($_SESSION['soft']['settings']['sms_format_points'])?$_SESSION['soft']['settings']['sms_format_points']:'').'</textarea></dd>
						
					</dl>
				</div>
			</p>
		</div>';
	}
	if($tab<>''){
		echo '<div class="col-lg-12">
			<div class="card">
				<div class="card-body">
					<h4 class="mt-0 header-title">Settings for Branch '.$_SESSION['branch_data']['title'].'</h4>
					<ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">'.$tab.'</ul>
					<div class="tab-content">'.$content.'</div>
				</div>
			</div>
		</div>';
	}
	echo '<script>
	var fonts = [{font: "Bodoni MT"},{font: "Bodoni MT Black"},{font: "adobe garamond pro"},{font: "Heisman"},{font: "Roboto, sans-serif"},{font: "Franklin Gothic"},{font: "Rockwell"},{font: "Verdana"},{font: "Cambria"},{font: "Futura"},{font: "Calibri"},{font: "Helvetica"},{font: "Ephesis"},{font: "Arial"},{font: "Times New Roman"},{font: "Georgia, serif"},{font: "Verdana"},{font: "Palatino"},{font: "Times"},{font: "Courier New"},{font: "Courier"},{font: "Garamond"},{font: "Comic Sans MS"},{font: "Tahoma"},{font: "Impact"},{font: "Arial Black"},{font: "Trebuchet MS"},{font: "Bookman"},{font: "SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace"},];
	remote_file="'.$remote_file.'";
	</script>';
?>
<script>
	function after_ajax(){
		
	}
	$('.settings_update').bind('change',function(){
		$.post(remote_file+"/general",{form_submit:'settings',title:$(this).attr('name'),value:$(this).val()},function(data){
			$.ajax_indicate(data);
		});
	});
	$( ".fonts" ).autocomplete({
		minLength: 1,
			autoFocus: false,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( fonts, function( value ) {
					return matcher.test(value.font.replace(/\ /g, ""));
				}).slice(0, 15) );
			},
			focus: function(event,ui){
				event.preventDefault();
				return false;
			},
			response: function(event, ui) {
				
			},
			select: function(event,ui){
				$(this).val(ui.item.font).trigger('change');
				return false;
			}
	}).data("ui-autocomplete")._renderItem = function(ul,item){
			return $("<li>").append("<a style='font-family:"+item.font+";'>"+item.font+"</a>").appendTo(ul);
		};;
</script>
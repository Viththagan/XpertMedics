<!DOCTYPE html>
<html lang="en">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
<link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans:300,300i,400,400i,500,500i,600,600i,700,700i|Roboto:300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet"> 
<link rel="apple-touch-icon" sizes="180x180" href="https://dfc.lk/app/icons/icon-192x192.png">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="manifest" href="<?php echo $config_url;?>_manifest.json" data-pwa-version="set_by_pwa.js">
<link rel="stylesheet" type="text/css" href="<?php echo $config_url;?>assets/framework.css">
<?php
			echo '<title>'.(isset($_SESSION['settings']['website_title'])?$_SESSION['settings']['website_title']:'SAAS Login').' - AgoySoft (Pvt) Ltd</title>
			<meta content="Software as Service" name="description" />
			<meta content="AgoySoft (Pvt) Ltd" name="author" />
			<!-- Plugins css -->
        <link href="'.$config_url.'plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="'.$config_url.'plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="'.$config_url.'plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="'.$config_url.'plugins/bootstrap-touchspin/css/jquery.bootstrap-touchspin.min.css" rel="stylesheet" />
		<link href="'.$config_url.'plugins/RWD-Table-Patterns/dist/css/rwd-table.min.css" rel="stylesheet" type="text/css" media="screen">
		<!-- jvectormap -->
        <link href="'.$config_url.'plugins/jvectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet">		
		<link rel="stylesheet" href="'.$config_url.'plugins/summernote/summernote-bs4.css">
			<!--Chartist Chart CSS -->
			<link rel="stylesheet" href="'.$config_url.'plugins/chartist/css/chartist.min.css">
			<link href="'.$config_url.'assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
			<link href="'.$config_url.'assets/css/metismenu.min.css" rel="stylesheet" type="text/css">
			<link href="'.$config_url.'assets/css/icons.css" rel="stylesheet" type="text/css">
            <link href="'.$config_url.'assets/css/style.css" rel="stylesheet" type="text/css">
            <link href="'.$config_url.'assets/css/autocomplete.css" rel="stylesheet" type="text/css">
			<!--Morris Chart CSS -->
        <link rel="stylesheet" href="'.$config_url.'plugins/morris/morris.css">
			<!-- DataTables -->
        <link href="'.$config_url.'plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="'.$config_url.'plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
       <!-- Dropzone css -->
        <link href="'.$config_url.'plugins/dropzone/dist/dropzone.css" rel="stylesheet" type="text/css">
		<!-- Responsive datatable examples -->
        <link href="'.$config_url.'plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" type="text/css" href="'.$config_url.'fancybox/jquery.fancybox.css?v=2.1.4" media="screen" />
		<!-- Sweet Alert -->
        <link href="'.$config_url.'plugins/sweet-alert2/sweetalert2.min.css" rel="stylesheet" type="text/css">
		<!--Jquery steps CSS -->
        <link rel="stylesheet" href="'.$config_url.'plugins/jquery-steps/jquery.steps.css">
            <!-- Toggle  -->
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
		 <!-- Magnific popup -->
        <link href="'.$config_url.'plugins/magnific-popup/magnific-popup.css" rel="stylesheet" type="text/css">
		
        <link href="'.$config_url.'plugins/emoji-picker-master/css/emoji.css" rel="stylesheet">

		<style>
.warning{background: rgb(241 8 8 / 47%) !important;}
.warning_10{background: rgba(226, 73, 0, 0.88) !important}
.warning2{background: rgba(226, 188, 0, 0.54) !important;}
.pending_invoice{background: rgba(226, 188, 0, 0.54) !important;}
#customer_data[plat="1"]{background-color: #ff18006b;}
#customer_data[plat="2"]{background-color: #33CCFF;}
#customer_data[plat="4"]{background-color: #00FF00;}
#customer_data[plat="6"]{background-color: #3333FF;}
#customer_data[plat="8"]{background-color: azure;}
.discount_special_offer{background: rgba(0, 226, 80, 0.28) !important;}
.discount_color_10{background: rgba(226, 219, 0, 0.28) !important;}
.discount_color_15{background: rgba(226, 219, 0, 0.69) !important;}
.discount_color_20{background: rgba(226, 73, 0, 0.65) !important;}
.discount_color_25{background: rgba(226, 0, 31, 0.69) !important;}
.discount_color_0{background: rgba(9, 90, 236, 0.57) !important;}
.mobile_430{background: rgb(9 236 236 / 34%) !important;}
.editable{min-width: 70px;}
.incoming_sms{background:rgb(54 236 9 / 34%) !important;}
.success{background:rgb(54 236 9 / 34%) !important;}
.center{text-align:center;}
.right{text-align:right;}
.pointer{cursor:pointer;}
.hide{display:none;}
.ui-helper-hidden-accessible{display:none;}
.ui-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 10000;
    float: left;
    display: none;
    min-width: 160px;   
    padding: 4px 0;
    margin: 0 0 10px 25px;
    list-style: none;
    background-color: #ffffff;
    border-color: #ccc;
    border-color: rgba(0, 0, 0, 0.2);
    border-style: solid;
    border-width: 1px;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
    -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    -webkit-background-clip: padding-box;
    -moz-background-clip: padding;
    background-clip: padding-box;
    *border-right-width: 2px;
    *border-bottom-width: 2px;
}

.ui-menu-item > a.ui-corner-all {
    display: block;
    padding: 3px 15px;
    clear: both;
    font-weight: normal;
    line-height: 18px;
    color: #555555;
    white-space: nowrap;
    text-decoration: none;
}
.ui-menu .ui-menu-item a{
     display: block;
    padding: 3px 15px;
    clear: both;
    font-weight: normal;
    line-height: 18px;
    color: #555555;
    white-space: nowrap;
    text-decoration: none;
}
.ui-state-hover, .ui-state-active {
    color: #ffffff !important;
    text-decoration: none !important;
    background-color: #0088cc !important;
    border-radius: 0px !important;
    -webkit-border-radius: 0px !important;
    -moz-border-radius: 0px !important;
    background-image: none !important;
}
.selected{background-color: lawngreen !important;}
.on_off .active{background-color: #09ff05d1 !important;border-color: #0bec07 !important;}
.on_off_personalized .active{background-color: #09ff05d1 !important;border-color: #0bec07 !important;}
#ajax_indicator{position: fixed;min-width: 50%;left: 10px;bottom: 10px;z-index: 100;}
</style>';
		?>
 </head>
    <body class="enlarged" data-keep-enlarged="true" style="font-family: SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;">
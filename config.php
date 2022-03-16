<?php
    session_start();
	$url = (!empty($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	date_default_timezone_set("Asia/Colombo");
	$current_time=date("Y-m-d H:i:s");
	$today=date("Y-m-d");
	if(!isset($_SESSION['soft']['ip']))$_SESSION['soft']['ip']=$_SERVER['REMOTE_ADDR'];
    $config_url="https://$_SERVER[SERVER_NAME]/soft/";
	$plugin_path='addons';
    $dir=dirname(__FILE__)."/$plugin_path/";
	
	$prefix='X';
	$sms_mask=array(0=>'XpertMedics');
	if(!isset($domain))$domain='xpertmedics.lk';
	$uicode_prefix='xm';
	$admin_mobile='0773946232';
	$client='00004';
	$business_name='Xpert Medics (Pvt) Ltd';
	$con = mysqli_connect("localhost","agoysoft","agoyatsoft03","xpertmedics");
	if (!$con)die("Connection error: " . mysqli_connect_error());
	$packing_company_id=194;
	$counter_expiry=86400;
	$upload_path='uploads';
	
	$error_access=3;
	$languages=array('en'=>'English','ta'=>'Tamil','si'=>'Sinhala');
    $preg_replace="/[^a-zA-Z0-9 \/,-.:()\[\]]+/";
	$product=array(
	'type'=>array(0=>'Non',1=>'Weighted'),
	'discount'=>array(0=>'Disabled',1=>'Enabled'),
	'price'=>array(0=>'Vary',1=>'Fixed'),
	'status'=>array(1=>'Active',0=>'Inactive',-1=>'Trashed'),
	);
	$po_status=array(0=>'Pending',1=>'Closed',2=>'Locked','-1'=>'Deleted',3=>'Closed',4=>'Closed');
	$supplier_status=array(0=>'Non-Important',1=>'Needy',2=>'Important',3=>'Fancy Suppliers');
	$payment_methods=array(1=>'Cash',4=>'Card',6=>'Voucher',5=>'Points',8=>'Smart Pay/UPAY',2=>'Cheque',3=>'Bank Transfer',7=>'Ez Cash',0=>'Credit');
	$payment_methods_print=array(1=>'Cash Tendered',4=>'Card',6=>'Gift Voucher',5=>'Redeemed Star Points',8=>'Smart Pay/UPAY',2=>'Cheque',3=>'Bank Deposit',7=>'Ez Cash',0=>'Credit');
	$memberships=array(''=>'Walkin',0=>'Customer',1=>'Staff',2=>'Relatives',3=>'Bulk',4=>'Platinum',5=>'DFC',6=>'Golden',8=>'Vidhya');
	$customer_types=array(0=>'No Customer',1=>'Guest',2=>'Wandering',3=>'Potential',4=>'Discount',5=>'Needy',6=>'Impulsive',7=>'Loyal',8=>'Staff',9=>'DFC');
	$query_avoid=array('INSERT','UPDATE','DELETE','FILE','CREATE','ALTER','INDEX','DROP','CREATETEMPORARYTABLES','SHOWVIEW','CREATEROUTINE','ALTERROUTINE','EXECUTE','CREATEVIEW','EVENT','TRIGGER','GRANT','SUPER','PROCESS','RELOAD','SHUTDOWN','SHOWDATABASES','LOCKTABLES','REFERENCES','REPLICATIONCLIENT','REPLICATIONSLAVE','CREATEUSER');
	$customer_types_info=array(0=>'They wont Buy from DFC anyhow'
	,1=>'Once or twice Visited, may buy n future'
	,2=>'Comes, just for entertainment, or for small needs. like ice cream, biscuits'
	,3=>'Come to purchase bulk once or twice, they can be moved to loyal'
	,4=>'Comes for discounted goods or for offers'
	,5=>'Purchases for needs, they will have specific idea what to purchase. they may purchase bulk somewhere else'
	,6=>'Come to shopping. no idea what they need. but will purchase the goods they are interested'
	,7=>'Attached with DFC. they hate to go other places. VVIP to DFC'
	,8=>'Staff both current and passed'
	,9=>'DFC internal purpose');
	// $payments_type_old=array('1'=>'Printing & Stationery','2'=>'Advertising & Promotions','3'=>'Fuel & Transport Charges','4'=>'Repairs & Maintenance','5'=>'Other Admin Expenses',
	// '6'=>'Security Service Charges','7'=>'Staff Expenses','8'=>'Donations','9'=>'Other Selling & Distribution','10'=>'Subscriptions','11'=>'Legal Fees','12'=>'Insurance','13'=>'Other Expenses','14'=>'Festival Expenses','15'=>'NBT','16'=>'Income Tax','17'=>'Rent','18'=>'Salary','19'=>'Wages','20'=>'Assets','21'=>'EPF,ETF');
	
	// '5'=>'Other Admin Expenses','6'=>'Security Service Charges','8'=>'Donations','10'=>'Subscriptions','11'=>'Legal Fees','13'=>'Other Expenses','14'=>'Festival Expenses','15'=>'NBT','17'=>'Rent','21'=>'EPF,ETF');
	$payments_type=array(1=>'Purchases',2=>'Salary',3=>'Printing & Stationery',4=>'Fuel & Transport Charges',5=>'Repairs & Maintenance',6=>'Advertising & Promotions',7=>'Other Selling & Distribution',8=>'Tax and Insurance',9=>'Wages',10=>'Pettycash',11=>'Staff Expenses',12=>'Assets');
	$pos_machines=array(74=>'BOC',1=>'Commercial',28=>'Peoples',2=>'HNB');
	$partners=array(522,523);
	$cheque_status=array(0=>'Drawn',1=>'Cancelled',2=>'Handed',3=>'Tranferred',4=>'Bounced',5=>'Bounced Paid');
	$sales_status=array(0=>'error',1=>'Credit',2=>'Paid',3=>'Items',4=>'Items sth wrong',5=>'Over paid',6=>'Over Paid <9',7=>'?',8=>'DB items',9=>'DB payments');
	$memberships_requirement=array(0=>0,1=>0,2=>5000,3=>'0',4=>25000,5=>0,6=>15000,8=>5000);
	$bill_payment_status=array(0=>'Pending',1=>'Processing',2=>'Success',3=>'Error');
	$salary_fields=array('Transport','Incentive','ot');
	
?>
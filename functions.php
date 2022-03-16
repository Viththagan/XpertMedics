<?php
	function invoice_no($no,$date,$till=0){
		global $prefix;
		// return sprintf('D'.date('ymd',strtotime($date)).$till."%03d",$no);
		return $prefix.date('ymd',strtotime($date)).$till.$no;
	}
	function print_cheque($id){
		global  $_SESSION,$config_url,$con;
			$result=mysqli_query($con,"SELECT * FROM payments_cheques WHERE id='$id'") or die (mysqli_error($con));
			$row=mysqli_fetch_assoc($result);
			if($row['payee']==''){
				$result=mysqli_query($con,"SELECT s.cheque_name FROM po_payments pp LEFT JOIN po ON po.id=pp.po 
				LEFT JOIN suppliers s ON s.id=po.supplier WHERE pp.payment='$row[payment]'") or die (mysqli_error($con));
				if(mysqli_affected_rows($con)>0){
					$row1=mysqli_fetch_assoc($result);
					$row['payee']=$row1['cheque_name'];
				}
			}
			$date=date('dmY',strtotime($row['transfer']));
			if($row['bank']==74){
				$date=date('dm',strtotime($row['transfer'])).'&nbsp;&nbsp;'.date('y',strtotime($row['transfer']));
			}
				$print='<style>
			.cheque .cross {content: "";width: 100px;height: 50px;position: absolute;transform: rotateZ(45deg);transform-origin: bottom right;width: 10px;border-left: solid;border-right: solid;top: -5px;Left: -5px;}
.cheque .date {padding-left: 116mm;padding-top: 6.5mm;position: fixed;letter-spacing: 11px;font-size: 23px;}
.cheque .payee {padding-left: 12mm;padding-top: 22mm;position: fixed;}
.cheque .amount_text {width:90mm;line-height: 8mm;padding-left: 8mm;padding-top: 31mm;position: fixed;}
.cheque .amount {padding-left: 115mm;padding-top: 38mm;position: fixed;font-size: 23px;}
.cheque .ac_pay {padding-left: 50mm;padding-top: 55mm;position: fixed;}
.cheque .ac_border {position: fixed;border-top: solid;border-bottom: solid;border-width: 1px;padding: 5px;}
.cheque .ac_text{font-size: 12px;margin: 10px;}
#cheque{transform: rotate(-90deg);margin-left: -660px;padding-right: 1300px;}
			</style>
			<div id="cheque">
			<div class="cheque">
				<span class="date">'.$date.'</span>
				<span class="payee">'.($row['payee']<>''?$row['payee']:'Cash').'</span>
				<span class="amount_text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ucwords(convertNumber($row['amount'])).' Only</span>
				<span class="amount">'.Number_format($row['amount'],2).'/=</span>
				'.($row['payee']<>''?'<span class="ac_pay"><span class="ac_border"><span class="ac_text">A/C PAYEE ONLY</span></span></span>':'').'
			</div>
			</div>';
		return $print;
	}
	function print_label($id){
		global  $_SESSION,$config_url,$con;
		
		$result=mysqli_query($con,"SELECT p.title,p.title_tamil,p.ui_code,l.*,y.id batch,y.date,y.manufactured FROM products_logs l LEFT JOIN yura_batch_history y ON l.referrer=y.id 
		LEFT JOIN products p ON p.id=l.product WHERE l.id='$id'");
		$row=mysqli_fetch_assoc($result);
		$file='https://barcode.tec-it.com/barcode.ashx?data='.$row['ui_code'].'&dpi=100&imagetype=Svg';
		file_get_contents($file);
			$title=$row['title'];
			$price=$row['mrp'];
			if($row['mrp']>$row['selling_price'] AND $row['selling_price']>0){
				$price='<span class="cross">'.$row['mrp'].'</span><br/>'.$row['selling_price'];
			}
			$price='MRP Rs/<span class="tamil">'.tamil('MRP').'</span> : <span style="font-size:16px;position:fixed;bottom:48px;line-height:14px;">'.$price.'</span>';
			$pkd='Pkd Date/<span class="tamil">'.tamil('Pkd').'</span> : '.$row['date'];
			$exp='Exp Date/<span class="tamil">'.tamil('Exp').'</span> : '.$row['expiry'];
			$batch='Batch No/<span class="tamil">'.tamil('Batch No').'</span> : '.$row['batch'];
			$htm='<img src="'.$file.'" class="barcode"/><br/>
			'.$title.'<br/>
			<span class="tamil">'.trim($row['title_tamil']).'</span><br/>
			'.$pkd.'<br/>
			'.$exp.'<br/>
			'.$batch.'<br/>
			'.$price.'<br/>
			<span style="font-size:13px;">'.$_SESSION['soft']['settings']['label_company_name'].'</span><br/>
			<span style="font-size:9px;">'.$_SESSION['soft']['settings']['label_address'].'</span>';
			$print='<div style="margin: -8px;">';
			$stickers=(isset($_SESSION['soft']['settings']['stickers_per_row'])?$_SESSION['soft']['settings']['stickers_per_row']:2);
			for($i=1; $i<=$stickers; $i++)$print.='<div class="print_label" style="padding-left: 5mm;">'.$htm.'</div>';
				$print.='</div>
			<style>
			.tamil{font-size:9px;}
			.barcode{height: 19mm;margin-top: -25px;}
			.cross{text-decoration:line-through}
			@page{size: landscape;}
			print_label {height: '.$_SESSION['soft']['settings']['label_height'].'mm;width: '.$_SESSION['soft']['settings']['label_width'].'mm;margin: -8px;}
			.print_label {float: left;font-size: '.$_SESSION['soft']['settings']['label_font_size'].'px;font-weight:bold;color:#000000;}
			.print_label:nth-child(1) {margin-left: 0mm;}
			</style>';
		return $print;
	}
	function print_receipt($id){
		global $_SESSION,$config_url,$con,$payment_methods_print;
		$result=mysqli_query($con,"SELECT s.*,u.short,u.first_name,c.nic,c.fname,c.lname,c.id cid,c.street,c.plat,c.mobile,c.total,c.invoice_count,c.experience,pin.points,d.id delivery_id 
			FROM sales s LEFT JOIN customers c ON s.customer=c.id LEFT JOIN sales_delivery d ON d.sales=s.id LEFT JOIN sales_points pin ON pin.sale=s.id LEFT JOIN users u ON u.id=s.user
			WHERE s.id='$id'") or die (mysqli_error($con));
		$data=mysqli_fetch_assoc($result);
		$img='https://dfc.lk/app/icons/icon-325x200.png';
		if(file_exists('uploads/'.$_SESSION['soft']['settings']['logo']))$img=$config_url.'uploads/'.$_SESSION['soft']['settings']['logo'];
		$html='<div class="row print_invoice" style="page-break-before:always;'.((isset($_SESSION['soft']['settings']['invoice_font']) AND $_SESSION['soft']['settings']['invoice_font']<>'')? 'font-family: '.$_SESSION['soft']['settings']['invoice_font'].';':'').'">
			<div class="col-12">
				<div class="invoice-title">
					<center><img src="'.$img.'" style="width:50%;"/>
					<div style="margin-top: 0px;line-height: 1;">'.$_SESSION['soft']['settings']['address'].'<br/>'.$_SESSION['soft']['settings']['phone_no'].'</div></center>
				</div><hr>
				<div class="invoice-title">
					<div class="mt-0 float-right">'.invoice_no($id,$data['date'],$data['till']).($data['delivery_id']>0?'<br/>'.$data['delivery_id']:'').'</div>
					<div class="mb-4">'.$data['first_name'].' ('.$data['branch'].':'.$data['till'].')<br/>'.date("Y-m-d h:i A",strtotime($data['added'])).'</div>
				</div>
				<div>
					<table class="table"><thead>
						<th><strong>#</strong></th>
						<th><strong>Item</strong></th>
						<th class="right"><strong>Qty</strong></th>
						<th class="right"><strong>MRP</strong></th>
						<th class="right"><strong>Discount</strong></th>
						<th class="right"><strong>Amount</strong></th></thead>
						<tbody>';
						$gross_total=0;
						$counter=1;
						$discount=0;
						$plat_total=0;
						$unilever_category=array(27,25,10,43,22,44,30,29,23);
						$unilever=0;
						$result=mysqli_query($con,"SELECT l.*,p.title,p.title_tamil,p.brand,p.category,d.title service,b.number 
						FROM products_logs l LEFT JOIN products p ON l.product=p.id LEFT JOIN bill_payments b ON b.log=l.id LEFT JOIN bill_payments_domain d ON d.id=b.service 
						WHERE l.referrer='$id'") or die (mysqli_error($con));
						while($item=mysqli_fetch_assoc($result)){
							if(in_array($item['brand'],$unilever_category))$unilever+=$item['selling_price']*$item['qty'];
							if($item['service']<>''){
								$item['title_tamil']=$item['service'].' - '.$item['number'];
							}
							$html.='<tr>
								<td class="center">'.$counter++.'</td>
								<td colspan="5">'.((strlen($item['title_tamil'])>3)?'<span class="tamil_text">'.$item['title_tamil'].'</span>':$item['title']).'</td>
							</tr>
							<tr>
								<td class="no-line"></td>
								<td class="center no-line"></td>
								<td class="no-line right">'.number_format($item['qty'],3).'</td>
								<td class="no-line right">'.number_format($item['mrp'],2).'</td>
								<td class="no-line right">'.number_format(($item['mrp']-$item['selling_price'])*$item['qty'],2).'</td>
								<td class="no-line right">'.number_format(($item['qty']*$item['selling_price']),2).'</td>
							</tr>';
							$gross_total+=($item['qty']*$item['mrp']);
							$discount+=($item['qty']*($item['mrp']-$item['selling_price']));
							if($item['purchased']>0){
								$plat_total+=($item['qty']*($item['purchased']*1.02));
							}
							else $plat_total+=($item['qty']*$item['selling_price']);
						}
						$net_total=$gross_total-$discount;
						$rounding=$net_total-ceil($net_total);
						$net_total=ceil($net_total+$data['charge']);
						$html.='<tr><td colspan="5" class="right">Gross Total:</td><td class="right">'.number_format($gross_total,2).'</td></tr>
						<tr><td colspan="5" class="right no-line">Discount:</td><td class="right no-line">('.number_format($discount,2).')</td></tr>
						'.($data['charge']>0?'<tr><td colspan="5" class="right no-line">Charge :</td><td class="right no-line">'.number_format($data['charge'],2).'</td></tr>':'').'
						'.($rounding>0?'<tr><td colspan="5" class="right no-line">Rounding:</td><td class="right no-line">'.number_format($rounding,2).'</td></tr>':'').'
						<tr><td colspan="5" class="right no-line">Net Total:</td><td class="right no-line">'.number_format($net_total,2).'</td></tr>';
						$pay=0;
						$result=mysqli_query($con,"SELECT * FROM sales_payments WHERE sales='$id'") or die (mysqli_error($con));
						while($payment=mysqli_fetch_assoc($result)){
							if($payment['type']==1){
								if($payment['cash']==0 AND $payment['amount']>0)$payment['cash']=$payment['amount'];
								$html.='<tr><td colspan="5" class="right no-line">Cash Tendered :</td><td class="right">'.number_format($payment['cash'],2).'</td></tr>';
								$pay+=$payment['cash'];
							}
							else {
								$html.='<tr><td colspan="5" class="right no-line">'.$payment_methods_print[$payment['type']].' :</td><td class="right">'.number_format($payment['amount'],2).'</td></tr>';
								$pay+=$payment['amount'];
							}
						}
						if($net_total>$pay)$html.='<tr><td colspan="5" class="right no-line">Due:</td><td class="right">'.number_format(($net_total-$pay),2).'</td></tr>';
						else if($net_total<$pay)$html.='<tr><td colspan="5" class="right no-line">Balance:</td><td class="right">'.number_format(($pay-$net_total),2).'</td></tr>';
						
						$exp='';
						$total_purchase_this_month=0;
						if($data['customer']>0){
							$result=mysqli_query($con,"SELECT sum(points-used) total FROM sales_points WHERE customer='$data[customer]' AND (expiry>=NOW() OR expiry is null)");
							$points=mysqli_fetch_assoc($result);
							$html.='<tr><td colspan="6">Name : '.$data['fname'].' '.$data['lname'].'</td></tr>';
							$html.='<tr><td colspan="6">Earned Points : '.number_format($data['points'],2).'</td></tr>
							<tr><td colspan="6">Total Points : '.number_format($points['total'],2).'</td></tr>';
							$month=date('m');
							$year=date('Y');
							// $result=mysqli_query($con,"SELECT SUM(si.qty*(si.discount)) saved FROM sales_items si LEFT JOIN sales s ON s.id=si.invoice WHERE s.customer='$data[customer]' AND MONTH(s.date)='$month' AND YEAR(s.date)='$year'") or die(mysqli_error($con));
							// $row=mysqli_fetch_assoc($result);
							// $html.='<tr><td colspan="6">You Saved in this Month : '.number_format(round($row['saved']),2).'</td></tr>';
							// $result=mysqli_query($con,"SELECT SUM(si.qty*(si.discount)) saved FROM sales_items si LEFT JOIN sales s ON s.id=si.invoice WHERE s.customer='$data[customer]'");
							// $row=mysqli_fetch_assoc($result);
							// $html.='<tr><td colspan="6">You Saved in this Year : '.number_format(round($row['saved']),2).'</td></tr>';
							
							// $exp='<hr/>';
							// $result=mysqli_query($con,"SELECT SUM(total) total FROM sales WHERE customer='$data[customer]' AND MONTH(date)='$month' AND YEAR(date)='$year'") or die(mysqli_error($con));
							// $row=mysqli_fetch_assoc($result);
							// $total_purchase_this_month=$row['total'];
							// $j=round($total_purchase_this_month/1000);
							// if($data['customer']==5)$j=18;
							// if($j>18)$j=18;
							// else if($j==0)$exp.='<i class="fas fa-angle-double-right"></i>';
							// for($i=0; $i<=$j; $i++){
								// $exp.='<i class="fas fa-angle-double-right"></i>';
							// }
							// $exp.='<hr/>';
							// $exp.='<hr/>
							// <span><i class="fas fa-walking"></i>&nbsp;&nbsp;'.$data['invoice_count'].' | 
							// &nbsp;<i class="fas fa-shoe-prints"></i>&nbsp;&nbsp;'.Number_format($data['total']/1000,0).' | 
							// &nbsp;<i class="fas fa-star-half-alt"></i>&nbsp;&nbsp;'.Number_format($data['experience']/100,0).'</span>
							// <hr/>';
						}
						$html.='</tbody>
					</table>
					'.$exp.'<table class="table">
					<tr><td class="center"><span class="tamil_text">'.(isset($_SESSION['soft']['settings']['invoice_footer'])?$_SESSION['soft']['settings']['invoice_footer']:$_SESSION['soft']['settings']['footer_tamil']).'</span></td></tr>
					<tr><td class="center">Software and Technical Support By :<br/>&#169; 2013, Agoysoft (pvt) Ltd.</td></tr>
					</table>
				</div>
			</div>
		</div>
		<style>.print_invoice .tamil_text{font-size: 14px !important;}
		.print_invoice{width:120mm !important;font-size:'.(isset($_SESSION['soft']['settings']['invoice_font_size'])?$_SESSION['soft']['settings']['invoice_font_size']:18).'px !important;font-weight:'.((isset($_SESSION['soft']['settings']['invoice_font_bold']) AND $_SESSION['soft']['settings']['invoice_font_bold']==1)?'bold':'').';padding-left:10px;}
		.print_invoice .table{width:100% !important;}
		.print_invoice .col-12{width:'.(isset($_SESSION['soft']['settings']['invoice_paper_width'])?$_SESSION['soft']['settings']['invoice_paper_width']:78).'mm !important;}
		.print_invoice .invoice-title{color:#000000;}
		.print_invoice td{padding: 0 !important}
		</style>';
		// if($data['plat']==0 AND ($net_total/$plat_total)>1.05 AND sizeof($data['item'])>2 AND $data['customer']>0 AND $data['branch']==1){
			// if(isset($_SESSION['settings']['plat_inst'])){
				// $text=str_replace('[amount]',Number_format($plat_total,2),$_SESSION['settings']['plat_inst']);
				// $text=str_replace('[total]',Number_format($total_purchase_this_month,2),$text);
				// $text=str_replace('[limit]',Number_format($plat_limit,2),$text);
				// $html.='<table class="main" style="page-break-before:always">
				// <tr><th colspan="6" style="text-align: center;">'.$text.'</th></tr>
				// </table>';
			// }
		// }
		// if($data['customer']>0){
			// $x=floor($unilever/500);
			// for($i=1; $i<=$x; $i++){
				// $html.='<table class="main" style="page-break-before:always">
					// <tbody>
						// <tr><td style="text-align: center;" colspan="2">*** Unilever Promo ***</td></tr>
						// <tr><td>Name : </td><td>'.$data['fname'].' '.$data['lname'].'</td></tr>
						// <tr><td>Address : </td><td>'.$data['customer_street'].'</td></tr>
						// <tr><td>Mobile : </td><td>'.$data['customer_phone'].'</td></tr>
						// <tr><td>Bill No : </td><td>'.invoice_no($data['invoice'],$data['date']).'</td></tr>
					// </tbody>
				// </table>';
			// }
		// }
		return $html;
	}
	function whatsapp_receipt($id){
		global $_SESSION,$config_url,$con,$payment_methods_print,$business_name;
		$result=mysqli_query($con,"SELECT s.*,u.short,u.first_name,c.nic,c.fname,c.lname,c.id cid,c.street,c.plat,c.mobile,c.total,c.invoice_count,
			c.experience,pin.points,d.id delivery_id,b.title branch_title
			FROM sales s LEFT JOIN customers c ON s.customer=c.id LEFT JOIN sales_delivery d ON d.sales=s.id LEFT JOIN sales_points pin ON pin.sale=s.id 
			LEFT JOIN users u ON u.id=s.user LEFT JOIN branches b ON b.id=s.branch WHERE s.id='$id'") or die (mysqli_error($con));
		$data=mysqli_fetch_assoc($result);
$space="   ";
$html="---".$space.$space.$space.$space."*$business_name*".$space.$space.$space.$space."---
";
$html.="***   *WhatsApp Invoice*   ***

";
$html.="Invoice # : *".invoice_no($id,$data['date'],$data['till'])."*";
$html.="

Cashier : *".$data['first_name']."*
Branch : *".$data['branch_title']."*
Till : *".$data['till']."*
Time : *".date("Y-m-d h:i A",strtotime($data['added']))."*";


$gross_total=0;
$discount=0;
$plat_total=0;
$discount_col=0;
$result=mysqli_query($con,"SELECT l.*,p.title,p.title_tamil,p.brand,p.category,d.title service,b.number 
FROM products_logs l LEFT JOIN products p ON l.product=p.id LEFT JOIN bill_payments b ON b.log=l.id LEFT JOIN bill_payments_domain d ON d.id=b.service 
WHERE l.referrer='$id'") or die (mysqli_error($con));
$items=array();
while($item=mysqli_fetch_assoc($result)){
	if($item['service']<>'')$item['title_tamil']=$item['service'].' - '.$item['number'];
	
	$items[$item['id']]['title']=((strlen($item['title_tamil'])>3)?$item['title_tamil']:$item['title']);
	$items[$item['id']]['qty']=number_format($item['qty'],3);
	$items[$item['id']]['mrp']=number_format($item['mrp'],2);
	$items[$item['id']]['discount']=($item['mrp']-$item['selling_price'])*$item['qty'];
	$items[$item['id']]['total']=$item['selling_price']*$item['qty'];
	
	if($items[$item['id']]['discount']>0)$discount_col=1;
	
	$gross_total+=($item['qty']*$item['mrp']);
	$discount+=$items[$item['id']]['discount'];
	if($item['purchased']>0)$plat_total+=($item['qty']*$item['purchased']*1.03);
	else $plat_total+=($item['qty']*$item['selling_price']*0.9);
}

$html.="

*#".$space."Item".$space."Qty".$space."MRP".($discount_col==1?$space."Discount":"").$space."Amount*
";
$counter=1;
foreach($items as $item){
$html.="
".$counter++.$space.$item['title']."
".$space.$space.$item['qty'].$space.($item['discount']>0?"~".$item['mrp']."~":$item['mrp']).($discount_col==1?$space.number_format($item['discount'],2):"").$space."*".number_format($item['total'],2)."*";
}

$net_total=$gross_total-$discount;
$rounding=$net_total-ceil($net_total);
$net_total=ceil($net_total+$data['charge']);
$html.="

Gross Total:    *".number_format($gross_total,2)."*".($discount>0?"
Discount:    *(".number_format($discount,2).")*":"").($data['charge']>0?"
Charge :    *".number_format($data['charge'],2)."*":"").($rounding>0?"
Rounding:    *".number_format($rounding,2)."*":"")."
Net Total:    *".number_format($net_total,2)."*";
$pay=0;
$result=mysqli_query($con,"SELECT * FROM sales_payments WHERE sales='$id'") or die (mysqli_error($con));
while($payment=mysqli_fetch_assoc($result)){
	if($payment['type']==1){
		if($payment['cash']==0 AND $payment['amount']>0)$payment['cash']=$payment['amount'];
$html.="
Cash Tendered :    *".number_format($payment['cash'],2)."*";
		$pay+=$payment['cash'];
	}
	else {
$html.="
".$payment_methods_print[$payment['type']]." :    *".number_format($payment['amount'],2)."*";
		$pay+=$payment['amount'];
	}
}
if($net_total>$pay){
$html.="
Due:    ".number_format(($net_total-$pay),2);
}
else if($net_total<$pay){
	$html.="
Balance:    ".number_format(($pay-$net_total),2);
}
if($data['customer']>0){
	$result=mysqli_query($con,"SELECT sum(points-used) total FROM sales_points WHERE customer='$data[customer]' AND (expiry>=NOW() OR expiry is null)");
	$points=mysqli_fetch_assoc($result);
	$html.="

Name : ".$data['fname']." ".$data['lname'];
	$html.="
Earned Points : *".number_format($data['points'],2)."*
Total Points : *".number_format($points['total'],2)."*";
	$month=date('m');
	$year=date('Y');
}
$html.="

".str_replace('<br/>','',$_SESSION['soft']['settings']['invoice_footer']);
$html.="

".$_SESSION['soft']['settings']['address']."
".$_SESSION['soft']['settings']['phone_no'];
$html.="

Powered By *AgoySoft (Pvt) Ltd*";
		return urlencode($html);
	}
	function print_delivery_list($id){
		global $_SESSION,$config_url,$con,$current_time;
		$result=mysqli_query($con,"SELECT s.*,u.short,u.first_name,c.nic,c.fname,c.lname,c.id cid,c.street,c.plat,c.mobile,c.total,c.invoice_count,c.experience,d.id delivery_id,d.print 
			FROM sales s LEFT JOIN sales_delivery d ON d.sales=s.id LEFT JOIN customers c ON s.customer=c.id LEFT JOIN users u ON u.id=s.user WHERE s.id='$id'") or die (mysqli_error($con));
		$data=mysqli_fetch_assoc($result);
		if($data['print']==0){
			mysqli_query($con,"UPDATE sales_delivery SET print='1' WHERE id='$data[delivery_id]'");
			$msg="Your Order $data[delivery_id] is Processing.\nYou may call now to add additional items, 0774308010.";
			$msg.="\nDaily Food City";
			mysqli_query($con,"INSERT INTO sms_sent(branch,number,mask,type,message,messaged,user) VALUES('$_SESSION[branch]','$data[mobile]','1','0','$msg','$current_time','0')")or die(mysqli_error($con));
		}
		$html='<div class="row print_invoice">
			<div class="col-12">
				<div class="invoice-title">
					<center><img src="https://dfc.lk/app/icons/icon-325x200.png" style="width:50%;"/>
					<h5 style="margin-top: 0px;line-height: 1;">Kokuvil<br/>021-222-4600 | 077-430-8010</h5></center>
				</div><hr>
				<div class="invoice-title">
					<h4 class="mt-0 float-right">'.$id.' - '.$data['delivery_id'].'</h4>
					<div class="mb-4">'.$data['first_name'].' ('.$data['branch'].':'.$data['till'].')<br/>'.date("Y-m-d h:i A",strtotime($data['added'])).'</div>
				</div>
				<div class="table-responsive" style="width:110% !important">
					<table class="table"><thead>
						<th><strong>#</strong></th>
						<th><strong>Item</strong></th>
						<th class="right"><strong>Qty</strong></th>
						<th class="right"><strong>MRP</strong></th>
						<th class="right"><strong>Discount</strong></th>
						<th class="right"><strong>Amount</strong></th></thead>
						<tbody>';
						$gross_total=0;
						$counter=1;
						$discount=0;
						$result=mysqli_query($con,"SELECT l.*,p.title,p.title_tamil,p.brand,p.category 
						FROM products_logs_temp l LEFT JOIN products p ON l.product=p.id WHERE l.referrer='$id'") or die (mysqli_error($con));
						while($item=mysqli_fetch_assoc($result)){
							$html.='<tr>
								<td class="center">'.$counter++.'</td>
								<td colspan="5">'.((strlen($item['title_tamil'])>3)?'<span class="tamil_text">'.$item['title_tamil'].'</span>':$item['title']).'</td>
							</tr>
							<tr>
								<td class="no-line"></td>
								<td class="center no-line"></td>
								<td class="no-line right">'.number_format($item['qty'],3).'</td>
								<td class="no-line right">'.number_format($item['mrp'],2).'</td>
								<td class="no-line right">'.number_format(($item['mrp']-$item['selling_price'])*$item['qty'],2).'</td>
								<td class="no-line right">'.number_format(($item['qty']*$item['selling_price']),2).'</td>
							</tr>';
							$gross_total+=($item['qty']*$item['mrp']);
							$discount+=($item['qty']*($item['mrp']-$item['selling_price']));
						}
						$net_total=$gross_total-$discount;
						
						$exp='';
						$total_purchase_this_month=0;
						if($data['customer']>0){
							$result=mysqli_query($con,"SELECT sum(points-used) total FROM sales_points WHERE customer='$data[customer]' AND (expiry>=NOW() OR expiry is null)");
							$points=mysqli_fetch_assoc($result);
							$html.='<tr><td colspan="6">Mobile : '.$data['mobile'].' ('.$data['plat'].')</td></tr>';
							$html.='<tr><td colspan="6">Name : '.$data['fname'].' '.$data['lname'].'</td></tr>';
							$html.='<tr><td colspan="6">Earned Star Points : '.number_format($data['points'],2).'</td></tr>
							<tr><td colspan="6">Total Star Points : '.number_format($points['total'],2).'</td></tr>';
							$month=date('m');
							$year=date('Y');
							$exp='<hr/>';
							$result=mysqli_query($con,"SELECT SUM(total) total FROM sales WHERE customer='$data[customer]' AND MONTH(date)='$month' AND YEAR(date)='$year'") or die(mysqli_error($con));
							$row=mysqli_fetch_assoc($result);
							$total_purchase_this_month=$row['total'];
							$j=round($total_purchase_this_month/1000);
							if($data['customer']==5)$j=18;
							if($j>18)$j=18;
							else if($j==0)$exp.='<i class="fas fa-angle-double-right"></i>';
							for($i=0; $i<=$j; $i++){
								$exp.='<i class="fas fa-angle-double-right"></i>';
							}
							$exp.='<hr/>';
							$exp.='<hr/>
							<span><i class="fas fa-walking"></i>&nbsp;&nbsp;'.$data['invoice_count'].' | 
							&nbsp;<i class="fas fa-shoe-prints"></i>&nbsp;&nbsp;'.Number_format($data['total']/1000,0).' | 
							&nbsp;<i class="fas fa-star-half-alt"></i>&nbsp;&nbsp;'.Number_format($data['experience']/100,0).'</span>
							<hr/>';
						}
						$html.='</tbody>
					</table>
					'.$exp.'<table class="table">
					<tr><td class="center"><span class="tamil_text">'.$_SESSION['soft']['settings']['footer_tamil'].'</span></td></tr>
					<tr><td class="center">Software and Technical Support By :<br/>&#169; 2013, Agoysoft (pvt) Ltd.</td></tr>
					</table>
				</div>
			</div>
		</div>
		<style>.print_invoice .tamil_text{font-size: 14px !important;}
		.print_invoice{width:120mm !important;font-size:18px !important;font-weight:bold;padding-left:20px;}
		.print_invoice .table{width:100% !important;}
		.print_invoice .col-12{width:78mm !important;}
		.print_invoice .invoice-title{color:#000000;}
		.print_invoice td{padding: 0 !important}
		</style>';
		return $html;
	}
	function print_payslip($id){
		global $_SESSION,$config_url,$con,$current_time;
		$result=mysqli_query($con,"SELECT ss.*,s.month,s.year,u.first_name,u.last_name,u.nic FROM salary_staff ss LEFT JOIN salary s ON s.id=ss.salary LEFT JOIN users u ON u.id=ss.staff WHERE ss.id='$id'");
		$row=mysqli_fetch_array($result);
		$row['break_down']=json_decode($row['break_down'],true);
		$earnings=array();
		$deductions=array();
		$earnings[]='<td>Basic</td><td class="right">'.number_format($row['basic'],2).'</td>';
		$total=array('earnings'=>$row['basic'],'deductions'=>0);
		foreach($row['break_down'] as $title=>$amount){
			if($amount>0){
				if($title=='ot')$title='Over Time';
				$earnings[]='<td>'.$title.'</td><td class="right">'.number_format($amount,2).'</td>';
				$total['earnings']+=$amount;
			}
			else if($amount<0){
				if($title=='ot')$title='No Pay';
				$amount=-$amount;
				$deductions[]='<td>'.$title.'</td><td class="right">'.number_format($amount,2).'</td>';
				$total['deductions']+=$amount;
			}
		}
		$htm='<div id="pay_slip">
				<div id="header">Pay Slip</div>
				<div style="clear:both"></div>
				<div class="customer">
					<table id="meta1" class="meta"><tbody><tr><td class="meta-head">Month :</td><td class="right">'.date('F',strtotime("01-$row[month]-$row[year]")).'</td></tr></tbody></table> 
					<table id="meta2" class="meta"><tbody><tr><td class="meta-head">Year :</td><td class="right">'.$row['year'].'</td></tr></tbody></table>
				</br>
				</div>
				<table class="data" style="margin: 5px 0 5px 0;">
					<tbody>
						<tr>
							<td>Employee ID:</td><td>:&nbsp;&nbsp;'.$row['nic'].'</td>
							<td>Employee Name:</td><td>:&nbsp;&nbsp;'.$row['last_name'].' '.$row['first_name'].'</td>
						</tr>
						<tr><td>No of Days :</td><td>:&nbsp;&nbsp;'.$row['days'].'</td><td>Hours :</td><td>:&nbsp;&nbsp;'.$row['hours'].'</td></tr>
					</tbody>
				</table>
				<div class="customer"> 
					<table border="1" width="100%" class="salary">
						<tr><td class="salary-head">Earnings</td><td class="salary-head">Amount</td><td class="salary-head">Deductions</td><td class="salary-head">Amount</td></tr>';
						$array=$earnings;
						if(sizeof($deductions)>sizeof($earnings))$array=$deductions;
						$i=0;
						foreach($array as $tds){
							$htm.='<tr>'.(isset($earnings[$i])?$earnings[$i]:'<td></td><td></td>')
							.(isset($deductions[$i])?$deductions[$i]:'<td></td><td></td>').'</tr>';
							$i++;
						}
						$htm.='<tr>
							<td class="total-head">Total</td><td class="total-head">'.number_format($total['earnings'],2).'</td>
							<td class="total-head">Total</td><td class="total-head">'.number_format($total['deductions'],2).'</td></tr>
						<tr>
							<td>&nbsp;</td><td>&nbsp;</td>
							<td class="total-head">Net Salary</td><td class="total-head">'.number_format(($total['earnings']-$total['deductions']),2).'</td>
						</tr>
					</table>
				</br>';
				$row['contribution']=json_decode($row['contribution'],true);
				if(sizeof($row['contribution'])>0){
					$htm.='<table border="1" width="100%" class="salary">
						<tr><td class="salary-head" colspan="4">Company Contribution</td></tr>
						<tr>
							<td class="total-head">EPF 12%</td><td class="total-head">'.number_format($row['contribution']['EPF 12%'],2).'</td>
							<td class="total-head">ETF 3%</td><td class="total-head">'.number_format($row['contribution']['ETF 3%'],2).'</td></tr>
					</table>';
				}
				$htm.='</br></div>
			<div class="customer">
				<table border="1" width="86%" style="border-width: 0px;">
					<tr>
						<td style="border-style: none; border-width: medium" width="308"><p align="center">............................................</td>
						<td style="border-style: none; border-width: medium" width="388"><p align="center">............................................</td>
					</tr>
					<tr>
						<td align="center" style="border-style: none; border-width: medium" width="308">Employee</td>
						<td align="center" style="border-style: none; border-width: medium" width="388">HRM/Director</td>
					</tr>
				</table>
			</div>				
	<div id="terms"><h5>'.$_SESSION['soft']['settings']['company_name'].'</h5>'.$_SESSION['soft']['settings']['address'].' ('.$_SESSION['soft']['settings']['phone_no'].')</div>
</div>
<style>
@page {size: 5.8in 8.3in;}
body { font: 14px/1.4 sylfaen; }
#pay_slip { width: 800px; margin: 0 auto; }
#pay_slip table { border-collapse: collapse; width: 100%; }
#pay_slip .meta td,#pay_slip  .table th { border: 1px solid black; padding: 5px;}
#pay_slip #header { height: 30px; width: 100%; margin: 10px 0; background: #222; text-align: center; color: white; font: bold 15px Helvetica, Sans-Serif;  text-transform: uppercase; letter-spacing: 20px; padding: 8px 0px; }
#pay_slip .customer { overflow: hidden; }
#pay_slip #customer-title { float: left; }
#pay_slip #meta1 { margin-top: 1px; width: 300px; border-width: 0px;float: left;}
#pay_slip #meta2 { margin-top: 1px; width: 300px; border-width: 0px; float:right;}
#pay_slip .meta1 td {border-width: 2px;}
#pay_slip .right{ text-align:right;padding-right: 10px;}
#pay_slip .center{ text-align:center}
#pay_slip .meta td.meta-head { text-align: left; background: #eee; }
#pay_slip .salary td.salary-head { text-align: center; background: #eee; }
#pay_slip .salary td.total-head { text-align: right; background: #eee; padding-right: 10px;}
#pay_slip #terms { text-align: center; margin: 20px 0 0 0; }
#pay_slip #terms h5{ text-transform: uppercase; font: 13px Helvetica, Sans-Serif; letter-spacing: 10px; border-bottom: 1px solid black; padding: 0 0 8px 0; margin: 0 0 8px 0; }
#pay_slip .data tr{height: 30px;}
</style>';
// <div>
					// <table border="1" width="100%" class="salary">
						// <tr><td class="salary-head">Date</td><td class="salary-head">Bank Name</td><td class="salary-head">Account Number</td><td class="salary-head">Amount</td></tr>
						// <tr><td class="center editable" add_class="date">26-12-2016</td><td class="center editable"></td><td class="center editable"></td><td class="right total">18,000.00</td></tr>
					// </table>
				// </br>	
				// </div>
		return $htm;
	}
	function generate_random_code_for_voucher(){
		global $con;
		$code=mt_rand(10000000,99999999);
		mysqli_query($con,"SELECT * FROM sales_vouchers WHERE code='$code'");
		if(mysqli_affected_rows($con)==0)return $code;
		else generate_random_code_for_voucher();
	}
	function list_images($img,$path,$title=''){
		global $config_url;
		$html='';
		if($img!=''){
			$images=explode(",",$img);
			foreach ($images as $image){
				if($image<>'' and file_exists($path.$image)){
					if(file_exists($path.'thumbnail/'.$image))$img=$path.'thumbnail/'.$image;
					else $img=resize_image($path.$image,$path.'thumbnail/'.$image,20,20);
					$html.=' <a class="float-left" href="'.$config_url.$path.$image.'" title="'.$title.'">
						<img class="img-fluid" alt="" src="'.$config_url.$path.'thumbnail/'.$image.'" width="20" height="20">
					</a>';
				}
			}
		}
		return $html;
	}
	function view_a_product($id,$file,$plugin,$type=0){
		global $con,$_SESSION,$config_url,$branch_url;
		$result=mysqli_query($con,"SELECT *,(SELECT balance FROM products_logs WHERE product='$id' AND branch='$_SESSION[branch]' ORDER BY id DESC LIMIT 0,1) available FROM products WHERE id='$id'");
		$htm='';
		if(mysqli_affected_rows($con)==1){
			$product=$result->fetch_assoc();
			$htm='<div class="card col-lg-9 mx-auto">
				<div class="table" table="products" file="'.$file.'">
					<h4 class="mt-0 header-title">'.$product['title'].' '.($product['title_tamil']<>''?'('.$product['title_tamil'].')':'').' ['.$id.']</h4>
					<p class="text-muted mb-4"></p>
					<ul class="nav nav-pills nav-justified" role="tablist">
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link active" data-toggle="tab" href="#data" role="tab">
								<span class="d-block"><i class="fas fa-align-left"></i></span>
								<span class="d-none d-sm-block">Data</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#history" role="tab">
								<span class="d-block"><i class="fas fa-history"></i></span>
								<span class="d-none d-sm-block">History</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#prices" role="tab">
								<span class="d-block"><i class="fas fa-tags"></i></span>
								<span class="d-none d-sm-block">Prices</span> 
							</a>
						</li>
						'.($product['parent']>0?'<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#parent" role="tab">
								<span class="d-block"><i class="far fa-caret-square-up"></i></span>
								<span class="d-none d-sm-block">Parent</span> 
							</a>
						</li>':'').'
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#promo" role="tab">
								<span class="d-block"><i class="fas fa-tags"></i></span>
								<span class="d-none d-sm-block">Offers</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#expiry" role="tab">
								<span class="d-block"><i class="fas fa-calendar-alt"></i></span>
								<span class="d-none d-sm-block">Expiry</span>
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active p-3" id="data" role="tabpanel">
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Title</label>
								<div class="col-sm-10"><span class="editable form-control" field="title" id="'.$product['id'].'">'.$product['title'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Title Tamil</label>
								<div class="col-sm-10"><span class="editable form-control" field="title_tamil" id="'.$product['id'].'">'.$product['title_tamil'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Title Sinhala:</label>
									<div class="col-sm-10"><span class="editable form-control" field="title_sinhala" id="'.$product['id'].'">'.$product['title_sinhala'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Remind:</label>
									<div class="col-sm-10"><span class="editable form-control" field="remind" id="'.$product['id'].'">'.$product['remind'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">UI Code:</label>
									<div class="col-sm-10"><span class="editable form-control" field="ui_code" id="'.$product['id'].'">'.$product['ui_code'].'</span></div>
							</div>
							'.($_SESSION['soft']['user_data']['user_level']==0?'<div class="form-group row">
								<label class="col-sm-2 col-form-label">Parent:</label>
									<div class="col-sm-10"><span class="editable form-control" field="parent" id="'.$product['id'].'">'.$product['parent'].'</span></div>
							</div>':'').'
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Bulk Code:</label>
									<div class="col-sm-10"><span class="editable form-control" field="bulk_code" id="'.$product['id'].'">'.$product['bulk_code'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Unit in Case:</label>
									<div class="col-sm-10"><span class="editable form-control" field="uic" id="'.$product['id'].'">'.$product['uic'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Expiry Alert (Days):</label>
									<div class="col-sm-10"><span class="editable form-control" field="expiry" id="'.$product['id'].'">'.$product['expiry'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Discount:</label>
								<div class="col-sm-10">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($product['discount']==1?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="discount" value="1" '.($product['discount']==1?'checked':'').'>On
										</label>
										<label class="btn btn-secondary pointer '.($product['discount']==0?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="discount" value="0" '.($product['discount']==0?'checked':'').'>Off
										</label>
									</div>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Status:</label>
								<div class="col-sm-10">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($product['status']==1?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="status" value="1" '.($product['status']==1?'checked':'').'>Active
										</label>
										<label class="btn btn-secondary pointer '.($product['status']==0?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="status" value="0" '.($product['status']==0?'checked':'').'>Hide
										</label>
									</div>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Price:</label>
								<div class="col-sm-10">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($product['price_vary']==1?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="price_vary" value="1" '.($product['price_vary']==1?'checked':'').'>Vary
										</label>
										<label class="btn btn-secondary pointer '.($product['price_vary']==0?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="price_vary" value="0" '.($product['price_vary']==0?'checked':'').'>Fixed
										</label>
									</div>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Type:</label>
								<div class="col-sm-10">
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($product['weighted']==1?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="weighted" value="1" '.($product['weighted']==1?'checked':'').'>Weight
										</label>
										<label class="btn btn-secondary pointer '.($product['weighted']==0?'active':'').'">
											<input type="radio" id="'.$product['id'].'" name="weighted" value="0" '.($product['weighted']==0?'checked':'').'>Count
										</label>
									</div>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Available Items:</label>
									<div class="col-sm-10"><span class="form-control '.($_SESSION['user_id']==1?'editable':'').'" field="available_items" id="'.$product['id'].'">'.$product['available'].'</span><span><i class="fas fa-sync fa-1x pointer update_available_qty" style="color:green" id="'.$product['id'].'"></i></span></div>
							</div>
						</div>
						<div class="tab-pane p-3" id="history" role="tabpanel">
							<span><i class="fas fa-sync fa-2x pointer update_available_qty" style="color:green" id="'.$product['id'].'"></i></span>
							<table class="table table-striped nowrap product_view" table="products_logs" file="'.$file.'" plugin="'.$plugin.'">
								<thead><tr><th class="center">#</th><th class="center">ID</th><th>Time</th><th class="right">Qty</th><th class="right">Unit</th>
								<th class="right">free</th><th class="right">Cost</th><th class="right">MRP</th><th class="right">Sold</th>
								<th class="right">IN</th><th class="right">Out</th><th class="right">Balance</th><th class="right">Non-Adj</th><th>Staff</th></tr></thead>
								<tbody>';
							$result=mysqli_query($con,"SELECT l.*,po.img,ifnull(po.date,ifnull(s.date,y.date)) date,u.short 
							FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type=1) LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
							LEFT JOIN yura_batch_history y ON (y.id=l.referrer AND l.type IN (3,4)) LEFT JOIN users u ON u.id=l.user
							WHERE l.product='$id' AND l.branch='$_SESSION[branch]' AND (l.added>=(SELECT ifnull(date(max(added)),'2021-04-01') FROM products_logs WHERE type=2 and product='$id' AND branch='$_SESSION[branch]') AND l.added>='2021-04-01') AND l.checked>=0 AND l.added>'2021-03-31'") or die(mysqli_error($con));
							$counter=1;
							$balance=0;
							$date='';
							while($row=mysqli_fetch_array($result)){
								$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
								$cost=0;
								if($qty>0)$cost=($row['purchased']*$row['qty'])/$qty;
								if($row['type']==2){
									$balance+=$qty;
									$htm.='<tr class="gradeA" id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td><td></td>
										<td>'.date('d-m-Y, h:i A',strtotime($row['added'])).'</td><td></td><td></td><td></td><td></td><td></td><td></td>
										<td class="right">'.$qty.'</td>
										<td class="right"></td>
										<td class="right">'.($row['balance']+0).'</td>
										<td class="right">'.$balance.'</td>
										<td class="right">'.$row['short'].'</td>
									</tr>';
									$date=date('Y-m-d',strtotime($row['added']));
								}
								else if(strtotime($row['date'])>=strtotime($date)){
									if($cost==$row['mrp']){
										$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
										FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
										$ro=mysqli_fetch_assoc($re);
										$diff=$ro['total']-$ro['tot'];
										if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
											$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
											$cost=$row['mrp']*$discounted_price;
											$cost=($cost*$row['qty'])/$qty;
										}
									}
									if($row['type']%2==0)$balance-=$qty;
									else $balance+=$qty;
									$htm.='<tr class="gradeA" id="'.$row['id'].'" style="cursor:pointer;">
										<td class="center">'.$counter++.'</td>
										<td class="center"><a href="'.$branch_url.'#sales/sales\\'.$row['referrer'].'" target="_blank">'.$row['referrer'].'</a></td>
										<td>'.$row['date'].' '.date('h:i A',strtotime($row['added'])).'</td>
										<td class="right">'.($row['qty']+0).'</td>
										<td class="right">'.$row['uic'].'</td>
										<td class="right">'.$row['free'].'</td>
										<td class="right">'.Number_format($cost,2).'</td>
										<td class="right">'.$row['mrp'].'</td>
										<td class="right">'.$row['selling_price'].'</td>
										<td class="right">'.($row['type']%2==1?$qty:'').'</td>
										<td class="right">'.($row['type']%2==0?$qty:'').'</td>
										<td class="right">'.($row['balance']+0).'</td>
										<td class="right">'.$balance.'</td>
										<td class="right">'.$row['short'].'</td>
									</tr>';
								}
							}
							$htm.='</tbody>
							</table>
						</div>
						<div class="tab-pane p-3" id="prices" role="tabpanel">
							<table class="table table-striped nowrap product_view" table="products_logs" file="'.$file.'" plugin="'.$plugin.'">
							<thead><tr><th class="center">#</th><th class="center">PO ID</th><th>Date</th><th class="right">Qty</th><th class="right">Unit</th>
								<th class="right">free</th><th class="right">Price</th><th class="right">Cost</th><th class="right">%</th><th class="right">MRP</th>
								<th class="right">MRPC</th><th class="right">G</th><th class="right">G%</th><th></th><th class="right">U</th><th class="right">Status</th><th></th><th></th></tr></thead>
							<tbody>';
						$q="";
						$limit=" ORDER BY date ASC";
						if($type==0)$q.="AND l.status='1'";
						else if($type==1)$limit=" ORDER BY date DESC LIMIT 0,20";
						$result=mysqli_query($con,"SELECT l.*,po.img,ifnull(po.date,y.date) date,po.checked,u.short FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type=1) 
						LEFT JOIN yura_batch_history y ON y.id=l.referrer AND l.type=3 LEFT JOIN users u ON u.id=l.user 
						WHERE l.product='$id' AND l.type IN(1,3) AND l.branch='$_SESSION[branch]' $q $limit") or die(mysqli_error($con));
						$counter=1;
						$class='editable';
						while($row=mysqli_fetch_array($result)){
							$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
							$cost=($row['purchased']*$row['qty'])/$qty;
							if($cost==$row['mrp']){
								$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
								FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
								$ro=mysqli_fetch_assoc($re);
								$diff=$ro['total']-$ro['tot'];
								if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
									$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
									$cost=$row['mrp']*$discounted_price;
									$cost=($cost*$row['qty'])/$qty;
								}
							}
							if($row['checked']==1 AND $_SESSION['soft']['user_data']['user_level']>1)$class='';
							$htm.='<tr class="gradeA" id="'.$row['id'].'" style="cursor:pointer;">
								<td class="center">'.$counter++.'</td>
								<td class="center">'.$row['referrer'].'</td>
								<td>'.$row['date'].'</td>
								<td class="right '.$class.'" field="qty">'.$row['qty'].'</td>
								<td class="right '.$class.'" field="uic">'.$row['uic'].'</td>
								<td class="right '.$class.'" field="free">'.$row['free'].'</td>
								<td class="right '.$class.'" field="purchased">'.Number_format($row['purchased'],2).'</td>
								<td class="right">'.Number_format($cost,2).'</td>
								<td class="right">'.Number_format((($row['mrp']-$cost)/$row['mrp'])*100,2).'%</td>
								<td class="right '.$class.'" field="mrp">'.$row['mrp'].'</td>
								<td class="right '.$class.'" field="mrpc">'.$row['mrpc'].'</td>
								<td class="right editable" field="selling_price">'.$row['selling_price'].'</td>
								<td class="right">'.($row['selling_price']>0?Number_format((($row['selling_price']-$cost)/$row['selling_price'])*100,2):Number_format((($row['mrp']-$cost)/$row['mrp'])*100,2)).'%</td>
								<td class="right">'.$row['short'].'</td>
								<td></td>
								<td>
									<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
										<label class="btn btn-secondary pointer '.($row['status']==1?'active':'').'"><input type="radio" name="status" value="1" '.($row['status']==1?'checked':'').'>On</label>
										<label class="btn btn-secondary pointer '.($row['status']==0?'active':'').'"><input type="radio" name="status" value="0" '.($row['status']==0?'checked':'').'>Off</label>
									</div>
								</td>
							</tr>';
						}
						$htm.='</tbody>
						</table>
						</div>';
						if($product['parent']>0){
							$result=mysqli_query($con,"SELECT id,title,title_tamil,ui_code,(SELECT balance FROM products_logs WHERE product='$product[parent]' AND branch='$_SESSION[branch]' ORDER BY id DESC LIMIT 0,1) available FROM products WHERE id='$product[parent]'");
							$parent=$result->fetch_assoc();
							$htm.='<div class="tab-pane p-3" id="parent" role="tabpanel">
							<h4 class="mt-0 header-title">'.$parent['title'].' - '.$parent['ui_code'].' '.($parent['title_tamil']<>''?'('.$parent['title_tamil'].')':'').' ['.$parent['id'].'] Available : '.$parent['available'].'</h4>
								<table class="table table-striped nowrap product_view" table="products_logs" file="'.$file.'" plugin="'.$plugin.'" style="font-size: 10px;">
								<thead><tr><th class="center">#</th><th class="center">PO ID</th><th>Date</th><th class="right">Qty</th><th class="right">Unit</th>
									<th class="right">free</th><th class="right">Price</th><th class="right">Cost</th><th class="right">%</th><th class="right">MRP</th>
									<th class="right">MRPC</th><th class="right">G</th><th class="right">G%</th><th></th><th class="right">U</th><th class="right">Status</th><th></th><th></th></tr></thead>
								<tbody>';
							$q="";
							$limit=" ORDER BY date ASC";
							if($type==0)$q.="AND l.status='1'";
							else if($type==1)$limit=" ORDER BY date DESC LIMIT 0,20";
							$result=mysqli_query($con,"SELECT l.*,po.img,ifnull(po.date,y.date) date,po.checked,u.short FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type=1) 
							LEFT JOIN yura_batch_history y ON y.id=l.referrer AND l.type=3 LEFT JOIN users u ON u.id=l.user 
							WHERE l.product='$product[parent]' AND l.type IN(1,3) AND l.branch='$_SESSION[branch]' $q $limit") or die(mysqli_error($con));
							$counter=1;
							$class='editable';
							while($row=mysqli_fetch_array($result)){
								$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
								$cost=($row['purchased']*$row['qty'])/$qty;
								if($cost==$row['mrp']){
									$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
									FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
									$ro=mysqli_fetch_assoc($re);
									$diff=$ro['total']-$ro['tot'];
									if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
										$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
										$cost=$row['mrp']*$discounted_price;
										$cost=($cost*$row['qty'])/$qty;
									}
								}
								if($row['checked']==1 AND $_SESSION['soft']['user_data']['user_level']>1)$class='';
								$htm.='<tr class="gradeA" id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.$counter++.'</td>
									<td class="center">'.$row['referrer'].'</td>
									<td>'.$row['date'].'</td>
									<td class="right '.$class.'" field="qty">'.$row['qty'].'</td>
									<td class="right '.$class.'" field="uic">'.$row['uic'].'</td>
									<td class="right '.$class.'" field="free">'.$row['free'].'</td>
									<td class="right '.$class.'" field="purchased">'.Number_format($row['purchased'],2).'</td>
									<td class="right">'.Number_format($cost,2).'</td>
									<td class="right">'.Number_format((($row['mrp']-$cost)/$row['mrp'])*100,2).'%</td>
									<td class="right '.$class.'" field="mrp">'.$row['mrp'].'</td>
									<td class="right '.$class.'" field="mrpc">'.$row['mrpc'].'</td>
									<td class="right editable" field="selling_price">'.$row['selling_price'].'</td>
									<td class="right">'.($row['selling_price']>0?Number_format((($row['selling_price']-$cost)/$row['selling_price'])*100,2):Number_format((($row['mrp']-$cost)/$row['mrp'])*100,2)).'%</td>
									<td class="right">'.$row['short'].'</td>
									<td></td>
									<td>
										<div class="btn-group btn-group-toggle mt-2 mt-xl-0 on_off" data-toggle="buttons">
											<label class="btn btn-secondary pointer '.($row['status']==1?'active':'').'"><input type="radio" name="status" value="1" '.($row['status']==1?'checked':'').'>On</label>
											<label class="btn btn-secondary pointer '.($row['status']==0?'active':'').'"><input type="radio" name="status" value="0" '.($row['status']==0?'checked':'').'>Off</label>
										</div>
									</td>
								</tr>';
							}
							$htm.='</tbody>
							</table>
							</div>';
						}
						$htm.='<div class="tab-pane p-3" id="promo" role="tabpanel">
							<table class="table" table="products_offer" file="'.$file.'" plugin="'.$plugin.'">
							<thead><tr><th>Date</th><th>MRP</th><th>Gold</th><th>Cost</th><th>Save</th><th>Max</th><th>Min</th><th>Min Bill</th><th>Exc</th>
							<th>Limit</th><th>Cash</th><th>SMS</th><th>Staff</th><th></th></tr></thead><tbody>';
							$from=date('Y-m-d');
							$to=date('Y-m-t');
							$result=mysqli_query($con,"SELECT po.*,u.short,
							(SELECT ((purchased*qty)/ifnull(if(uic>0,(uic*qty),qty)+free,if(uic>0,(uic*qty),qty))) FROM products_logs WHERE type=1 AND product='$id' AND qty>0 AND purchased>0 ORDER BY id DESC LIMIT 0,1) cost,
							(SELECT referrer FROM products_logs WHERE type=1 AND product='$id' AND qty>0 AND mrp=po.mrp AND purchased>0 ORDER BY id DESC LIMIT 0,1) referrer
							FROM products_offer po LEFT JOIN products p ON p.id=po.product LEFT JOIN users u ON u.id=po.user 
							WHERE p.id='$id' AND (po.start_date<='$from' AND po.end_date>='$to') OR (po.start_date BETWEEN '$from' AND '$to') OR (po.end_date BETWEEN '$from' AND '$to')") or die(mysqli_error($con)); //limit 93,1
							$counter=1;
							while($row=mysqli_fetch_array($result)){
								if($row['cost']==$row['mrp']){
									$re=mysqli_query($con,"SELECT SUM(pi.qty*pi.purchased) tot,po.discount,(SELECT SUM(qty*purchased) FROM products_logs WHERE referrer='$row[referrer]' AND mrp=purchased) total 
									FROM po LEFT JOIN products_logs pi ON (pi.referrer=po.id AND pi.type='1') WHERE po.id='$row[referrer]'") or die(mysqli_error($con));
									$ro=mysqli_fetch_assoc($re);
									$diff=$ro['total']-$ro['tot'];
									if($ro['discount']>0 AND ($diff>-1 AND $diff<1)){
										$discounted_price=($ro['total']-$ro['discount'])/$ro['tot'];
										$row['cost']=$row['mrp']*$discounted_price;
										$row['cost']=($row['cost']*$row['qty'])/$qty;
									}
								}
								$htm.='<tr class="grade'.($row['mrp']<$row['cost']?'X':'A').'" id="'.$row['id'].'" style="cursor:pointer;">
									<td class="center">'.date('d-m-Y',strtotime($row['start_date'])).'<br/>'.date('d-m-Y',strtotime($row['end_date'])).'</td>
									<td class="right editable" field="mrp">'.$row['mrp'].'</td>
									<td class="right editable" field="price">'.$row['price'].'</td>
									<td class="right">'.number_format($row['cost'],2).'<br/>'.($row['cost']>0?number_format((($row['price']-$row['cost'])/$row['cost'])*100,2):'').'</td>
									<td class="right">'.($row['mrp']-$row['price']).' ('.number_format((($row['mrp']-$row['price'])/$row['mrp'])*100,2).'%)</td>
									<td class="center editable" field="max_qty">'.$row['max_qty'].'</td>
									<td class="center editable" field="min_qty">'.$row['min_qty'].'</td>
									<td class="right editable" field="min_bill">'.$row['min_bill'].'</td>
									<td class="right editable" field="exclude_promo">'.$row['exclude_promo'].'</td>
									<td class="right editable" field="limit_variety">'.$row['limit_variety'].'</td>
									<td class="right editable" field="cash_only">'.$row['cash_only'].'</td>
									<td class="right editable" field="sms_to_history">'.$row['sms_to_history'].'</td>
									<td class="right">'.$row['short'].'</td>
									<td class="center"><a class="action-icons c-delete" field="delete">Delete</a>
								</tr>';
							}
								$htm.='</tbody>
							</table>
						</div>
						<div class="tab-pane p-3" id="expiry" role="tabpanel">
							<table class="table" table="po_items_expiry" file="'.$file.'" plugin="'.$plugin.'">
							<thead><tr><th>#</th><th>Purchased</th><th>Expiry</th><th>Qty</th><th>Stock</th><th>Checked</th><th>Staff</th></tr></thead><tbody>';
							// $result=mysqli_query($con,"SELECT pie.*,(if(l.uic>0,(l.qty*l.uic),l.qty)+l.free) qty,po.date purchased,u.first_name,
							// (SELECT balance FROM products_logs WHERE product='$id' ORDER BY id DESC LIMIT 0,1) available
								// FROM products_logs l LEFT JOIN po on po.id=l.referrer LEFT JOIN users u ON u.id=l.user 
								// LEFT JOIN po_items_expiry pie ON pie.po_item=l.id WHERE l.product='$id' AND pie.status='1' AND l.branch='$_SESSION[branch]' ORDER BY pie.date ASC") or die(mysqli_error($con));
							// $counter=1;
							// while($row=mysqli_fetch_array($result)){
								// $htm.='<tr class="gradeA" id="'.$row['id'].'" style="cursor:pointer;">
									// <td class="center">'.$counter++.'</td>
									// <td class="center">'.date('d-m-Y',strtotime($row['purchased'])).'</td>
									// <td class="center">'.date('d-m-Y',strtotime($row['date'])).'</td>
									// <td class="center">'.($row['qty']+0).'</td>
									// <td class="center">'.($row['available']+0).'</td>
									// <td class="center"><i class="fa fa-check fa-1x boolean_check" field="status" value="0"></i></td>
									// <td class="center">'.$row['first_name'].'</td>
								// </tr>';
							// }
								$htm.='</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<style>.product_view > tbody > tr > td, .product_view > tfoot > tr > td, .product_view > thead > tr > td {padding: 15px 2px;}</style>';
		}
		return $htm;
	}
	function view_a_customer($id,$file,$plugin){
		global $con,$memberships,$sms_mask,$config_url,$_SESSION;
		$result=mysqli_query($con,"SELECT * FROM customers WHERE id='$id'");
		$htm='';
		if(mysqli_affected_rows($con)==1){
			$customer=$result->fetch_assoc();
			$htm='<div class="card col-lg-9 mx-auto">
				<div class="table" table="customers" file="'.$file.'">
					<h4 class="mt-0 header-title">'.$customer['mobile'].' '.($customer['fname']<>''?'('.$customer['fname'].' '.$customer['lname'].')':'').' ['.$id.']</h4>
					<p class="text-muted mb-4"></p>
					<ul class="nav nav-pills nav-justified" role="tablist">
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link active" data-toggle="tab" href="#data" role="tab">
								<span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
								<span class="d-none d-sm-block">Data</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#recent_invoices" role="tab">
								<span class="d-block d-sm-none"><i class="far fa-user"></i></span>
								<span class="d-none d-sm-block">Recent</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#history" role="tab">
								<span class="d-block d-sm-none"><i class="far fa-user"></i></span>
								<span class="d-none d-sm-block">Summary</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#milk_powder" role="tab">
								<span class="d-block d-sm-none"><i class="far fa-user"></i></span>
								<span class="d-none d-sm-block">Milk Powder</span> 
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#sms" role="tab">
								<span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
								<span class="d-none d-sm-block">SMS Summary</span>   
							</a>
						</li>
						<li class="nav-item waves-effect waves-light">
							<a class="nav-link" data-toggle="tab" href="#credit" role="tab">
								<span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
								<span class="d-none d-sm-block">Credit</span>   
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active p-3" id="data" role="tabpanel">
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Mobile</label>
								<div class="col-sm-10"><span class="form-control" field="mobile" id="'.$customer['id'].'">'.$customer['mobile'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Loyalty #</label>
								<div class="col-sm-10"><span class="form-control editable" field="loyalty_card" id="'.$customer['id'].'">'.$customer['loyalty_card'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Platinum #</label>
								<div class="col-sm-10"><span class="form-control editable" field="platinum_card" id="'.$customer['id'].'">'.$customer['platinum_card'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Membership</label>
								<div class="col-sm-10"><span class="form-control editable_select" field="plat" data="'.str_replace('"',"'",json_encode($memberships)).'" value="'.$customer['plat'].'" id="'.$customer['id'].'">'.$memberships[$customer['plat']].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">First Name</label>
								<div class="col-sm-10"><span class="editable form-control" field="fname" id="'.$customer['id'].'">'.$customer['fname'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Last Name</label>
								<div class="col-sm-10"><span class="editable form-control" field="lname" id="'.$customer['id'].'">'.$customer['lname'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Name in Tamil</label>
								<div class="col-sm-10"><span class="form-control" field="fname" id="'.$customer['id'].'">'.$customer['name_ta'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Remind</label>
								<div class="col-sm-10"><span class="form-control editable" field="remind" id="'.$customer['id'].'">'.$customer['remind'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Address</label>
								<div class="col-sm-10"><span class="form-control editable" field="street" id="'.$customer['id'].'">'.$customer['street'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Address (Google)</label>
								<div class="col-sm-10"><span class="form-control" field="address" id="'.$customer['id'].'">'.$customer['address'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">NIC</label>
								<div class="col-sm-10"><span class="editable form-control" field="nic" id="'.$customer['id'].'">'.$customer['nic'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">DOB</label>
								<div class="col-sm-10"><span class="form-control" field="dob" id="'.$customer['id'].'">'.date('d-m-Y',strtotime($customer['dob'])).'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Joined</label>
								<div class="col-sm-10"><span class="form-control" field="" id="'.$customer['id'].'">'.date('d-m-Y',strtotime($customer['added'])).'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Delivery Charge</label>
								<div class="col-sm-10"><span class="form-control editable" field="delivery_charge" id="'.$customer['id'].'">'.$customer['delivery_charge'].'</span></div>
							</div>
							<div class="form-group row">
								<label class="col-sm-2 col-form-label">Credit Limit</label>
								<div class="col-sm-10"><span class="form-control editable" field="credit_limit" id="'.$customer['id'].'">'.$customer['credit_limit'].'</span></div>
							</div>
						</div>
						<div class="tab-pane p-3" id="recent_invoices" role="tabpanel">
							<table class="table mb-0 form-group">
								<thead><tr><th>#</th><th>ID</th>'.($_SESSION['branches']>1?'<th>'.language('Branch').'</th>':'').'<th>'.language('Time').'</th><th>'.language('Total').'</th>
								<th>'.language('Discount').'</th><th>%</th><th>'.language('Paid').'</th><th>'.language('Balance').'</th>
								<th>'.language('Staff').'</th><th>'.language('Action').'</th>
								</tr></thead><tbody>';
								$result=mysqli_query($con,"SELECT s.*,u.first_name,b.title branch_title,
								(SELECT SUM(qty*selling_price) FROM products_logs WHERE type='0' AND referrer=s.id) total,
								(SELECT SUM(amount) FROM sales_payments WHERE type IN(5,6) AND sales=s.id) cost,
								(SELECT SUM(amount*0.025) FROM sales_payments WHERE type=4 AND sales=s.id) comm,
								(SELECT SUM(amount) FROM sales_payments WHERE sales=s.id) paid,SUM((l.selling_price-l.purchased)*l.qty) profit,SUM((l.mrp-l.selling_price)*l.qty) dis
								FROM sales s LEFT JOIN users u ON u.id=s.user LEFT JOIN sales_points p ON p.sale=s.id 
								LEFT JOIN products_logs l ON (l.referrer=s.id AND l.type='0') LEFT JOIN branches b ON b.id=s.branch
								WHERE s.customer='$id' GROUP BY s.id ORDER BY s.added DESC LIMIT 0,25") or die(mysqli_error($con));
								$counter=1;
								while($row=mysqli_fetch_array($result)){
									$class='';
									$row['profit']-=$row['comm'];
									$row['profit']-=$row['cost'];
									$row['total']+=$row['charge'];
									if($row['profit']<0)$class='table-warning';
									if($row['plat']>0)$class='plat_'.$row['plat'];
									$htm.='<tr id="'.$row['id'].'" style="cursor:pointer;" class="'.($row['closed']==0?'pending_invoice':'').'">
										<td class="center">'.$counter++.'</td>
										<td class="center">'.$row['id'].'</td>
										'.($_SESSION['branches']>1?'<td>'.$row['branch_title'].'</td>':'').'
										<td>'.date('d-m-Y',strtotime($row['date'])).'<br/>'.date('h:i A',strtotime($row['added'])).'</td>
										<td class="right">'.Number_format($row['total'],2).'</td>
										<td class="right">'.Number_format($row['dis'],2).'</td>
										<td class="right">'.($row['total']>0?Number_format(($row['dis']/($row['total']+$row['dis']))*100,2):'').'</td>
										<td class="right">'.Number_format($row['paid'],2).'</td>
										<td class="right">'.Number_format($row['total']-$row['paid'],2).'</td>
										<td>'.$row['first_name'].'</td>
										<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light print_invoice_btn">Print</button></td></td>
									</tr>';
								}
							$htm.='</tbody></table>
							</div>
							<div class="tab-pane p-3" id="history" role="tabpanel">
								<table class="table">
								<thead><tr><th>#</th>'.($_SESSION['branches']>1?'<th>'.language('Branch').'</th>':'').'<th>Month</th><th>Total</th></tr></thead><tbody>';
								$last_year=date('Y',strtotime('last year'));
								$result=mysqli_query($con,"SELECT SUM(l.qty*l.selling_price) total,year(s.date) year,month(s.date) month,s.branch,b.title branch_title 
								FROM products_logs l LEFT JOIN sales s on (s.id=l.referrer AND l.type=0) LEFT JOIN branches b ON b.id=s.branch WHERE l.type=0 AND s.customer='$customer[id]' AND s.date>='$last_year-01-01' GROUP BY s.branch,year,month ORDER BY s.date ASC");
								$counter=1;
								while($row=mysqli_fetch_assoc($result)){
									$htm.='<tr><td>'.$counter++.'</td>
									'.($_SESSION['branches']>1?'<td>'.$row['branch_title'].'</td>':'').'
									<td>'.$row['year'].' - '.$row['month'].'</td>
									<td class="right">'.number_format($row['total'],2).'</td></tr>';
								}
							$htm.='</tbody></table>
							</div>
							<div class="tab-pane p-3" id="milk_powder" role="tabpanel">
								<table class="table">
								<thead><tr><th>#</th><th>Month</th><th>Total</th></tr></thead><tbody>';
								$result=mysqli_query($con,"SELECT l.qty,s.date,p.title,p.ui_code 
								FROM products_logs l LEFT JOIN sales s on (s.id=l.referrer AND l.type=0) LEFT JOIN products p ON p.id=l.product
								WHERE l.type=0 AND s.customer='$customer[id]' AND s.date>='2021-08-01' AND p.ui_code IN(4791085292515,4791085292522,4791085261078,4791085261122,4791085081065,4791085081072,4792116101127,4792116101110,4792116104517,4792029000104,4791034100014) ORDER BY s.date ASC");
								$counter=1;
								while($row=mysqli_fetch_assoc($result)){
									$htm.='<tr><td>'.$counter++.'</td>
									<td class="center">'.date('d-m-Y',strtotime($row['date'])).'</td>
									<td>'.$row['title'].' - '.$row['ui_code'].'</td>
									<td class="right">'.number_format($row['qty'],2).'</td></tr>';
								}
							$htm.='</tbody></table>
							</div>
							<div class="tab-pane p-3" id="sms" role="tabpanel">
								<table class="table">
								<thead><tr><th>#</th><th>Date</th><th>Message</th><th>Type</th></tr></thead><tbody>';
								$result=mysqli_query($con,"SELECT * FROM sms_sent WHERE number='$customer[mobile]' ORDER BY messaged DESC LIMIT 0,50");
								$counter=1;
								while($row=mysqli_fetch_assoc($result)){
									$type='0774308010';
									if($row['method']==0){
										$type=$sms_mask[$row['mask']];
									}
									else if($row['mask']>0)$type='0764308010';
									$htm.='<tr><td>'.$counter++.'</td>
									<td class="center">'.$row['messaged'].'</td>
									<td>'.$row['message'].'</td>
									<td>'.$type.'</td></tr>';
								}
							$htm.='</tbody>
								</table>
							</div>
							<div class="tab-pane p-3" id="credit" role="tabpanel">
								<table class="table">
									<thead><tr><th>#</th><th>'.language('ID').'</th>'.($_SESSION['branches']>1?'<th>'.language('Branch').'</th>':'').'<th>'.language('Till').'</th><th>'.language('Time').'</th><th>'.language('Total').'</th>
									<th>'.language('Paid').'</th><th>'.language('Balance').'</th>
									<th>'.language('Staff').'</th><th>'.language('Payment').'</th><th>'.language('Print').'</th>
									</tr></thead><tbody>';
									// $result=mysqli_query($con,"SELECT s.*,SUM(l.qty*l.selling_price) si_total,
									// (SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid,u.first_name,b.title branch_title 
								// FROM sales s LEFT JOIN products_logs l ON (s.id=l.referrer AND l.type=0) LEFT JOIN users u ON u.id=s.user LEFT JOIN branches b ON b.id=s.branch
								// WHERE s.closed=1 AND s.date>'2021-01-01' AND s.customer='$id' GROUP BY s.id HAVING (si_total-paid)>0 ORDER BY s.added ASC")or die(mysqli_error($con));
									$result=mysqli_query($con,"SELECT s.*,u.first_name,b.title branch_title,
									(SELECT SUM(l.qty*l.selling_price) FROM products_logs l WHERE l.referrer=s.id AND l.type=0) si_total,
									(SELECT sum(amount) FROM sales_payments WHERE s.id=sales) paid FROM sales s LEFT JOIN customers c ON c.id=s.customer 
									LEFT JOIN users u ON u.id=s.user LEFT JOIN branches b ON b.id=s.branch
									WHERE s.date>'2021-01-01' AND s.customer='$id' HAVING (si_total-ifnull(paid,0))>1 ORDER BY s.added DESC")or die(mysqli_error($con));
									$counter=1;
									// $color=array('light','success','info','warning','danger');
									$sum=array('total'=>0,'paid'=>0);
									$colspan=3;
									if($_SESSION['branches']>1)$colspan=4;
									while($row=mysqli_fetch_array($result)){
										$htm.='<tr id="'.$row['id'].'" style="cursor:pointer;">
											<td class="center">'.$counter++.'</td>
											<td class="center">'.$row['id'].'</td>
											'.($_SESSION['branches']>1?'<td>'.$row['branch_title'].'</td>':'').'
											<td class="center">'.$row['till'].'</td>
											<td>'.date('d-m-Y',strtotime($row['date'])).'<br/>'.date('h:i A',strtotime($row['added'])).'</td>
											<td class="right">'.Number_format($row['si_total'],2).'</td>
											<td class="right">'.Number_format($row['paid'],2).'</td>
											<td class="right">'.Number_format($row['si_total']-$row['paid'],2).'</td>
											<td>'.$row['first_name'].'</td>
											<td class="center">'.(($row['si_total']-$row['paid'])>1?'<button type="button" class="btn btn-primary waves-effect waves-light popup" href="'.$config_url.'remote.php?file=sales/sales&form=add_payment&id='.$row['id'].'&amount='.($row['si_total']-$row['paid']).'&">Pay</button>':'').'</td>
											<td class="center"><button type="button" class="btn btn-primary waves-effect waves-light print_invoice_btn">Print</button></td>
										</tr>';
										$sum['total']+=$row['si_total'];
										$sum['paid']+=$row['paid'];
									}
								$htm.= '</tbody><tfoot><tr><td colspan="'.$colspan.'"></td><td>Total</td>
									<td class="right">'.Number_format($sum['total'],2).'</td>
									<td class="right">'.Number_format($sum['paid'],2).'</td>
									<td class="right">'.Number_format(($sum['total']-$sum['paid']),2).'</td><td colspan="3"></td>
									</tr></tfoot></table>
							</div>
						</div>
					</div>
				</div>';
		}
		return $htm;
	}
	function validateDate($myDateString,$format="Y-m-d"){
		$val=(bool)strtotime($myDateString);
		if($val==1)return date($format,strtotime($myDateString));
		else return 0;
	}
	function update_qty($id){
		global $con,$_SESSION;
		$result=mysqli_query($con,"SELECT l.*,ifnull(po.date,ifnull(s.date,y.date)) date 
		FROM products_logs l LEFT JOIN po ON (po.id=l.referrer AND l.type=1) LEFT JOIN sales s ON (s.id=l.referrer AND l.type=0) 
		LEFT JOIN yura_batch_history y ON (y.id=l.referrer AND l.type IN(3,4))
		WHERE l.product='$id' AND l.branch='$_SESSION[branch]' AND (l.added>=(SELECT ifnull(max(added),'2021-04-01') FROM products_logs WHERE type=2 and product='$id') AND l.added>='2021-04-01') AND l.checked>=0 ORDER BY l.id ASC") or die(mysqli_error($con));
		$balance=0;
		$date='';
		if(mysqli_affected_rows($con)==0){
			$result2=mysqli_query($con,"SELECT MAX(id) id FROM products_logs WHERE product=$id AND branch='$_SESSION[branch]'") or die(mysqli_error($con));
			$row2=mysqli_fetch_assoc($result2);
			mysqli_query($con,"UPDATE products_logs SET balance='0',stock_adj='1' WHERE id='$row2[id]'");
		}
		while($row=mysqli_fetch_assoc($result)){
			$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
			if($row['type']==2){
				$balance+=$qty;
				$date=date('Y-m-d',strtotime($row['added']));
			}
			else if(strtotime($row['date'])>=strtotime($date)){
				if($row['type']%2==0)$balance-=$qty;
				else $balance+=$qty;
				mysqli_query($con,"UPDATE products_logs SET balance='$balance',stock_adj='1' WHERE id='$row[id]'");
			}
		}
		return $balance;
	}
	function stock_adj($product,$qty){
		global $con,$_SESSION,$current_time;
		mysqli_query($con,"INSERT INTO products_logs(branch,product,type,qty,balance,added,user) VALUES('$_SESSION[branch]','$product',2,'$qty','$qty','$current_time','$_SESSION[user_id]')");
		if($qty==0){
			mysqli_query($con,"UPDATE products_logs SET status=0 WHERE product='$product' AND type=1 AND branch='$_SESSION[branch]'");
		}
	}
	function delete_invoice($id){
		global $con;
		$result=mysqli_query($con,"SELECT * FROM sales WHERE id='$id'");
		$row=mysqli_fetch_array($result);
		if($row['closed']==0){
			mysqli_query($con,"DELETE FROM products_logs_temp WHERE referrer='$id' AND type='0'")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM sales WHERE id='$id'")or die(mysqli_error($con));
		}
		else {
			mysqli_query($con,"DELETE FROM products_logs_temp WHERE referrer='$id' AND type='0'")or die(mysqli_error($con));
			$result=mysqli_query($con,"SELECT * FROM products_logs WHERE referrer='$id' AND type='0'")or die(mysqli_error($con));
			while($row=mysqli_fetch_assoc($result)){
				update_qty($row['product']);
			}
			mysqli_query($con,"DELETE FROM products_logs WHERE referrer='$id' AND type='0'")or die(mysqli_error($con));
			$result=mysqli_query($con,"SELECT * FROM sales_payments WHERE sales='$id'")or die(mysqli_error($con));
			while($row=mysqli_fetch_assoc($result)){
				if($row['type']==2){
					$result1=mysqli_query($con,"SELECT amount FROM sales_cheque WHERE id='$row[child]'")or die(mysqli_error($con));
					$row1=mysqli_fetch_assoc($result1);
					if($row['amount']==$row1['amount'])mysqli_query($con,"DELETE FROM sales_cheque WHERE id='$row[child]'")or die(mysqli_error($con));
					else mysqli_query($con,"UPDATE sales_cheque used=used-'$row[amount]' WHERE id='$row[child]'")or die(mysqli_error($con));
				}
				else if($row['type']==4){
					$result1=mysqli_query($con,"SELECT amount FROM sales_card WHERE id='$row[child]'")or die(mysqli_error($con));
					$row1=mysqli_fetch_assoc($result1);
					if($row['amount']==$row1['amount'])mysqli_query($con,"DELETE FROM sales_card WHERE id='$row[child]'")or die(mysqli_error($con));
					else mysqli_query($con,"UPDATE sales_card used=used-'$row[amount]' WHERE id='$row[child]'")or die(mysqli_error($con));
				}
			}
			mysqli_query($con,"DELETE FROM sales_payments WHERE sales='$id'")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM sales WHERE id='$id'")or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM sales_points WHERE sale='$id'")or die(mysqli_error($con));
		}
	}
	function delete_payment($id){
		global $con;
		mysqli_query($con,"DELETE FROM po_payments WHERE payment='$id'")or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM payments_cheques WHERE payment='$id'")or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM payments WHERE id='$id'")or die(mysqli_error($con));
	}
	function payable($staff){
		global $con,$current_time,$_SESSION;
		$result=mysqli_query($con,"SELECT ss.id,ss.basic,ss.break_down,ifnull((SELECT sum(amount) FROM payments WHERE referrer=ss.id AND category=2),0) paid 
		FROM salary s LEFT JOIN salary_staff ss ON s.id=ss.salary WHERE ss.staff='$staff' and ss.status=0 ORDER BY s.id ASC");
		$payable=array();
		while($row=mysqli_fetch_assoc($result)){
			$balance=($row['basic']+array_sum(json_decode($row['break_down'],true)))-$row['paid'];
			if($balance>0)$payable[$row['id']]=$balance;
			else if($balance==0)mysqli_query($con,"UPDATE salary_staff SET status='1' WHERE id='$row[id]'");
		}
		return $payable;
	}
	function pay_salary($staff,$amount,$data=''){
		global $con,$current_time,$_SESSION;
		if($data=='')$data=array('description'=>'Salary','counter_id'=>'NULL','date'=>date('Y-m-d'));
		$result=mysqli_query($con,"SELECT * FROM users WHERE id='$staff'");
		$row=mysqli_fetch_assoc($result);
		$msg="Dear $row[first_name], Your Salary $amount has been paid on $data[date].\n$data[description]";
		$payable=payable($staff);
		$salary=$amount;
		if(!isset($_SESSION['user_id']))$_SESSION['user_id']=0;
		if(!isset($_SESSION['branch']))$_SESSION['branch']=1;
		foreach($payable as $id=>$balance){
			if($salary>0){
				$paid=$salary;
				if($balance<$salary)$paid=$balance;
				$salary-=$paid;
				mysqli_query($con,"INSERT INTO payments(branch,date,description,amount,category,type,session,referrer,added,user) 
					VALUES('$_SESSION[branch]','$data[date]','$data[description]','$paid','2',1,$data[counter_id],'$id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
			}
		}
		if($salary>0){
			$month=date('m');
			$year=date('Y');
			$result=mysqli_query($con,"SELECT ss.id FROM salary s LEFT JOIN salary_staff ss ON s.id=ss.salary WHERE s.month='$month' AND s.year='$year' AND ss.staff='$staff'");
			$id=0;
			if(mysqli_affected_rows($con)==1){
				$row1=mysqli_fetch_assoc($result);
				$id=$row1['id'];
			}
			else {
				$result=mysqli_query($con,"SELECT id FROM salary WHERE month='$month' AND year='$year'");
				$sid=0;
				if(mysqli_affected_rows($con)==1){
					$row1=mysqli_fetch_assoc($result);
					$sid=$row1['id'];
				}
				else {
					mysqli_query($con,"INSERT INTO salary(year,month,added,user) VALUES('$year','$month','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					$sid=mysqli_insert_id($con);
				}
				mysqli_query($con,"INSERT INTO salary_staff(staff,salary,basic,added,user) VALUES('$staff','$sid',0,'$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
				$id=mysqli_insert_id($con);
			}
			mysqli_query($con,"INSERT INTO payments(branch,date,description,amount,category,type,session,referrer,added,user) 
				VALUES('$_SESSION[branch]','$data[date]','$data[description]','$salary','2',1,$data[counter_id],'$id','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
		}
		mysqli_query($con,"INSERT INTO sms_sent(branch,number,method,mask,type,message,messaged,user) 
		VALUES('$_SESSION[branch]','$row[mobile]',1,1,'0','$msg','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
					
	}
	function get_attendance($from,$to,$id){
		global $con;
		$result=mysqli_query($con,"SELECT logged FROM users_log WHERE logged BETWEEN '$from' AND '$to' AND user='$id' ORDER BY logged") or die(mysqli_error($con));
		$days=array();
		while($row=mysqli_fetch_array($result)){
			$days[date('d-m-Y',strtotime($row['logged']))][]=strtotime($row['logged']);
		}
		$return=array('days'=>sizeof($days));
		$month_sum=0;
		foreach($days as $date=>$logs){
			$in=min($logs);
			$out=max($logs);
			$size=sizeof($logs);
			$dur_over=0;
			$seconds=0;
			if($size>1)$dur_over=$out-$in;
			$effect=0;
			if($size>2 AND $size%2==0){
				$i=1;
				$temp='';
				foreach($logs as $time){
					if($i%2==1)$temp=$time;
					else $seconds+=$time-$temp;
					$i++;
				}
				$effect=$seconds;
			}
			else $effect=$dur_over;
			$month_sum+=$effect;
		}
		$min=round($month_sum/60);
		$hours=number_format((($min-($min%60))/60)+(($min%60)/60),2);
		$return['hours']=$hours;
		return $return;
	}
?>
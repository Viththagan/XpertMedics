<?php
	// print_r($_COOKIE);
	// print_r($_GET);print_r($_POST);
	// setcookie('Counter_login',0, time(), "/");
	$date=date('Y-m-d');
	if(isset($_POST['form_submit'])){//print_r($_POST);return;
		$_POST['date']=date('Y-m-d',strtotime($_POST['date']));
		$_POST['manufactured']=date('Y-m-d',strtotime($_POST['manufactured']));
		$_POST['expiry']=date('Y-m-d',strtotime($_POST['expiry']));
		if($_POST['cost']=='')$_POST['cost']=0;
		mysqli_query($con,"INSERT INTO yura_batch_history(date,manufactured,cost,added,user) VALUES('$_POST[date]','$_POST[manufactured]','$_POST[cost]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
		$batch=mysqli_insert_id($con);
		foreach($_POST['product_raw'] as $id=>$data){
			$product=json_decode($data,true);
			mysqli_query($con,"INSERT INTO products_logs(product,type,referrer,qty,purchased,mrp,selling_price,added,user) 
			VALUES('$id',4,'$batch','$product[qty]','$product[purchased]','$product[mrp]','$product[purchased]','$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
			update_qty($id);
		}
		echo '<table class="table mb-0 form-group"><thead><tr><th>Title</th><th>Qty</th><th>MRP</th><th>Selling</th><th>Sticker</th></tr></thead><tbody>';
		
		foreach($_POST['product'] as $id=>$data){
			$product=json_decode($data,true);
			$result=mysqli_query($con,"SELECT * FROM products_logs WHERE product='$id' ORDER BY id DESC LIMIT 0,1")or die(mysqli_error($con));
			$balance=0;
			if(mysqli_affected_rows($con)==1){
				$row=mysqli_fetch_assoc($result);
				if($row['balance']!='' AND $row['balance']!=null)$balance=$row['balance'];
			}
			$balance+=$product['qty'];
			mysqli_query($con,"INSERT INTO products_logs(product,type,referrer,qty,balance,purchased,mrp,selling_price,expiry,status,added,user) 
			VALUES('$id',3,'$batch','$product[qty]','$balance','$product[cost]','$product[mrp]','$product[selling_price]','$_POST[expiry]',1,'$current_time','$_SESSION[user_id]')")or die(mysqli_error($con));
			$log=mysqli_insert_id($con);
			$result=mysqli_query($con,"SELECT * FROM products WHERE id='$id'");
			$row = mysqli_fetch_assoc($result);
			echo '<tr class="pointer print_barcode" id="'.$log.'"><td>'.$row['title'].'</td><td>'.$product['qty'].'</td><td>'.$product['mrp'].'</td><td>'.$product['selling_price'].'</td><td>'.ceil($product['qty']/2).'</td></tr>';
		}
		echo '</tbody></table>';
		return;
	}
	else if(isset($_POST['select'])){
		switch($_POST['select']){
			case 'product':
				$prices = array();
				$result = mysqli_query($con, "SELECT l.*,s.company,po.date 
				FROM products_logs l LEFT JOIN po ON po.id=l.referrer LEFT JOIN suppliers s ON s.id=po.supplier WHERE l.product='$_POST[id]' and l.type='1' AND l.status='1' and 
				l.id in(SELECT max(id) FROM products_logs WHERE product='$_POST[id]' AND mrp>0 AND purchased>0 AND qty>0 AND status='1' and type='1' group by mrp,purchased)") or die(mysqli_error($con));
				while($row = $result->fetch_assoc()){
					if($row['free']>0){
						$qty=($row['uic']>0?($row['qty']*$row['uic']):$row['qty'])+$row['free'];
						$row['cost']=($row['purchased']*$row['qty'])/$qty;
					}
					else $row['cost']=($row['uic']>0?($row['purchased']/$row['uic']):$row['purchased']);
					$row['cost']=number_format($row['cost'],2, '.', '');
					$row['selling_price']=(($row['mrpc']>0 AND ($row['mrpc']<=$row['selling_price'] OR $row['selling_price']==null) AND $row['mrpc']<$row['mrp'])?$row['mrpc']:(($row['selling_price']<$row['mrp'] AND $row['selling_price']>0 AND $row['discount']==1)?$row['selling_price']:$row['mrp']));
					array_push($prices,$row);
				}
				
				echo json_encode(array('prices'=>$prices));
				break;
		}
		return;
	}
	else if(isset($_POST['print'])){
		echo print_label($_POST['id']);
		return;
	}
	$result=mysqli_query($con,"SELECT p.*,(SELECT COUNT(*) FROM products_logs WHERE product=p.id) so FROM products p WHERE p.company IN (0,1) AND status='1' ORDER BY so DESC");
	$products=array();
	while($row = $result->fetch_assoc())array_push($products,$row);
	echo '<script>
	var products='.json_encode($products).';
	</script>';
	unset($products);
	{
	$loader = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
	<div id="ajax_indicator" class="hide alert alert-success alert-dismissible fade show" role="alert"><i class="mdi mdi-check-all mr-2"></i><span class="text"></span>
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
	echo '<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div id="invoice" class="card-body">
                    <form id="invoice-header" action="" validate>
						<div class="container invoice_header" changed="0">
							<div class="row">
								<div class="col-md-2">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label print_barcode_last">Date</label>
										<div class="col-lg-8">
											<input name="date" type="text" class="form-control date" value="'.date('d-m-Y').'" '.($_SESSION['soft']['user_level']==0?'':'disabled').'>
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Manufactured</label>
										<div class="col-lg-8">
											<input name="manufactured" type="text" class="form-control date" value="'.date('d-m-Y').'">
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Expiry</label>
										<div class="col-lg-8">
											<input name="expiry" type="text" class="form-control date" value="'.date('d-m-Y',strtotime('+6months')).'">
										</div>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group row">
										<label class="col-lg-4 col-form-label">Cost</label>
										<div class="col-lg-8">
											<input name="cost" type="number" class="form-control">
										</div>
									</div>
								</div>
							</div>
						</div>
						<fieldset class="container">
							<div class="row">
								<table class="table mb-0 form-group">
									<thead><tr><th>#</th><th>Product</th><th>Qty</th><th class="right">Price</th><th class="right">Purchased</th><th class="right">Total</th><th></th></tr></thead>
									<tbody id="invoice-item-container_raw">';
									echo '</tbody>
									<tbody><tr>
											<th scope="row"></th>
											<td class="title" style="position: relative;">
												<input type="text" id="form-autocomplete" class="search_input x-large form-control product_raw">'.$loader.'
											</td><td colspan="4"></td>
										</tr>
									</tbody>
									<tfoot><tr><td></td><td>Total</td><td colspan="3"></td><td class="product-total right">0.00</td></tr></tfoot>
								</table>
							</div>
						</fieldset>
				</div>
			</div>
		</div>
	</div>';
	echo '<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div id="invoice" class="card-body">
						<fieldset class="container">
							<div class="row">
								<table class="table mb-0 form-group">
									<thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Ratio</th><th>Cost</th><th class="right">MRP</th><th class="right">Selling Price</th><th class="right">Total</th></tr></thead>
									<tbody id="invoice-item-container">';
									echo '</tbody>
									<tbody><tr>
											<th scope="row"></th>
											<td class="title" style="position: relative;">
												<input type="text" id="form-autocomplete" class="search_input x-large form-control product">'.$loader.'
											</td><td colspan="6"></td>
										</tr>
									</tbody>
									<tfoot><tr><td></td><td>Total</td><td colspan="5"></td><td class="product-total right">0.00</td></tr></tfoot>
								</table>
							</div>
							<div class="row card-body">
							<button type="button" class="hide btn btn-primary btn-lg waves-effect waves-light mr-1 preview_btn btn-block" id="pay_button">Add</button>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>';
	}
	if (strpos(dirname(__FILE__),'/') !== false)$folders=explode('/',dirname(__FILE__));
	else $folders=explode('\\',dirname(__FILE__));
	$plugin=end($folders);
	echo '<script>
	plugin="'.$plugin.'";
	btn=\''.$btn.'\';
    remote_file="'.$config_url.'remote.php?file='.$plugin.'/";
	</script>';
?>
<script>
	function after_ajax(){
		
	}
	$(document).unbind('keydown');
	$(document).bind('keydown',"f1", function(e) {//alert(1);
		$(".product").focus();
		return false;
	});
	$(document).bind('keydown',"alt", function(e) {//alert(1);
		$('.product_tr:last-child').find('.qty').trigger('click');
		return false;
	});
	$('.print_barcode_last').click(function(){
		window.print();
	});
	$('#pay_button').click(function(){
		var data = $('#invoice-header').serializeArray();
		data.push({name: "form_submit", value: 'add_production'});
		$('#invoice-item-container_raw .product_tr').each(function(id){
			var temp = new Array();
			temp.push({name: "mrp", value: $(this).attr('mrp')});
			temp.push({name: "log_id", value: $(this).attr('log_id')});
			temp.push({name: "purchased", value: $(this).attr('purchased')});
			temp.push({name: "qty", value: $(this).find('.qty').text()});
			var temp2 = {};
			$.map(temp, function(n, i){
				temp2[n['name']] = n['value'];
			});
			data.push({name: "product_raw["+$(this).attr('product_id')+"]", value: JSON.stringify(temp2)});
		});
		$('#invoice-item-container .product_tr').each(function(id){
			var temp = new Array();
			temp.push({name: "qty", value: $(this).find('.qty').text()});
			temp.push({name: "mrp", value: $(this).find('.mrp').text()});
			temp.push({name: "uic", value: $(this).find('.uic').text()});
			temp.push({name: "cost", value: $(this).find('.cost').text()});
			temp.push({name: "selling_price", value: $(this).find('.selling_price').text()});
			var temp2 = {};
			$.map(temp, function(n, i){
				temp2[n['name']] = n['value'];
			});
			data.push({name: "product["+$(this).attr('product_id')+"]", value: JSON.stringify(temp2)});
		});
		$.post(remote_file+"repack",data,function(data){//alert(data);
			$('#barcode_list').html(data);
			$('.barcode_list_but').trigger('click');
			$('.product_tr').remove();
			$('.print_barcode').click(function(){
				$.post(remote_file+"repack",{print: 'print_barcode',id:$(this).attr('id')},function(data){
					// $('#printableArea').html(data);
					// invoice_total(ele)q
					// setTimeout(function () {window.print();},500);
				});
			});
			
		});
	});
	if(typeof products!='undefined' && $( ".product" ).length >0){
		var count = 0;
		function select_product(product,type=''){
			$('.product'+type).val('');
            $('#invoice-item-container'+type).next().find('.lds-ellipsis').css('display', 'inline-block');
			$.post(config_url+"remote.php?file=products/repack",{select:'product',id:product.id,type:type},function(data){
				pi=jQuery.parseJSON(data);
				prices=pi.prices;
				promo=pi.promo;
				var mrp='';
				product.purchased=0;
				product.pid=0;
				product.selling_price=0;
				product.discount=0;
				if(prices.length==1){
					mrp=prices[0].mrp;
					product.selling_price=prices[0].selling_price;
					product.purchased=prices[0].cost;
					product.pid=prices[0].id;
					product.discount=prices[0].discount;
				}
				modal='data-toggle="modal" data-target=".price_vary"';
				$('.lds-ellipsis').css('display', 'none');
				$('#invoice-item-container'+type).append($('<tr></tr>').attr({id : "product_tr"+type+"_"+count, class: "product_tr", changed: 0, product_id: product.id, mrp : mrp, purchased : product.purchased, log_id : product.pid,selling_price:product.selling_price,discount:product.discount,category:product.category,brand:product.brand,prices:JSON.stringify(prices)}).html(
					'<td class="product_count"></td><td class="title" '+modal+'>'+product.ui_code+' - '+product.title+'</td>'+
					'<td class="qty">1</td>'+(type==''?'<td class="uic price">'+product.uic+'</td><td class="cost"></td>':'')+
					'<td class="mrp right price">'+mrp+'</td><td class="price selling_price right">'+mrp+'</td>'+
					'<td class="product_total right">'+mrp+'</td><td class="product_delete pointer"><i class="mdi mdi-delete" hide="1"></i></td>') );
				count_products(type);
				$(".edit_value").focus().select();
				$('#product_tr'+type+"_"+count).change((e)=>{
					$(e.currentTarget).attr('changed', '1');
					update(e);
				});
				$('#product_tr'+type+"_"+count).find('.product_delete').click(delete_product);
				$('#product_tr'+type+"_"+count).find('.title').click(price_vary);
				if(prices.length>=1 && type=='_raw')$('#product_tr'+type+"_"+count).find('.title').trigger('click');
				$('#product_tr'+type+"_"+count).trigger('change');
				bind_qty_click($('#product_tr'+type+"_"+count).find('.qty'));
				if(type=='')bind_price_click($('#product_tr'+type+"_"+count).find('.price'));
				else if(prices.length==0)bind_price_click($('#product_tr'+type+"_"+count).find('.price'));
				count++;
			});
		}
		function qty_update_enable(){
			$('.edit_value').on('focusout keypress', function(e) {
				if(e.which == 13 || e.which == 0){
					tr=$(e.currentTarget).parents('tr');
					if($(this).val()<10000){
						if($(this).val()!=0 && $(this).val()!='')$(this).parent().html($(this).val());
						else $(this).parent().html(1);
					}
					else {
						$(".product").val($(this).val()).trigger('keydown');
						$(this).parent().html(1);
					}
					bind_qty_click(tr.find('.qty'));
				}
			});
		}
		function bind_qty_click(ele){
			ele.click(function(){
				$(this).html('<input type="number" value="'+$(this).text()+'" class="edit_value form-control" style="width: 100px;"/>');
				$(".edit_value").focus().select();
				$(this).unbind("click");
				qty_update_enable();
			});
		}
		function bind_price_click(ele){
			ele.click(function(){
				$(this).html('<input type="number" value="'+$(this).text()+'" class="edit_value form-control" style="width: 100px;"/>');
				$(".edit_value").focus().select();
				$(this).unbind("click");
				$('.edit_value').on('focusout keypress', function(e) {
					if(e.which == 13 || e.which == 0){
						bind_price_click($(this).parents('.price'));
						$(this).parent().html($(this).val());
					}
				});
			});
		}
		function update(e){
            ele=$(e.currentTarget);
			setTimeout(function () { // needed because nothing has focus during 'focusout'
                if ( ($(':focus').closest(ele).length <= 0)) {
					if(ele.parent().attr('id')=='invoice-item-container_raw')ele.find('.selling_price').text(Number(ele.attr('purchased')));
					ele.find(".product_total").text( (Number(ele.find('.selling_price').text()) * Number(ele.find('.qty').text()) ).toFixed(2) );
					invoice_total(ele.parent());
					$(".product"+type).focus();
                }
            }, 0);
        }
		function count_products(type){
            $('#invoice-item-container'+type+' .product_count').each(function(id){
                $(this).text(id+1);
            });
        }
		function invoice_total(ele){
            var sum=0;
			$('#pay_button').show();
			ele.find('.product_total').each(function(id){
                if($(this).text()!=='' && $(this).text()>0 && Number($(this).parent().find('.mrp').text())>=Number($(this).parent().find('.selling_price').text()))sum+=Number($(this).text());
				else $('#pay_button').hide();
				if(Number($(this).parent().find('.selling_price').text())<=Number($(this).parent().find('.cost').text()))$('#pay_button').hide();
            });
			if($('#invoice-item-container_raw .product_total').length==0 || $('#invoice-item-container .product_total').length==0 || ($('#invoice-item-container_raw .product_total').length>1 && $('input[name="type"]:checked').val()==0))$('#pay_button').hide();
			ele.parent().find('.product-total').text( sum.toFixed(2) );
			cost_calculation();
        }
		$('input[name="cost"]').on('focusout keypress', function(e) {
			if(e.which == 13 || e.which == 0){
				cost_calculation();
			}
		});
		function cost_calculation(){
            var cost=$('input[name="cost"]').val();
			if(cost=='')cost=0;
			cost=Number(cost)+Number($('#invoice-item-container_raw').parent().find('.product-total').text());
			if($('#invoice-item-container .product_total').length==1){
				cost=(cost/Number($('#invoice-item-container .qty').text()));
				$('#invoice-item-container .cost').text(cost.toFixed(2));
			}
			else if($('#invoice-item-container .product_total').length>1){
				var output=0;
				$('#invoice-item-container tr').each(function(i){
					output=output+(Number($(this).find('.qty').text())/Number($(this).find('.uic').text()));
				});
				unit_cost=cost/output;
				$('#invoice-item-container tr').each(function(i){
					$(this).find('.cost').text((unit_cost/Number($(this).find('.uic').text())).toFixed(2));
				});
			}
			if(Number($('#invoice-item-container_raw').parent().find('.product-total').text())>Number($('#invoice-item-container').parent().find('.product-total').text()))$('#pay_button').hide();
        }
		function delete_product(e){
			ele=$(e.currentTarget).parents('tr');
			parent=ele.parent();
			ele.remove();
			count_products(parent.attr('id').replace('invoice-item-container',''));
			invoice_total(parent);
           
        }
		function price_vary(e){
            ele=$(e.currentTarget).parents('tr');
			prices=jQuery.parseJSON( ele.attr('prices') );
			var htm='<table class="table mb-0 form-group"><thead><tr><th>Supplier</th><th>Date</th><th>Qty</th><th>Unit</th><th>Free</th><th>Purchased</th><th>MRP</th><th>Cost</th></tr></thead><tbody>';
			$.each( prices, function( key, value ) {
				htm=htm+'<tr class="pointer select_price" id="' + ele.attr('id') + '" data="' +JSON.stringify(value).replaceAll('"', "'")+ '">'+
				'<td>' + value.company + '</td>'+
				'<td>' + value.date + '</td>'+
				'<td>' + value.qty + '</td>'+
				'<td>' + value.uic + '</td>'+
				'<td>' + value.free + '</td>'+
				'<td>' + value.purchased + '</td>'+
				'<td>' + value.mrp + '</td>'+
				'<td>' + value.cost + '</td></tr>';
			});
			htm=htm+'</tbody></table>';
			$('#price_vary').html(htm);
			$('.select_price').click(select_price);
			// $(document).unbind('keyup').keyup(function(e){
                // alert(e.which);
            // });
			$(document).unbind('keyup').keyup(function(e){
				// e.preventDefault();
				// $('body').css('overflow','hidden');
				if(e.which == 40){
					if($('.select_price.selected').length==1){
						if($('.selected').next().length==1)$('.selected').removeClass('selected').next().addClass('selected');
						else {
							$('.selected').removeClass('selected');
							$('.select_price:first-child').addClass('selected');
						}
					}
					else $('.select_price:first-child').addClass('selected');
				}
				else if(e.which == 38){
					if($('.select_price.selected').length==1){
						if($('.selected').prev().length==1)$('.selected').removeClass('selected').prev().addClass('selected');
						else {
							$('.selected').removeClass('selected');
							$('.select_price:last-child').addClass('selected');
						}
					}
					else $('.select_price:last-child').addClass('selected');
				}
                else if(e.which == 13){
					$('.selected').trigger('click');
					// $('body').css('overflow','auto');
				}
			});
        }
		function select_price(e){
            ele=$(e.currentTarget);
			prices=jQuery.parseJSON( ele.attr('data').replaceAll("'",'"') );
			$('#'+ele.attr('id')).attr('mrp',prices.mrp);
			$('#'+ele.attr('id')).attr('selling_price',prices.selling_price);
			$('#'+ele.attr('id')).attr('purchased',prices.cost);
			$('#'+ele.attr('id')).attr('log_id',prices.id);
			$('#'+ele.attr('id')).find('.mrp').text(prices.mrp).trigger('change');
			$('#'+ele.attr('id')).find('.edit_value').focus().select();
			$(".price_vary .close").trigger('click');
        }
		$( ".product_raw" ).autocomplete({
			minLength: 1,
			autoFocus: true,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( products, function( value ) {
					return value.category==98 && (matcher.test(value.title.replace(/\ /g, "")) ||  matcher.test(value.ui_code) ||  matcher.test(value.bulk_code) ||  matcher.test(value.remind) || matcher.test(value.mrp));
				}).slice(0, 15) );
			},
			focus: function(event,ui){
				event.preventDefault();
				return false;
			},
			response: function(event, ui) {
				$(this).val($(this).val().trim());
				if (ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].ui_code || ui.content[0].remind.indexOf($(this).val()) != -1)){
					ui.item=ui.content[0];
					$(this).val(ui.content[0].ui_code);
					select_product(ui.item,'_raw');
					$(this).autocomplete( "close" );
				}
			},
			select: function(event,ui){
				select_product(ui.item,'_raw');
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul,item){
			tam='';
			if(item.title_tamil!='')tam="&nbsp;("+item.title_tamil+")";
			return $("<li>").append("<a>"+item.title+tam+"&nbsp;[Rs."+item.mrp+"]<br>"+item.ui_code+"</a>").appendTo(ul);
		};
		$( ".product" ).autocomplete({
			minLength: 1,
			autoFocus: true,
			source: function( request, response ) {
				var matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term.replace(/\ /g, "") ), "i" );
				response( $.grep( products, function( value ) {
					return value.category!=98 && (matcher.test(value.title.replace(/\ /g, "")) ||  matcher.test(value.ui_code) ||  matcher.test(value.bulk_code) ||  matcher.test(value.remind) || matcher.test(value.mrp));
				}).slice(0, 15) );
			},
			focus: function(event,ui){
				event.preventDefault();
				return false;
			},
			response: function(event, ui) {
				$(this).val($(this).val().trim());
				if (ui.content.length == 1 && ($(this).val().toUpperCase()==ui.content[0].ui_code || ui.content[0].remind.indexOf($(this).val()) != -1)){
					ui.item=ui.content[0];
					$(this).val(ui.content[0].ui_code);
					select_product(ui.item);
					$(this).autocomplete( "close" );
				}
			},
			select: function(event,ui){
				select_product(ui.item);
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul,item){
			tam='';
			if(item.title_tamil!='')tam="&nbsp;("+item.title_tamil+")";
			return $("<li>").append("<a>"+item.title+tam+"&nbsp;[Rs."+item.mrp+"]<br>"+item.ui_code+"</a>").appendTo(ul);
		};
	}
	function ajax_indicate(msg){
        console.log(msg)
        if( $('#ajax_indicator').hasClass('hide') ){
            show();
            setTimeout(hide,2000);
        }else{
            hide();
        }
        function show(){
            $('#ajax_indicator span.text').html(msg);
            $('#ajax_indicator').fadeIn();
            setTimeout(hide,2000);
        }
        function hide(){
            $('#ajax_indicator').fadeOut();
        }
    }
</script>
<div class="modal fade price_vary" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	 <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="price_vary"></div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade barcode_list" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
	 <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div id="barcode_list"></div>
			</div>
		</div>
	</div>
</div>
<div class="hide barcode_list_but" data-toggle="modal" data-target=".barcode_list"></div>
<style>
#cash_register .form-group{margin-bottom: 0.2rem;}
.ui-menu-item{cursor: pointer;}
#ajax_indicator{position: fixed;min-width: 50%;
left: 10px;
bottom: 10px;
z-index: 100;}
</style>
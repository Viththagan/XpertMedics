<?php
	if(isset($_SESSION['soft']['user_id'])){
		$img='https://dfc.lk/app/icons/icon-325x200.png';
		if(file_exists('uploads/'.$_SESSION['soft']['settings']['logo']))$img=$config_url.'uploads/'.$_SESSION['soft']['settings']['logo'];
		echo '<script type="text/javascript">
			var config_url="'.$config_url.'";
			var user_id='.$_SESSION['soft']['user_id'].';
			var user_name="'.$_SESSION['soft']['user_data']['first_name'].'";
			var logo="'.$img.'";
			var user_level='.(isset($_SESSION['soft']['user_data']['user_level'])?$_SESSION['soft']['user_data']['user_level']:0).';
		</script>';
	}
	echo '<div id="js_plugin"></div>';
?>
<script>
/*=================
Agoy's
===================*/
//document.addEventListener('ready',function(){
    $(function(){
        // $('.open-left').trigger('click');
		agoy_init_run=0;
		hash_changed=0;
		
        $.progress_bar_init = function(){
            $('#grid_container').html($('#progress-bar').html());
            function progress(){
                value=Number($('#grid_container .progress-bar').attr('aria-valuenow'))+1;
                $('#grid_container .progress-bar').attr('aria-valuenow',value);
                $('#grid_container .progress-bar').attr('style','width: '+value+'%;');
                $('#grid_container .progress-bar').text(value+'%');
                if(value<50)setTimeout(progress,100);
                else if(value<75)setTimeout(progress,200);
                else if(value<85)setTimeout(progress,300);
                else if(value<95)setTimeout(progress,500);
                else if(value<=99)setTimeout(progress,1000);
                else if(value==100)$.progress_bar_init();
            }
            progress();
        };
        $.page_title = function(title){
			$('#page_title').html(title);
			$(document).attr("title", title+' - Daily Food City (Pvt) Ltd');
		};
        $.open_page = function(file,form=null){
			$('#grid_container').html($('#progress').html());
			hash_changed=1;
			location.hash=file;
			if(file.split('/')[1]=='analytics'){
				$('#leftbar').hide();
				$('.enlarged #wrapper .navbar-custom').attr('style','margin-left: 0px !important;');
				$('.enlarged #wrapper .topbar .topbar-left').attr('style','width: 0px !important;');
				$('.content-page').attr('style','margin-left: 0px;');
			}
			else {
				$('#leftbar').show();
				$('.enlarged #wrapper .navbar-custom').attr('style','margin-left: 70px !important;');
				$('.enlarged #wrapper .topbar .topbar-left').attr('style','width: 70px;');
				$('.content-page').attr('style','margin-left: 70px;');
			}
            title=file.split('/')[1].toLowerCase().replace(/\b[a-z]/g, function(letter) {
				return letter.toUpperCase();
			});//alert(config_url);
			if(form==null)form={width: screen.width, height:screen.height};
            $.get(config_url+"remote.php?file="+file.replace('\\','&id='),form,function(d){//alert(d);
                $('#grid_container').html(d);
				$.page_title(title);
				agoy_init_run=0;
				hash_changed=0;
				$.agoy_init();
            });
        };
        window.onhashchange = function() {
			if(hash_changed==0)$.trigger_page_with_hash();
		}
		$.trigger_page_with_hash = function(){
			if(window.location.hash!=''){
				var loc = window.location.hash.replace('%20',' ');
				loc=loc.slice(1,loc.length);
				ele=document.createElement('DIV');
				$(ele).attr({'file':loc});
				$.open_page($(ele).attr('file'));
			}
		};
		$.trigger_page_with_hash();
        $.add_table_button = function(plugin,table,title,file,id,type,vars){
            $.add_table_button_run_delay = function(plugin,table,title,file,id,type,vars){
                if($("table[table='"+table+"'],table[parent='"+table+"']").length>0){
                    $("#DataTables_Table_0_wrapper .col-sm-12:eq(0)").removeClass('col-md-6').addClass('col-md-9');
                    $("#DataTables_Table_0_wrapper .col-sm-12:eq(1)").removeClass('col-md-6').addClass('col-md-3');
					tools=$("table[table='"+table+"'],table[parent='"+table+"'").parents('#DataTables_Table_0_wrapper').find('.dt-buttons');
                    if($("#"+id).length == 0){
						href=config_url+"remote.php?file="+plugin+'/'+file+vars;
						var cls='popup';
						if(type=='menu')cls='back';
						tools.append('<button class="btn btn-secondary buttons-copy buttons-html5 '+cls+'" tabindex="0" aria-controls="datatable-buttons" title="'+title+'" href="'+href+'" id="'+id+'" file="'+plugin+'/'+file+'"><span><span>'+title+'</span></button>');
                        if(type=='menu')$.menu_init();
						else $.agoy_init();
                    }
                }
            };
            setTimeout(function(){$.add_table_button_run_delay(plugin,table,title,file,id,type,vars);},500);
        };
        $.form_submit = function(form_ele){//$('#test').text($('#test').text()+','+67);
			var form_submitted=0;
			if(form_submitted==0){
				$.post(config_url+"remote.php?file="+form_ele.attr('action'), form_ele.serializeArray(),
                function(data){
                    if(data!=='')data = jQuery.parseJSON(data);
                    if(data.callback == 1)after_submit(data.parameter);
                    else $.open_page(form_ele.attr('action'));
                });
				form_submitted=1;
			}
        };
		$.popup_form_submit = function(form_ele,call_back=''){
			var form_submitted=0;
			if(form_submitted==0){
				$.post(config_url+"remote.php?file="+form_ele.attr('action'), form_ele.serializeArray(),
				function(data){
					if(data!=''){
						if(call_back=='print'){
							$('#printableArea').html(data);
							window.print();
						}
						else alert(data);
					}
					// form_ele
				});
				form_submitted=1;
			}
		};
		$.view_table = function(form_ele){
            $.post(config_url+"remote.php?file="+form_ele.attr('action'), form_ele.serializeArray(),
                function(data){
                    $('#'+form_ele.attr('view')).html(data);
					agoy_init_run=0;
                    $.agoy_init();
                });
        };
		$.numberWithCommas = function(x) {
			return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
		$.agoy_init = function(){
			if(typeof after_ajax == 'function')after_ajax();
			$('form').parsley();
            $(".select").select2();
            $( ".date" ).datepicker({autoclose: true});
			$('.agoy_menu').unbind('click').click(function(){
				if($(this).attr('href')!='')location.hash=$(this).attr('href');
			});
			$('.boolean_check').click(function(){
				tr=$(this).parents('tr');
				if($(this).attr('value')!='' && $(this).attr('field')!=''){
					var table=tr.parents('.table').attr('table');
					var file=tr.parents('.table').attr('file');
					if (typeof tr.attr('table')!== typeof undefined && tr.attr('table')!== false)table=tr.attr('table');
					$.post(config_url+"remote.php?file="+file,{edit:table,id:tr.attr('id'),field:$(this).attr('field'),value:$(this).attr('value')},function(d){
						tr.remove();
					});
				}
			});
			$(".update_available_qty").click(function(){
				$(this).addClass("fa-spin");
				but=$(this);
				$.post(config_url+"remote.php?file=products/products",{form_submit:'adj_stock',id:$(this).attr('id')},function(d){
					if(d!='')alert(d);
					but.remove();
				});
			});
            $('.on_off input[type="radio"]').unbind('change').bind('change',function(){
				if($(this).val()!='' && $(this).attr('name')!=''){
					var table=$(this).parents('.table').attr('table');
					var file=$(this).parents('.table').attr('file');
					id=0;
					if(typeof $(this).parents('tr').attr('id')!== typeof undefined && $(this).parents('tr').attr('id')!== false)id=$(this).parents('tr').attr('id');
					else if(typeof $(this).attr('id')!== typeof undefined && $(this).attr('id')!== false)id=$(this).attr('id');
					if (typeof $(this).attr('table')!== typeof undefined && $(this).attr('table')!== false)table=$(this).attr('table');
					if(id>0){
						$.post(config_url+"remote.php?file="+file,{edit:table,id:id,field:$(this).attr('name'),value:$(this).val()},function(d){
							
						});
					}
				}
				$('.'+$(this).attr('name')).toggleClass('hide');
			});
            // $('form.form_submit').unbind('submit').submit(function(event){
                // event.preventDefault();
                // $.form_submit($(this));
            // });
			popup_form_submit=0;
            $('form.popup_form_submit').submit(function(event){
                if(popup_form_submit==0){
					popup_form_submit=1;
					event.preventDefault();
					id=$(this).find('input[name="id"]').val();
					if($('#callback').val()=='remove')$('#'+id).remove();
					else if($('#callback').val()=='back')window.history.back();
					$.popup_form_submit($(this),$('#callback').val());
					$('.mfp-close').trigger('click');
				}
            });
            $('form.view_table').unbind('submit').submit(function(event){
                event.preventDefault();
                $.view_table($(this));
            });
            $('.first_field:first').focus();
			$('#selectable').unbind("click").click(function(){
				table=$(this).parents('table');
				table.find('.selectable').parents('tr').toggleClass('selected').trigger('classChange');
			});
			$('.selectable').unbind("click").click(function(){
				$(this).parents('tr').toggleClass('selected').trigger('classChange');
			});
            $('.editable').unbind("click").click(function(){
                var e = jQuery.Event("keypress");
                e.which = 13;
                $(".edit_value").trigger(e);
                var add_class = '';
                var add_id = '';
                var removed_class = '';
				if($(this).hasClass("form-control")){
					$(this).toggleClass("form-control");
					removed_class="form-control";
				}
                if (typeof $(this).attr('add_class') !== typeof undefined && $(this).attr('add_class') !== false)add_class=$(this).attr('add_class');
                if (typeof $(this).attr('add_id') !== typeof undefined && $(this).attr('add_id') !== false)add_id=$(this).attr('add_id');
                $(this).html('<input type="text" value="'+$(this).text().replace(',','')+'" class="edit_value '+add_class+' form-control" id="'+add_id+'"/>');
                // $.agoy_init();
                $(".edit_value").focus().select();
                $(this).unbind("click");
				$('.edit_value').on('focusout keypress', function(e) {
					if(e.which == 13 || e.which == 0){
						ele=$(e.currentTarget);
						tr=ele.parents('tr');
						var field=ele.parent().attr('field');
						var table=ele.parents('.table').attr('table');
						var file=ele.parents('.table').attr('file');
						var id=ele.parent().parent().attr('id');
						if (typeof ele.parent().attr('id')!== typeof undefined && ele.parent().attr('id')!== false)id=ele.parent().attr('id');
						if (typeof ele.parent().attr('table')!== typeof undefined && ele.parent().attr('table')!== false)table=ele.parent().attr('table');
						var value=$(this).val();
						if(ele.hasClass('dec')){
							value=Number(ele.val().replace(/[^0-9\.]+/g,"")).toFixed(2);
						}
						tr.attr(field,value);
						ele.parent().html(value).toggleClass(removed_class);
						$.post(config_url+"remote.php?file="+file, { edit: table, field: field, id: id, value:value},function(d){if(d!='')alert('editable '+d);});
						$.agoy_init();
					}
                });
            });
            $('.editable_select').unbind("click").click(function(){
				select=$(this);
				data=jQuery.parseJSON($(this).attr('data').replaceAll("'",'"'));
				val=$(this).attr('value');
				var removed_class = '';
				if($(this).hasClass("form-control")){
					$(this).toggleClass("form-control");
					removed_class="form-control";
				}
				htm='<select class="form-control select_edit">';
					$.each( data, function( key,value ) {
						htm=htm+'<option value="' + key + '" '+ (val==key?'selected':'') +'>' + value + '</option>';
                    });
					htm=htm+'</select>';
				select.html(htm);
				$(".select_edit").select2();
				select.unbind("click");
				$('.select_edit').bind('change',function(e){
					ele=$(e.currentTarget);
					tr=ele.parents('tr');
					var field=ele.parent().attr('field');
					var table=ele.parents('.table').attr('table');
					var file=ele.parents('.table').attr('file');
					var id=ele.parent().parent().attr('id');
					if (typeof ele.parent().attr('id')!== typeof undefined && ele.parent().attr('id')!== false)id=ele.parent().attr('id');
					if (typeof ele.parent().attr('table')!== typeof undefined && ele.parent().attr('table')!== false)table=ele.parent().attr('table');
					var value=$(this).val();
					tr.attr(field,value);
					ele.parent().html(ele.children("option:selected").text()).attr('value',value).toggleClass(removed_class);
					$.post(config_url+"remote.php?file="+file, { edit: table, field: field, id: id, value:value},function(d){if(d!='')alert('editable '+d);});
					$.agoy_init();
				});
			});
            $('.text_count').unbind('keyup').keyup(function(e){
				count=$(this).val().length;
				if($(this).parent().find('#text_count_result').length==0){
					$(this).parent().append('<ul class="parsley-errors-list filled"><li class="parsley-required" id="text_count_result">'+count+'</li></ul>');
				}
				else $('#text_count_result').text(count);
            });
            $('.enter_next').unbind('keyup').keyup(function(e){
                if(e.which==13)$(this).parent().next().find('input').focus();
            });
            $('.form_submit_click').unbind('click').click(function(){
				form_ele=$(this).parents('form');
				$.open_page(form_ele.attr('action'),form_ele.serializeArray());
				// $.form_submit($(this).parents('form'));
            });
            $('.form_submit_return').unbind('keyup').keyup(function(e){
				if(e.which==13){
					form_ele=$(this).parents('form');
					$.open_page(form_ele.attr('action'),form_ele.serializeArray());
				}
            });
            $('.form_submit_change').unbind('change').bind('change',function(){
				val=$(this).val();
				form_ele=$(this).parents('form');
				// setTimeout(function(){
					if(val!=''){
						$.open_page(form_ele.attr('action'),form_ele.serializeArray());
					}// $.form_submit($(this).parents('form'));
				// },0);
            });
            $('.enter_view_table_inline').unbind('keyup').keyup(function(e){
                if(e.which==13)$.view_table($(this).parents('form'));
            });
            $('.view_table_inline').unbind('click').click(function(){
                $.view_table($(this).parents('form'));
            });
            $('input[type="checkbox"]').unbind('click').click(function(){
                open=$(this).attr('id');
                if($(this).is(":checked"))$('div[open="'+open+'"]').show();
                else $('div[open="'+open+'"]').hide();
            });
			var form_submitted=0;
            /*$('.popup_form').unbind('click').click(function(){
				event.preventDefault();
				td=$(this);
				tr=$(this).parents('tr');
                plugin=$(this).parents('table').attr('plugin');
                table=$(this).parents('table').attr('table');
                file=$(this).parents('table').attr('file');
                callback=$(this).attr('callback');
                if (typeof td.attr('table') !== typeof undefined && td.attr('table') !== false)table=td.attr('table');
                id=tr.attr('id');
				if($('.selected').length>0){
					var id = [];
					$('.selected').each(function (){
						id.push(parseInt($(this).attr('id')));
					});
				}
                value_array=$(this).attr('value').split(',');
                target=$(this).attr('data-target');
				field=target.substring(1, target.length);
                if(value_array!=''){
                    $.each( value_array, function( key,value ) {
                        // $(target).find("#"+field).find("option[value="+value+"]").prop("selected", "selected");
                    });
                }
				$(target).find("#id").val(id);
				$('#'+field).unbind("click").click(function(){
					if(form_submitted==0){
						$(this).parents('form').submit(function(event){
							event.preventDefault();
							$.popup_form_submit($(this));
							if(callback=='remove')tr.remove();
						});
						form_submitted=1;
					}
					$(".close").trigger('click');
				});
            });*/
            $('.popup_select').unbind('click').click(function(){
                td=$(this);
                plugin=$(this).parents('table').attr('plugin');
                file=$(this).parents('table').attr('file');
                table=$(this).parents('table').attr('table');
                if (typeof td.attr('table') !== typeof undefined && td.attr('table') !== false)table=td.attr('table');
                target=$(this).attr('data-target');
				field=target.substring(1, target.length);
				menu_id='';
				if (typeof td.attr('data') !== typeof undefined && td.attr('data') !== false){
					d=td.attr('data').split('-');
					if(d[1]!='')menu_id='-'+d[1];
					data = jQuery.parseJSON(d[0].replaceAll("'",'"'));
					htm='';
					$.each( data, function( key,value ) {
						htm=htm+'<option value="' + value+menu_id + '">' + value + '</option>';
                    });
					$(target).find("#"+field).html(htm);
				}
                id=$(this).parents('tr').attr('id');
				if($('.selected').length>0){
					var id = [];
					$('.selected').each(function (){
						id.push(parseInt($(this).attr('id')));
					});
				}
                value_array='';
				if($(this).attr('value')!='')value_array=$(this).attr('value').split(',');
				$(target).find("#"+field).val([]);
                if(value_array!=''){
                    $.each( value_array, function( key,value ) {
						$(target).find("#"+field).find("option[value="+value+menu_id+"]").prop("selected", "selected");
						// $(target).find("#"+field).val(value).trigger('change');
                    });
                }
                // setTimeout(function(){$.agoy_init();},500);
                if($('.'+field+'_update').length==0){
                    $("#"+field).unbind("change").bind('change',function(){
                        select=$(this);
						if (typeof td.attr('field') !== typeof undefined && td.attr('field') !== false)field=td.attr('field');
						if (typeof td.attr('id') !== typeof undefined && td.attr('id') !== false)id=td.attr('id');
                        $.post(config_url+"remote.php?file="+file,
                            { edit: table, field: field, id: id, value:$(this).val()},function(d){
                            if(d!='')alert(d);
                            td.text(select.children("option:selected").text());
							if($('.selected').length>0){
								$('.selected').each(function (){
									$(this).find('td[data-target="'+target+'"]').text(select.children("option:selected").text());
								});
							}
                            td.attr('value',select.val());
                            $(".close").trigger('click');
                        });
                    });
                }
                else {
                    $('.'+field+'_update').unbind("click").click(function(){
                        select=$("#"+field);
                        $.post(config_url+"remote.php?file="+file,
                            { edit: table, field: field, id: id, value:$("#"+field).val()},function(d){
                            if(d!='')alert(d);
                            td.text(select.children("option:selected").toArray().map(item => item.text).join());
                            td.attr('value',select.val());//JSON.stringify(select.val()).replaceAll('"',"'")
                            $(".close").trigger('click');
                        });
                    });
                }
            });
			$('.popup').magnificPopup({type: 'ajax',callbacks: {
				beforeOpen: function() {
					console.log('Start of popup initialization');
				},
				elementParse: function(item) {
					// Function will fire for each target element
					// "item.el" is a target DOM element (if present)
					// "item.src" is a source that you may modify
					console.log('Parsing content. Item object that is being parsed:', item);
				},
				change: function() {
					console.log('Content changed');
					console.log(this.content); // Direct reference to your popup element
				},
				resize: function() {
					console.log('Popup resized');
				},
				open: function() {},
				beforeClose: function() {
					// Callback available since v0.9.0
					console.log('Popup close has been initiated');
				},
				close: function() {
					console.log('Popup removal initiated (after removalDelay timer finished)');
				},
				afterClose: function() {
					console.log('Popup is completely closed');
				},
				markupParse: function(template, values, item) {
					// Triggers each time when content of popup changes
					// console.log('Parsing:', template, values, item);
				},
				updateStatus: function(data) {
					console.log('Status changed', data);
					// "data" is an object that has two properties:
					// "data.status" - current status type, can be "loading", "error", "ready"
					// "data.text" - text that will be displayed (e.g. "Loading...")
					// you may modify this properties to change current status or its text dynamically
				},
				imageLoadComplete: function() {
					// fires when image in current popup finished loading
					// avaiable since v0.9.0
					console.log('Image loaded');
				},
				parseAjax: function(mfpResponse) {
					// mfpResponse.data is a "data" object from ajax "success" callback
					// for simple HTML file, it will be just String
					// You may modify it to change contents of the popup
					// For example, to show just #some-element:
					// mfpResponse.data = $(mfpResponse.data).find('#some-element');
					// mfpResponse.data must be a String or a DOM (jQuery) element
					console.log('Ajax content loaded:', mfpResponse);
				},
				ajaxContentAdded: function() {// Ajax content is loaded and appended to DOM
					$.agoy_init();
					$('#type').unbind('change').bind('change',function(){
						$('.hide').hide();
						$('.type_'+$(this).val()).show();
					});
				}
				}
			});
            $('.tamil').keyup(function(){
                $(this).val(romanished($(this).val()));
            });
			$('.next_input').keyup(function(e){
				if(e.which == 13){
					if($(this).next('input').length>0)$(this).next('input').focus();
					else if($(this).parent().next().find('input').length>0)$(this).parent().next().find('input')[0].focus();
					else if($(this).parent().parent().next().find('input').length>0)$(this).parent().parent().next().find('input')[0].focus();
				}
			});
            $('.sinhala').keyup(function(e){if(e.which == 32)$(this).val(sinhala($(this).val()));});
            $('.sinhala').focusout(function(){$(this).val(sinhala($(this).val()));});
            $('.agoy_delete').click(function(){
                tr=$(this).parent().parent();
                var table=tr.parents('table').attr('table');
                var file=tr.parents('table').attr('file');
                var id=tr.attr('id');
                Swal.fire({
                    title: "Are you sure?",text: "You won't be able to revert this!",type: "warning",showCancelButton: true,
                    confirmButtonColor: "#02a499",cancelButtonColor: "#ec4561",confirmButtonText: "Yes, delete it!"
                }).then(function (result) {
                    if (result.value) {
                        $.post(config_url+"remote.php?file="+file, { todelete: table, id: id},function(d){if(d!='')alert('ajax_delete : '+d);});
                        tr.remove();
                        Swal.fire("Deleted!", "Your file has been deleted.", "success");
                    }
                });
            });
            $('.agoy_edit').click(function(){
                tr=$(this).parent().parent();
                $.open_edit_page(tr);
            });
			
			$('.repeater').repeater({
                defaultValues: {'textarea-input': 'foo','text-input': 'bar','select-input': 'B','checkbox-input': ['A', 'B'],'radio-input': 'B'},
                show: function () {$(this).slideDown();},
                hide: function (deleteElement) {
                    if(confirm('Are you sure you want to delete this element?')) {$(this).slideUp(deleteElement);}
                },
                ready: function (setIndexes) {}
            });
			window.outerRepeater = $('.outer-repeater').repeater({
                defaultValues: { 'text-input': 'outer-default' },
                show: function (){console.log('outer show');$(this).slideDown();},
                hide: function (deleteElement) {console.log('outer delete');$(this).slideUp(deleteElement);},
                repeaters: [{
                    selector: '.inner-repeater',
                    defaultValues: { 'inner-text-input': 'inner-default' },
                    show: function () {console.log('inner show');$(this).slideDown();},
                    hide: function (deleteElement) {console.log('inner delete');$(this).slideUp(deleteElement);}
                }]
            });
            if($(".summernote").length > 0){
                $('.summernote').summernote({
                    height: 300,                 // set editor height
                    minHeight: null,             // set minimum height of editor
                    maxHeight: null,             // set maximum height of editor
                    focus: true                 // set focus to editable area after initializing summernote
                });
            }
            if($("#editor").length > 0){
                tinymce.init({
                    selector: "textarea#editor",theme: "modern",height:300,
                    plugins: [
                        "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                        "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                        "save table contextmenu directionality emoticons template paste textcolor"
                    ],
                    toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",
                    style_formats: [
                        {title: 'Bold text', inline: 'b'},
                        {title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
                        {title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
                        {title: 'Example 1', inline: 'span', classes: 'example1'},
                        {title: 'Example 2', inline: 'span', classes: 'example2'},
                        {title: 'Table styles'},
                        {title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
                    ]
                });
            }
            $("div.dropzone").each(function(index){
                form=$(this).parents('form');
                $(this).dropzone({
                    url: config_url+"ajax_image.php",
                    dictDefaultMessage: "",
                    addRemoveLinks: true,
                    params:{'path':$(this).attr('path'),'query':$(this).attr('query'),'name':$(this).attr('name')},
                    sending: function (file,item,form) {
                        // form.append("test2",23);
                    },
                    success: function (file, imgName) {
                        form.append('<input type="hidden" name="image" value="'+imgName+'"/>');
                    }
                });
            });
			$("img.dropzone").each(function(index){
                img=$(this);
                form=$(this).parents('form');
                tr=$(this).parents('tr');
                $(this).dropzone({
                    url: config_url+"ajax_image.php",
                    addRemoveLinks: true,
                    params:{'path':$(this).attr('path'),'title':$(this).attr('title'),'name':$(this).attr('name'),'id':tr.attr('id'),'query':$(this).attr('query')},
                    sending: function (file,item,form) {
                        // form.append("test2",23);
                    },
                    success: function (file, data) {//alert(imgName);
						data=jQuery.parseJSON(data);
						image=config_url+img.attr('path')+data.file;
						if(data.id>0)$('#'+data.id).find('img').attr('src',image);
						else img.attr('src',image);
                    }
                });
            });
			$('.image-popup-no-margins').magnificPopup({
				type: 'image',
				closeOnContentClick: true,
				closeBtnInside: false,
				fixedContentPos: true,
				mainClass: 'mfp-no-margins mfp-with-zoom', // class to remove default margin from left and right side
				image: {
					verticalFit: true
				},
				zoom: {
					enabled: true,
					duration: 300 // don't foget to change the duration also in CSS
				}
			});
			$('.zoom-gallery').magnificPopup({
				delegate: 'a',
				type: 'image',
				closeOnContentClick: false,
				closeBtnInside: false,
				mainClass: 'mfp-with-zoom mfp-img-mobile',
				image: {
					verticalFit: true,
					titleSrc: function(item) {
						return item.el.attr('title') + ' &middot; <a href="'+item.el.attr('href')+'" target="_blank">image source</a>';
					}
				},
				gallery: {
					enabled: true
				},
				zoom: {
					enabled: true,
					duration: 300, // don't foget to change the duration also in CSS
					opener: function(element) {
						return element.find('img');
					}
				}
			});
                // if(typeof $('.table-responsive').responsiveTable !== typeof undefined){
                    // $('.table-responsive').responsiveTable({addDisplayAllBtn: 'btn btn-secondary'});
                // }
			if($('.customized_table').length>0 && agoy_init_run==0){
				agoy_init_run=agoy_init_run+1;
				var table_count=0;
				$('.customized_table').each(function(){
					this_table=$(this);
					var cols=['copy', 'excel', 'pdf', 'colvis'];
					if(typeof this_table.data('buts')!== typeof undefined && this_table.data('buts')!='')cols=this_table.data('buts').split(",");
					var table = $(this).DataTable({colReorder: true,buttons: [cols,{
							extend: 'print',autoPrint: true,exportOptions: {columns: ':visible'},
							customize: function ( win ) {
								var time=new Date().toLocaleString("sv-SE");
								$(win.document.body).css( 'font-size', '10pt' )
									.prepend(
										'Printed By : '+user_name+'<br/>Time : '+time+'<img src="'+logo+'" style="position:absolute; top:0; left:0;z-index: -1;opacity: 0.1;" />'
									);
								$(win.document.body).find( 'table' ).addClass( 'compact' ).css( 'font-size', 'inherit' );
							}
						}],
						"footerCallback": function ( row, data, start, end, display ) {
							var api = this.api(), data;
							// Remove the formatting to get integer data for summation
							var intVal = function ( i ) {
								return typeof i === 'string' ? i.replace(/[\$,]/g, '')*1 : typeof i === 'number' ? i : 0;
							};
							var sum=this_table.data('sum');
							if(typeof sum!== typeof undefined && sum!=''){
								if(Number.isInteger(sum)){
									pageTotal = api.column( sum, { page: 'current'} ).data().reduce( function (a, b) {return intVal(a) + intVal(b);}, 0 );
									$( api.column( sum ).footer() ).html($.numberWithCommas(pageTotal.toFixed(2)));
								}
								else {
									columns=this_table.data('sum').split(",");
									$.each(columns,function(i,index){
										// index=this_table.find('thead th:contains("'+v+'")').index();
										// total = api.column( index ).data().reduce( function (a, b) {return intVal(a) + intVal(b);}, 0 );
										pageTotal = api.column( index, { page: 'current'} ).data().reduce( function (a, b) {return intVal(a) + intVal(b);}, 0 );
										$( api.column( index ).footer() ).html($.numberWithCommas(pageTotal.toFixed(2)));
									});
								}
							}
						}
					});
					if(typeof this_table.data('hideColumns')!== typeof undefined && this_table.data('hideColumns')!=''){
						columns=this_table.data('hideColumns').split(",");
						i=0;
						const htm = [];
						$.each(columns,function(i,v){
							index=this_table.find('thead th:contains("'+v+'")').index()+i;
							htm[i]='<button type="button" class="btn btn-outline-primary waves-effect waves-light toggle-column" data-column="'+index+'">'+v+'</button>';
							table.column(index).visible(false);
							i++;
						});
						this_table.parents('.card-body').prepend('<div>Toggle column: '+htm.join(" ")+'</div>');
					}
					$('.toggle-column').on( 'click', function (e) {
						$(this).toggleClass('btn-primary btn-outline-primary');
						e.preventDefault();
						var column = table.column( $(this).attr('data-column') );
						column.visible( ! column.visible() );
					});
					// con=table.buttons().container();
					if(typeof this_table.data('button')!== typeof undefined && this_table.data('button')==true){
						table.buttons().container().appendTo('#DataTables_Table_'+table_count+'_wrapper .col-sm-12:eq(0)');//.dataTables_wrapper
					}
					table_count++;
				});
			}
            // $('.table-responsive').responsiveTable({});
        };
		$.ajax_indicate = function(msg){
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
		};
	
		$('.branch_select').click(function(){
			$('#dropdownMenuLink').text($(this).text());
			$('.branch_select').show();
			$(this).hide();
			$.post(config_url+"remote.php?file=branch",{'id':$(this).attr('id')},function(data){
				window.location.href=config_url+data+'/'+location.hash;
				// location.reload();
			});
		});
        jQuery.browser.msie = false;
        jQuery.browser.version = 0;
        if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
            jQuery.browser.msie = true;
            jQuery.browser.version = RegExp.$1;
        }
    })( jQuery );
//});
</script>
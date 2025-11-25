/**
 * Control the interactions for making an automated feed wizard.
 * 
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense
 * @since   1.6.16-fork
 */

/**
 * To be added to jQuery $.fn.get_adv_xpath, makes various unknown things.
 */
function extend_adv_xpath() {
	$('#adv')
	.prepend(
		'<p style="text-align: left;">' + 
			'<input style="vertical-align: middle; margin: 2px;" class="xpath" ' + 
			'type="radio" name="xpath" value=\'\'>' + 
				'custom: ' + 
				'<input type="text" id="custom_xpath" name="custom_xpath" ' + 
				'style="width:70%" ' + 
				'onkeyup="try{val=$(\'#custom_xpath\').val();valid=$(document).xpath(val);}catch(err){val=\'\';valid=0;}if(valid==0){$(this).parent().find(\'.xpath\').val(\'\');if($(this).parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', true);$(\'#custom_img\').attr(\'src\',\'icn/exclamation-red.png\');}else {$(this).parent().find(\'.xpath\').val(val);if($(this).parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', false);$(\'#custom_img\').attr(\'src\',\'icn/tick.png\');}return false;" onpaste="setTimeout(function() {try{val=$(\'#custom_xpath\').val();valid=$(document).xpath(val);}catch(err){val=\'\';valid=0;}if(valid==0){$(this).parent().find(\'.xpath\').val(\'\');if($(\'#custom_xpath\').parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', true);$(\'#custom_img\').attr(\'src\',\'icn/exclamation-red.png\');}else {$(\'#custom_xpath\').parent().find(\'.xpath\').val(val);if($(\'#custom_xpath\').parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', false);$(\'#custom_img\').attr(\'src\',\'icn/tick.png\');}}, 0);" value=\'\'>' + 
				'</input>' + 
			'<img id="custom_img" src="icn/exclamation-red.png" alt="-" />' + 
			'</input>' + 
		'</p>'
	);
	$('#adv').show();
	$('*').removeClass("lwt_marked_text");
	$('*[class=\'\']').removeAttr( 'class' );
	var val1=$($('#mark_action :selected').data()).get( 0 ).tagName.toLowerCase(),
	 attr='',
	 node_count=0,
	attr_v='',
	attr_p='',
	val_p='';
	for (var i=0, attrs=this[0].attributes, l=attrs.length; i<l; i++){
		if(attrs.item(i).nodeName=='id'){
			var id_cont=attrs.item(i).nodeValue.split(' ');
			for (var z=0; z<id_cont.length; z++){
				var val='//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + id_cont[z] + ' ")]]';
				$('#adv')
				.prepend(
					'<p style="text-align: left;">' + 
						'<input style="vertical-align: middle; margin: 2px;" ' + 
						'class="xpath" type="radio" name="xpath" value=\''+val+'\'>' + 
							'contains id: «'+ id_cont[z] +'»' +
						'</input>' + 
					'</p>'
				);
			}
		}
		if(attrs.item(i).nodeName=='class'){
			var cl_cont=attrs.item(i).nodeValue.split(' ');
			for (var z=0; z<cl_cont.length; z++){
				val = '//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + 
				cl_cont[z] + ' ")]]';
				$('#adv')
				.prepend(
					'<p style="text-align: left;">' + 
						'<input style="vertical-align: middle; margin: 2px;" ' + 
						'class="xpath" type="radio" name="xpath" value=\''+val+'\'>'+
							'contains class: «'+ cl_cont[z] +'»' + 
						'</input>' +
					'</p>'
				);
			}
		}
					if(i>0)attr_v += ' and ';
					if(i==0)attr_v += '[';
					attr_v +='@' + attrs.item(i).nodeName;
					attr_v += '="' + attrs.item(i).nodeValue + '"';
					if(i==(attrs.length-1))attr_v += ']';
	}
	this.parents().each(function(){
		var pa=$(this).get(0);
					for(var i=0, attrs=pa.attributes, l=attrs.length; i<l; i++){
				if(node_count==0){
		if(attrs.item(i).nodeName=='id'){
			id_cont=attrs.item(i).nodeValue.split(' ');
			for (var z=0; z<id_cont.length; z++){
				val='//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + 
				id_cont[z] + ' ")]]';
				$('#adv')
				.prepend(
					'<p style="text-align: left;">' + 
						'<input style="vertical-align: middle; margin: 2px;" ' + 
						'class="xpath" type="radio" name="xpath" value=\''+val+ '/'+ 
						val1+'\'>' + 
							'parent contains id: «'+ id_cont[z] +'»' + 
						'</input>' + 
					'</p>'
				);
			}
		}
		if(attrs.item(i).nodeName=='class'){
			cl_cont=attrs.item(i).nodeValue.split(' ');
			for (var z=0; z<cl_cont.length; z++){
				if(cl_cont[z]!='lwt_filtered_text'){
					val='//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + cl_cont[z] + ' ")]]';
					$('#adv').prepend('<p style="text-align: left;"><input style="vertical-align: middle; margin: 2px;" class="xpath" type="radio" name="xpath" value=\''+val+ '/'+ val1+'\'>parent contains class: «'+ cl_cont[z] +'»</input></p>');
				}
			}
		}
		}
						if(attrs.length > 1 || attrs.item(i).nodeValue!='lwt_filtered_text'){
						if(i>0 && attrs.item(i).nodeValue!='lwt_filtered_text')attr_p += ' and ';
						if(i==0)attr_p += '[';
						if(attrs.item(i).nodeValue!='lwt_filtered_text')attr_p +='@' + attrs.item(i).nodeName;
						if(attrs.item(i).nodeValue!='lwt_filtered_text')attr_p += '="' + attrs.item(i).nodeValue.replace('lwt_filtered_text','').trim() + '"';
						if(i==(attrs.length-1))attr_p += ']';
						}
					}
		val_p= pa.tagName.toLowerCase() + attr_p+ '/'+val_p ;attr_p='';
		pa='';
		node_count++;
	});
	$('#adv').prepend('<p style="text-align: left;"><input style="vertical-align: middle; margin: 2px;" class="xpath" type="radio" name="xpath" value=\'/'+val_p+val1 + attr_v+'\'>all: « /'+ val_p.replace('=""','') +val1 + attr_v.replace('=""','') +' »</input></p>');
	$( '#adv input[type="radio"]' ).each(function(z){
		if(typeof z == 'undefined')z=1;
		if(typeof $(this).attr('id') == 'undefined'){
			$(this).attr('id','rb_'+z++);
		}
		$(this).after('<label class="wrap_radio" for="'+$(this).attr('id')+'"><span></span></label>');
	});
}

const lwt_feed_wiz_opt_inter = {
	clickHeader: function (event) {
		if(!($(event.target).hasClass( "lwt_selected_text" ))){
			if(!($(event.target).hasClass( "lwt_filtered_text" ))){
			if($(event.target).hasClass( "lwt_marked_text" )){
				$("#mark_action").empty();
				$('*').removeClass("lwt_marked_text");
				$('*[class=\'\']').removeAttr( 'class' );
				$('button[name="button"]').prop('disabled', true);
				$('<option/>').val('').text('[Click On Text]')
				.appendTo('#mark_action');
				return false;
			}
			else{
				$('*').removeClass("lwt_marked_text");
				$("#mark_action").empty();
				var filter_array = [];
				$(event.target).parents(':not(html,body)').addBack()
				.each(function() {
					if(!($(this).hasClass( "lwt_filtered_text" ))){
						filter_array = [];
						$(this).parents('.lwt_filtered_text').each(function(){
							$(this).removeClass('lwt_filtered_text');
							filter_array.push(this);
						});
						$('*[class=\'\']').removeAttr( 'class' );
						var el=this;
					if($(this).attr('style')==='')$(this).removeAttr( "style" );
					val1=$(this).get( 0 ).tagName.toLowerCase();
					var attr='',
					attr_v='',
					attr_p='',
					attr_mode='',
					val_p='';
					if($('select[name="select_mode"]').val()!='0'){
						attr_mode=5;
					}
					else if($(this).attr('id'))attr_mode=1;
					else if($(this).parent().attr('id'))attr_mode=2;
					else if($(this).attr('class'))attr_mode=3;
					else if($(this).parent().attr('class'))attr_mode=4;
					else attr_mode=5;
					for (var i=0, attrs=el.attributes, l=attrs.length; i<l; i++){
					if(attr_mode==5 || (attrs.item(i).nodeName=='class' && attr_mode!=1) || (attrs.item(i).nodeName=='id')){
						attr += attrs.item(i).nodeName;
						attr += '="' + attrs.item(i).nodeValue + '" ';
						if(i>0)attr_v += ' and ';
						attr_v +='@' + attrs.item(i).nodeName;
						attr_v += '="' + attrs.item(i).nodeValue + '"';
					}
					}
					attr=attr.replace('=""','').trim();
					if(attr_v)attr_v='['+attr_v+']';
					if(attr_mode!=1 && attr_mode!=3){
						for(var i=0, attrs=$(this).parent().get(0).attributes, l=attrs.length; i<l; i++){
							if(attr_mode==5 || (attrs.item(i).nodeName=='class' && attr_mode!=2) || (attrs.item(i).nodeName=='id')){
								if(i>0)attr_p += ' and ';
								attr_p +='@' + attrs.item(i).nodeName;
								attr_p += '="' + attrs.item(i).nodeValue + '"';
							}
						}
						if(attr_p)attr_p='['+attr_p+']';
						val_p=$(this).parent().get( 0 ).tagName.toLowerCase()+attr_p + '§';
					}val_p=val_p.replace('body§', '');
					var attrsplit=attr.substr(0,20);
					if(!(attrsplit==attr))attrsplit = attrsplit + '... ';
					if(!(attrsplit==''))attrsplit = " " + attrsplit;
					if(event.target==this)
						$("<option/>").val(
							'//'+ val_p.replace('=""','')
							.replace('[ and @','[@') +val1 + attr_v.replace('=""','')
							.replace('[ and @','[@')
						).text(
							"<" + val1.replace('[ and @','[@') + 
							attrsplit.replace('[ and @','[@') + ">"
						).data(el)
						.attr("selected", true).prependTo("#mark_action");
					else 
						$("<option/>").val(
							'//'+ val_p.replace('=""','')
							.replace('[ and @','[@') +val1 + 
							attr_v.replace('=""','').replace('[ and @','[@')
						).text(
							"<" + val1.replace('[ and @','[@') + 
							attrsplit.replace('[ and @','[@') + ">"
						).data(el).prependTo("#mark_action");
					for (var i in filter_array) {
						$(filter_array[i]).addClass('lwt_filtered_text');
					}
					}
				});
				$('button[name="button"]').prop('disabled', false);
				var attr=$('#mark_action').val();
				attr=attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][').replace('§', '>');
				filter_array = [];
				$(this).parents('.lwt_filtered_text').each(function(){
					$(this).removeClass('lwt_filtered_text');
					filter_array.push(this);
				});				
				$(attr+':not(.lwt_selected_text)').find('*:not(.lwt_selected_text)')
				.addBack().addClass("lwt_marked_text");
				for (var i in filter_array) {
					$(filter_array[i]).addClass('lwt_filtered_text');
				}
			return false;
			}
		} else{
			event.preventDefault();
		}
		} else{
						var selected_Array = [];
						var filter_array = [];
						$('.lwt_selected_text').each(function(){
							selected_Array.push(this);
						});
			$(event.target).parents('*').addBack().each(function() {
				if(!($(this).parent().hasClass( "lwt_selected_text" )) && $(this).hasClass( "lwt_selected_text" )){
					if($(this).hasClass('lwt_highlighted_text')){
						$('*').removeClass('lwt_highlighted_text');
					}
				else{
						el=this;
							$('*').removeClass('lwt_selected_text');
							filter_array = [];
							$(this).parents('.lwt_filtered_text').each(function(){
								$(this).removeClass('lwt_filtered_text');
								filter_array.push(this);
							});
						$('*[class=\'\']').removeAttr( 'class' );
						$('#lwt_sel li').each(function(){
							$('*').removeClass('lwt_highlighted_text');
							$(this).addClass('lwt_highlighted_text');
							$(document).xpath($(this).text()).addClass('lwt_highlighted_text');
							if($(el).hasClass('lwt_highlighted_text')){
								return false;
							}
						});
						for (var i in selected_Array) {
							$(selected_Array[i]).addClass('lwt_selected_text');
						}
					}
				}
			});
						for (var i in filter_array) {
							$(filter_array[i]).addClass('lwt_filtered_text');
						}					
			$('button[name="button"]').prop('disabled', true);
			$("#mark_action").empty();
			$('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
			return false;
		}
	},

	highlightSelection: function() {
		let sel_array="";
		$('#lwt_sel li').each(function(){
			if($(this).hasClass('lwt_highlighted_text')){
				$(document).xpath($(this).text())
				.not($('#lwt_header').find('*').addBack())
				.addClass('lwt_highlighted_text').find('*').addBack()
				.addClass('lwt_selected_text');
			}
			else 
				sel_array+=$(this).text() + " | ";
		});
		if(sel_array!="")
			$(document).xpath(sel_array.replace(/ \| $/, '')).find('*')
			.addBack().not($('#lwt_header').find('*').addBack())
			.addClass('lwt_selected_text');
		return sel_array;
	}
};

const lwt_feed_wizard = {
	prepareInteractions: function () {
		if (
			$('#lwt_sel').html()=='' && 
			parseInt($('input[name=\'step\']').val(), 10) == 2
		)
			$('#next').prop('disabled', true);
		else 
			$('#next').prop('disabled', false);
		$('#lwt_last').css('margin-top',$('#lwt_header').height());
		$('#lwt_header').nextAll().on('click', lwt_feed_wiz_opt_inter.clickHeader);
		$('*').removeClass('lwt_filtered_text');
		$('*[class=\'\']').removeAttr( 'class' );
		lwt_feed_wiz_opt_inter.highlightSelection();
		for (var i in filter_Array) {
			$(filter_Array[i]).addClass('lwt_filtered_text');
		}
		$('*[style=\'\']').removeAttr( 'style' );
		$( "#lwt_header select" ).wrap( "<label class='wrap_select'></label>" );
		$(document).mouseup(function(){
			$([
				"select:not(:active)", "button", "input[type=button]", 
				".wrap_radio span", ".wrap_checkbox span"
			].join()).trigger('blur');
		});
	},

	deleteSelection: function () {
		$('*').removeClass('lwt_selected_text').removeClass('lwt_marked_text');
		$('*').removeClass('lwt_filtered_text');
		$('#lwt_header').nextAll().find('*').addBack().removeClass('lwt_highlighted_text');
		$(this).parent().remove();
		var sel_array="";
		$('#lwt_sel li').each(function(){
			if($(this).hasClass('lwt_highlighted_text')){
				$(document).xpath($(this).text()).not($('#lwt_header').find('*')
				.addBack()).addClass('lwt_highlighted_text').find('*').addBack()
				.addClass('lwt_selected_text');
			}
			else sel_array+=$(this).text() + " | ";
		});
		if(sel_array!="")
			$(document).xpath(sel_array.replace(/ \| $/, '')).find('*')
			.addBack().not($('#lwt_header').find('*').addBack())
			.addClass('lwt_selected_text');
		for (var i in filter_Array) {
				$(filter_Array[i]).addClass('lwt_filtered_text');
		}
		$('*[class=\'\']').removeAttr( 'class' );
		$('*[style=\'\']').removeAttr( 'style' );
		$('#lwt_last').css('margin-top',$('#lwt_header').height());
		if (
			$('#lwt_sel').html()=='' && 
			parseInt($('input[name=\'step\']').val(), 10)==2
		)
			$('#next').prop('disabled', true);
		return false;
	},

	changeXPath: function() {
		$('#adv_get_button').prop('disabled', false);
		$(this).parent().find('img').each(function(){
			if($(this).attr('src')=='icn/exclamation-red.png')
				$('#adv_get_button').prop('disabled', true);
		});
		return false;
	},

	clickAdvGetButton: function(){
		$('*').removeClass('lwt_filtered_text');
		$('*[class=\'\']').removeAttr( 'class' );
		if(typeof $('#adv :radio:checked').val()!='undefined'){
			$( '#lwt_sel' ).append(
				'<li style=\'text-align: left\'>' + 
				'<img class=\'delete_selection\' src=\'icn/cross.png\' ' + 
				'title=\'Delete Selection\' alt=\'\' /> '+ 
				$('#adv :radio:checked').val() + 
				'</li>'
			);
			$(document).xpath($('#adv :radio:checked').val()).find('*')
			.addBack().not($('#lwt_header').find('*').addBack())
			.addClass('lwt_selected_text');
			$('#next').prop('disabled', false);
		}
		$('#adv').hide();
		$('#lwt_last').css('margin-top',$('#lwt_header').height());
		for (var i in filter_Array) {
			 $(filter_Array[i]).addClass('lwt_filtered_text');
		}
		return false;
	},

	clickSelectLi: function(){
		if($(this).hasClass('lwt_highlighted_text')){
			$('*').removeClass('lwt_highlighted_text');
		}
		else{
			var selected_Array = [];
			$('.lwt_selected_text').each(function(){
				$(this).removeClass('lwt_selected_text');
				selected_Array.push(this);
			});
			$('*').removeClass('lwt_filtered_text');
			$('*').removeClass('lwt_highlighted_text');
			$('*[class=\'\']').removeAttr( 'class' );
			$(this).addClass('lwt_highlighted_text');
			
			$(document).xpath($(this).text()).not($('#lwt_header').find('*').addBack())
			.addClass('lwt_highlighted_text').find('*').addBack()
			.addClass('lwt_selected_text');
	
			for (var i in filter_Array) {
				 $(filter_Array[i]).addClass('lwt_filtered_text');
			}
			for (var i in selected_Array) {
				 $(selected_Array[i]).addClass('lwt_selected_text');
			}
	
		}
		return false;
	},

	changeMarkAction: function(){
		$('*').removeClass('lwt_marked_text');
		$('*[class=\'\']').removeAttr( 'class' );
		attr=$('#mark_action').val();
		attr=attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][')
		.replace('§', '>');
			$('*').removeClass('lwt_filtered_text');
		$(attr).find('*:not(.lwt_selected_text)').addBack().addClass('lwt_marked_text');
		for (var i in filter_Array) {
			 $(filter_Array[i]).addClass('lwt_filtered_text');
		}
		return false;
	},

	clickGetOrFilter: function(){
		$('*').removeClass('lwt_marked_text');
		if($('select[name=\'select_mode\']').val()=='adv'){
			$('#adv p').remove();
			$('*[style=\'\']').removeAttr( 'style' );
			$('#adv_get_button').prop('disabled', true);
			$($('#mark_action :selected').data()).get_adv_xpath();
		}
		else{
			$('#next').prop('disabled', false);
			attr=$('#mark_action').val();
			attr=attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][')
			.replace('§', '>');
			var filter_Array = [];
			$('.lwt_filtered_text').each(function(){
				$(this).removeClass('lwt_filtered_text');
				filter_Array.push(this);
			});
			$('*').removeClass('lwt_filtered_text');
			$(attr).find('*').addBack().addClass('lwt_selected_text');
			for (var i in filter_Array) {
				 $(filter_Array[i]).addClass('lwt_filtered_text');
			}
			$('#lwt_sel').append(
				'<li style=\'text-align: left\'>' + 
				'<img class=\'delete_selection\' src=\'icn/cross.png\' ' + 
				'title=\'Delete Selection\' alt=\''+
				$('#mark_action').val()+'\' /> '+ 
				$('#mark_action').val().replace('§', '/') + 
				'</li>'
			);
		}
		$(this).prop('disabled', true);
		$('#mark_action').empty();
		$('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
		$('#lwt_last').css('margin-top',$('#lwt_header').height());
		return false;
	},

	clickNextButton: function() {
		$('#article_tags,#filter_tags').val($('#lwt_sel').html())
		.prop('disabled', false);
		const html = $('#lwt_sel li').map(function(){
			return $(this).text();
		}).get().join(' | ');
		$('input[name=\'html\']').val(html);
		let val = parseInt($('input[name=\'step\']').val(), 10);
		if (val == 2){
			$('input[name=\'html\']').attr('name','article_selector')
			$('select[name=\'NfArticleSection\'] option').each(function(){
				art_sec=$('#lwt_sel li').map(function(){
					return $(this).text();
				}).get().join(' | ');
				$(this).val(art_sec);
			});
		}
		$('input[name=\'step\']').val(++val);
		document.lwt_form1.submit();
		return false;
	},

	changeHostStatus: function(){
		let host_status = $(this).val();
		let current_host = $('input[name=\'host_name\']').val();
		$('select[name=\'selected_feed\'] option').each(function(){
			let opt_str=$(this).text();
			let host_name=opt_str.replace(/[▸\-][0-9\s]*[★☆\-][\s]*host:/, '');
			if(host_name.trim()==current_host.trim()){
				$(this).text(
					opt_str.replace(
						/([▸\-][0-9\s]*?)\s[★☆\-]\s(.*)/, 
						'$1 '+host_status.trim()+' $2'
					)
				)
			}
		});
		return false;
	}
}

/**
 * A mess of different things for preparing interactions with feed wizard.
 * 
 * @deprecated Since 2.9.1, use lwt_feed_wizard.prepareInteractions
 */
function feedwizard_prepare_interaction() {
	return lwt_feed_wizard.prepareInteractions();
}


$(document).on('click','.delete_selection', lwt_feed_wizard.deleteSelection);

$(document).on('change','.xpath', lwt_feed_wizard.changeXPath);

$(document).on('click','#adv_get_button', lwt_feed_wizard.clickAdvGetButton);

$(document).on('click','#lwt_sel li', lwt_feed_wizard.clickSelectLi);

$(document).on('change','#mark_action', lwt_feed_wizard.changeMarkAction);

$(document).on('click','#get_button,#filter_button', lwt_feed_wizard.clickGetOrFilter);

$(document).on('click','#next', lwt_feed_wizard.clickNextButton);

$(document).on('change','#host_status', lwt_feed_wizard.changeHostStatus);

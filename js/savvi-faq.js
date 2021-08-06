jQuery(function(){
	var $ = jQuery;
	
	$('.savvi-answer').slideUp(0);
	
// 	$('[data-savvi-faq-uid]').each(function(count){
// 	});
	
	$('.savvi-click-area').on('click', function(e){
		var $faq = $(this).closest('.savvi-faq');
		var order = parseInt($faq.attr('data-savvi-faq-order'));
		var faq_id = parseInt($faq.attr('data-savvi-faq-id'));
		var faq_label = $faq.attr('data-savvi-faq-label');
		var event_id = $(this).closest('[data-savvi-faq-event-id]').attr('data-savvi-faq-event-id');
		
		if(event_id == ''){
			return;
		}
		
		var interaction = '';
		
		if($(this).hasClass('savvi-collapsed')){
			$faq.find('.savvi-answer').slideDown(350);
			$(this).removeClass('savvi-collapsed');
			$(this).attr('aria-expanded', 'true');
			interaction = 'open';
		}else{
			$faq.find('.savvi-answer').slideUp(350);
			$(this).addClass('savvi-collapsed');
			$(this).attr('aria-expanded', 'false');
			interaction = 'close';
		}
		
		var params = {
			faq_id: faq_id,
			faq_label: faq_label,
			interaction: interaction,
			order: order,
			event_id: event_id,
		}
		
		if(interaction == 'open'){
			register(params);
		}
	});

	var hash = window.location.hash.substr(1);
	var $faq = $('#savvi-faq-'+hash);
	if($faq.length == 1){
		$faq[0].scrollIntoView({block:'center'});
		$faq.find('.savvi-click-area').trigger('click');
		window.location.hash = '#!';
	}

	function register(params){
		var data = params;
		data.action = 'savvi_faq_register_interaction';
		
		$.ajax({
			type: 'POST',
			url: '/wp-admin/admin-ajax.php',
			data: data,
			cache: false,
			success: function(result){
				var result = JSON.parse(result);
				trace(result.response);
				
				//update the event id with the returned value:
				//$('[data-savvi-faq-event-id]').attr('data-savvi-faq-event-id', result.stemeventid);
			},
			error: null,
			dataType: 'text'
		});
	}
	
	function trace(m){console.log(m);}
});

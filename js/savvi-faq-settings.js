jQuery(function(){
	var $ = jQuery;

	tinymce.init({
		selector: '#savvi-faq-textarea',
		toolbar: 'undo redo | bold italic | link',
		plugins: 'link',
		menubar: false,
		branding: false,
		statusbar: false
	});

	$('[data-savvi-faq-action="new-faq-modal"]').on('click', function(e){
		resetModal();

		$('.savvi-faq-modal h2').text('Create FAQ');
		$('.savvi-faq-modal [data-savvi-faq-action="add-faq"]').val('Add FAQ');

		$('#form-lKGCM5p5w3Jn .savvi-faq-overlay').addClass('show');
		return false;
	});

	$('#form-lKGCM5p5w3Jn').on('click', '[data-savvi-faq-action="edit-faq-modal"]', function(e){
		resetModal();

		var faq_id = parseInt($(this).closest('[data-savvi-faq-id]').attr('data-savvi-faq-id'));
		var faq = savvi_faqs.find(obj => {return obj.faq_id === faq_id});

		$('.savvi-faq-modal h2').text('Edit FAQ');
		$('.savvi-faq-modal [data-savvi-faq-action="add-faq"]').val('Update FAQ');
		
		$('#form-lKGCM5p5w3Jn [name="faq_id"]').val(faq_id);
		$('#form-lKGCM5p5w3Jn [name="question"]').val(faq.question);
		tinymce.get('savvi-faq-textarea').setContent(faq.answer);
		
		$('#form-lKGCM5p5w3Jn .savvi-faq-overlay').addClass('show');
		return false;
	});

	//DELETE FAQ:
	$('#form-lKGCM5p5w3Jn').on('click', '[data-savvi-faq-action="delete-faq"]', function(e){
		var faq_id = parseInt($(this).closest('[data-savvi-faq-id]').attr('data-savvi-faq-id'));
		var faq = savvi_faqs.find(obj => {return obj.faq_id === faq_id});
		if(!confirm('Are you sure you want to delete the FAQ "'+faq.question+'"?')){
			return false;
		}

		var data = {action: 'savvi_faq_delete', faq_id: faq_id}
		
		$('#form-lKGCM5p5w3Jn .savvi-faq-loading').addClass('show');
		
		$.ajax({
			type: 'POST',
			url: '/wp-admin/admin-ajax.php',
			data: data,
			cache: false,
			success: function(result){
				var json = JSON.parse(result);
				savvi_faqs = json.faqs;
				writeFAQs();
				//$('#form-lKGCM5p5w3Jn .savvi-faq-loading').removeClass('show');
			},
			error: null,
			dataType: 'text'
		});

		return false;
	});
	
	$('#form-lKGCM5p5w3Jn .savvi-faq-close').on('click', function(e){
		$('#form-lKGCM5p5w3Jn .savvi-faq-overlay').removeClass('show');
		return false;
	});
	
	$('#form-lKGCM5p5w3Jn [data-savvi-faq-action="toggle-view"]').on('click', function(e){
		if(tinymce.get('savvi-faq-textarea').isHidden()){
			tinymce.get('savvi-faq-textarea').show();
		}else{
			tinymce.get('savvi-faq-textarea').hide();
		}
		return false;
	});
	
	$('#savvi-options-info').on('click', '.notice-dismiss', function(e){
		$(this).closest('.notice').fadeOut(125, function(){
			$(this).remove();
		});
		return false;
	});

	//SAVE FAQ:
	$('[data-savvi-faq-action="add-faq"]').on('click', function(e){
		//show editor so raw edits are captured:
		if(tinymce.get('savvi-faq-textarea').isHidden()){
			tinymce.get('savvi-faq-textarea').show();
		}

		var data = {
			action: 'savvi_faq_save',
			faq_id: $('#form-lKGCM5p5w3Jn [name="faq_id"]').val(),
			question: $('#form-lKGCM5p5w3Jn [name="question"]').val(),
			answer: tinymce.get('savvi-faq-textarea').getContent(),
		}

		$('#form-lKGCM5p5w3Jn .savvi-faq-loading').addClass('show');
		
		$.ajax({
			type: 'POST',
			url: '/wp-admin/admin-ajax.php',
			data: data,
			cache: false,
			success: function(result){
				var json = JSON.parse(result);
				if(json.result == 0){
					alert(json.message);
					$('#form-lKGCM5p5w3Jn .savvi-faq-loading').removeClass('show');
				}else{
					savvi_faqs = json.faqs;
					writeFAQs();
				}
			},
			error: null,
			dataType: 'text'
		});

		$('.savvi-faq-modal h2').text('Create FAQ');
		$('.savvi-faq-modal [data-savvi-faq-action="add-faq"]').val('Add FAQ');
		$('#form-lKGCM5p5w3Jn .savvi-faq-overlay').addClass('show');
		return false;
	});

	//SAVE OPTIONS:
	$('[data-savvi-faq-action="save"]').on('click', function(e){
		var data = {
			action: 'savvi_faq_options_save',
			//faq_collapsible: $('#form-lKGCM5p5w3Jn input[name="savvi-faq-collapsible"]').is(':checked') ? 1 : 0,
			container_url: $('#form-lKGCM5p5w3Jn input[name="savvi-faq-container-url"]').val(),
		}

		$('#form-lKGCM5p5w3Jn .savvi-faq-loading').addClass('show');
		
		$.ajax({
			type: 'POST',
			url: '/wp-admin/admin-ajax.php',
			data: data,
			cache: false,
			success: function(result){
				//var json = JSON.parse(result);
				$('#savvi-options-info').append('<div class="notice notice-info is-dismissible"><p>The options were successfully saved.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')

				$('#form-lKGCM5p5w3Jn .savvi-faq-loading').removeClass('show');
			},
			error: null,
			dataType: 'text'
		});


		return false;
	});
	
	function writeFAQs(){
		window.location.reload(true);
	}
	
	function resetModal(){
		tinymce.get('savvi-faq-textarea').show();

		$('#form-lKGCM5p5w3Jn [name="faq_id"]').val(0);
		$('#form-lKGCM5p5w3Jn [name="view"]').val('edit');
		$('#form-lKGCM5p5w3Jn [name="question"]').val('');
		tinymce.get('savvi-faq-textarea').setContent('');
	}
	
	var uniqueID = (function(){var id = 0; return function(){return id++;};})();

	function trace(m){console.log(m);}
});

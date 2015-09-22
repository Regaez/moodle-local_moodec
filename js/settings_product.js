$(document).on('ready', function(){

	// Attach event handler to tier selection element
	$('#id_product_type').on('change', function(){

		var fieldCount = parseInt($('#id_format').val(), 10);

		console.log(fieldCount);

		for (var i = fieldCount; i > 1; i--) {
			
			if( $('#id_product_type').val() === 'PRODUCT_TYPE_SIMPLE' ) {
				console.log('hide');
				$('#id_product_variation_header_'+i).hide();
			} else {
				console.log('show');
				$('#id_product_variation_header_'+i).show();
			}

		}

	});
});
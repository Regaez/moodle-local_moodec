$(document).on('ready', function(){

	// Auto submit filter forms when selection changes 
	$('.filter-bar select').on('change', function(){
		$('.filter-bar').submit();
	});

	// Update variable product information
	$('.product-form .product-tier').on('change', function(){
		var id = $(this).val();
		var parent = $(this).parents('.product-item');

		// Update price
		var newPrice = $('.product-price', parent).attr('data-tier-' + id);
		$('.product-price .amount', parent).text(newPrice);

		// Update course duration
		var newDuration = $('.product-duration', parent).attr('data-tier-' + id);
		$('.product-duration', parent).text(newDuration);
	});

});
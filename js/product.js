$(document).on('ready', function(){

	// Attach event handler to tier selection element
	$('.product-tier').on('change', function(){
		var id = $(this).val();

		// Update price
		var newPrice = $('.product-price').attr('data-tier-' + id);
		$('.product-price .amount').text(newPrice);

		// Update course duration
		var newDuration = $('.product-duration').attr('data-tier-' + id);
		$('.product-duration').text(newDuration);
	});
});
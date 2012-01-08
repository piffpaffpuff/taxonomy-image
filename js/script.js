jQuery(document).ready(function($) {

	// remove taxonomy image button
	removeTaxonomyImage = function() {
		jQuery('#taxonomy_image_id').val('');		
		jQuery("#taxonomy_image_placeholder").empty();
		jQuery("#taxonomy_image_add_button").show();
		jQuery("#taxonomy_image_remove_button").hide();
	}
		
	// this javascript is called from the button inside the media manager
	// remove the media uploader and set the image
	window.sendTaxonomyImageToTerm = function(id, image) {
		tb_remove();
		
		jQuery('#taxonomy_image_id').val(id);		
		jQuery("#taxonomy_image_placeholder").empty();
		
		// build the image
		var img = new Image();
			
		// loading of image has completed
		jQuery(img).load(function() {
			jQuery(this).show();
			jQuery("#taxonomy_image_placeholder").append(this);
			jQuery("#taxonomy_image_add_button").hide();
			jQuery("#taxonomy_image_remove_button").show();
		}).error(function() {
			jQuery(this).hide();
			removeTaxonomyImage();
		}).attr("src", image);
	}
	
	// remove taxonomy image button
	jQuery("#taxonomy_image_remove_button").click(function() {
		removeTaxonomyImage();
		return false;
	});
	
	// remove the image on the quick edit form once the tax is saved
	if(jQuery("#taxonomy_image_add_button").length > 0) {
		jQuery('#submit').ajaxComplete(function(event, request, settings) {
			removeTaxonomyImage();
		});
	}

});


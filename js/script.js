jQuery(document).ready(function($) {
	
	
	// remove taxonomy image button
	Insofern_removeTaxonomyImage = function() {
		jQuery('#insofern_taxonomy_image_id').val('');		
		jQuery("#insofern_taxonomy_image_placeholder").empty();
		jQuery("#insofern_taxonomy_image_add_button").show();
		jQuery("#insofern_taxonomy_image_remove_button").hide();
	}
		
	// this javascript is called from the button inside the media manager
	// remove the media uploader and set the image
	window.Insofern_sendTaxonomyImageToTerm = function(id, image) {
		tb_remove();
		
		jQuery('#insofern_taxonomy_image_id').val(id);		
		jQuery("#insofern_taxonomy_image_placeholder").empty();
		
		// build the image
		var img = new Image();
			
		// loading of image has completed
		jQuery(img).load(function() {
			jQuery(this).show();
			jQuery("#insofern_taxonomy_image_placeholder").append(this);
			jQuery("#insofern_taxonomy_image_add_button").hide();
			jQuery("#insofern_taxonomy_image_remove_button").show();
		}).error(function() {
			jQuery(this).hide();
			Insofern_removeTaxonomyImage();
		}).attr("src", image);
	}
	
	// remove taxonomy image button
	jQuery("#insofern_taxonomy_image_remove_button").click(function() {
		Insofern_removeTaxonomyImage();
		return false;
	});
	
	// remove the image on the quick edit form once the tax is saved
	if(jQuery("#insofern_taxonomy_image_add_button").length > 0) {
		jQuery('#submit').ajaxComplete(function(event, request, settings) {
			Insofern_removeTaxonomyImage();
		});
	}

});


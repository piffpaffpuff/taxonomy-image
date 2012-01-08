function setTaxonomyImage(id, image) {
	var win = window.dialogArguments || opener || parent || top;
	win.sendTaxonomyImageToTerm(id, image);
}

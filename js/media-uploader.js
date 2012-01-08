function Insofern_setTaxonomyImage(id, image) {
	var win = window.dialogArguments || opener || parent || top;
	win.Insofern_sendTaxonomyImageToTerm(id, image);
}

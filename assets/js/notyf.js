
function addUrlFields() {
	var element = document.querySelector("#notyf-url-fields");
	var html = '<tr><td><select name="request_urls_type[]"><option value="order" selected="selected">Commandes</option><option value="cart">Ajouts au panier</option><option value="registration">Inscriptions</option></select></td><td><input type="text" name="request_urls[]" value="" placeholder="https://notyf.com/pixel-webhook/exemple" class="regular-text"></td><td><button class="button button-primary btn-notyf-remove-field" type="button" onclick="removeUrlField(this);"><span class="dashicons dashicons-trash" style="vertical-align: sub;"></span></button></td></tr>';
	element.insertAdjacentHTML('beforeend', html);
}
function removeUrlField(el) {
	el.parentNode.parentNode.remove();
}

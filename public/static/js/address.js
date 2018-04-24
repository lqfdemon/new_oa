/*联系人显示格式转换*/
function conv_address_item(name, data) {
	html = '<nobr><label>';
	html += '		<input class="ace" type="checkbox" name="addr_id" value="' + data + '"/>';
	html += '		<span class="lbl">' + name + '</span></label></nobr>';
	return html;
}

/*联系人显示格式转换*/
function conv_address_item_radio(name, data) {
	html = '<nobr><label>';
	html += '		<input text="' + name + '"class="ace" type="radio" name="addr_id" value="' + data + '"/>';
	html += '		<span class="lbl">' + name + '</span></label></nobr>';
	return html;
}

function conv_inputbox_item(name, data) {
	html = "<span data=\"" + data + "\" id=\"" + data + "\">";
	html += "<nobr><b  title=\"" + name + "\">" + name + "</b>";
	html += "<a class=\"del\" title=\"删除\"><i class=\"fa fa-times\"></i></a></nobr></span>";
	return html;
}
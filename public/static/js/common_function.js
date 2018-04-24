/*赋值*/

function set_val(name, val) {

	if ($("#" + name + " option").length > 0) {
		if (val == "") {
			$("#" + name + " option:first").attr('selected', 'selected');
		} else {
			$("#" + name + " [value=" + val + "]").attr('selected', 'selected');
		}
		return;
	}
	if (($("#" + name).attr("type")) === "checkbox") {
		if (val == 1) {
			$("#" + name).attr("checked", true);
			return;
		}
	}

	if ($("." + name).length > 0) {
		if (($("." + name).first().attr("type")) === "checkbox") {
			var arr_val = val.split(",");
			for (var s in arr_val) {
				$("input." + name + "[value=" + arr_val[s] + "]").attr("checked", true);
			}
		}
	}

	if (($("#" + name).attr("type")) === "text") {
		$("#" + name).val(val);
		return;
	}
	if (($("#" + name).attr("type")) === "hidden") {
		$("#" + name).val(val);
		return;
	}
	if (($("#" + name).attr("rows")) > 0) {
		$("#" + name).text(val);
		return;
	}
	
	$("#" + name).val(val);
}
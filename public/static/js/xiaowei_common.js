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
}

/*打开弹出窗口*/
function winopen(url, w, h) {
	url = fix_url(url);
	$("body").addClass("modal-open");
	$("div.shade").show();
	var _body = $("body").eq(0);
	if ($("#dialog").length == 0) {
		if (!is_mobile()) {
			_body.append("<div id=\"dialog\" ><iframe class=\"myFrame\" src='" + url + "' style='width:" + w + "px;height:100%' scrolling='no' ></iframe></div>");
			$("#dialog").css({
				"width" : w,
				"height" : h,
				"position" : "fixed",
				"z-index" : "3000",
				"top" : ($(window).height() / 2 - h / 2),
				"left" : (_body.width() / 2 - w / 2),
				"background-color" : "#ffffff"
			});
		} else {
			$("div.shade").css("width", _body.width());
			_body.append("<div id=\"dialog\" ><iframe class=\"myFrame\" src='" + url + "' style='width:100%;height:100%' scrolling='no' ></iframe></div>");
			$("#dialog").css({
				"width" : _body.width(),
				"height" : h,
				"position" : "fixed",
				"z-index" : "3000",
				"top" : 0,
				"left" : 0,
				"background-color" : "#ffffff"
			});
		}
	} else {
		$("#dialog").show();
	}
}
/* 关闭弹出窗口*/
function myclose() {
	console.log($("#dialog"));
	$("#dialog").remove();
	$("#dialog").empty();
	console.log('remove');
}

/*联系人显示格式转换*/
function fix_url(url) {
	var ss = url.split('?');
	url = ss[0] + "?";
	for (var i = 1; i < ss.length; i++) {
		url += ss[i] + "&";
	}
	if (ss.length > 0) {
		url = url.substring(0, url.length - 1);
	}
	return url;
}

/* 判断是否是移动设备 */
function is_mobile() {
	return navigator.userAgent.match(/mobile/i);
}
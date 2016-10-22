var missing_translations = new Object();
translate_init = function() {
	missing_translations = new Object();
}

translate_add_id = function(id, org) {
	if ($(id)) missing_translations[id] = org;
}

translate_first_on_the_list = function() {
	for (var id in missing_translations) {
		if ($(id).innerHTML) continue;
		lang_translate(missing_translations[id], id);
		return;
	}
	document.querySelectorAll(".nav_button")[2].down("a").onclick(); // A bit lazy way
}

lang_translate = function (original, span_id) {
        var span = jq('#'+span_id);
	var ret = prompt("Translate: "+original, span.html());
	if (ret === null) return;
	span.html(ret);
	span.css('color','red');
	jq.ajax('modules/Base/Lang/Administrator/update_translation.php', {
		method: 'post',
		data:{
			original: original,
			new: ret,
			cid: Epesi.client_id
		},
		success:function(t) {
			if(span.length)span.css('color', "black");
		}
	});
}

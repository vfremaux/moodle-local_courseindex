/**
* Deprecated... Deferred to customlabel constraint stategy
*
*/
function refreshlevel(wwwroot, level, selectobj, leveloptions){
	
	params = "filter=" + selectobj.options[selectobj.selectedIndex].value;
	levelarr = leveloptions.split(',');
	for (d in levelarr){
		params += '&level'+level+'[]='+levelarr[d];
	}
    var url = wwwroot + "/local/coursecatalog/ajax/refreshSelector.php?" + params;

	$().get(url, null, function(data, textstatus){
		$('#menu_level'+level).parent().html(data);
	});

}
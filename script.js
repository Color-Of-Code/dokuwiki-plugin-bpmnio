jQuery(document).ready(function() {
    jQuery("textarea[id^=__bpmnio_]").each(function(i, tag) { try {
        var xml = jQuery(tag).text();
	var id = jQuery(tag).attr('id');
	// avoid doing it twice
	jQuery(tag).removeAttr('id');
        // bundle exposes the viewer / modeler via the BpmnJS variable
  	var BpmnViewer = window.BpmnJS;
  	var containerdiv = document.createElement('div');
  	containerdiv.className = "canvas";
  	jQuery(tag).parent().append(containerdiv);
  	var viewer = new BpmnViewer({ container: containerdiv });
	viewer.importXML(xml, function(err) {
	    if (err) {
	        containerdiv.text = err;
      	        console.log('error rendering', err);
    	    } else {
                var canvas = viewer.get('canvas');
                // hack: adjust the div height because it doesn't automatically..
                var bBox = canvas._viewport.node.getBBox();
                var height = bBox.height + 5;
                containerdiv.style.height = "" + height + 'px';
            }
	});
	jQuery(tag).remove();
    }catch(err){
        console.warn(err.message);
    }});
});

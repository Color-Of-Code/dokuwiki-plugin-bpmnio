
jQuery(document).ready(function() {
    jQuery("textarea[id^=__bpmnio_], #plugin_bpmnio_editor").each(function(i, tag) { try {
        var xml = jQuery(tag).text();
        xml = decodeURIComponent(escape(window.atob(xml)));
  	var id = jQuery(tag).attr('id');
	// avoid doing it twice
	jQuery(tag).removeAttr('id');

	// bundle exposes the viewer / modeler via the BpmnJS variable
	if(id == 'plugin_bpmnio_editor')
  	  var BpmnViewer = window.BpmnJS;
  	else
  	  var BpmnViewer = window.BpmnJS.Viewer;
  	var containerdiv = document.createElement('div');
  	containerdiv.className = "canvas";
    jQuery(tag).parent().append(containerdiv);
  	var viewer = new BpmnViewer({ container: containerdiv });
    var updatePostData = function() {
        viewer.saveXML({format: true}, function(err, xml) {
            if(!err)
            {
                jQuery('input[name="plugin_bpmnio_data"]').val(window.btoa(xml));
            }
        });
    };
	viewer.importXML(xml, function(err) {
	    if (err) {
            containerdiv.text = err;
            console.log('error rendering', err);
        } else {
                var canvas = viewer.get('canvas');
                var bboxViewport = canvas.getDefaultLayer().getBBox(true);
                var bboxSvg = canvas.getSize();
                canvas.viewbox({ x: bboxViewport.x, y: bboxViewport.y, width: bboxSvg.width, height: bboxSvg.height });
                var height = bboxViewport.height + 4;
                
                if(id == 'plugin_bpmnio_editor')
                {
                    viewer.on('commandStack.changed', updatePostData);
                    updatePostData();
                    containerdiv.style.height = "800px";
                }
                else
                {
                    // hack: adjust the div height because it doesn't automatically.
                    containerdiv.style.height = "" + height + 'px';
                }
                
                // Fix #3 by introducing a small space to allow clicks.
                containerdiv.style.marginRight = "32px";
            }
	});
    jQuery(tag).remove();
    }catch(err){
        console.warn(err.message);
    }});
    
});

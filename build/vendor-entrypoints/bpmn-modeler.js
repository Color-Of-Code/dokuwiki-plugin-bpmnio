import Modeler from 'bpmn-js/lib/Modeler';
import NavigatedViewer from 'bpmn-js/lib/NavigatedViewer';
import Viewer from 'bpmn-js/lib/Viewer';

const root = globalThis;
Object.assign(Modeler, {
  Modeler,
  NavigatedViewer,
  Viewer,
});
root.BpmnJS = Modeler;

export default Modeler;

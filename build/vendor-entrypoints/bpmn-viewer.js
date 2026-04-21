import Viewer from 'bpmn-js/lib/Viewer';

const root = globalThis;
root.BpmnJS = Viewer;
root.BpmnJS.Viewer = Viewer;

export default Viewer;

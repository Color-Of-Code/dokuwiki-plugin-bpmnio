import Modeler from 'bpmn-js/lib/Modeler';
import NavigatedViewer from 'bpmn-js/lib/NavigatedViewer';
import Viewer from 'bpmn-js/lib/Viewer';
import lintModule from 'bpmn-js-bpmnlint';
import { config, resolver } from '../generated/bpmnlintrc.packed.js';

const root = globalThis;
Object.assign(Modeler, {
  Modeler,
  NavigatedViewer,
  Viewer,
});
root.BpmnJS = Modeler;

// Same packed lint config as the viewer bundle. Attaching it to each exported
// constructor (and to window globals) keeps the render script independent of
// which bundle loaded last.
const lintConfig = { config, resolver };

root.BpmnLintModule = lintModule;
root.BpmnLintConfig = lintConfig;
for (const Ctor of [Modeler, NavigatedViewer, Viewer]) {
  Ctor.lintModule = lintModule;
  Ctor.lintConfig = lintConfig;
}

export default Modeler;

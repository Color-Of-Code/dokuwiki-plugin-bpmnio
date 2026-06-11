import Viewer from 'bpmn-js/lib/Viewer';
import lintModule from 'bpmn-js-bpmnlint';
import { config, resolver } from '../generated/bpmnlintrc.packed.js';

const root = globalThis;
root.BpmnJS = Viewer;
root.BpmnJS.Viewer = Viewer;

// Linter integration. bpmn-js-bpmnlint runs in a plain Viewer: every service it
// needs (canvas, overlays, elementRegistry, eventBus, translate, bpmnjs) ships
// with the Viewer, and its editor-action helper resolves editorActions
// optionally. The render script passes lintModule + lintConfig into the
// constructor; we expose them here so it can opt diagrams in or out.
const lintConfig = { config, resolver };

root.BpmnLintModule = lintModule;
root.BpmnLintConfig = lintConfig;
Viewer.lintModule = lintModule;
Viewer.lintConfig = lintConfig;

export default Viewer;

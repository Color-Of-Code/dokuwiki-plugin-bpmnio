import Modeler from 'dmn-js/lib/Modeler';
import Viewer from 'dmn-js/lib/Viewer';

const root = globalThis;
Object.assign(Modeler, {
  Modeler,
  Viewer,
});
root.DmnJS = Modeler;

export default Modeler;

import * as viewerLib from "./viewer.mjs";

viewerLib.PDFViewerApplicationOptions.set('enableComment', true);
viewerLib.PDFViewerApplicationOptions.set('defaultUrl', "");
window.parent.viewerLib = viewerLib;

const bc = new BroadcastChannel("pdfjs");
bc.postMessage('');
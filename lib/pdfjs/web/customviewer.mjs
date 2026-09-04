import * as viewerLib from "./viewer.mjs";

const searchParams = new URLSearchParams(window.location.search);

if (searchParams.get("readonly") === '1') {
    viewerLib.PDFViewerApplicationOptions.set('annotationEditorMode', -1);
} else {
    viewerLib.PDFViewerApplicationOptions.set('enableComment', true);
}

viewerLib.PDFViewerApplicationOptions.set('defaultUrl', "");
window.parent.viewerLib = viewerLib;

const bc = new BroadcastChannel("pdfjs");
bc.postMessage('');
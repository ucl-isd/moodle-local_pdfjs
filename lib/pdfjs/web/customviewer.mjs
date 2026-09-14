import * as viewerLib from "./viewer.mjs";

const searchParams = new URLSearchParams(window.location.search);
const readonly = searchParams.get("readonly");
const pdfjsinstanceid = searchParams.get("pdfjsinstanceid");

if (readonly === '1') {
    viewerLib.PDFViewerApplicationOptions.set('annotationEditorMode', -1);
} else {
    viewerLib.PDFViewerApplicationOptions.set('enableComment', true);
}

if (!window.parent.viewerLib) {
    window.parent.viewerLib = {};
}

viewerLib.PDFViewerApplicationOptions.set('defaultUrl', "");
window.parent.viewerLib[pdfjsinstanceid] = viewerLib;

const bc = new BroadcastChannel("pdfjs");
bc.postMessage(pdfjsinstanceid);
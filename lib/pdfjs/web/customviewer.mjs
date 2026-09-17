import * as viewerLib from "./viewer.mjs";

const searchParams = new URLSearchParams(window.location.search);
const readonly = searchParams.get("readonly");
const pdfjsinstanceid = searchParams.get("pdfjsinstanceid");
const filepath = searchParams.get("filepath");

if (readonly === '1') {
    viewerLib.PDFViewerApplicationOptions.set('annotationEditorMode', -1);
} else {
    viewerLib.PDFViewerApplicationOptions.set('enableComment', true);
}

viewerLib.PDFViewerApplicationOptions.set('defaultUrl', "");

if (window.parent) {
    if (!window.parent.viewerLib) {
        window.parent.viewerLib = {};
    }
    window.parent.viewerLib[pdfjsinstanceid] = viewerLib;
    const bc = new BroadcastChannel("pdfjs");
    bc.postMessage(pdfjsinstanceid);
}

if (filepath) {
    viewerLib.PDFViewerApplication.open({url: filepath});
}

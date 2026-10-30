import * as viewerLib from "./viewer.mjs";

const searchParams = new URLSearchParams(window.location.search);
const readonly = searchParams.get("readonly") === '1';
const pdfitemid = searchParams.get("pdfitemid");
const filepath = searchParams.get("filepath");
const fileid = searchParams.get("fileid");

if (readonly) {
    viewerLib.PDFViewerApplicationOptions.set('annotationEditorMode', -1);
} else {
    viewerLib.PDFViewerApplicationOptions.set('enableComment', true);
}

viewerLib.PDFViewerApplicationOptions.set('defaultUrl', "");

if (window.parent) {
    if (!window.parent.viewerLib) {
        window.parent.viewerLib = {};
    }
    if (!window.parent.viewerLib[pdfitemid]) {
        window.parent.viewerLib[pdfitemid] = {};
    }
    window.parent.viewerLib[pdfitemid][fileid] = viewerLib;

    if (!readonly) {
        const bc = new BroadcastChannel("pdfjs");

        window.addEventListener("load", () => {
            var buttons =
            `<div id="localpdfjsSaveAnnotationsDiv" class="toolbarButtonWithContainer">
                <button id="localpdfjsSaveAnnotationsButton" class="toolbarButton" type="button" title="Save annotations">Save
                </button>
            </div>
            <div id="localpdfjsClearAnnotationsDiv" class="toolbarButtonWithContainer">
                <button id="localpdfjsClearAnnotationsButton" class="toolbarButton" type="button" title="Clear annotations">Clear
                </button>
            </div>
            <div id="annotationsSaveSeparator" class="verticalToolbarSeparator"></div>`;

            window.document.getElementById('toolbarViewerRight').insertAdjacentHTML('afterbegin', buttons);

            window.document.getElementById('localpdfjsClearAnnotationsButton').onclick = function () {
                bc.postMessage({
                    pdfitemid: pdfitemid,
                    fileid: fileid,
                    message: 'clearannotations',
                });
            };

            window.document.getElementById('localpdfjsSaveAnnotationsButton').onclick = function () {
                bc.postMessage({
                    pdfitemid: pdfitemid,
                    fileid: fileid,
                    message: 'saveannotations',
                });
            };
        });
    }
}

if (filepath) {
    viewerLib.PDFViewerApplication.open({url: filepath});
}

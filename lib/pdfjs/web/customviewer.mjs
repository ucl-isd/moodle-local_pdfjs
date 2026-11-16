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

    // The iframe holding this viewer, or null when the viewer is not embedded in one.
    const iframe = window.frameElement;

    if (!readonly || iframe) {
        const bc = new BroadcastChannel("pdfjs");

        window.addEventListener("load", () => {
            // The viewer is embedded in a Moodle page from the same origin, so use its string cache.
            window.parent.require(['core/str'], ({getStrings}) => {
                getStrings([
                    {key: 'saveannotations', component: 'local_pdfjs'},
                    {key: 'clearannotations', component: 'local_pdfjs'},
                    {key: 'fullscreen', component: 'local_pdfjs'},
                ]).then(([savelabel, clearlabel, fullscreenlabel]) => {
                    if (iframe) {
                        // Place the full screen button alongside the zoom controls.
                        window.document.getElementById('scaleSelectContainer').insertAdjacentHTML('afterend',
                        `<div id="fullScreenSeparator" class="verticalToolbarSeparator"></div>
                        <div id="localpdfjsFullScreenDiv" class="toolbarButtonWithContainer">
                            <button id="localpdfjsFullScreenButton" class="toolbarButton" type="button" title="${fullscreenlabel}">${fullscreenlabel}
                            </button>
                        </div>`);

                        window.document.getElementById('localpdfjsFullScreenButton').onclick = function () {
                            bc.postMessage({
                                pdfitemid: pdfitemid,
                                fileid: fileid,
                                message: 'togglefullscreen',
                            });
                        };
                    }

                    if (!readonly) {
                        window.document.getElementById('toolbarViewerRight').insertAdjacentHTML('afterbegin',
                        `<div id="localpdfjsSaveAnnotationsDiv" class="toolbarButtonWithContainer">
                            <button id="localpdfjsSaveAnnotationsButton" class="toolbarButton" type="button" title="${savelabel}">${savelabel}
                            </button>
                        </div>
                        <div id="localpdfjsClearAnnotationsDiv" class="toolbarButtonWithContainer">
                            <button id="localpdfjsClearAnnotationsButton" class="toolbarButton" type="button" title="${clearlabel}">${clearlabel}
                            </button>
                        </div>
                        <div id="annotationsSaveSeparator" class="verticalToolbarSeparator"></div>`);

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
                    }
                });
            });
        });
    }
}

if (filepath) {
    viewerLib.PDFViewerApplication.open({url: filepath});
}

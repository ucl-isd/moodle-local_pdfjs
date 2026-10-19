import * as viewerLib from "./viewer.mjs";

const searchParams = new URLSearchParams(window.location.search);
const readonly = searchParams.get("readonly") === '1';
const pdfjsinstanceid = searchParams.get("pdfjsinstanceid");
const filepath = searchParams.get("filepath");

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
    window.parent.viewerLib[pdfjsinstanceid] = viewerLib;
    const bc = new BroadcastChannel("pdfjs");
    bc.postMessage({
        instanceid: pdfjsinstanceid,
        message: 'loaded',
    });

    if (!readonly) {
        window.addEventListener("load", (event) => {
            var btnclear = document.createElement("button");
            btnclear.setAttribute("id", "localpdfjsClearAnnotationsButton");
            btnclear.setAttribute("class", "toolbarButton");
            btnclear.setAttribute("type", "button");
            btnclear.setAttribute("title", "Clear annotations");
            btnclear.textContent = "Clear";
            document.getElementById("editorComment").before(btnclear);

            btnclear.onclick = function () {
                bc.postMessage({
                    instanceid: pdfjsinstanceid,
                    message: 'clearannotations',
                });
            }

            var btnsave = document.createElement("button");
            btnsave.setAttribute("id", "localpdfjsClearAnnotationsButton");
            btnsave.setAttribute("class", "toolbarButton icon fa fa-floppy-disk");
            btnsave.setAttribute("type", "button");
            btnsave.setAttribute("title", "Save annotations");
            btnsave.textContent = "Save";
            document.getElementById("editorComment").before(btnsave);

            btnsave.onclick = function () {
                bc.postMessage({
                    instanceid: pdfjsinstanceid,
                    message: 'saveannotations',
                });
            }

            var saveseparator = document.createElement("div");
            saveseparator.setAttribute("id", "annotationsSaveSeparator");
            saveseparator.setAttribute("class", "verticalToolbarSeparator");
            document.getElementById("editorComment").before(saveseparator);
        });
    }
}

if (filepath) {
    viewerLib.PDFViewerApplication.open({url: filepath});
}

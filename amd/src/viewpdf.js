// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Andrew Hancox <andrewdchancox@googlemail.com>
 */

import Ajax from 'core/ajax';
import {prefetchStrings} from 'core/prefetch';
import {getString} from 'core/str';
import {add as addToast} from 'core/toast';
import Config from 'core/config';

var controller = {
    saveannotations: (currentfiledataset) => {
        var pdfjsinstance = window.viewerLib[currentfiledataset.pdfjsinstanceid];

        pdfjsinstance.PDFViewerApplication.pdfViewer._layerProperties.annotationEditorUIManager?.endCurrentEditing();
        return pdfjsinstance.PDFViewerApplication.pdfDocument.saveDocument()
            .then(pdfdata => {
                const blob = new Blob([pdfdata], {type: "application/pdf"});
                const data = new FormData();
                data.append("annotations", blob, "annotated.pdf");
                data.append('pdfitemid', currentfiledataset.pdfitemid);
                data.append('fileid', currentfiledataset.fileid);
                data.append('filename', currentfiledataset.filename);
                data.append('contextid', currentfiledataset.contextid);
                data.append('sesskey', Config.sesskey);

                return fetch(Config.wwwroot + "/local/pdfjs/handlers/uploadannotatedsubmissionajax.php", {
                    method: "POST",
                    body: data,
                });
            })
            .then(response => {
                const result = response.json();
                currentfiledataset.annotatedfileurl = result.url;
                currentfiledataset.annotatedfileid = result.fileid;

                pdfjsinstance.PDFViewerApplication.pdfDocument.annotationStorage.resetModified();

                return addToast(getString('annotationssaved', 'local_pdfjs'), {type: 'success'});
            });
    },

    loadpdf: async function (currentfiledataset) {
        var url = currentfiledataset.href;

        if (currentfiledataset.annotatedfileurl) {
            url = currentfiledataset.annotatedfileurl;
        }

        var pdfjsinstance = window.viewerLib[currentfiledataset.pdfjsinstanceid];
        if (pdfjsinstance.PDFViewerApplication.pdfDocument) {
            pdfjsinstance.PDFViewerApplication.pdfDocument.annotationStorage.resetModified();
        }

        return pdfjsinstance.PDFViewerApplication.open({url: url});
    },

    clearannotations: (currentfiledataset) => {
        if (!currentfiledataset.annotatedfileid) {
            return controller.loadpdf(currentfiledataset);
        }
        return Ajax.call([{
            methodname: 'local_pdfjs_clearannotations',
            args: {
                pdfitemid: currentfiledataset.pdfitemid,
                fileid: currentfiledataset.annotatedfileid
            },
        }])[0]
            .then(() => {
                currentfiledataset.annotatedfileurl = '';
                currentfiledataset.annotatedfileid = '';
                controller.loadpdf(currentfiledataset);
                return addToast(getString('annotationscleared', 'local_pdfjs'), {type: 'success'});
            })
            .catch((error) => {
                addToast(error, {type: 'error'});
            });
    },
};


export const init = async (formwrapperid, pdfjsinstanceid, readonly) => {
    var currentfiledataset;

    prefetchStrings('local_pdfjs', [
        'annotationssaved',
        'annotationscleared',
    ]);

    const rootselector = '[data-pdfjsinstanceid="' + pdfjsinstanceid + '"] ';
    const viewfilebuttons = document.querySelectorAll(rootselector + '[data-action="localpdfjs_viewfile"]');
    const formwrapper = document.querySelector("#" + (formwrapperid || 'noid') + " form");

    viewfilebuttons.forEach((node) => {
            node.addEventListener("click", async(event) => {
                currentfiledataset = event.target.dataset;
                await controller.loadpdf(currentfiledataset).promise;
            });
        }
    );

    const bc = new BroadcastChannel("pdfjs");

    bc.onmessage = (message) => {
        if (message.data.instanceid !== pdfjsinstanceid) {
            return;
        }

        if (message.data.message == 'loaded') {
            if (viewfilebuttons[0]) {
                currentfiledataset = viewfilebuttons[0].dataset;
                controller.loadpdf(currentfiledataset);
            }
        }

        if (message.data.message == 'clearannotations') {
            controller.clearannotations(currentfiledataset);
        }

        if (message.data.message == 'saveannotations') {
            controller.saveannotations(currentfiledataset);
        }
    };

    if (!readonly && formwrapper) {
        formwrapper.addEventListener("submit", (event => {
            var pdfjsintance = window.viewerLib[pdfjsinstanceid];
            // Exit if no annotations have been added/removed/modified.
            if (!pdfjsintance.PDFViewerApplication._hasChanges()) {
                return;
            }

            event.preventDefault();
            controller.saveannotations(currentfiledataset).then(() => {
                if (event.submitter.type === 'submit') {
                    const input = window.document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', event.submitter.name);
                    input.setAttribute('value', event.submitter.value);
                    event.target.appendChild(input);
                }
                pdfjsintance.PDFViewerApplication._hasChanges = function () {
                    return false;
                };

                return event.target.submit();
            });
        }));
    }
};

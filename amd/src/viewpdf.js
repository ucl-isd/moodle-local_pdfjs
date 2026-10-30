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
    saveannotations: (pdfitemid, fileid) => {
        var pdfjsinstance = window.viewerLib[pdfitemid][fileid];

        pdfjsinstance.PDFViewerApplication.pdfViewer._layerProperties.annotationEditorUIManager?.endCurrentEditing();

        return pdfjsinstance.PDFViewerApplication.pdfDocument.saveDocument()
            .then(pdfdata => {
                const blob = new Blob([pdfdata], {type: "application/pdf"});
                const data = new FormData();
                data.append("annotations", blob, "annotated.pdf");
                data.append('pdfitemid', pdfitemid);
                data.append('annotatedfileid', fileid);
                data.append('sesskey', Config.sesskey);

                return fetch(Config.wwwroot + "/local/pdfjs/handlers/uploadannotatedsubmissionajax.php", {
                    method: "POST",
                    body: data,
                })
                    .then(response => response.json())
                    .then(parsedresponse => {
                        if (parsedresponse.fileid) {
                            pdfjsinstance.PDFViewerApplication.pdfDocument.annotationStorage.resetModified();
                            return addToast(getString('annotationssaved', 'local_pdfjs'), {type: 'success'});
                        } else {
                            return addToast(getString('annotationssavefailed', 'local_pdfjs'), {type: 'error'});
                        }
                    });
            });
    },

    loadpdf: async function (pdfitemid, fileid, fileurl) {
        var pdfjsinstance = window.viewerLib[pdfitemid][fileid];
        if (pdfjsinstance.PDFViewerApplication.pdfDocument) {
            pdfjsinstance.PDFViewerApplication.pdfDocument.annotationStorage.resetModified();
        }

        return pdfjsinstance.PDFViewerApplication.open({url: fileurl});
    },

    clearannotations: (pdfitemid, fileid, fileurl) => {
        return Ajax.call([{
            methodname: 'local_pdfjs_clearannotations',
            args: {
                pdfitemid: pdfitemid,
                fileid: fileid
            },
        }])[0]
            .then(() => {
                controller.loadpdf(pdfitemid, fileid, fileurl);
                return addToast(getString('annotationscleared', 'local_pdfjs'), {type: 'success'});
            })
            .catch((error) => {
                addToast(error, {type: 'error'});
            });
    },
};


export const init = async (formwrapperid, pdfitemid, readonly) => {
    prefetchStrings('local_pdfjs', [
        'annotationssaved',
        'annotationscleared',
        'annotationssavefailed',
    ]);

    const formwrapper = document.querySelector("#" + (formwrapperid || 'noid') + " form");

    const bc = new BroadcastChannel("pdfjs");

    bc.onmessage = (message) => {
        if (message.data.pdfitemid !== pdfitemid) {
            return;
        }

        if (message.data.message == 'clearannotations') {
            const iframe = document.getElementById("pdfjsiframe_" + message.data.pdfitemid + "_" + message.data.fileid);
            controller.clearannotations(message.data.pdfitemid, message.data.fileid, iframe.dataset.fileurl);
        }

        if (message.data.message == 'saveannotations') {
            controller.saveannotations(message.data.pdfitemid, message.data.fileid);
        }
    };

    if (!readonly && formwrapper) {
        formwrapper.addEventListener("submit", (event => {
            event.preventDefault();

            var saveannotationspromises = [];

            for (const fileid in window.viewerLib[pdfitemid]) {
                // Exit if no annotations have been added/removed/modified.
                if (window.viewerLib[pdfitemid][fileid].PDFViewerApplication._hasChanges()) {
                    saveannotationspromises.push(controller.saveannotations(pdfitemid, fileid));
                }
            }

            Promise.allSettled(saveannotationspromises).then(() => {
                if (event.submitter.type === 'submit') {
                    const input = window.document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', event.submitter.name);
                    input.setAttribute('value', event.submitter.value);
                    event.target.appendChild(input);
                }

                for (const fileid in window.viewerLib[pdfitemid]) {
                    window.viewerLib[pdfitemid][fileid].PDFViewerApplication._hasChanges = function () {
                        return false;
                    };
                }

                event.target.submit();
            });
        }));
    }
};

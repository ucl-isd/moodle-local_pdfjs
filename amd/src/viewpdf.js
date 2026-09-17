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
        return window.viewerLib[currentfiledataset.pdfjsinstanceid].PDFViewerApplication.pdfDocument.saveDocument()
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
                return addToast(getString('annotationssaved', 'local_pdfjs'), {type: 'success'});
            });
    },

    loadpdf: async function (dataset) {
        var url = dataset.href;

        if (dataset.annotatedfileurl) {
            url = dataset.annotatedfileurl;
        }

        return window.viewerLib[dataset.pdfjsinstanceid].PDFViewerApplication.open({url: url});
    },

    clearannotations: (currentfiledataset) => {
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
    prefetchStrings('local_pdfjs', [
        'annotationssaved',
        'annotationscleared',
    ]);

    const rootselector = '[data-pdfjsinstanceid="' + pdfjsinstanceid + '"] ';
    const viewfilebuttons = document.querySelectorAll(rootselector + '[data-action="localpdfjs_viewfile"]');
    const saveannotations = document.querySelector(rootselector + '[data-action="localpdfjs_saveannotations"]');
    const clearannotations = document.querySelector(rootselector + '[data-action="localpdfjs_clearannotations"]');
    const formwrapper = document.querySelector("#" + (formwrapperid || 'noid') + " form");

    viewfilebuttons.forEach((node) => {
            node.addEventListener("click", async () => {
                await controller.loadpdf(currentfiledataset).promise;
            });
        }
    );

    var currentfiledataset;

    if (viewfilebuttons[0]) {
        currentfiledataset = viewfilebuttons[0].dataset;
        await controller.loadpdf(currentfiledataset).promise;
    }

    if (!readonly && saveannotations) {
        saveannotations.addEventListener("click", () => {
            controller.saveannotations(currentfiledataset);
        });
    }

    if (!readonly && clearannotations) {
        clearannotations.addEventListener("click", () => {
            controller.clearannotations(currentfiledataset);
        });
    }

    if (!readonly && formwrapper) {
        formwrapper.addEventListener("submit", (event => {
            // Exit if no annotations have been added/removed/modified.
            if (!window.viewerLib[pdfjsinstanceid].PDFViewerApplication._hasChanges()) {
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

                return event.target.submit();
            });
        }));
    }
};

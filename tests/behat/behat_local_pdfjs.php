<?php
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
 * @package    local_pdfjs
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Andrew Hancox <andrewdchancox@googlemail.com>
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps for driving the pdf.js viewer.
 */
class behat_local_pdfjs extends behat_base {
    /**
     * Open a file's tab in the pdf.js viewer inside an element and switch into it, waiting for the PDF to load.
     *
     * @Given I switch to the PDF viewer for :filename in the :element :selectortype
     * @param string $filename
     * @param string $element
     * @param string $selectortype
     */
    public function i_switch_to_the_pdf_viewer_for_in(string $filename, string $element, string $selectortype): void {
        $container = $this->get_selected_node($selectortype, $element);

        // Each file has a tab whose button names the file and targets its viewer iframe.
        $tab = $this->find('named_exact', ['button', $filename], false, $container);
        $tab->click();
        $iframe = $this->find('css', $tab->getAttribute('data-target'), false, $container);
        $this->getSession()->switchToIFrame($iframe->getAttribute('name'));

        $this->wait_for_pdf_viewer();
    }

    /**
     * Add a free text annotation to the first page of the PDF in the current viewer.
     *
     * @Given /^I add the text annotation "(?P<text_string>(?:[^"]|\\")*)" to the PDF$/
     * @param string $text
     */
    public function i_add_the_text_annotation_to_the_pdf(string $text): void {
        $this->wait_for_pdf_viewer();

        $this->execute('behat_general::i_click_on', ['#editorFreeTextButton', 'css_element']);

        // Switching editor mode is asynchronous, and clicks on the page are ignored until it completes.
        $layer = ".page[data-page-number='1'] .annotationEditorLayer.freetextEditing";
        $this->ensure_element_exists($layer, 'css_element');

        // A WebDriver click on the layer does not create an editor, so dispatch the pointer events pdf.js listens for.
        $this->execute_script("
            (() => {
                const layer = document.querySelector(\"{$layer}\");
                const rect = layer.getBoundingClientRect();
                const options = {
                    bubbles: true,
                    cancelable: true,
                    button: 0,
                    isPrimary: true,
                    pointerType: 'mouse',
                    clientX: rect.x + 100,
                    clientY: rect.y + 100,
                };
                layer.dispatchEvent(new PointerEvent('pointerdown', options));
                layer.dispatchEvent(new PointerEvent('pointerup', options));
            })()
        ");

        $this->ensure_element_exists('.freeTextEditor', 'css_element');
        $this->execute('behat_general::i_type', [$text]);
    }

    /**
     * Wait until the viewer has loaded a document and rendered its first page.
     */
    protected function wait_for_pdf_viewer(): void {
        $this->spin(
            function (behat_local_pdfjs $context): bool {
                return (bool)$context->evaluate_script(
                    "return !!(window.PDFViewerApplication && window.PDFViewerApplication.pdfDocument" .
                    " && document.querySelector(\".page[data-page-number='1'] canvas\"));"
                );
            },
            [],
            behat_base::get_extended_timeout()
        );
    }
}

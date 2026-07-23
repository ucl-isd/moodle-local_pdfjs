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

namespace local_pdfjs\output;

use context;
use file_storage;
use local_pdfjs\local\lib;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use stored_file;
use templatable;

class pdf implements renderable, templatable {
    /** @var stored_file[] */
    private array $files;
    private context $context;
    private int $pdfitemid;
    private string $component;
    private string $formwrapperid;

    /**
     * Constructor
     *
     * @param stored_file[] $files array of annotatable files
     */
    public function __construct(array $files, context $context, string $component, int $pdfitemid, string $formwrapperid = '') {
        $this->files = $files;
        $this->context = $context;
        $this->pdfitemid = $pdfitemid;
        $this->component = $component;
        $this->formwrapperid = $formwrapperid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output The renderer
     * @return stdClass Data to be used by the template
     */
    public function export_for_template(renderer_base $output): stdClass {

        $template = new stdClass();

        $template->formwrapperid = $this->formwrapperid;
        $template->files = [];

        $annotatedfiles = $this->get_file_annotations();
        foreach ($this->files as $file) {
            if ($file->get_mimetype() !== 'application/pdf') {
                continue;
            }

            $model = [
                'filename' => $file->get_filename(),
                'href' => moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $this->component,
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                ),
                'contextid' => $file->get_contextid(),
                'fileid' => $file->get_id(),
                'pdfitemid' => $this->pdfitemid,
            ];

            if (isset($annotatedfiles[$file->get_id()])) {
                $annotatedfile = $annotatedfiles[$file->get_id()];
                $model['annotatedfileurl'] = moodle_url::make_pluginfile_url(
                    $annotatedfile->get_contextid(),
                    'local_pdfjs',
                    $annotatedfile->get_filearea(),
                    $annotatedfile->get_itemid(),
                    $annotatedfile->get_filepath(),
                    $annotatedfile->get_filename()
                );
                $model['annotatedfileid'] = $annotatedfile->get_id();
            }

            lib::register_file_for_annotating($file->get_id());

            $template->files[] = (object)$model;
        }

        $template->multiplefiles = (count($template->files) > 1);

        return $template;
    }

    private function get_file_annotations(): array {
        global $USER;

        $fs = new file_storage();

        $annotatedfiles = [];
        $files = $fs->get_area_files(
            $this->context->id,
            'local_pdfjs',
            'pdfannotations',
            $this->pdfitemid
        );

        foreach ($files as $file) {
            if ($file->get_userid() !== $USER->id) {
                continue;
            }
            if ($file->get_filename() == '.') {
                continue;
            }

            $annotatedfiles[$file->get_source()] = $file;
        }

        return $annotatedfiles;
    }
}

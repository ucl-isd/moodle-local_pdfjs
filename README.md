# What is it #
- A wrappper for the Mozilla pdf.js library
- This will allow you to render an annotatable pdf with controls to store/clear your annotations
- This is currently exclusively used by the UCL version of mod_coursework
- Expect breaking changes until this plugin has been implemented in a few more spaces.
# To Use it #
- Unzip the plugin in the moodle .../local/pdfjs directory.
- Where you want to render an annotatable PDF in your plugin do something like this:
````
$html .= $this->output->render(new \local_pdfjs\output\pdf(
        $arrayofstoredfiles,
        $acontextobject,
        'frankenstylenameofmodule',
        $idofthingbeingannotated,
        'idofformelementtoautotriggersavingannotations'
    ));
````

Author
------

The module has been written and is currently maintained by Andrew Hancox <andrewdchancox@googlemail.com>

Copyright
------

2026 onwards University College London https://www.ucl.ac.uk/

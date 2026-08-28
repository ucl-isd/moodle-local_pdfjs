Get latest stable release from https://github.com/mozilla/pdf.js/releases
Copy contents into local/pdfjs/lib/pdfjs

Reapply hacks to local/pdfjs/lib/pdfjs/viewer.html, in latest version this means:

`<script src="viewer.mjs" type="module"></script>`

Becomes:

```
    <!--
  <script src="viewer.mjs" type="module"></script>
    -->
    <script src="customviewer.mjs" type="module"></script>
    <link rel="stylesheet" href="customviewer.css" />
```
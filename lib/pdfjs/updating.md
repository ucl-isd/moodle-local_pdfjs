Get latest stable release from https://mozilla.github.io/pdf.js/

## CSS

### Renamespacing
Copy `viewer.css` to `wrapperviewer.scss`
To namespace all CSS variables, search and replace

`:root`

to

`&`

Change:

`[dir="rtl"]& {`

to

``&[dir="rtl"] {``

### Tidying
To allow SASS to compile, search and replace `:;` with `: ;`

### Compile

```sass wrappedviewer.scss > wrappedviewer.css```

### HTML

Take the contents of the body tag in view.html and drop it into the `div.localpdfjs_pdfjswrapper` element in `viewpdf.mustache`

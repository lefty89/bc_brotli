bc_brotli
=========

What does it do
----------------------
Makes Googles new [brotli compression](https://github.com/google/brotli/) available for TYPO3 JavaScript and CSS optimisation. Compared to gzip, brotli reduces the size of resources (html, css, js) between 17% and 25% and allows faster loading of websites.

Requirements
-------------------
* TYPO3 CMS 7.6.0 or later
* GNU Compiler Collection (GCC)
* The update script requires a browser that support server send events, [see](http://caniuse.com/#feat=eventsource)

Installation
----------------
Simply extract or clone the content into the *typo3conf* folder. After enabling the extension in the backend you have put the brotli binary into the extension folder at path: *Resources/Private/Bin.* Therefore you can use the prepared update script.

![Extension Manager](https://raw.githubusercontent.com/lefty89/bc_brotli/master/Documentation/Images/AdministratorManual/ExtensionManager.png "Extension Manager")
![Update Script](https://raw.githubusercontent.com/lefty89/bc_brotli/master/Documentation/Images/AdministratorManual/UpdateScript.png "Update Script")

After the installation is completed you have to add the following code into the *.htaccess* file of your TYPO3 root directoy.

```apache
    <Files *.js.br>
      AddType "text/javascript" .br
      AddEncoding br .br
    </Files>
    <Files *.css.br>
      AddType "text/css" .br
      AddEncoding br .br
    </Files>
```

Usage
--------
Nothing more to do. If the client browser accepts content encoding for brotli TYPO3 will automatically delivered the right file, else it falls back to gzip or non compression resources.




# Contributing to LWT

This guide is mainly aimed for developers, but it can give useful insights on how LWT is structured, which could help you for debugging. The first step you need to take is to clone LWT from the official GitHub repository ([HugoFara/lwt](https://github.com/HugoFara/lwt)).

## Get Composer

Getting [Composer](https://getcomposer.org/download/) is required if you want to edit LWT on the server side, but it will also make be useful to edit JS and CSS code, so it is *highly recommended*. Composer is a light-weight dependency manager that *does not need* a server for running. 

Once Composer is ready, go the the lwt folder (most likely ``lwt/``), and type
```bash
composer install --dev
```

This will automatically dowload all the required dependencies.

## Create and Edit Themes
Themes are stored at ``src/themes/``. If you want to create a new theme, simply add it in a subfolder. You can also edit existing themes. 

To apply the changes you made on a theme, run
```bash
make minify
```

This command will minify all CSS and JS.

Alternatively, you can run
```bash
php -r "require 'src/php/minifier.php'; minifyAllCSS();"
```
 It minifies CSS only.

### Debug your theme

You may not want to see your code minified, so you can use 
```bash
make no-minify
```

It has the same effect as copying the folder ``scr/themes/`` to ``themes/``.

### My theme does not contain all the Skinning Files!

That's not a problem at all. When LWT looks for a file that should be contained in ``src/themes/{{The Theme}}/``, it checks if the file exists. If not, it goes to ``css/`` and tries to get the same file. With this system, your themes **does not need to have the same files as ``src/css/``**. 

## Change JS behaviour

As with themes, LWT minifies JS code for a better user experience. Please refer to the previous section for detailed explanations, this section will only go through import points.

### Edit JS code
Clear code is stored at ``src/js/``. Once again, the *actual* code used by LWT should be at ``js/``. After you have done any modification, either run ``make minify`` or ``php -r "require 'src/php/minifier.php'; minifyAllJS();"``. 

### Debug JS code
To copy code in a non-obfuscated form, run ``make no-minify``, or replace the content of ``js/`` by ``src/js/``.

## Improving Documentation

To regenerate all documentation, use ``make doc``.

### General Documentation
The documentation in split across Markdown (``.md``) files in ``docs/``. Then, those files are requested by ``info.php``. The final version is ``info.html``, that contains all files. 

To regenerate ``info.hml``, run ``make info.html``.

### Code Documentation
Code documentation (everything under ``docs/html/``) is automatically generated. If you see an error, the PHP code is most likely at fault. However, don't hesitate to signal the issue.

Currently, the documentation is generated through Doxygen (run ``doxygen Doxyfile`` to regenerate it), but this is likely to change.


## Other ways of Contribution

### Drop a star on GitHub
This is an Open-Source project. It means that anyone can contribute, but nobody gets paid for improving it. Dropping a star, leaving a comment or posting an issue is *essential*, because the only retributions developers get from time spent on LWT is to discuss with users.

### Share the Word
LWT is a non-profitable software, so we won't have much time, and no money, to advertise LWT. If you enjoy LWT and want to see it grow, share it! 

### Discuss
Either go to the forum of the [official LWT version](https://sourceforge.net/p/learning-with-texts/discussion/), or come and [discuss on the community version](https://github.com/HugoFara/lwt/discussions).

Thanks for your interest in contributiong!


================
Writing ATK Docs
================

New users of Agile Toolkit rely on documentation. To make it easier for the
maintainers to update documentation - each component of ATK framework comes
with a nice documentation builder.

Writing ATK Documentation
=========================

Open file "docs/index.rst" in your editor. Most editors will support
"reSTructured Text" through add-on. The support is not perfect, but it works.

If you are updating a feature - find a corresponding ".rst" file. Your editor
may be able to show you a preview. Modify or extend documentation as needed.

See also: http://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html

Building and Testing Documentation
==================================

Make sure you have "Docker" installed, follow simple instructions in
"docs/README.md".

Integrating PhpStorm
--------------------

You can integrate PhpStorm build process like this:

.. figure:: images/doc-build-phpstorm1.png
   :scale: 50 %
   :alt: Create build configuration for the Dockerfile


.. figure:: images/doc-build-phpstorm2.png
   :scale: 50 %
   :alt: Adjust Port settings to expose 80 as 8080

.. figure:: images/doc-build-phpstorm3.png
   :scale: 50 %
   :alt: Use "Ctrl+R" anytime to build docs


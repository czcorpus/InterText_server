# InterText Server

InterText is an on-line editor for aligned parallel texts. It was developed for
the project [InterCorp](http://www.korpus.cz/intercorp/?lang=en) to edit and
manage alignments of multiple parallel language versions of texts at the level
of sentences, but it is designed with flexibility in mind and supports custom
XML documents and Unicode character set. The software is written in PHP and uses
MySQL database as back-end.

_InterText Server_ is the original server based implementation with web-based interface, using PHP a MySQL database. It is designed for management of large, collaborative projects with many users, and - unlike the personal desktop application called _Intertext Editor_ - its installation and deployment requires at least some basic skills of server administrator.

See the [InterText project homepage](http://wanthalf.saga.cz/intertext) for more details.

## Features

- can manage any number of texts
- can manage any number of text (language) versions for each text
- import and export of any valid XML document (see LIMITATIONS & KNOWN ISSUES!)
- support of unicode (UTF-8) by default
- automatic conversion of custom entities into UTF-8 characters on import
- arbitrary alignments between any pair of (language) versions of the same text
- one level alignment, every text version can define its own XML elements
containing text to be aligned
- integration of the 'hunalign' and 'TCA2' automatic aligners
- import and export of alignments in TEI XML format (stand-off alignment, no
conversion, see below for details)
- opt. export of documents with 'corresp' attributes on aligned elements
- opt. export of documents with text segments enclosed in <seg> elements (for
ParaConc compatibility)
- possibility to edit text on-the-fly when editing alignments (can be forbidden
on per-text basis)
- keeps history of all changes to the text for later revision
- possibility to change segmentation of elements (e.g. sentences) by splitting
or merging them in the alignment editor (can be forbidden on per-text basis)
- possibility to split or merge container (parent) elements (e.g. paragraphs)
- separate possibility to prevent the change of segmentation (structure) for
'pivot' text versions
- automatic (one- or two-level) re-numbering of text elements after change in
segmentation (structure)
- possibility to set bookmarks in the alignment and jump quickly between them
- possibility to search for substrings, fulltext search and regular expression
based search in the texts (as limited by the MySQL-engine capabilities), search
for "suspicious" alignments and edited/changed elements, etc.
- basic workflow management based on three-level user hierarchy (no own user
management, uses external database of users) and three-(four)-level status of
alignments
- command-line access to the import and export functions for batch-processing
- triggers for external scripts on the change of alignment status
- synchronization of texts and alignments with external InterText editor clients


## Technical details on file formats

- the system expects use of id-attributes with (at most) two-part numbers (two levels) for all 
alignable elements, the separator can be any of : , . - _ characters; their 
parent elements can be numbered by simple numbers only; prefixes to the 
id-attributes are possible, but they will be stripped on import (and can be 
regenerated on export as "long-ids" by the corresponding function in 
config/export_customization.php
- the default (re-)numbering scheme is to have simple numbers for containers
(parents) and two-part numbers for alignable elements, separated by a colon
(e.g. "12:3" for the third sentence (element) in the 12th paragraph (container))
- if nested containers are detected in the document, only simple (single level)
numbering of the alignable elements is applied and their parents (containers)
are ignored
- the file format for TEI alignment file is one single "linkGrp" element with 
two attributes: "toDoc" and "fromDoc"; their values should point to the 
filenames of the separate documents, or at least have the form: 
"document_name.version_name.extensions" - extensions are optional, but 
"document_name" and "version_name" are used to identify the aligned document
versions of this alignment according to the names as declared in InterText; 
the "linkGrp" element then contains the "link" elements, each corresponding 
to one position (segment) in the alignment, with the following attributes: 
 - "xtargets" is a semicolon separated list of element's id-values linked 
together (first a space-separated list of element id-s from the  "toDoc" 
document, and after the semicolon a space-separated list of element id-s
from the "fromDoc" document); 
 - "status" is an optional attribute with the status of the link - known values
are "man" (for manually confirmed link), "auto"  (for automatically aligned 
elements), "plain" (for unaligned / unconfirmed / uknown status); 
 - "mark" is used internally to preserve user bookmarks from the editor, only 
values 0 and 1 are known, but for 0 no attribute is generated at all on export
 - "type" is only generated on export for convenience, it gives a dash 
separated count of elements linked together by the link (e.g. "1-2")


## LIMITATIONS & KNOWN ISSUES

- the package does not contain the Hunalign nor the TCA2 automatic aligners
- DOCTYPE, entity definitions and the XML declaration element are not imported
(preserved) from the XML file (a problem of the PHP XMLReader module); the only
preserved node types are: elements with their attributes (see below for
exception!), text and CDATA contents, comments, processing instructions and
whitespace formatting (CDATA not tested); you can (as a workaround) add your own
DOCTYPE on export or otherwise modify the exported XML header by modification of
the corresponding function in config/export_customization.php
- the PHP XMLReader module is obscure in many ways and its behaviour changes
in different versions of PHP; it is highly suggested to validate every document
by a separate XML validator before import into InterText - the validation officially
supported by the XMLReader behaves unpredictably and erratically, if it works at all; 
in case of further issues, remove any DOCTYPE as well
- the "id" attributes of elements are parsed and only final numbers are
extracted: two-part numbers for alignable elements (e.g. "12:3") and single
numbers for the other elements; the alignable elements and their parents
(containers) get renumbered when the document structure is changed in the
editor; in the two-level numbering mode, any other elements will just lose their
id-attributes (i.e. will be cut down to any final numbers like the containers,
if there were any); long id-attributes (and other id-s) can be (as a workaround)
restored/re-created on export by the corresponding function defined in
config/export_customization.php; by configuring InterText to use single-level 
numbering of elements exclusively, the IDs of elements (except of the alignable ones)
can be kept, however!
- some versions of "hunalign" are known to fail (segmentation fault) 
with texts larger than ca. 30000 elements (this is not a problem of InterText)


## For more information read...

- INSTALL - installation instructions, set-up, customization and other
administration-related information
- UPDATE.txt - instructions for update
- help.php - user-manual, including details on the functions and principles of
the system
- CHANGELOG - list of changes (added features and fixed bugs)

For the very basic principles and problems of parallel text alignment, you may also refer to the manual of the _InterText Editor_, available (a.o.) from the [project homepage](http://wanthalf.saga.cz/intertext).

## Acknowledgement:

This software and documentation was partly supported from the implementation of the Czech National Corpus project (LM2011023) funded by the _Ministry of Education, Youth and Sports_ of the Czech republic within the framework of _Large Research, Development and Innovation Infrastructures_.

## License:

This software is licensed under the GNU General Public License v3. (http://www.gnu.org/licenses/gpl-3.0.html)

- Copyright (c) 2010-2016 Pavel Vondřička
- Copyright (c) 2010-2016 Charles University in Prague, Faculty of Arts, Institute of the Czech National Corpus


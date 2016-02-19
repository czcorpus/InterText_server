<?php
/*  Copyright (c) 2010-2016 Pavel Vondřička (Pavel.Vondricka@korpus.cz)
 *  Copyright (c) 2010-2016 Charles University in Prague, Faculty of Arts,
 *                          Institute of the Czech National Corpus
 *
 *  This file is part of InterText Server.
 *
 *  InterText Server is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  InterText Server is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with InterText Server.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
require 'settings.php';
require 'header.php';
?>
<div id="help">

<h1>InterText user guide</h1>

<a name="almanager"></a>
<h2>Alignment manager</h2>

<p>Alignment manager is a table listing all alignments accessible to the logged user. (Administrators can get a list of all alignments of one particular text (version) only, using the <a href="#textmanager">text manager</a>.)</p>

<ul>
<li>The first column is used for selection of alignments for batch changes (see <a href="#batch">below</a>).</li>
<li>The second column presents the name of the text, common for all its (language) versions.</li>
<li>The third column lists alignments of every text, showing pairs of versions aligned together (in the form "version1 &lt;=&gt; version2"). The <a href="#aleditor">alignment editor</a> can be opened for a particular alignment by clicking its name</a>.</li>
<li>(Administrators can see additional symbols behind the name of the alignment in the second column: the button <img src="icons/swap.png" alt="[S]"/> swaps the left and right version (side) in the alignment; the switch <img src="icons/merge.png" alt="merge"/> enables or disables the editor to change the structure (i.e. segmentation of alignable elements, e.g. sentences) in the central (pivot) text version (set in the settings of InterText). The latter permission is disabled by default and must be enabled by the administrator for each single alignment. When a new alignment is added (the central text version is aligned with a new text version), this permission is automatically reset (i.e. disabled). It is supposed that changing the structure of text versions already aligned with some other version is generally undesirable, even though InterText automatically takes care of the consistence of all alignments present in the system when doing structural changes in some text. In some projects it cannot be excluded that other alignments of the text version already exist which are not present in InterText at the very moment.)</li>
<li>In the next column, administrators and responsible supervisors (coordinators) can set the current editor of the alignment. Only the currently set editor is allowed to edit the alignment (though the supervisor and the administrators are allowed to edit it anytime as well, in the current configuration).</li>
<li>The following switched marked by the icon <img src="icons/document-edit.png" alt="edit"/> permits (or disables) the editor to edit the texts in the alignment (correct typos, etc.) and change their structure (i.e. segmentation of alignable elements, e.g. sentences) at the same time. This setting concerns both text versions. The ability to change structure of the central (pivot) version must be additionaly enabled by the administrator (see above), otherwise it will be blocked.</li>
<li>Supervisors and administrators can transfer the responsibility for the alignment to another supervisor (or administrator) in the following column. By clicking the name of the currently responsible supervisor, a selection widget appears with the list of all supervisors and administrators. Selecting a new user and confirming the change transfers the responsibility to the new user. Since supervisors can anly access alignment within their own responsibility, the original supervisor will no longer be able to see the the alignment nor take this change back. Administrators have access to all alignments all the time, and they can thus transfer the responsibility freely.</li>
<li>The last column (next to last for administrators) displays the state of the alignment. "Open" alignments can be freely edited by the editor (and supervisor). Other states make the alignment read-only. When the editing is finished and the alignment can be submitted to the corpus, the supervisor can change the state to "finished". After exporting the alignment to the corpus, the administrator locks the alignment by setting the state "closed", which cannot be changed by the supervisors anymore. (The state "finished" can still be changed back to "open" by the supervisor.) Administrators can also lock the alignment by setting the state "blocked" because of any other unspecified reason. The last possible state "remote editor" marks alignments downloaded by their editors into the desktop application <i>InterText editor</i> for off-line processing. Such alignments may be any time updated (overwrited) remotely. This state is set automatically by the application and can only be released by the editor or (by force) by an administrator.</li>
<li>(Administrators can delete the particular alignment by clocking the icon <img src="icons/edit-delete-shred.png" alt="[DELETE]" /> in the very last column.)</li>
</ul>

<a name="batch"></a>
<h3>Applying changes in batch</h3>
<p>Most of the mentioned changes can be applied to several alignments at the same time. The alignments can be selected in the first column of the table. The symbols <img src="icons/add.png" alt="[+]" title="select all"/> and <img src="icons/remove.png" alt="[-]" title="unselect all"/> do select or unselect all alignments at the current page. Selecting alignments on different pages is not possible (use filter and/or change the page size instead!). Changes are then applied according to the following rules:</p>

<ul>
<li>If no alignment is selected, each change only applies to the alignment where it is triggered.</li>
<li>If some change is triggered for one (any) of the selected alignments, the user will be offered to apply it to all the other selected alignments as well. If refused, the change will only be applied to the given alignment separately.</li>
<li>If some alignments are selected, but a change is triggered for an alignment which is not part of the selection, the change will only be applied to this alignment.</li>
<li>If some change is applied in batch, but the user does not have permission to apply the change to some of the selected alignments or the change cannot be applied to them because of some other reason, the change will only be applied to the rest of selected alignments and the user will be informed about all the failed attempts.</li>
</ul>

<p>The following changes can be applied in batch: swapping sides (versions) in the alignment, enabling or disabling changes in the structure of pivot text versions, enabling or disabling editing of text contents in general, change of editor, change of supervisor and change of state of the alignment. Batch delete of alignments is not possible.</p>

<h3>Paging in the alignment manager</h3>
<p>The list of alignments is shown by pages. Moving back and forward in the list is possible by the links "&lt;&lt; previous" and "next &gt;&gt;". The number of alignments shown on one page can be changed by the selector "show" in the top bar. The value can be set to 10, 20, 30, 50 or 100 alignments per page, or paging can be turned off and the complete list of alignments can be shown by using the setting "all". 
</p>

<h3>Sorting the list of alignments</h3>
<p>The table of alignments is by default primarily sorted by the name of the text (alphabetically) and secondarily by the name of the left side (first) text version. The sorting order can be changed by clicking the arrows in the table header. The primary sorting by the text name (normal or reverse aplhabetical) can be both activated and deactivated (by clicking the active arrow again) independently of the secondary sorting key (either the first (left) or second (right) version of the aligned texts!). By deactivating the primary sorting by the text name, the alignments will only be listed by the name of the selected (left or right) text version. Sorting by the name of the editor, supervisor or state is always primary and deactivates the sorting (and grouping) by the name of the text.</p>

<h3>Filtering the list of alignments</h3>
<p>In the first row of the table, there are selectors and a text field for specification of criteria to show only specific alignments. Use the "Filter!" button at the end of the row to filter the list of alignments according to the selected criteria. If the mode if the filter is set to "auto" in the top bar, the changes to selectors are applied immediately. Changes in the text field must be submitted by pressing ENTER.</p>

<a name="newalign"></a>
<h3>Creating new alignments</h3>
<?php if ($USER['type']==$USER_ADMIN) { ?>
<p>Administrators can create new alignments using the option presented in the menu of the alignment manager, when only a list of alignments for <i>one particular</i> text version was selected in the <a href="#textmanager">text manager</a>. Alignments can also be imported by the command-line tool <em>align</em> (run the script without parameters to get help) or while importing texts by the command-line tool <em>import</em> (see <a href="#newtext">adding new texts</a>).</p>

<ul>
<li>In the form displayed, the second version has to be selected to align the currently selected text version with).</li>
<li>The alignment can be imported from a ready file in the TEI XML format, containing the element <em>linkGrp</em> pointing to the aligned versions (in any order/direction, but the attributes <em>toDoc</em> and <em>fromDoc</em> must either contain the filenames of the imported text files or have the form <em>text_name.version_name.any_extension</em> corresponding to the names of the text and the text versions as registered in InterText) and containg a list of <em>link</em> elements with the attribute <em>xtargets</em> (a list of identifiers of elements grouped in one aligned segment separated by a space; first for the target text <em>toDoc</em> and then, separated by a semicolon, a list for the source text <em>srcDoc</em>) and possibly the atrribute <em>status</em>.</li>
<li>In case some segments in the imported alignment file have no <em>status</em> set or their status value is uknown to InterText, the status selected in the form as <em>default status</em> will be set for them.</li>
<li>The choice of the automatic aligner (and a profile) will be applied to all elements not aligned by the imported alignment file. If no alignment file is imported, the whole text will be aligbned automatically by the selected automatic aligner.</li>
<?php } else { ?>
<p>New alignments can only be added by the administrators.</p>

<ul>
<?php } ?>
<li>Currently supported methods of automatic alignment are: a)  <em>hunalign</em> - language independent statistical aligner, which can use a language specific dictionary, if available; b) <em>TCA2</em> - language dependent aligner, requiring a profile (basic dictionary and setting basic parameters of the average ratio of text length, etc.) specific for a particular pair of languages; c) the emergency 1:1 aligner provided by InterText (<em>plain alignment</em>, aligns all elements one-to-one and a possible rest of elements on one side as one-to-zero). The automatic aligners set the status of all aligned segments to "automatic", except of the emergency <em>plain aligner</em> that sets the status to "unconfirmed".</li>
<li>The ckeckbox "do not close log after finishing" can keep the page with the log of the whole alignment process open after the aligner is finished; the user then has to use the link in the menu to get back to the alignment manager. Otherwise, the alignment manager opens automatically when the process is finished.</li>
</ul>

<p>The process of automatic alignment can take several minutes or even tens of minutes (for long texts in TCA2) to run. The progress of the <em>hunalign</em> aligner is not shown in the progress bar, but the information about the currently running stage is displayed.</p>

<a name="aleditor"></a>
<h2>Alignment editor</h2>

<p>The editor page has four main parts in the vertical plan: <em>top bar, editor table, bottom bar</em> and <em>status bar</em>. All operations in the editor are executed immediately in the server database; that implies that the work can be interrupted or terminated any time, without the need to save the alignment or close the editor; on the other hand, there is no way how to undo the executed changes other than making a reverse action manually. Therefore, any changes (especially those requiring confirmation) should be well considered. After finishing your work, it is strongly suggested to log out from the system if working on a public computer not protected by a user password (just closing the browser may not be enough to protect your work from latter "experiments" of other users).</p>

<h3>Editor table</h3>

<ul>
<li>Each page presents only a part of the aligned texts. The text can be browsed by moving back or forward through the pages. The number of segments displayed on one single page can be changed in the <em>top bar</em>.</li>
<li>Each line in the table represents one segment (or position) - a group of aligned elements (i.e. elements corresponding to each other, e.g. sentences).</li>
<li>The leftmost column shows the number of the position (segment) in the whole alignment. Clicking this number can set the position as the current position (cursor) in the alignment - i.e. move the start of the page to the selected position (See <em>setting operation mode</em> on the <a href="#topbar">top bar</a> for more details.)</li>
<li>The second column shows bookmarks. Clicking the symbol <img src="icons/nomark.png" alt="0"/> marks the position with the symbol <img src="icons/mark.png" alt="1"/> and the position can quickly be found from the <a href="#topbar">top bar</a>. Clicking the symbol again removes the mark.</li>
<li>The central pair of columns shows in each row (position) the group of correcponding elements (e.g. sentences) in the two aligned texts. Each single alignable element (e.g. a sentence) starts with the symbol of small blue triangle (arrow). A double triangle (arrow) marks the start of a new element container (e.g. a paragraph) - i.e. it marks e.g. the first sentence in each new paragraph. A tooltip shows the identifier of each element (sentence) when the mouse cursor is hovering above it.</li>
<li>Elements preceded by the icon <img src="icons/changelog.png" alt=[ch]/> have been changed (edited, split or merged) previously. Clicking the icon opens the history of changes (for more details see "Editing text" below).</li>
<li>Buttons for editing the alignment are present at the center of the table: each segment contains four green buttons, two single and two double arrows pointed upwards and downwards, used for moving the text between the positions (see below for explanation of the editing process).</li>
<li>In the very center, there is a narrow column between the pair of the text columns: it contains two additional blue arrows used for moving both texts up and dows simultaneously (see below for details).</li>
<li>The right column shows the marks indicationg the status of each segment. Three states are distinguished: 1. (manually) confirmed segment (marked by the symbol <img src="icons/dialog-ok-apply.png" alt="manual"/>), 2. segment created by the automatic aligner (marked by <img src="icons/automatic.png" alt="automatic alignment"/>), 3. segment aligned by the emergency 1:1 aligner, unknown or generally unconfirmed (marked by <img src="icons/status_unknown.png" alt="plain link"/>).</li>
<li>Clicking the status symbol in the last column allows the editor to manually toggle the status of the individual segments between the states 1 (confirmed) and 3 (unconfirmed). This function can be used for additional revisions, but it is not expected to be used in the normal process of the verification and correction of the automatic alignment.</li>
</ul>

<p><b>Editing alignment</b></p>

<ul>
<li><img src="icons/arrow-up.png" alt="element up"/> The single green arrow pointing upwards moves the first element (sentence) from the current segment to the previous one (one row up). The rest of the text remains unchanged.</li>
<li><img src="icons/arrow-down.png" alt="element down"/> The single green arrow pointing downwards moves the last element (sentence) from the current segment to the next one (one row down). The rest of the text remains unchanged.</li>
<li><img src="icons/arrow-up-double.png" alt="text up"/> The double green arrow pointing upwards moves the whole text one position (row) up (from the current position on; one side only); i.e. the current segment will be merged with the previous segment and all the following text (segments) will be moved one position upwards.</li>
<li><img src="icons/arrow-down-double.png" alt="text down"/> The double green arrow pointing downwards moves the whole text one position (row) down (from the current position on; one side only); i.e. the current segment will become empty (on the current side) and the segments move to the following positions.</li> 
<li><img src="icons/go-up.png" alt="both up"> The blue arrow pointing upwards moves both texts one position up; i.e. the whole current segment will be fully merged with the previous segment on both sides. Clicking the blue arrow is identical to clicking the green double arrows on both sides.</li>
<li><img src="icons/go-down.png" alt="both down"> The blue arrow pointing downwards moves both texts one position (row) down; i.e. an empty segment will be inserted on the current position. Clicking the blue arrow is identical to clicking the green double arrows on both sides.</li>
<li><img src="icons/arrow.png" alt="&gt;"/> Clicking the blue triangle (arrow) at the beginning of each element (sentence) allows the user to move the text (from the current position on, one side only) to any arbitrary position up or down. The new number of the position can be entered in the dialog that appears. In this way, the text on one side can be moved several positions up or down at once without the need to click the double green arrows repeatedly. This function can be efficiently used when there is a gap in one of the text and the (other) side has to be moved by several positions at once. (The system will not allow the user to move the text upwards to positions already occupated by the previous text, of course, but just to the free positions, within the gap.) Confirming the form without entering a valid position number offers the user to add or remove container (e.g. paragraph) break just before the element (sentence) - see below.</li>
</ul>

<p><b>Editing text (correcting typos or mistakes)</b></p>

<p>Typos or other mistakes in the text can be corrected, if the supervisor allows the editor to do it. Every single element (sentence) can be opened for editing by double clicking its contents; after the text is changed, the new contents can be saved (or the changes can be cancelled) by clicking the appropriate one of the buttons that appear under the editing frame.</p>
<p>The changes to each element contents are recorded in a history (changelog), which can be viewed by clicking the icon <img src="icons/changelog.png" alt=[ch]/> appearing in front of all elements that have been changed (edited, split or merged) previously, or which can be permanently displayed for all changed elements by toggling the same icon (switch) in the top bar. A table with the history of all changes (sorted in a reverse chronological order) appears, showing the type of change (with the author and date of the change) followed by the previous state of the contents of the element, as it was like just before the change was made. So, you can follow the changes of each element by reading the table from the bottom up. The history may also show nested tables with a history of elements merged into/appended to the current element (and therefore deleted). By clicking the text of any of the previous versions of the element contents, the current text can be replaced by the older version (after an additional confirmation). (However, no un-splitting or un-merging of elements will be done - this function cannot be used to automatically undo any changes to the text structure.)</p>
<p><b>WARNING!</b> The editing function is not supposed to be freely used to move words between elements (e.g. wrongly segmented sentences). See below for "correcting the text stucture (sentence segmentation)".</p>

<p><b>Correcting the text stucture (sentence and paragraph segmentation)</b></p>

<p>If text editing is permitted, the editor can also change the segmentation of the text into the alignable elements. (Changing the structure of the central (pivot) text versions must be additionally permitted by the administrator - if the central (pivot) text is already aligned with several otehr text versions, it may be undesirable to change its structure.)</p>
<ul>
<li>When an existing element (sentence) has to be split up into two (or more), it must be opened for editing (by a double click) and an empty row (i.e. 2x ENTER) must be inserted between the two (or more) newly desired elements (sentences). After saving the changes, the element (sentence) will be split into two (or several) independent elements, which can be freely moved between the segments.</li>
<li>To merge two (wrongly split) elements into one, they must be both members of one segment. Then the icon <img src="icons/merge.png" alt="merge"/> appears at the end of the first one and clicking it merges the element (sentence) with the following one. The merger must be confirmed in an additional dialog, because this operation can be fully irreversible in cases, when there is another structural element between the two alignable elements, e.g. a paragraph break - such (invisible) element or element break will be completely lost and cannot be recovered even by splitting the newly created element again: paragraph breaks or other structural elements besides the alignable ones cannot be inserted into the structure in InterText.</li>
<li>A paragraph break (parent element) can be inserted or removed by clicking on the blue (double) triangle (arrow) at the beginning of a sentence (element). The first dialog offers moving the sentence to a new position (see above), but confirming the empty form by "OK" opens a second dialog, which offers insertion or deletion (depending on the current state) of a paragraph break at the start of the sentence.</li>
</ul>

<a name="topbar"></a>
<h3>The top bar, settings and operation modes</h3>

<p>The top bar contains two blue arrows on the left and right side; they can be used to paginate through the alignment back and forward. (It is also possible to paginate using the functional keys F7 and F8 to move one page backwards and forwards respectively. Some browsers may ask you for permission to use these keys for this function, however.) The following additional icons and switches are available in the center of the bar:</p>

<ul>
<li>The blue arrows with bar (<img src="icons/go-first.png" alt="go to start" /> and <img src="icons/go-last.png" alt="go to end" />) can be used to jump to the beginning (first page) and end (last page) of the alignment.</li>
<li><img src="icons/go-up.png" alt="[LIST]" /> The blue arrow pointed upwards exits the editor and returns to the list of alignments (see <a href="#almanager">alignment manager</a>).</li>
<li><img src="icons/help-contents.png" alt="help" /> The symbol of book with a question mark links to this user guide.</li>
<li><img src="icons/document-save.png" alt="[EXPORT]" /> The symbol of floppy disk opens a new bar just below the top bar, where the texts and the alignment can be exported and downloaded. The export (download) is activated by selecting the format of choice for the desired text or the alignment. Currently supported formats are: clean XML text (with simple, short identifiers); clean XML text with long identifiers used in the project InterText (configured in settings to contain the document ID in each sentence ID); clean XML text with long identifiers used in the project ECPC (configured in settings to contain the original filename in each sentence ID); XML text with "corresp" attributes for each sentence, pointing to the corresponding elements (sentences) in the other currently aligned text (used e.g. by TCA2); text with segments, as used in ParaConc. The stand-off alignment can only be exported in the TEI XML format, either with short or long (full) identifiers. All texts are exported in the UTF-8 encoding.</li>
<li><a name="realign"></a><img src="icons/automatic.png" alt="[REALIGN]" /> The symbol of wheel can be used to re-align a part of the text by the automatic aligner. The alignment will be applied to all segments beginning with the first one marked  (by status) as "unconfirmed" (even though it may be followed by other confirmed segments!). It means that the whole part of alignment from the first unconfirmed position will be deleted and automatically aligned again. The action must first be confirmed in a dialog. For details see section <a href="#newalign">creating new alignments</a>.) This function can be efficiently used in situations, when one of the texts (usually the translation) is missing a whole long paragraph or even a chapter, which made the initial automatic alignment confused: after manually rearranging and confirming the whole gap the rest of the alignment can be re-aligned to save the editor from correnting all the mistakes created by the automatic aligner in the rest of the text. If the text does not contain several other gaps, the automatic aligner can now align the rest of the text much better. If permitted by the administrator, the editor can also select a different method of automatic alignment at this moment - e.g. for partially translated text with several gaps, the "plain alignment" can be more useful than any automatic aligner (producing more mistakes than useful results in such situations), saving the editor additional effort. (This function can be disabled by the administrator in the settings.)</li>
<li><img src="icons/layer-visible-on.png" alt="[C]" /> The symbol of eye is a switch used to toggle the permanent visibility of the control buttons (arrows) in the editor table. The hidden controls can make the texts easier to read/scan through - they only appear when the mouse cursor hovers above the area of the controls.</li>
<li>The icon <img src="icons/changelog.png" alt=[ch]/> can be used to toggle permanent display of all changes from the history/changelog (see section <i>Editing text</i> above).</li>
<li>The switch "<span class="non11">non-1:1</span>" is used to toggle highlighting segments with element alignment ratio other than 1:1. These segments are usually the most problematic ones.</li>
<li><em>Operation mode selector</em> changes the behaviour of the editor in reaction to every change in alignment (using the control buttons/arrows in the editor table):
<ul>
	<li>The "manual status update" modes are suitable for small changes or revisions of corrected (confirmed) alignments. In this mode no automatic changes to the status of the segments are applied.</li>
	<li>The "auto update status" modes are aimed at users who wish InterText to automatically update the status of the currently changed segment and all previous segment to "confirmed", whenever a change is applied to the alignment (it is expected that the editor checks the alignment continuously from the start to the end and corrects only incorrectly aligned segments - changing some segment then implies that the editor has checked all preceding segments and found no problems in them).</li>
	<li>In the modes with appended "roll" functionality, InterText moves the start of the current page closer to the currently edited position every time a chnage to the alignment is made. The number in the parenthesis indicates how close to the position the page will be moved: "-2" means that the refreshed page will start two positions before the changed one, "-5" means five positions before, and "act." means that the refreshed page will show segments exactly from the currently changed position. The currently changed position (row) is being highlighted with red colour, in order to quickly visually attract the editor back to the current position after the refresh and change of the page.</li>
</ul> The default mode of the editor is the "auto &amp; roll (-2)" mode. After each change the page will newly show segments so that the changed position will be the third row from the top and it will be highlighted by red colour. Status will be updated to "confirmed" for all preceding positions.</li>
<li><em>The "p/p" switch</em> allows the user to change the number of positions (segments) displayed on one page. The default value is 20; values of 10, 50 or 100 positions can be selected (Longer pager means slower refreshes when editing.).</li>
<li>The symbol of bokmarks with arrows back and forward (<img src="icons/go-prev.png" alt="&lt;"/><img src="icons/mark.png"/><img src="icons/go-next.png" alt="&gt;"/>) allows the user to quickly jump back and forth to the previous or next (closest) position bookmarked in the second column.</li>
<li>The symbol <img src="icons/search.png" alt="search"/> opens a new search bar just below the top bar. See section <a href="#searchbar">searching the text</a> for more details.</li>
<li>The symbol of an arrow with a dot <img src="icons/go-jump.png" alt="goto"/> allows the user to quickly jump to any arbitrary position in the alignment. The number of the desired position has to be entered in the dialog which appears.</li>
<li>The symbol <img src="icons/to-check.png" alt="skip to unchecked" /> allows the user to quickly jump to the first unconfirmed position in the alignment (i.e. any segment with status other than "confirmed"). The user can thus quickly continue a previously started work at the position where the corrections were interrupted last time.</li>
<?php if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) { ?>
<li>The symbol of journal <img src="icons/journal.png" alt="[j]" /> is only shown if there is a history of changes to the alignment available (not to be confused with the history of changes to the text!). It opens a page showing the full history (see <a href="#journal">below</a>).</li>
<?php } ?>
</ul>

<h3>Bottom bar</h3>
<p>The bottom bar contains the same blue arrows used to paginate through the alignment back and forth, like the top bar. In addition, in the center there is another special button, which can be used to confirm all segments on the current page (i.e. set their status to "confirmed") and move the editor to the next page. This button can be used in case there has been nothing to be corrected on the current page.</p>

<h3>Status bar</h3>
<p>The status bar shows the approximate current position in the alignment, shown both in the form of per cent and the page number from the number of all pages. It also shows the number of all positions (segments) in the alignment.</p>

<a name="searchbar"></a>
<h3>Searching the text</h3>

<p>The text can be searched from the search bar, activated by the symbol <img src="icons/search.png" alt="search"/> on the <a href="#topbar">top bar</a>. First the mode of search should be selected, a string to be searched for entered and the left or right side (or both) selected for the search. Clicking the left or right arrow allows for jumping back and forth to the previous or next (closest) occurence of the searched element. The search starts from the beginning again when it reaches end of the alignment, or from the end when the beginning is reached in the opposite direction (the user gets a warning notice).</p>

<p>Current search modes:</p>
<ul>
<li>Searching for a "substring" can either be both case and diacritics "insensitive" or the match must be "exact". Parts of words or whole strings of words can be searched in this way.</li>
<?php if (!$DISABLE_FULLTEXT) { ?>
<li>The "fulltext" search is always insensitive to both case and diacritics. It is possible to search for whole words in any of their combination - always within one alignable element (sentence) only, not a whole segment! (WARNING: only words of the length of 4 characters or more can be searched, unless the MySQL database is configured differently!) The option "all words" will search for elements containing all words entered as the search term (in any possible order or distribution in the element/sentence). (Only word forms can be searched for, not lemmas!)</li>
<li>The "custom" search allows for more advanced fulltext search. The sign '+' must precede all word forms that <i>must</i> be present in the element (at the same time); the sign '-' must precede word forms that <i>must not</i> be present in the element (sentence) at the same time; forms without a sign will be considered <i>optional</i>, i.e. only one of them has to present. This search is limited in the same way as the previously described fulltext search (which behaves exactly as the custom search with all forms precedede by a '+' sign). See <a href="http://dev.mysql.com/doc/refman/5.1/en/fulltext-boolean.html" target="_blank">MySQL documentation: "Boolean Full-text Searches"</a> for more details.</li>
<?php } ?>
<li>Searching using regular expressions is available in two forms: as "exact" matching and matching partially "insensitive in ascii" (unfortunately, the case insensitivity does not work well for letters with diacritics in the current versions of the MySQL database). More details about the supported regular expressions can be found in the <a href="http://dev.mysql.com/doc/refman/5.1/en/regexp.html" target="_blank">MySQL documentation</a>.</li>
<li>Searching for an "element ID" allows the user to search for an element with a particular identifier.</li>
<li>Searching for an "empty segment" allows the user to find segments where there is no text on the selected side of the alignment. (The search term entered is completely irrelevant in this search.)</li>
<li>Searching for a "non-1:1 segment" allows the user to quickly jump to segments containing alignments in a non-trivial ratio (i.e. other than 1:1). (Both the search term entered and the choice of left/right side are completely irrelevant in this search.)</li>
<li>Searching for "large segments (>2:2)" allows the user to quickly jump to segments containing alignments with at least two (or more) elements on both sides. (Both the search term entered and the choice of left/right side are completely irrelevant in this search.)</li>
<li>Searching for "changed/edited elements" allows the user to find elements which have been changed (i.e. split, merged or their contents have been edited).</li>
</ul>

<p>The searching capabilities are limited to the capabilities of the <a href="http://www.mysql.com" target="_blank">MySQL</a> database used.</p>

<?php if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) { ?>
<a name="journal"></a>
<h3>History of changes in the alignment</h3>

<p>If logging of all changes to the alignments is turned on in the configuration of InterText (logging of changes to the texts is always done automatically), this history of changes can be shown by clicking the journal icon <img src="icons/journal.png" alt="[j]" /> in the top bar of the editor. It opens a page with a table of all changes to the alignment in a chronological order:</p>

<ul>
<li>The first column shows the type of change illustrated by the corresponding arrow symbol used, and also the name of the language version concerned (if not both).</li>
<li>The second column shows the (original!) number of position where the change was triggered. Clicking the number opens the alignment at the given position. Beware, that the changes also change the position numbers!</li>
<li>The third column shows the "speed" of the user's advance since the last change. If this change happened more then five positions away from the last changed, the number shows the relative speed of advance in positions per minute, supposing the editor is checking the alignment progressively. In such case, a high value in this column may indicate less careful process of verification or even "skipping" of text.</li>
<li>The last two columns show the name of the user and the date and time of the change.</li>
</ul>
<?php } ?>
<a name="textmanager"></a>
<h2>Text manager</h2>

<p>The text manager is only accessible to the administrators. It can be used to add new texts and alignments to the system or to delete them.
<?php if ($USER['type']==$USER_ADMIN) { ?>
The table lists all texts available in the system. The first column shows the name of every text and the second column a list of all its (language) versions. A more detailed list of the versions appears in the second column, when the name of the text is clicked in the first column. In addition, the symbol <img src="icons/document-new.png" alt="[ADD VERSION]"/> appears in the second column, which can be used to import a new (language) version of the selected text to the system. Clicking a name of one particular text version opens the <a href="#almanager">alignment manager</a> listing only existing alignments for the selected text version; a new alignment with another version can also be created here. Clicking the link "[all alignments]" in the top bar opens the alignment manager with the list of all alignments.</p>

<p>The detailed view of text versions shows the following symbols:</p>
<ul>
<li><img src="icons/document-save.png" alt="[EXPORT]" /> The symbol of floppy disk exports the text in a plain XML format, independen on any particular alignment. Short or long identifiers can be selected as an option. Other forms of export are accessible from the <a href="#aleditor">alignment editor</a> or by the command-line tool <em>export</em>.</li>
<li><img src="icons/format-list-ordered.png" alt="[UPDATE]" /> The numbering symbol starts a two-level renumbering of identifiers of all alignable elements (and their containers, e.g. paragraphs) in the form of two numbers separated by a colon: the first parts is the number of the parent element (container), the second part is the number of the alignable element within its parent; e.g. a fifth sentence in the 30th paragraph will get the ID "30:5". The parent elements (containers) get only a single number without the colon, of course. (This action is automatically run every time the text is being exported and its alignable elements do not have unique identifiers.) Before running the operation, the text is checked for nested container/parental elements and if found, the renumbering will only be single level numbering, i.e. the containers/parent will not be numbered. (The check can take a long time under some special conditions!)</li>
<li><img src="icons/flag-red.png" alt="no uniq ids" /> The symbol of a red flag indicates that the text does not have unique identifiers at the moment and they will have to be regenerated before exporting the text. If the text was not imported without unique identifiers, it indicates that the structure of the text has been changed since the import. (The flag is reset when exporting the file in any way, because the renumbering is automatically enforced before each export.)</li>
<li><img src="icons/flag-yellow.png" alt="text changed" /> The symbol of a red flag indicates that the text has been changed (edited) and is not thus identical with the imported text anymore. (The flag is reset when exporting the file in any way.)</li>
<li><img src="icons/edit-delete-shred.png" alt="[DELETE]" /> The symbol for deleting texts is only available for texts which do not take part in any alignment (at the moment). Before deleting a text, it is thus necessary to delete all its alignments.</li>
</ul>

<h3>Paging in the text manager</h3>
<p>The list of texts is shown by pages. Moving back and forward in the list is possible by the links "&lt;&lt; previous" and "next &gt;&gt;". The number of texts shown on one page can be changed by the selector "show" in the top bar. The value can be set to 10, 20, 30, 50 or 100 texts per page, or paging can be turned off and the complete list of texts can be shown by using the setting "all". 
</p>

<a name="newtext"></a>
<h3>Adding a new text or a new text version</h3>

<p>New text can be added by the appropriate option in the menu of the <a href="#textmanager">text manager</a>. New (language) version of a text can be added by clicking the icon <img src="icons/document-new.png" alt="[ADD VERSION]"/> in the opened view of all versions of a particular text. Clicking any of these options opens a form for importing new text files.</p>

<ul>
<li>When adding a completely new text, its name must be entered.</li>
<li>Then the name of the newly added version has to be entered.</li>
<li>A local file with the text must be selected for import (upload to the server). The file must be a valid XML file of any structure, without any undefined entities.</li>
<li>The names of all XML elements containing alignable text must be entered, separated by a space.</li>
<li>DTD validation can optionally be enforced.</li>
</ul>

<p>New texts and text versions can also be imported by the command-line tool <em>import</em></p> (help is displayed when running the script without any parameters). This tool can (unlike the web interface) report details about any possible errors or problems with the import and validity of the imported XML file. In addition, it allows the user to create or import alignment to some other (already imported) version at the same time, by calling the command-line tool <em>align</em>.</p>

<?php } else { ?>
</p>
<?php } ?>

<a name="usermanager"></a>
<h2>User manager</h2>

Administrators can do basic user management (adding, editing and deleting users) by selecting "[users]" in the menu. Other users may change their passwords here.

</div>
</body>
</html>
<?php
$lang['admin']['help_function_calshow'] = <<<EOT
  <h3>What does this do?</h3>
  <p> Extracts a booking list, such as for a holiday letting property, from a
list stored in either a page or a global block and displays it as monthly calendar blocks
color-coded to show busy and free days.  Dates in the past are greyed out.  Weekend days 
(which may be charged differently) are indicated in a different color.</p>

  <h3>How do I use it?</h3>
  <p> There are two parts to using calshow: storing the data, and inserting a tag in the place you wish it to be displayed.

  <h4>storing the data</h4>
<p>Generate the table of data using any plain text editor (windows users: use wordpad rather 
than a word processor). Each entry should have four, pipe-separated fields,
  <pre>        arrival-date|departure-date|name|type</pre>
(an optional <tt>&lt;br /&gt;</tt> may be added after each line but this is dprecated: it is better 
to enclose the whole list in &lt;pre&gt;&lt;/pre&gt; to protect it from wysiwyg editors).</p>
  <p>Decide whether to store it in a global content block or in a normal page.  
If you use a page, this can be viewed by anybody so you will generally not want to include it in 
the indexed namespace of your site.  Calshow will look by default for the page 'calendar'.  If you 
put it in a global content block, it will only be visible via the admin interface, which is usually 
better.</p>
<p> Create the new page or global content block, turn off wysiwyg (if it is active) and paste the data table 
into it. Add &quot;&lt;pre&gt;&quot; in a line at the top of the data, and  &quot;&lt;/pre&gt;&quot;
in a line at the bottom &endash; this protects the table from reformatting by wysiwyg editors if it 
is erroneously opened using one.  Save the block or page.</p> 
  <h4>displaying the table</h4>
  <p> On the page you want the calendar to show, insert<br />
<code>{calshow [show="n"] [skip="m"] [weekdays="w"] [block="bn"|page="pn"]}</code><br />
where: </p>
  <ul>
    <li><tt>&nbsp;n</tt> - the number of months to view - default 12</li>
	<li><tt>&nbsp;m</tt> - the number of months to skip forward and backward - default 6</li>
    <li><tt>&nbsp;w</tt> - the number of weekdays (as distinct from weekend days) - default 5</li>
    <li><tt>bn</tt> - the global content block name, or </li>
	<li><tt>pn</tt> - the page alias</li>
  </ul>
<p>Only one of <tt>block</tt> or <tt>page</tt> can be used: the default is <code>page='calendar'</code>.</p>

<p> Formatting of the calendar is controlled by css.</p>
<p> Some gross errors in the input data are checked for: "out" date earlier than "in" date or excaptionally long booking period will mark the dates as css class="error".</p>
<p> A sample table and code can be seen at http://co-ho.net/calendar-test.html.</p>
EOT;
?>

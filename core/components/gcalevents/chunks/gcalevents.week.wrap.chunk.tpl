[[+includeHeader:eq=`1`:then=`<h2 class="gcalevents_weekview_title">[[%gcalevents.week:ucfirst]] [[+curWeek]]</h2>`:else=``]] 
<table class="gcalevents_weekview_table">
	<caption><div class="prevWeekLink"><a href="[[~[[*id]]]]?w=[[+prevWeek:lt=`10`:then=`0[[+prevWeek]]`:else=`[[+prevWeek]]`]]&y=[[+prevYear]]"><div class="prevWeek">&nbsp;</div> [[%gcalevents.previous_week]]</a></div> <div class="nextWeekLink"><a href="[[~[[*id]]]]?w=[[+nextWeek:lt=`10`:then=`0[[+nextWeek]]`:else=`[[+nextWeek]]`]]&y=[[+nextYear]]">[[%gcalevents.next_week]] <div class="nextWeek">&nbsp;</div></a></div></caption>
	<tr>
    	<th>&nbsp;</th>[[+headers]]
    </tr>
    <tr class="gcalevents_allday_row">
    	<td>&nbsp;</td>[[+alldays]]
    </tr>
	<tr class="gcalevents_normal_row">
    	<td>[[+scale]]</td>[[+wrapper]]
    </tr>
</table>
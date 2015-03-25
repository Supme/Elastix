<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12" id="r1">
        <td width="10%" align="left">
            {$report.LABEL}:
            {$report.INPUT}
        </td>
        <td width="10%" align="left">
            <div id="span">
                {$span.LABEL}:
                {$span.INPUT}
            </div>
        </td>
        <td width="10%" align="right">
            <span class="required">*</span>{$date_start.LABEL}:
            {$date_start.INPUT}
        </td>
        <td width="10%" align="right">
            <span class="required">*</span>{$date_end.LABEL}:
            {$date_end.INPUT}
        </td>
    </tr>
</table>
<hr/>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12" id="r1">
        <td width="33%" align="left">
            <div id="queue_in">
                {$queue_in.LABEL}:
                {$queue_in.INPUT}
            </div>
        </td>
        <td width="33%" align="left">
            <div id="queue_out">
                {$queue_out.LABEL}:
                {$queue_out.INPUT}
            </div>
        </td>
        <td width="33%" align="left">
            <div id="agent">
                {$agent.LABEL}:
                {$agent.INPUT}
            </div>
        </td>
    </tr>
    <tr>
        <td width="33%" align="left">
            <div id="ivr">
                {$ivr.LABEL}:
                {$ivr.INPUT}
            </div>
        </td>
    </tr>
</table>
<center><input class="button" type="submit" name="show" value="{$show}" /></center>


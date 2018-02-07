/**
 * Created by aagafonov on 20.03.14.
 */
var module_name = 'dm_monitoring';

var i = 0;

$(document).ready(function() {

    $('#about_elastix2').click(function() { $('#acerca_de').dialog('open'); });
    $('#acerca_de').dialog({
        autoOpen: false,
        width: 500,
        height: 300,
        modal: true,
        buttons: [
            {
                text: "Close",
                click: function() { $(this).dialog('close'); }
            }
        ]
    });

    loadOper();
    loadStat();

    setInterval(function(){loadOper()}, 3000);
    setInterval(function(){loadStat()}, 10000);

});

function loadStat()
{
    $.post('index.php?menu=' + module_name + '&rawmode=yes', {
            menu:		module_name,
            rawmode:	'yes',
            action:		'loadstat',
            campaign:    document.getElementById("campaign").options[document.getElementById("campaign").selectedIndex].value
        },
        function (respuesta) {
            $(function(){$('#statistic').html((respuesta));});
        })
        .fail(function() {
            alert('Failed to connect to server to run request!');
        });
}

function loadOper()
{
    $.post('index.php?menu=' + module_name + '&rawmode=yes', {
            menu:		module_name,
            rawmode:	'yes',
            action:		'loadoper',
            campaign:    document.getElementById("campaign").options[document.getElementById("campaign").selectedIndex].value
        },
        function (respuesta) {
            $(function(){$('#operators').html((respuesta));});
        })
        .fail(function() {
            alert('Failed to connect to server to run request!');
        });
}
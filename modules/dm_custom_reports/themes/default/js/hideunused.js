/**
 * Created by aagafonov on 20.03.14.
 */

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

    report = document.getElementById("report");
    report.setAttribute("onchange", "changeReport();");
    report = document.getElementById("span");
    report.setAttribute("onchange", "changeSpan();");
    changeReport();
    changeSpan();

});

function show(element) {
    element = document.getElementById(element);
    element.style.display = "block";
    }

function hide(element) {
    element = document.getElementById(element);
    element.style.display = "none";
}

function changeReport() {
    var selectBox = document.getElementById("report");
    var selectedValue = selectBox.options[selectBox.selectedIndex].value;
    if(selectedValue == 'calls') {
        hide('ivr');
        show('agent');
        show('queue_in');
        show('queue_out');
        show('span');
    }
    if(selectedValue == 'oncalls') {
        hide('ivr');
        hide('agent');
        show('queue_in');
        show('queue_out');
        show('span');
    }
    if(selectedValue == 'ivr') {
        show('ivr');
        hide('agent');
        hide('queue_in');
        hide('queue_out');
        show('span');
    }
    if(selectedValue == 'volvo') {
        hide('ivr');
        hide('agent');
        hide('queue_in');
        hide('queue_out');
        show('span');
    }
    if(selectedValue == 'agentlogin') {
        hide('ivr');
        show('agent');
        show('queue_in');
        show('queue_out');
        hide('span');
    }
    if(selectedValue == 'agentbreak') {
        hide('ivr');
        show('agent');
        hide('queue_in');
        hide('queue_out');
        hide('span');
    }
}

function changeSpan() {
    var selectBox = document.getElementById("span");
    var selectedValue = selectBox.options[selectBox.selectedIndex].value;
}
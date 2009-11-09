var objs = new Array();
var order = new Array();
var now = 0;
var c2fstate = -3;

function initc2f() {
    objs = new Array();
    order = new Array();
    now = 0;
    c2fstate = -3;
}
function changeSelection(x) {
    if(objs[x] == true) {
        document.getElementById('o'+x).innerHTML = '';
        document.getElementById(x).style.color = 'black';
        document.getElementById(x).style.backgroundColor = 'white';
        objs[x] = false;
    } else {
        document.getElementById('o'+x).innerHTML = now+1;
        document.getElementById(x).style.color = 'red';
        document.getElementById(x).style.backgroundColor = 'yellow';
        objs[x] = true;
    }
}
function manipulateArray(x) {
    if(objs[x] == true) {
        var nr = document.getElementById('o'+x).innerHTML-1;
        order.splice(nr,1);
        for(var i = nr; i < order.length; i++) {
            document.getElementById('o'+order[i]).innerHTML = i+1;
        }
        now--;
        changeSelection(x);
    } else {
        order[now] = x;
        changeSelection(x);
        now++;
    }
}
function c2f() {
    if(c2fstate == -3) {
        if(document.getElementById("c2fBox") == undefined) {
            alert("This template is not compatible with Click 2 Fill function");
            return;
        }
        document.getElementById("c2fBox").innerHTML = '<textarea id="c2ftxt" rows="3" cols="50">Paste your data here</textarea><div id="c2fs"></div><input type="button" class="button" onclick="c2fScan()" value="Scan/Edit"/>';
        c2fstate = -1;
        var el = document.getElementsByTagName("input");
        for(var i = 0; i < el.length; i++) {
            if(el[i].type == 'text') {
                var functxt = String(el[i].onclick);
                if(functxt.indexOf('c2fstate') != -1) continue;
                el[i].oldonclick = (el[i].onclick)?el[i].onclick:function(){};
                el[i].onclick = function () {
                    this.oldonclick(); if(c2fstate==2 && order.length>0) this.value = copyText();
                };
            }
        }
        el = document.getElementsByTagName("textarea");
        for(i = 0; i < el.length; i++) {
            if(el[i].id != 'c2ftxt') {
                el[i].oldonclick = (el[i].onclick)?el[i].onclick:function(){};
                el[i].onclick = function() {
                    this.oldonclick(); if(c2fstate==2 && order.length>0) this.innerHTML = copyText();
                }
            }
        }
    }
    if(c2fstate <= 0) {
        document.getElementById("c2fBox").style.display = 'block';
        var tmp = document.getElementById("c2ftxt");
        tmp.focus();
        tmp.select();
        c2fstate = -c2fstate;
    } else {
        document.getElementById("c2fBox").style.display = 'none';
        c2fstate = -c2fstate;
    }
}
function c2fScan() {
    if(c2fstate == 1) {
        var words = document.getElementById("c2ftxt").value.split(new RegExp("[, \n\t]"));
        document.getElementById("c2fs").innerHTML = '';
        for(var i = 0; i < words.length; i++) {
            if(words[i].length == 0) continue;
            var id = 'c2f'+i;
            document.getElementById("c2fs").innerHTML += '<div id="'+id+'" onclick="manipulateArray(\''+id+'\')">'+words[i]+'</div><span id="'+'o'+id+'"></span>';
            objs[id] = false;
        }
        document.getElementById("c2fs").style.display = 'block';
        document.getElementById("c2ftxt").style.display = 'none';
        c2fstate = 2;
        now = 0;
        order = new Array();
    } else {
        var tmp = document.getElementById("c2ftxt");
        tmp.style.display = 'block';
        tmp.focus();
        tmp.select();
        document.getElementById("c2fs").style.display = 'none';
        c2fstate = 1;
    }
}
function copyText() {
    var arr = new Array();
    for(var i = 0; i < order.length; i++) {
        arr[i] = document.getElementById(order[i]).innerHTML;
        changeSelection(order[i]);
    }
    order = new Array();
    now = 0;
    return(arr.join(' '));
}
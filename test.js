/*
  ApiTest by Steve Lee
  Version: 0.1
  Homepage: http://straight-street.com/apitest.html
  Copyright (c) 2009-2012, Garry Paxton
  Code licensed under the BSD License:
  http://straight-street.com/licence.txt
*/

if (!Array.prototype.forEach)
{
  Array.prototype.forEach = function(fun /*, thisp*/)
  {
    var len = this.length >>> 0;
    if (typeof fun !== "function")
      throw new TypeError();

    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in this)
        fun.call(thisp, this[i], i, this);
    }
  };
}

if (!Array.prototype.map)
{
  Array.prototype.map = function(fun /*, thisp*/)
  {
    var len = this.length >>> 0;
    if (typeof fun !== "function")
      throw new TypeError();

    var res = new Array(len);
    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in this)
        res[i] = fun.call(thisp, this[i], i, this);
    }

    return res;
  };
}

function getURLArgs(url)
{
    var args = url.split('?')[1]; // tsk, making lots of assumptions here
    var pairs = args.split('&'); 
    var obj = {};
    for (i=0; i<pairs.length; i++)
    {
        var pair = pairs[i].split('='); // more assumptions
        obj[decodeURI(pair[0])] = ((pair[1] !== undefined) ? decodeURIComponent(pair[1]) : '');
    }
    return obj;
}

function resetPage()
{
    var theForm = document.getElementById('the_form');
    document.getElementById('symbols').innerHTML = '';
}

function initPage()
{
	var args = getURLArgs(''+window.location);
    if (args.find !== undefined)
    {
        getSymbols( args.find );
    }
    else if (args.tag !== undefined)
    {
        getTag( args.tag );
    }
}

function submitForm()
{
    var theForm = document.getElementById('the_form');
    var find = (theForm.find.value); // TODO sanitise thie UTF-8
    getSymbols(find);
}

function requestJSON(url)
{
    var SCRID = 'jsonp-script';
    var head = document.getElementsByTagName('head')[0];
    var scr = document.getElementById(SCRID);
    if (scr)
    {
        head.removeChild(scr);
    }
    var js = document.createElement("script");
    js.src = url;
    js.type = "text/javascript"; // should be application/javascript but IE sulks
    js.id = SCRID;
    head.appendChild(js);       
}

/*
function URLExists(url)
{
    var xhrObj = new XMLHttpRequest();
    xhrObj.open("HEAD", url, false); 
    xhrObj.send(null);
    return (xhrObj.readyState===4 &&  xhrObj.status===200);
}
*/

function showSymbols(obj)
{
     function makeImage(src, width, height, title, error)
    {
        var widthc = (width) ? ' width="'+escape(width)+'"' : '';
        var heightc = (height) ? ' height="'+escape(height)+'"' : '';
        var titlec = (title) ? ' title="'+title+'"' : '';
        var errorc = (error) ? ' onerror="'+error+'"' : '';
        return '<img src="'+encodeURI(src) + '"' + widthc + heightc + titlec + errorc + '" />';
    }
    function makeSVGImage(src, width, height, title, error)
    {
        var widthc = (width) ? ' width="'+escape(width)+'"' : '';
        var heightc = (height) ? ' height="'+escape(height)+'"' : '';
        var titlec = (title) ? ' title="'+title+'"' : '';
        var errorc = (error) ? ' onerror="'+error+'"' : '';
        return '<object type="image/svg+xml" data="'+encodeURI(src)+'" '+widthc + heightc + titlec + errorc + '>Image not available or SVG not supported</object>';
    }
    function makeLink(href, content, title)
    {
        var titlec = (title) ? 'title="'+title+'"' : '';
        return '<a href="'+encodeURI(href)+'" '+titlec+'>'+content+'</a>';
    }

    function supportsSVG()
    {
        try
        {
            var i = document.createElement('object');
            i.type='image/svg+xml';
            i.data='http://straight-street.com/img/apitest.svg';
            i.title = name;
        }
        catch (e)
        {
            return false;
        }
        return true;
    }
    
    function supportsWMF()
    {
        try
        {
            var i = document.createElement('img');
            i.src='http://straight-street.com/img/apitest.wmf';
            i.width=0;
            i.height=0;
            document.appendChild(i);
            document.removeChild(i);
        }
        catch (e)
        {
            return false;
        }
        return true;
    }
    
    resetPage();
    
    var divSymbols = document.getElementById('symbols');
    
    function mkLinks(obj)
    {
       var s = '';
       for (n in obj)
       {
       	 s+='<a href="'+window.location.pathname+'?find='+encodeURIComponent(n)+'">'+n+'</a><br/>'+"\n";
       } 
       return s;	
    }
    
    var string = '<table border="1"><thead><tr><th>Name</th><th>Rated</th><th>tags</th><th>Thumb</th>';
    if (supportsSVG())
    {
        string += '<th>SVG</th>';
    }
    if (supportsWMF())
    {
        string += '<th>WMF</th>';
    }
//    string += '<th>PNG</th>';
    string += '</tr></thead><tbody>\n'; // we use innerHTML rather than direct DOM  calls as they are much slower
    var media = obj.symbols
    for (i=0; i<media.length; i++)
    {
        var symbol = media[i];

        string +='<tr>\n';
        string += '<td>'+symbol.name+'</td>\n';
        //for (t=0; t<symbol.tagNames.length; t++) 
        //    symbol.tagNames[t]=encodeURIComponent(symbol.tagNames[t]);
        //string += '<td>'+symbol.tagNames.join('<br/>')+'</td>\n';
        string += '<td>'+((symbol.rated==0)?'':'Yes')+'</td>\n';
        string += '<td>'+mkLinks(symbol.tags)+'</td>\n';
        string += '<td>'+makeImage(symbol.thumbnailURL, 40, 40)+'</td>\n';
        var onError = 'this.parentNode.parentNode.innerHTML=\'Image not available\';';
        if (supportsSVG())
        {
            if (true || URLExists(symbol.imageSVGURL))
            {
                string +=  '<td>'+makeLink(symbol.imageSVGURL, makeSVGImage(symbol.imageSVGURL, 200, 200, '', onError), 'click for full size image')+'<br/>';
                string += makeLink(symbol.imageSVGURL,'Full size svg');
                string += '</td>\n';
            }
            else
            {
                string += '<td></td>\n';
            }
        }
        
        if (supportsWMF())
        {
            if (true || URLExists(symbol.imageWMFURL))
            {
                string +=  '<td>'+makeLink(symbol.imageWMFURL, makeImage(symbol.imageWMFURL, 200, 200, '', onError), 'click for full size image')+'<br/>';
                string += makeLink(symbol.imageWMFURL,'Full size wmf');
                string += '</td>\n';
            }
            else
            {
                string += '<td></td>\n';
            }
        }
        
/*        if (true || URLExists(symbol.imagePNGURL))
        {
            string +=  '<td>'+makeLink(symbol.imagePNGURL, makeImage(symbol.imagePNGURL, 200, 200, '', onError), 'click for full size image')+'<br/';
            string += makeLink(symbol.imagePNGURL,'Full size png');
            string += '</td>\n';
        }
        else
        {
             string += '<td></td>\n';
        }
*/
        string +='</tr>\n';

    }
    string += '</tbody></table>';
    document.getElementById('symbols').innerHTML = string;
    
    document.getElementById('count').innerHTML = ''+escape(obj.totalItemCount)+' symbols - page ' +escape(obj.page) + ' of ' + escape(obj.pageCount);

    var theForm = document.getElementById('the_form');
    var nextBtn = theForm.next;
    if (obj.nextURL)
    {
        var args = getURLArgs(obj.nextURL);
        var path = obj.nextURL.split('?')[0].split('/'); 
        var find = path[path.length-3];
        var page = path[path.length-2];
        var pagesize = path[path.length-1];
        var code = 'getSymbols("'+escape(find)+'", '+ parseInt(page)+');';
        nextBtn.setAttribute('onclick', code);
        nextBtn.disabled = false;
        }
    else
    {
        nextBtn.disabled = true;
    }
}

function showTag(obj)
{
    resetPage();
    
    var s = '<p>Symbols with tag = \''+obj.tag.name+'\'</p><p>';
    for (n in obj.tag.media)
    {
    	s += n+'<br/>\n';
    }
    s += '</p>';
    document.getElementById('symbols').innerHTML = s;
}

function getTag( tag, page )
{
    page = page || 0;
    var theForm = document.getElementById('the_form');
    theForm.find.value = tag;
	    
    var url = '/api/tag/EN/'+tag+'?page='+page+'&appid=SSApiTest';
    requestJSON('http://straight-street.com'+url+'&callback=showTag');
}

function getSymbols( find, page )
{
    page = page || 0;
    var theForm = document.getElementById('the_form');
    theForm.find.value = find;
    var url = 'http://straight-street.com/api/symbols/EN/'+find+'/'+page+'/'+'15'+'?&appid=SSApiTest';
    requestJSON(url+'&callback=showSymbols');
/*     var xhrObj = new XMLHttpRequest();
        function xhrHandler()
        {
            if (xhrObj.readyState === 4 && xhrObj.status === 200)
            {
                var obj = JSON.parse(xhrObj.responseText);
                showSymbols(obj);	
            }
        }
        xhrObj.onreadystatechange = xhrHandler;
        xhrObj.open("GET", url, true);
        xhrObj.send("");
*/
}

window.onload=initPage;
function get_html_translation_table(table,quote_style){var entities={},histogram={},decimal=0,symbol='';var constMappingTable={},constMappingQuoteStyle={};var useTable={},useQuoteStyle={};useTable=(table?table.toUpperCase():'HTML_SPECIALCHARS');useQuoteStyle=(quote_style?quote_style.toUpperCase():'ENT_COMPAT');constMappingTable[0]='HTML_SPECIALCHARS';constMappingTable[1]='HTML_ENTITIES';constMappingQuoteStyle[0]='ENT_NOQUOTES';constMappingQuoteStyle[2]='ENT_COMPAT';constMappingQuoteStyle[3]='ENT_QUOTES';if(!isNaN(useTable)){useTable=constMappingTable[useTable];}
if(!isNaN(useQuoteStyle)){useQuoteStyle=constMappingQuoteStyle[useQuoteStyle];}
if(useTable=='HTML_SPECIALCHARS'){entities['38']='&amp;';if(useQuoteStyle!='ENT_NOQUOTES'){entities['34']='&quot;';}
if(useQuoteStyle=='ENT_QUOTES'){entities['39']='&#039;';}
entities['60']='&lt;';entities['62']='&gt;';}else if(useTable=='HTML_ENTITIES'){entities['38']='&amp;';if(useQuoteStyle!='ENT_NOQUOTES'){entities['34']='&quot;';}
if(useQuoteStyle=='ENT_QUOTES'){entities['39']='&#039;';}
entities['60']='&lt;';entities['62']='&gt;';entities['160']='&nbsp;';entities['161']='&iexcl;';entities['162']='&cent;';entities['163']='&pound;';entities['164']='&curren;';entities['165']='&yen;';entities['166']='&brvbar;';entities['167']='&sect;';entities['168']='&uml;';entities['169']='&copy;';entities['170']='&ordf;';entities['171']='&laquo;';entities['172']='&not;';entities['173']='&shy;';entities['174']='&reg;';entities['175']='&macr;';entities['176']='&deg;';entities['177']='&plusmn;';entities['178']='&sup2;';entities['179']='&sup3;';entities['180']='&acute;';entities['181']='&micro;';entities['182']='&para;';entities['183']='&middot;';entities['184']='&cedil;';entities['185']='&sup1;';entities['186']='&ordm;';entities['187']='&raquo;';entities['188']='&frac14;';entities['189']='&frac12;';entities['190']='&frac34;';entities['191']='&iquest;';entities['192']='&Agrave;';entities['193']='&Aacute;';entities['194']='&Acirc;';entities['195']='&Atilde;';entities['196']='&Auml;';entities['197']='&Aring;';entities['198']='&AElig;';entities['199']='&Ccedil;';entities['200']='&Egrave;';entities['201']='&Eacute;';entities['202']='&Ecirc;';entities['203']='&Euml;';entities['204']='&Igrave;';entities['205']='&Iacute;';entities['206']='&Icirc;';entities['207']='&Iuml;';entities['208']='&ETH;';entities['209']='&Ntilde;';entities['210']='&Ograve;';entities['211']='&Oacute;';entities['212']='&Ocirc;';entities['213']='&Otilde;';entities['214']='&Ouml;';entities['215']='&times;';entities['216']='&Oslash;';entities['217']='&Ugrave;';entities['218']='&Uacute;';entities['219']='&Ucirc;';entities['220']='&Uuml;';entities['221']='&Yacute;';entities['222']='&THORN;';entities['223']='&szlig;';entities['224']='&agrave;';entities['225']='&aacute;';entities['226']='&acirc;';entities['227']='&atilde;';entities['228']='&auml;';entities['229']='&aring;';entities['230']='&aelig;';entities['231']='&ccedil;';entities['232']='&egrave;';entities['233']='&eacute;';entities['234']='&ecirc;';entities['235']='&euml;';entities['236']='&igrave;';entities['237']='&iacute;';entities['238']='&icirc;';entities['239']='&iuml;';entities['240']='&eth;';entities['241']='&ntilde;';entities['242']='&ograve;';entities['243']='&oacute;';entities['244']='&ocirc;';entities['245']='&otilde;';entities['246']='&ouml;';entities['247']='&divide;';entities['248']='&oslash;';entities['249']='&ugrave;';entities['250']='&uacute;';entities['251']='&ucirc;';entities['252']='&uuml;';entities['253']='&yacute;';entities['254']='&thorn;';entities['255']='&yuml;';}else{throw Error("Table: "+useTable+' not supported');return false;}
for(decimal in entities){symbol=String.fromCharCode(decimal);histogram[symbol]=entities[decimal];}
return histogram;}

function htmlspecialchars(string,quote_style){var histogram={},symbol='',tmp_str='',entity='';tmp_str=string.toString();if(false===(histogram=get_html_translation_table('HTML_SPECIALCHARS',quote_style))){return false;}
for(symbol in histogram){entity=histogram[symbol];tmp_str=tmp_str.split(symbol).join(entity);}
return tmp_str;}

String.prototype.trim = function() {
	return this.replace(/^\s+/, '').replace(/\s+$/, '');
};

jQuery.fn.first = function() {
	return jQuery(this).filter(function(index){return (index == 0);});
};

jQuery.fn.makeAbsolute = function(rebase) {
	 return jQuery(this).each(function() {
		  var el = jQuery(this);
		  var pos = el.position();
		  el.css({ position: "absolute",
				marginLeft: 0, marginTop: 0,
				top: pos.top, left: pos.left });
		  if (rebase)
				el.remove().appendTo("body");
	 });
}

jQuery.fn.pause = function(duration) {
	return jQuery(this).animate({ dummy: 1 }, duration);
};

// minified json2.js library (stringify function only) from json.org (http://www.json.org/js.html)
if(!this.JSON){this.JSON={};}
(function(){function f(n){return n<10?'0'+n:n;}
if(typeof Date.prototype.toJSON!=='function'){Date.prototype.toJSON=function(key){return isFinite(this.valueOf())?this.getUTCFullYear()+'-'+
f(this.getUTCMonth()+1)+'-'+
f(this.getUTCDate())+'T'+
f(this.getUTCHours())+':'+
f(this.getUTCMinutes())+':'+
f(this.getUTCSeconds())+'Z':null;};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf();};}
function quote(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==='string'?c:'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+string+'"';}
function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==='object'&&typeof value.toJSON==='function'){value=value.toJSON(key);}
if(typeof rep==='function'){value=rep.call(holder,key,value);}
switch(typeof value){case'string':return quote(value);case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}
gap+=indent;partial=[];if(Object.prototype.toString.apply(value)==='[object Array]'){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||'null';}
v=partial.length===0?'[]':gap?'[\n'+gap+
partial.join(',\n'+gap)+'\n'+
mind+']':'['+partial.join(',')+']';gap=mind;return v;}
if(rep&&typeof rep==='object'){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==='string'){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}
v=partial.length===0?'{}':gap?'{\n'+gap+partial.join(',\n'+gap)+'\n'+
mind+'}':'{'+partial.join(',')+'}';gap=mind;return v;}}
if(typeof JSON.stringify!=='function'){JSON.stringify=function(value,replacer,space){var i;gap='';indent='';if(typeof space==='number'){for(i=0;i<space;i+=1){indent+=' ';}}else if(typeof space==='string'){indent=space;}
rep=replacer;if(replacer&&typeof replacer!=='function'&&(typeof replacer!=='object'||typeof replacer.length!=='number')){throw new Error('JSON.stringify');}
return str('',{'':value});};}
}());

// ezcookie
(function($){dOptions={expires:365,domain:'',secure:false,path:'/'}
$.cookie=function(cookieName){var value=null;if(document.cookie&&document.cookie!=''){var cookies=document.cookie.split(';');for(var i=0;i<cookies.length;i++){var cookie=jQuery.trim(cookies[i]);if(cookie.substring(0,cookieName.length+1)==(cookieName+'=')){value=decodeURIComponent(cookie.substring(cookieName.length+1));break;}}}
try{return $.parseJSON(value);}catch(e){return value;}}
$.subCookie=function(cookie,key){var cookie=$.cookie(cookie);if(!cookie||typeof cookie!='object'){return null;}
return cookie[key];}
$.setCookie=function(cookieName,cookieValue,options){var options=typeof options!='undefined'?$.extend(dOptions,options):dOptions;var path='; path='+(options.path);var domain='; domain='+(options.domain);var secure=options.secure?'; secure':'';if(cookieValue&&(typeof cookieValue=='function'||typeof cookieValue=='object')){cookieValue=JSON.stringify(cookieValue);}
cookieValue=encodeURIComponent(cookieValue);var date;if(typeof options.expires=='number'){date=new Date();date.setTime(date.getTime()+(options.expires*24*60*60*1000));}else{date=options.expires;}
var expires='; expires='+date.toUTCString();document.cookie=[cookieName,'=',cookieValue,expires,path,domain,secure].join('');}
$.setSubCookie=function(cookie,key,value,options){var options=typeof options!='undefined'?$.extend(dOptions,options):dOptions;var existingCookie=$.cookie(cookie);var cookieObject=existingCookie&&typeof existingCookie=='object'?existingCookie:{};cookieObject[key]=value;$.setCookie(cookie,cookieObject,options);}
$.removeSubCookie=function(cookie,key){var cookieObject=$.cookie(cookie);if(cookieObject&&typeof cookieObject=='object'&&typeof cookieObject[key]!='undefined'){delete cookieObject[key];$.setCookie(cookie,cookieObject);}}
$.removeCookie=function(cookie){$.setCookie(cookie,'',{expires:-1});}
$.clearCookie=function(cookie){$.setCookie(cookie,'');}})(jQuery);

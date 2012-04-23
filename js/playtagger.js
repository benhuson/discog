// This version of Playtagger has been customized by Dan Coulter of Blogs for Bands for use with his Discography plugin.
if(typeof(Delicious) == 'undefined') Delicious = {}
Delicious.Mp3 = {
	playimg: null,
	player: null,
	go: function() {
		jQuery("a.delicious").before("<img src='http://static.delicious.com/img/play.gif' title='Listen' class='delicious' />");
		var imgs = jQuery("img.delicious");
		for ( i = 0; i < imgs.length; i++ ) {
			var img = imgs.eq(i);
			link = img.next();
			img.click(Delicious.Mp3.makeToggle(img[0], link.attr("href")));
			img.css("height", "12");
			img.css("width", "12");
			img.css("border", "none");
			img.css("cursor", "pointer");
		}
	},
	toggle: function(img, url) {
		if (Delicious.Mp3.playimg == img) Delicious.Mp3.destroy()
		else {
			if (Delicious.Mp3.playimg) Delicious.Mp3.destroy()
			var a = img.nextSibling, c=''
			if (document.defaultView && document.defaultView.getComputedStyle) c = document.defaultView.getComputedStyle(a,null).getPropertyValue('color')
			else if (a.currentStyle) c = a.currentStyle['color']
			c = c.replace(/^rgb|[ ()]/g, '')
			var hex = c.match(/^#?([a-f0-9]{6})$/), hexSm = c.match(/^#?([a-f0-9]{3})$/), rgb = c.match(/^([0-9]+),([0-9]+),([0-9]+)$/)
			if (rgb) { var c16 = "0123456789abcdef", c=''; for(var i=1,d;d=rgb[i];i++) { var j=d%16; c=c+c16.charAt((d-j)/16)+c16.charAt(j) } }
			else if (hex) c = hex[1]
			else if (hexSm) c = hexSm[1].charAt(0) + hexSm[1].charAt(0) + hexSm[1].charAt(1) + hexSm[1].charAt(1) + hexSm[1].charAt(2) + hexSm[1].charAt(2)
			else c = "000000"
			img.src = 'http://static.delicious.com/img/stop.gif'; Delicious.Mp3.playimg = img;
			Delicious.Mp3.player = document.createElement('span')
			Delicious.Mp3.player.innerHTML = '<object style="vertical-align:bottom;margin-right:0.2em" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"' +
			'codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"' +
			'width="100" height="14" id="player" align="middle">' +
			'<param name="wmode" value="transparent" />' +
			'<param name="allowScriptAccess" value="sameDomain" />' +
			'<param name="flashVars" value="theLink='+url+'&amp;fontColor='+c+'" />' +
			'<param name="movie" value="http://static.delicious.com/swf/playtagger.swf" /><param name="quality" value="high" />' +
			'<embed style="vertical-align:bottom;margin-right:0.2em" src="http://static.delicious.com/swf/playtagger.swf" flashVars="theLink='+url+'&amp;fontColor='+c+'"'+
			'quality="high" wmode="transparent" width="100" height="14" name="player"' +
			'align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash"' +
			' pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>'
			img.parentNode.insertBefore(Delicious.Mp3.player, img.nextSibling)
	}},
	destroy: function() {
		Delicious.Mp3.playimg.src = 'http://static.delicious.com/img/play.gif'; Delicious.Mp3.playimg = null
		Delicious.Mp3.player.removeChild(Delicious.Mp3.player.firstChild); Delicious.Mp3.player.parentNode.removeChild(Delicious.Mp3.player); Delicious.Mp3.player = null
	},
	makeToggle: function(img, url) { return function(){ Delicious.Mp3.toggle(img, url) }}
}

Delicious.addLoadEvent = function(f) { var old = window.onload
	if (typeof old != 'function') window.onload = f
	else { window.onload = function() { old(); f() }}
}

Delicious.addLoadEvent(Delicious.Mp3.go)


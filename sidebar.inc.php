<div id="sidebar">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<?php echo'
<label for="searchname">',__("Search by Name:"),'</label><br>
<input class="search" id="searchname" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<span id="advsrch">',__("Advanced"),'</span>
<br>
<form action="search.php" method="post" class="hide advsearch">
<br>
<label for="searchadv">',__("Advanced Search:"),'</label><br>
<input class="search" id="searchadv" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
<select name="key">
	<option value="label">',__("Label"),'</option>
	<option value="ctag">',__("Custom Tag"),'</option>
	<option value="serial">',__("Serial Number"),'</option>
	<option value="asset">',__("Asset Tag"),'</option>
	<option value="owner">',__("Owner"),'</option>
	<option value="ip">',__("PrimaryIP"),'</option>
</select>';
?>
<div class="ui-icon ui-icon-close"></div>
</form>
  <script type="text/javascript">
	function addlookup(inputobj,lookuptype){
		// clear any existing autocompletes
		if(inputobj.hasClass('ui-autocomplete-input')){inputobj.autocomplete('destroy');}
		// clear out previous search arrows
		inputobj.next('.text-arrow').remove();
		// Position the arrow
		var inputpos=inputobj.position();
		var arrow=$('<div />').addClass('text-arrow');
		arrow.click(function(){
			inputobj.autocomplete("search", "");
		});
		// add the autocomplete
		inputobj.autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				$.getJSON('scripts/ajax_search.php?'+lookuptype, {q: req.term}, function(data){
					var suggestions=[];
					$.each(data, function(i,val){
						suggestions.push(val);
					});
					add(suggestions);
				});
			},
			open: function(){
				$(this).autocomplete("widget").css({'width': inputobj.width()+6+'px'});
			}
		}).next().after(arrow);
		arrow.css({'top': inputpos.top+'px', 'left': inputpos.left+inputobj.width()-(arrow.width()/2)});
	}
	$('#advsrch, #searchadv ~ .ui-icon.ui-icon-close').click(function(){
		var here=$(this).position();
		$('#searchadv, #searchname').val('');
		$('#searchadv').parents('form').height(here.top).toggle('slide',200).removeClass('hide');
		if($('#searchadv').hasClass('ui-autocomplete-input')){$('#searchadv').autocomplete('destroy');}
		if($(this).text()=='<?php echo __("Advanced");?>'){$(this).text('<?php echo __("Basic");?>');$('#searchadv ~ select[name="key"]').trigger('change');}else{$(this).text('<?php echo __("Advanced");?>');}
	});
  </script>
  <script type="text/javascript" src="scripts/mktree.js"></script> 
  <script type="text/javascript" src="scripts/konami.js"></script> 
	<hr>
<?php


	function buildmenu($menu){
		$level='';
		foreach($menu as $key => $item){
			$level.="<li>";
			if(!is_array($item)){
				$level.="$item";
			}else{
				$level.="<a>$key</a><ul>";
				$level.=buildmenu($item);
				$level.="</ul>";
			}
			$level.="</li>";
		}
		return $level;
	}

	$menu=buildmenu(array_merge_recursive($rmenu,$rrmenu,$camenu,$wamenu,$samenu));
	
	print "<ul class=\"nav\">$menu</ul>
	<hr>
	<div>
	<a href=\"index.php\">".__("Home")."</a>\n";

	$lang=GetValidTranslations();
	//strip any encoding info and keep just the country lang pair
	$locale=explode(".",$locale);
	$locale=$locale[0];
	echo '	<div class="langselect hide">
		<label for="language">Language</label>
		<select name="language" id="language" current="'.$locale.'">';
		foreach($lang as $cc => $translatedname){
			// This is for later. For now just display list
			//$selected=""; //
			if($locale==$cc){$selected=" selected";}else{$selected="";}
			print "\t\t\t<option value=\"$cc\"$selected>$translatedname</option>";
		}
	echo '		</select>
	</div>';

	$container = new Container();
	echo $container->BuildMenuTree();
	
?>
	</div>
<script type="text/javascript">
if (typeof jQuery == 'undefined') {
	alert('jQuery is not loaded');
	window.location.assign("http://opendcim.org/wiki/index.php?title=Errors:Operational");
}
if (typeof jQuery.ui == 'undefined') {
	alert('jQueryUI is not loaded');
	window.location.assign("http://opendcim.org/wiki/index.php?title=Errors:Operational");
}

$("#sidebar .nav a").each(function(){
	var loc=window.location;
	if($(this).attr("href")=="<?php echo basename($_SERVER['PHP_SELF']);?>" || $(this).attr("href")==loc.href.substr(loc.href.indexOf(loc.host)+loc.host.length+1)){
		$(this).addClass("active");
		$(this).parentsUntil("#ui-id-1","li").children('a:first-child').addClass("active");
	}
});
$("#sidebar .nav").menu();

$('#searchname').width($('#sidebar').innerWidth() - $('#searchname ~ button').outerWidth());
addlookup($('#searchname'),'name');
$('#searchadv ~ select[name="key"]').change(function(){
	addlookup($('#searchadv'),$(this).val())
}).height($('#searchadv').outerHeight());

function resize(){
	// This function will run each 500ms for 2.5s to account for slow loading content
	var count=0;
	subresize();
	var longload=setInterval(function(){
		subresize();
		if(count>4){
			clearInterval(longload);
			window.resized=true;
		}
		++count;
	},500);

	function subresize(){
		// page width is calcuated different between ie, chrome, and ff
		$('#header').width(Math.floor($(window).outerWidth()-(16*3))); //16px = 1em per side padding
		var widesttab=0;
		// make all the tabs on the config page the same width
		$('#configtabs > ul ~ div').each(function(){
			widesttab=($(this).width()>widesttab)?$(this).width():widesttab;
		});
		$('#configtabs > ul ~ div').each(function(){
			$(this).width(widesttab);
		});
		var pnw=$('#pandn').outerWidth(),hw=$('#header').outerWidth(),maindiv=$('div.main').outerWidth(),
			sbw=$('#sidebar').outerWidth(),width,mw=$('div.left').outerWidth()+$('div.right').outerWidth()+20,
			main,cw=$('.main > .center').outerWidth();
		widesttab+=58;
		// find widths
		width=(cw>mw)?cw:mw;
		main=(pnw>width)?pnw:width; // Find the largest width of possible content in maindiv
		main+=12; // add in padding and borders
		width=((main+sbw)>hw)?main+sbw:hw; // find the widest point of the page

		// The math just isn't adding up across browsers and FUCK IE
		if((main+sbw)<width){ // page is larger than content expand main to fit
			$('div.main').width(width-sbw-12); 
		}else{ // page is smaller than content expand the page to fit
			$('div.main').width(width-sbw-12); 
			$('#header').width(width+4);
			$('div.page').width(width+6);
		}

		// If the function MoveButtons is defined run it
		if(typeof movebuttons=='function'){
			movebuttons();
		}
	}
}
$(document).ready(function(){
	resize();
	// redraw the screen if the window size changes for some reason
	$(window).resize(function(){
		if(this.resizeTO){ clearTimeout(this.resizeTO);}
		this.resizeTO=setTimeout(function(){
			resize();
		}, 500);
	});
	$('#header').append($('.langselect'));
	var top = (($("#header").height() / 2)-($(".langselect").height() / 2));
	$(".langselect").css({"top": top+"px", "right": "40px", "z-index": "99", "position": "absolute"}).removeClass('hide').appendTo("#header");
	$("#language").change(function(){
		$.ajax({
			type: 'POST',
			url: 'scripts/ajax_language.php',
			data: 'sl='+$("#language").val(),
			success: function(){
				// new cookie was set. reload the page for the translation.
				location.reload();
			}
		});

	});
});

</script>

</div>


<noscript>
<div id="gandalf"><div>
                                  .--.
                                 ,' |
                                 '  |
                               ,'    \
  __   __                     .'      |
  \ \ / /__ _  _              '       |
   \ V / _ \ || |            |        |              -.
    |_|\___/\_,_|         ___'        |             / |--
                     _,,-'            '--'''--._    | || |.-'\
                    /        ..._______         `:. |       ,'
                   | _,.,---'|         `.`^`--..   '.      /
 ___ _         _ _ `'      | | <o>   <o> | |    ``''`     /
/ __| |_  __ _| | |      .' ,;      \    |  \      |     /
\__ \ ' \/ _` | | |     /    \    (__)   /   `.    |    |
|___/_||_\__,_|_|_|  ,-'      | ________       \   ;    |
                    |         )/ _.--.__ \     /  /    /
                     `-._   ,./_/ `--'  \_\   /   |__ /
    _  _     _           '.'|              | '  ,'   `
   | \| |___| |_            |              |    (      \
   | .` / _ \  _|            |             |    (       \
   |_|\_\___/\__|             \            |    '`.._   /
                              |            |    /  / `./
                              \           /    |   |
   ___                         `|        |     |  /
  | _ \__ _ ______              |       /      /  |
  |  _/ _` (_-<_-<              \   _.-`      |  /
  |_| \__,_/__/__/               \./          |  |
                                              /  /
                                             |   |
                                             /  /
                                            /   |
                                            |  |
                                           /   /
                                           |  .'
                                           |  |
                                          |  .'
                                          '  |
                                          | |
                                         /  /
                                        |_,'

</div><p>Please enable javascript to continue</p></div>

</noscript>

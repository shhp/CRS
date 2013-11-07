function new_tag(tag){
	if(tag != ""){
		$.ajax(
			{
				url: 'newtag',
				type: 'POST',
				data: {tag : ""+tag},
				success: function (data, textStatus){
					var result = data.result;
					if(result == 1)
						document.getElementById('message').innerHTML = "Successfully add tag:" + tag;
					else if(result == -1)
						document.getElementById('message').innerHTML = "tag: " + tag + " already exits";
					else if(result == -2)
						document.getElementById('message').innerHTML = "tag: " + tag + " isn't a good tag";
				}
			}
		);
	}
	
}

$(window).load(function () {
	 $( "#interest_tag" ).autocomplete({
	     source: 'gethint',
	     minLength: 2
	 });
});


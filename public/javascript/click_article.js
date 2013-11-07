function see_article(id){
	//alert(id);
	$.ajax(
			{
				url: 'recommend/clickarticle',
				type: 'POST',
				data: {id : id},
				dataType: 'json',
				success: function (data, textStatus){
					//alert(data.result);
				}
			}
	);
}
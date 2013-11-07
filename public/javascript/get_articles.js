var articles = new Array();

$(window).load(function () {
	//setTimeout("get_articles()",1000*60);
});

function Article(id,title,link,description,relevance,related_tag){
	this.id = id;
	this.title = title;
	this.link = link;
	this.description = description;
	this.relevance = relevance;
	this.related_tag = related_tag;
}

function get_articles(){
	$.ajax(
			{
				url: 'recommend',
				type: 'GET',
				dataType: 'json',
				success: function (data, textStatus){
//					document.getElementById('message').innerHTML = "Successfully add tag:" + tag;
					var num = data.article_num;
					articles = [];
					if(num > 0){
						var new_articles = data.articles;
						for(var index in new_articles){
							var article = new_articles[index];
							articles.push(new Article(article.id,article.title,article.link,article.description,article.relevance,article.related_tag));
						}
						
						$('#inform_new_articles').show();
						document.getElementById("inform_new_articles").innerHTML = "<a href='javascript:show_articles()'>"+articles.length+" new articles</a>";
					}
					
//					var show = "";
//					for(var index in articles)
//						show += articles[index].title + "\n";
//					alert("articles:\n"+show);
				}
			}
	);
	setTimeout("get_articles()",1000 * 60 );
}

function show_articles(){
	$('#inform_new_articles').hide();
	var area = document.getElementById("show_articles_area");
	if(document.getElementById("no_articles"))
		area.removeChild(document.getElementById("no_articles"));
	
	for(var index in articles){
		var p = document.createElement("p");
		p.setAttribute("id", ""+articles[index].id);
		p.setAttribute("class", "article");
		p.innerHTML = "<a href='" + articles[index].link + "' onclick='window.open(this.href);see_article("+articles[index].id+");return false;'>" 
		               + articles[index].title + "</a>"
		               + "(<span style='color:#FF5050'>" + articles[index].related_tag +":" + articles[index].relevance +"</span>)"
		               +"<br>"
		               + articles[index].description;
//		area.appendChild(p);
		$('#show_articles_area').prepend(p);
	}
}




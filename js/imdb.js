function imdbsearch (title)
{
	if (title=="") {
		window.alert("Error! You must enter a movie title to search on");
	} else {
		var win = window.open("http://us.imdb.com/Tsearch?title=" + title, "IMDb", "height=500,width=800,resizable=0,scrollbars=1,menubar=0,toolbar=0,location=0,directories=0,status=0,left=100,top=100");
	}
}

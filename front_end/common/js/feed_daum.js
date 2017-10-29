$.jGFeed('http://media.daum.net/rss/today/primary/all/rss2.xml',
function(feeds){
  // Check for errors
  if(!feeds){
	// there was an error
	return false;
  }

  //KangMin
  //shuffle
  feeds.entries = $.shuffle(feeds.entries);

  // do whatever you want with feeds here
  result = '';
  for(var i=0; i<3; i++){
	var entry = feeds.entries[i];
	// Entry title
	//entry.title;
	$('#feed_daum_'+(i+1)).attr('href', entry.link);
	$('#feed_daum_'+(i+1)).html(entry.title);
  }
}, 10);
//10개를 가져와서 섞은후 3개만 보여주기위해
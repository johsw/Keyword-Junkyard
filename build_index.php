<?php
  /*
    CREATE TABLE `wordstats` (
      `word` varchar(255) NOT NULL,
      `count` bigint(20) unsigned NOT NULL,
      PRIMARY KEY (`word`)
    )
  */
  $start = time();
  
  $result = mysql_query('TRUNCATE TABLE `wordstats`;');
  
  $link = mysql_connect('localhost', 'root', 'root');

   if (!$link) {
       die('Could not connect: ' . mysql_error());
   }
   mysql_select_db('information');
    mysql_set_charset ('utf8', $link);
   mb_internal_encoding("UTF-8");
   
    $query = '
      SELECT nr.title, nr.body, cfu.field_underrubrik_value  
      FROM node AS n
      JOIN node_revisions AS nr ON nr.vid = n.vid
      JOIN content_field_underrubrik AS cfu ON cfu.vid = n.vid 
      WHERE n.type = "avisartikel"
      ORDER BY created DESC
      LIMIT 0, 1000;';
   
   $result = mysql_query($query);
   $doc_count = 0;
   $word_count = 0;
   while ($row = mysql_fetch_object($result)) {

   
      $text = strip_tags($row->title.' '.$row->field_underrubrik_value. ' '.$row->body);
   
       $words = mb_split("([\s\?,\":\.«»'\(\)\!])", trim(mb_strtolower($text)), -1);
       $frequency = array_count_values($words);
       $stopwords = array('af', 'alle', 'andet', 'andre', 'at', 'begge', 'blev', 'blevet', 'blive', 'bliver', 'da', 'de', 'dem', 'den', 'denne', 'der', 'deres', 'derfor', 'det', 'dette', 'dig', 'din', 'dog', 'du', 'efter', 'ej', 'eller', 'en', 'end', 'ene', 'eneste', 'enhver', 'er', 'et', 'fem', 'fire', 'flere', 'fleste', 'for', 'fordi', 'forrige', 'fra', 'få', 'får', 'før', 'god', 'gøre', 'gå', 'går', 'ham', 'han', 'hans', 'har', 'havde', 'have', 'hele', 'hendes', 'helt', 'her', 'hun', 'hvad', 'hvem', 'hver', 'hvilken', 'hvis', 'hvor', 'hvordan', 'hvorfor', 'hvornår', 'i', 'ifølge', 'ikke', 'ind', 'ingen', 'intet', 'jeg', 'jeres', 'jo', 'kan', 'kom', 'kommer', 'kun', 'kunne', 'lav', 'lidt', 'lige', 'lille', 'man', 'mand', 'mange', 'med', 'meget', 'mellem', 'men', 'mener', 'mens', 'mere', 'mig', 'mod', 'må', 'måske', 'ned', 'ni', 'nogen', 'noget', 'nogle', 'nok', 'nu', 'ny', 'nyt', 'nær', 'næste', 'næsten', 'når', 'og', 'også', 'om', 'op', 'os', 'otte', 'over', 'på', 'se', 'seks', 'selv', 'ses', 'siden', 'sin', 'sig', 'siger', 'skal', 'skulle', 'som', 'stor', 'store', 'syv', 'så', 'ti', 'til', 'to', 'tre', 'ud', 'uden', 'under', 'var', 'ved', 'vi', 'vil', 'ville', 'vores', 'være', 'været');


       $words_without_stopwords = array_diff($words, $stopwords);
       
  
       foreach($frequency AS $key => $value){
         if(!preg_match('/[[:alpha:]]/', $key) || in_array($key, $stopwords)){
           unset($frequency[$key]); 
          }
    
       }
       arsort($frequency);

       foreach($frequency AS $key => $value){
          $sql = 'INSERT INTO wordstats SET word = "'.$key.'", count = '.$value.'
                                 on duplicate key update count=count+'.$value;
          mysql_query($sql);
                             
       }
       $doc_count++;
       $word_count += count($words_without_stopwords);
    
   }
   $end = time();
   
   $time = $end - $start;
   $sql = 'UPDATE wordstats SET doc_freq = (count/'.$doc_count.'), word_freq = (count/'.$word_count.');';

   mysql_query($sql);
   print 'Total documents: '. $doc_count. '<br />';
   print 'Total words: '. $word_count. '<br />';
   print 'Total time: '. $time .' secs. ('. $doc_count/$time .' documents per sec. )';
  
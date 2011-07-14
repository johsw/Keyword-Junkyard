<?php
  /*
    CREATE TABLE `wordstats` (
      `word` varchar(255) NOT NULL,
      `count` bigint(20) unsigned NOT NULL,
      PRIMARY KEY (`word`)
    )
  */
  if(!isset($_GET['artid'])){
    print "no id!"; exit;
  }

  $link = mysql_connect('localhost', 'root', 'root');

   if (!$link) {
       die('Could not connect: ' . mysql_error());
   }
   mysql_select_db('information');
    mysql_set_charset ('utf8', $link);
   mb_internal_encoding("UTF-8");
   $result = mysql_query('TRUNCATE TABLE `temp_wordstats`;');
   
    $query = '
      SELECT nr.title, nr.body, cfu.field_underrubrik_value  
      FROM node AS n
      JOIN node_revisions AS nr ON nr.vid = n.vid
      JOIN content_field_underrubrik AS cfu ON cfu.vid = n.vid 
      WHERE n.type = "avisartikel" AND n.nid = '. $_GET['artid'] .'
      LIMIT 0, 1;';

   $result = mysql_query($query);
   while ($row = mysql_fetch_object($result)) {

   
      $text = strip_tags($row->title.' '.$row->field_underrubrik_value. ' '.$row->body);
   
       $words = mb_split("([\s\?,\":\.«»'\(\)\!])", trim(mb_strtolower($text)), -1);
       $frequency = array_count_values($words);
       $stopwords = array('af', 'alle', 'andet', 'andre', 'at', 'begge', 'blev', 'blevet', 'blive', 'bliver', 'da', 'de', 'dem', 'den', 'denne', 'der', 'deres', 'derfor', 'det', 'dette', 'dig', 'din', 'dog', 'du', 'efter', 'ej', 'eller', 'en', 'end', 'ene', 'eneste', 'enhver', 'er', 'et', 'fem', 'fire', 'flere', 'fleste', 'for', 'fordi', 'forrige', 'fra', 'få', 'får', 'før', 'god', 'gøre', 'gå', 'går', 'ham', 'han', 'hans', 'har', 'havde', 'have', 'hele', 'hendes', 'helt', 'her', 'hun', 'hvad', 'hvem', 'hver', 'hvilken', 'hvis', 'hvor', 'hvordan', 'hvorfor', 'hvornår', 'i', 'ifølge', 'ikke', 'ind', 'ingen', 'intet', 'jeg', 'jeres', 'jo', 'kan', 'kom', 'kommer', 'kun', 'kunne', 'lav', 'lidt', 'lige', 'lille', 'man', 'mand', 'mange', 'med', 'meget', 'mellem', 'men', 'mener', 'mens', 'mere', 'mig', 'mod', 'må', 'måske', 'ned', 'ni', 'nogen', 'noget', 'nogle', 'nok', 'nu', 'ny', 'nyt', 'nær', 'næste', 'næsten', 'når', 'og', 'også', 'om', 'op', 'os', 'otte', 'over', 'på', 'se', 'seks', 'selv', 'ses', 'siden', 'sin', 'sig', 'siger', 'skal', 'skulle', 'som', 'stor', 'store', 'syv', 'så', 'ti', 'til', 'to', 'tre', 'ud', 'uden', 'under', 'var', 'ved', 'vi', 'vil', 'ville', 'vores', 'være', 'været');


  
       foreach($frequency AS $key => $value){
         if(!preg_match('/[[:alpha:]]/', $key) || in_array($key, $stopwords)){
           unset($frequency[$key]); 
          }
    
       }
       arsort($frequency);
       
         $words_without_stopwords = array_diff($words, $stopwords);
         $word_count = count($words_without_stopwords);
  
       foreach($frequency AS $key => $value){
         $insert_sql = 'INSERT INTO temp_wordstats SET word = "'.$key.'", count = '.$value.', doc_freq = (count/1), word_freq = (count/'.$word_count.')';
          mysql_query($insert_sql);                    
         
       }

        $comp_sql = '
          SELECT w.word, (tw.word_freq - w.word_freq) AS diff
          FROM wordstats AS w
          JOIN temp_wordstats AS tw ON w.word_freq < tw.word_freq AND tw.word = w.word
          ORDER BY diff DESC';
         $comp_result = mysql_query($comp_sql);
         while($row = mysql_fetch_object($comp_result)) {
           print '<b>'. utf8_decode($row->word). '</b> ('. $row->diff .') <br />';
         }
      print '<br /><br />------------------<br /><br />';
      print utf8_decode($text);
   }  
?>
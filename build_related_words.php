<?php

  $start = time();
  
  //$result = mysql_query('TRUNCATE TABLE `wordstats`;');
  
  $link = mysql_connect('localhost', 'root', 'root');

   if (!$link) {
       die('Could not connect: ' . mysql_error());
   }
   mysql_select_db('information');
   mysql_set_charset ('utf8', $link);
   mb_internal_encoding("UTF-8");
   
    $query = '
      SELECT tid, name FROM term_data WHERE vid = 16
    ';
   
   $result = mysql_query($query);
   $supercounter = 0;
   while ($row = mysql_fetch_object($result)) {
      
      print_r($row);
      $articles_sql = "SELECT n.nid, nr.title, nr.body, cfu.field_underrubrik_value  FROM `node` AS n
      JOIN `node_revisions` AS nr ON n.vid = nr.vid
       JOIN content_field_underrubrik AS cfu ON cfu.vid = n.vid 
      WHERE n.type IN ('avisartikel', 'ritzau_telegram') AND nr.title LIKE '%". $row->name ."%'";
      
    $articles_result = mysql_query($articles_sql);
    $doc_count = 0;
    $word_count = 0;
    $overall_frequency = array();
    $doc_frequency = array();
       while ($articles_row = mysql_fetch_object($articles_result)) {
   
                  $text = strip_tags($articles_row->title.' '.$articles_row->field_underrubrik_value. ' '.$articles_row->body);
   
                   $words = mb_split("([\s\?,\":\.«»'\(\)\!])", trim(mb_strtolower($text)), -1);
                   $frequency = array_count_values($words);
                   $stopwords = array('af', 'alle', 'andet', 'andre', 'at', 'begge', 'blev', 'blevet', 'blive', 'bliver', 'da', 'de', 'dem', 'den', 'denne', 'der', 'deres', 'derfor', 'det', 'dette', 'dig', 'din', 'dog', 'du', 'efter', 'ej', 'eller', 'en', 'end', 'ene', 'eneste', 'enhver', 'er', 'et', 'fem', 'fire', 'flere', 'fleste', 'for', 'fordi', 'forrige', 'fra', 'få', 'får', 'før', 'god', 'gøre', 'gå', 'går', 'ham', 'han', 'hans', 'har', 'havde', 'have', 'hele', 'hendes', 'helt', 'her', 'hun', 'hvad', 'hvem', 'hver', 'hvilken', 'hvis', 'hvor', 'hvordan', 'hvorfor', 'hvornår', 'i', 'ifølge', 'ikke', 'ind', 'ingen', 'intet', 'jeg', 'jeres', 'jo', 'kan', 'kom', 'kommer', 'kun', 'kunne', 'lav', 'lidt', 'lige', 'lille', 'man', 'mand', 'mange', 'med', 'meget', 'mellem', 'men', 'mener', 'mens', 'mere', 'mig', 'mod', 'må', 'måske', 'ned', 'ni', 'nogen', 'noget', 'nogle', 'nok', 'nu', 'ny', 'nyt', 'nær', 'næste', 'næsten', 'når', 'og', 'også', 'om', 'op', 'os', 'otte', 'over', 'på', 'se', 'seks', 'selv', 'ses', 'siden', 'sin', 'sig', 'siger', 'skal', 'skulle', 'som', 'stor', 'store', 'syv', 'så', 'ti', 'til', 'to', 'tre', 'ud', 'uden', 'under', 'var', 'ved', 'vi', 'vil', 'ville', 'vores', 'være', 'været');


                   $words_without_stopwords = array_diff($words, $stopwords);
       
  
                   foreach($frequency AS $key => $value){
                     if(!preg_match('/[[:alpha:]]/', $key) || in_array($key, $stopwords)){
                       unset($frequency[$key]); 
                      } else {
                        if(isset($overall_frequency[$key])) {
                          $overall_frequency[$key] += $value;
                          $doc_frequency[$key]++;
                        } else {
                          $overall_frequency[$key] = $value;
                          $doc_frequency[$key] = 1;
                        }
                      } 
                   }
      
                   
                 
                   $doc_count++;
                   $word_count += count($words_without_stopwords);
              }
              arsort($overall_frequency);
              $comp = array();
              foreach($overall_frequency AS $word => $count) {
                $sql_compare = 'SELECT word, ('.($count / $word_count).')-word_freq AS diff FROM wordstats WHERE word = "'.$word.'" AND word_freq < '.($count / $word_count).';';
                //print $sql_compare. ' <br />';
                $compare_result = mysql_query($sql_compare);
                
                if($compare_result) {
                  $compare_row = mysql_fetch_object($compare_result);
                  if($compare_row && isset($doc_frequency[$compare_row->word]) && $doc_frequency[$compare_row->word] > 1) {
                    $comp[$compare_row->word] = $compare_row->diff;
                  
                  }
                  //$comp[$compare_row->word] = $compare_row->diff;
                }
              }
              //arsort($comp);
              print '<pre>'.utf8_decode(print_r($comp,1)). '</pre>';
             $supercounter++;
             if($supercounter > 50){ exit; }
   }
   
   $end = time();
   
   $time = $end - $start;
  /* $sql = 'UPDATE wordstats SET doc_freq = (count/'.$doc_count.'), word_freq = (count/'.$word_count.');';

   mysql_query($sql);
   print 'Total documents: '. $doc_count. '<br />';
   print 'Total words: '. $word_count. '<br />';
   print 'Total time: '. $time .' secs. ('. $doc_count/$time .' documents per sec. )';*/
  
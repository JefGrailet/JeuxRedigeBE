<?php

class ReviewFirstReactionIR
{
   /*
   * Converts the array modelizing a review into a string which corresponds to the first message 
   * that should be displayed in a topic containing reactions to such a piece of content.
   *
   * @param mixed $review[]  The array modelizing the review
   * @return string          The first message of the topic containing reactions for this content
   */

   public static function process($review)
   {
      $truncatedText = strip_tags($review['comment']);
      
      // Also removes any post-parsing tag
      $videos = array();
      preg_match_all("/\!video\[([_a-zA-Z0-9\.\\/;:\?\=\-]*?)\]/", $truncatedText, $videos);
      for($i = 0; $i < count($videos[1]); $i++)
         $truncatedText = str_replace($videos[0][$i], '', $truncatedText);
      
      $images = array();
      preg_match_all("/\!img\[([_a-zA-Z0-9\.\\/;:\-]*?)\]/", $truncatedText, $images);
      for($i = 0; $i < count($images[1]); $i++)
         $truncatedText = str_replace($images[0][$i], '', $truncatedText);
      
      $clips = array();
      preg_match_all("/\!clip\[([_a-zA-Z0-9\.\\/;:\-]*?)\]/", $truncatedText, $clips);
      for($i = 0; $i < count($clips[1]); $i++)
         $truncatedText = str_replace($clips[0][$i], '', $truncatedText);
      
      $miniatures = array();
      $accents = "áàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ";
      preg_match_all("/\!mini\[([_a-zA-Z0-9\.\\/;:\-]*?)\](\[([a-zA-Z0-9 ".$accents."\.\,:;'\?\=\-]*)\])?/", $truncatedText, $miniatures);
      for($i = 0; $i < count($miniatures[1]); $i++)
         $truncatedText = str_replace($miniatures[0][$i], '', $truncatedText);
      
      $usersPretty = array();
      preg_match_all("/\!user\[([a-zA-Z0-9_-]{3,20})\]/", $truncatedText, $usersPretty);
      for($i = 0; $i < count($usersPretty[1]); $i++)
         $truncatedText = str_replace($usersPretty[0][$i], '', $truncatedText);
      
      // Final truncated text
      $txtLength = 1500;
      if(strlen($truncatedText) > 1500)
      {
         $pos = strrpos(substr($truncatedText, 0, 1500), ' ');
         if($pos !== FALSE)
            $truncatedText = substr($truncatedText, 0, $pos)."...";
      }
      
      $output = '[g]'.$review['game'].': '.$review['title'].'[/g]'."\n";
      $output .= "\n";
      $output .= $truncatedText."\n";
      $output .= "\n";
      $output .= "[url=".PathHandler::reviewURL($review)."]Consulter l'évaluation en entier[/url]";
      
      return $output;
   }
}

?>

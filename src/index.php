<?php

/**
* Home page.
*/

require './libraries/Header.lib.php';
require './model/Article.class.php';
require './model/Topic.class.php';
require './view/intermediate/ArticleThumbnail.ir.php';
require './view/intermediate/TopicThumbnail.ir.php';

WebpageHandler::redirectionAtLoggingIn();

// Webpage settings
WebpageHandler::addCSS('pool');
WebpageHandler::noContainer();

// Gets the last featured articles and active topics
$articles = null;
$topics = null;
$favorited = null;
try
{
   $articles = Article::getFeaturedArticles(5);
   $topics = Topic::getTopics(0, 12);
   if(LoggedUser::isLoggedIn() && $topics != NULL)
      Topic::getUserViews($topics);
}
catch(Exception $e)
{
   $errorTplInput = array('error' => 'dbError');
   $tpl = TemplateEngine::parse('view/content/Index.fail.ctpl', $errorTplInput);
   WebpageHandler::wrap($tpl, 'Impossible d\'atteindre le contenu');
}

if($articles == NULL && $topics == NULL)
{
   $errorTplInput = array('error' => 'noContent');
   $tpl = TemplateEngine::parse('view/content/Index.fail.ctpl', $errorTplInput);
   WebpageHandler::wrap($tpl, 'Impossible d\'atteindre le contenu');
}

/* From this point, all the content has been extracted from the DB. All what is left to do is
* to render both article and topic thumbnails. */

$fullInput = array();
for($i = 0; $i < count($articles); $i++)
{
   $intermediate = ArticleThumbnailIR::process($articles[$i]);
   array_push($fullInput, $intermediate);
}

// Sorts articles depending on whether they have a highlight or not
$fullInputLarge = array();
$fullInputSmall = array();
$maxHighlight = 2;
for($i = 0; $i < count($fullInput); $i++)
{
   $highlight = Article::getHighlightStatic($fullInput[$i]['ID']);
   if(strlen($highlight) > 0 && count($fullInputLarge) < $maxHighlight)
   {
      $fullInput[$i]['highlight'] = $highlight;
      array_push($fullInputLarge, $fullInput[$i]);
   }
   else
      array_push($fullInputSmall, $fullInput[$i]);
}

// Rendered article thumbnails
$articleThumbnails = '';
if(count($fullInputLarge) > 0)
{
   $fullOutputLarge = TemplateEngine::parseMultiple('view/content/ArticleHighlight.ctpl', $fullInputLarge);
   if(TemplateEngine::hasFailed($fullOutputLarge))
   {
      $errorTplInput = array('error' => 'wrongTemplating');
      $tpl = TemplateEngine::parse('view/content/Index.fail.ctpl', $errorTplInput);
      WebpageHandler::wrap($tpl, 'Impossible d\'atteindre le contenu');
   }

   for($i = 0; $i < count($fullOutputLarge); $i++)
      $articleThumbnails .= $fullOutputLarge[$i];
}

if(count($fullInputSmall) > 0)
{
   $fullOutputSmall = TemplateEngine::parseMultiple('view/content/ArticleThumbnail.ctpl', $fullInputSmall);
   if(TemplateEngine::hasFailed($fullOutputSmall))
   {
      $errorTplInput = array('error' => 'wrongTemplating');
      $tpl = TemplateEngine::parse('view/content/Index.fail.ctpl', $errorTplInput);
      WebpageHandler::wrap($tpl, 'Impossible d\'atteindre le contenu');
   }

   for($i = 0; $i < count($fullOutputSmall); $i++)
      $articleThumbnails .= $fullOutputSmall[$i];
}

// Rendered topic thumbnails
$topicThumbnails = '';
$fullInput = array();
for($i = 0; $i < count($topics); $i++)
{
   $intermediate = TopicThumbnailIR::process($topics[$i]);
   array_push($fullInput, $intermediate);
}

if(count($fullInput) > 0)
{
   $fullOutput = TemplateEngine::parseMultiple('view/content/TopicThumbnail.ctpl', $fullInput);
   if(TemplateEngine::hasFailed($fullOutput))
   {
      $errorTplInput = array('error' => 'wrongTemplating');
      $tpl = TemplateEngine::parse('view/content/Index.fail.ctpl', $errorTplInput);
      WebpageHandler::wrap($tpl, 'Impossible d\'atteindre le contenu');
   }

   for($i = 0; $i < count($fullOutput); $i++)
      $topicThumbnails .= $fullOutput[$i];
}

// Final HTML code (with page configuration)
$finalTplInput = array('articles' => '', 'thumbnails' => '');
if(strlen($articleThumbnails) > 0)
   $finalTplInput['articles'] = 'yes||'.$articleThumbnails;
if(strlen($topicThumbnails) > 0)
   $finalTplInput['thumbnails'] = 'yes||'.$topicThumbnails;

$content = TemplateEngine::parse('view/content/Index.ctpl', $finalTplInput);

// Displays the produced page
WebpageHandler::wrap($content, '');

?>

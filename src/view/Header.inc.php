<?php

/**
* Header of a page; contains all the HTML that models the design and includes basic JavaScript
* features. The PHP indentation may look odd, but it guarantees that the displayed page remains
* coherent regarding HTML when inspecting the code.
*/

if(!isset($pageTitle) || strlen($pageTitle) == 0)
{
   $pageTitle = 'Project AG';
}
else
{
   $pageTitle .= ' - Project AG';
}

$webRootPath = PathHandler::HTTP_PATH();

?>
<!DOCTYPE html>
<html lang="fr">
   <head>
      <meta charset="UTF-8" />
      <link rel="stylesheet" href="<?php echo $webRootPath; ?>style/default.css" />
      <link rel="stylesheet" href="<?php echo $webRootPath; ?>style/icons.css" />
      <script text="text/javascript">
      var ConfigurationValues = {};
      ConfigurationValues.HTTP_PATH = '<?php echo $webRootPath; ?>';
      </script>
      <script type="text/javascript" src="<?php echo $webRootPath; ?>javascript/jquery-3.2.1.min.js"></script>
      <script type="text/javascript" src="<?php echo $webRootPath; ?>javascript/default<? echo PathHandler::JS_EXTENSION(); ?>"></script>
<?php

// After main CSS/JS files, the particular ones
for($i = 0; $i < count(WebpageHandler::$CSSFiles); $i++)
{
   echo '      <link rel="stylesheet" href="'.$webRootPath.'style/'.WebpageHandler::$CSSFiles[$i].'.css" />'."\n";
}
for($i = 0; $i < count(WebpageHandler::$JSFiles); $i++)
{
   echo '      <script type="text/javascript" src="'.$webRootPath.'javascript/'.WebpageHandler::$JSFiles[$i].PathHandler::JS_EXTENSION().'"></script>'."\n";
}

// Auto-activated JS features (navigation mode, auto preview and auto refresh)
$cond1 = in_array('pages', WebpageHandler::$JSFiles) && WebpageHandler::$miscParams['default_nav_mode'] !== 'classic';
$cond2 = in_array('preview', WebpageHandler::$JSFiles) || in_array('quick_preview', WebpageHandler::$JSFiles);
$cond2 = $cond2 || in_array('segment_editor', WebpageHandler::$JSFiles);
$cond2 = $cond2 || in_array('content_editor', WebpageHandler::$JSFiles);
$cond2 = $cond2 && Utils::check(WebpageHandler::$miscParams['auto_preview']);
$cond3 = in_array('refresh', WebpageHandler::$JSFiles) && Utils::check(WebpageHandler::$miscParams['auto_refresh']);
if($cond1 || $cond2 || $cond3)
{
   echo '      <script type="text/javascript">'."\n";
   echo '      $(document).ready(function() {'."\n";
   
   // Default navigation mode
   if($cond1)
   {
      switch(WebpageHandler::$miscParams['default_nav_mode'])
      {
         case 'dynamic':
            echo '         if (typeof PagesLib !== \'undefined\') { PagesLib.switchNavMode(2); }'."\n";
            break;
         case 'flow':
            echo '         if (typeof PagesLib !== \'undefined\') { PagesLib.switchNavMode(3); }'."\n";
            break;
         default:
            break;
      }
   }
   
   // Automatic (or quick) preview
   if($cond2)
   {
      echo '         if (typeof PreviewLib !== \'undefined\') { PreviewLib.previewMode(); }'."\n";
      echo '         else if (typeof QuickPreviewLib !== \'undefined\') { QuickPreviewLib.enable(); }'."\n";
      echo '         else if (typeof SegmentEditorLib !== \'undefined\') { SegmentEditorLib.previewMode(); }'."\n";
      echo '         else if (typeof ContentEditorLib !== \'undefined\') { ContentEditorLib.previewMode(); }'."\n";
   }
   
   /*
   * Remark for auto preview: previewMode() is defined in both preview.js and 
   * quick_preview.js, and as these files are mutually exclusive (never invoked at the same 
   * time), we do not need to check here which kind of preview is activated. The existing 
   * previewMode() always matches the current type of preview.
   */
   
   // Automatic refresh
   if($cond3)
      echo '         if (typeof RefreshLib !== \'undefined\') { RefreshLib.changeAutoRefresh(); }'."\n";
   
   echo '      });'."\n";
   echo '      </script>'."\n";
}

// Meta-tags (it's assumed all meta_ fields are filled if the title is not empty)
if(strlen(WebpageHandler::$miscParams['meta_title']) > 0)
{
   echo '      <meta property="og:title" content="'.WebpageHandler::$miscParams['meta_title'].'">'."\n";
   echo '      <meta property="og:description" content="'.WebpageHandler::$miscParams['meta_description'].'">'."\n";
   echo '      <meta property="og:image" content="'.WebpageHandler::$miscParams['meta_image'].'">'."\n";
   echo '      <meta property="og:url" content="'.WebpageHandler::$miscParams['meta_url'].'">'."\n";
   
   echo '      <meta property="og:site_name" content="Project AG">'."\n";
   echo '      <meta name="twitter:image:alt" content="Vignette">'."\n";
}

// Page title
echo '      <title>'.$pageTitle.'</title>'."\n";
?>
   </head>
 
   <body>
      <div id="blackScreen"></div>
      <div id="bubble"></div>
      
<?php
if(!LoggedUser::isLoggedIn())
{
?>
      <div id="connection" class="window" style="display:none;"> 
         <div class="windowTop">
            <span class="windowTitle"><strong>Connexion</strong></span> 
            <span class="closeDialog">Fermer</span>
         </div>
         <div class="windowContent">
            <form method="post" action="<?php echo $webRootPath; ?>LogIn.php">
            <table class="windowFields">
               <tr>
                  <td class="connectionColumn1">Pseudo:</td>
                  <td class="connectionColumn2"><input type="text" name="pseudo"/></td>
                  <td class="connectionColumn3"><a href="<?php echo $webRootPath; ?>Registration.php">M'inscrire</a></td>
               </tr>
               <tr>
                  <td class="connectionColumn1">Mot de passe:</td>
                  <td class="connectionColumn2"><input type="password" name="pwd"/></td>
                  <td class="connectionColumn3"><a href="<?php echo $webRootPath; ?>PasswordReset.php">Mot de passe perdu ?</a></td>
               </tr>
            </table>
            <p>
<?php
   if(WebpageHandler::$redirections['log_in'])
   {
      echo '               <input type="hidden" name="redirection" value="'.$webRootPath.$_SERVER['REQUEST_URI'].'"/>';
   }
   else
   {
       echo '               <input type="hidden" name="redirection" value=""/>';
   }
?>
               <input type="checkbox" name="rememberMe"/> Se souvenir de moi 
               <input type="submit" name="sent" value="Connexion"/>
            </p>
            </form>
         </div>
      </div>
<?php
}

// Other dialog boxes, if provided by the calling code.
if(isset($dialogs) && !empty($dialogs))
{
   echo $dialogs;
}

// Finally, lightbox for pictures.
?>
      <div id="lightbox" style="display: none;" data-cur-file="none">
         <div class="lightboxContent"></div>
         <div class="lightboxBottom">
            <div class="LBLeft"></div>
            <div class="LBCenter"></div>
            <div class="LBRight"></div>
         </div>
      </div>
      <div id="topMenu">
         <div class="websiteMainMenu">
            <p>
               <a class="websiteTitle" href="<?php echo $webRootPath; ?>">Project AG</a><sup>Beta</sup> &nbsp;
               <a href="<?php echo $webRootPath; ?>Articles.php">Articles</a> | 
               <a href="<?php echo $webRootPath; ?>Forum.php">Forum</a>
            </p>
         </div>
         <?php
if(LoggedUser::isLoggedIn())
{
   $padding = '            ';
   echo '<ul id="showUserMenu">'."\n";
   echo $padding.'<li>';
   
   /*
    * Handles function account of a logged user (i.e., the link to switch between accounts) and 
    * "My account" page.
    */
   
   $alternateAccount = '';
   $pseudoPart = '<img src="'.PathHandler::getAvatarSmall(LoggedUser::$data['used_pseudo']).'" class="avatarMini" alt="Avatar mini"/> ';
   $adminTools = false;
   if(strlen(LoggedUser::$data['function_pseudo']) > 0 && LoggedUser::$data['function_name'] !== 'alumnus')
   {
      if(LoggedUser::$data['function_pseudo'] === LoggedUser::$data['used_pseudo'])
      {
         if(LoggedUser::$data['function_name'] === 'administrator')
         {
            $pseudoPart .= '<span style="color: rgb(255,63,63);">'.LoggedUser::$data['function_pseudo'].'</span>';
         }
         else
         {
            $pseudoPart .= LoggedUser::$data['pseudo'];
         }
         $r = str_replace('&', 'amp;', "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
         $alternateAccount = '<a href="'.$webRootPath.'SwitchAccount.php?pos='.$r.'">Changer pour '.LoggedUser::$data['pseudo'].'</a>';
         $adminTools = true; // Always, for now
      }
      else
      {
         $pseudoPart .= LoggedUser::$data['pseudo'];
         if(LoggedUser::$data['function_name'] === 'administrator')
         {
            $r = str_replace('&', 'amp;', "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $alternateAccount = '<a href="'.$webRootPath.'SwitchAccount.php?pos='.$r.'">Changer pour <span style="color: rgb(255,63,63);">'.LoggedUser::$data['function_pseudo'].'</span></a>';
         }
      }
   }
   else
   {
      $pseudoPart .= LoggedUser::$data['pseudo'];
   }
   
   // Display with dropdown menu
   echo $padding.$pseudoPart.''."\n";
   echo $padding.'<ul id="userMenu">'."\n";
   if(strlen($alternateAccount) > 0)
   {
      echo $padding.'   '.'<li><i class="icon-menu_switch"></i> '.$alternateAccount.'</li>'."\n";
   }
   echo $padding.'   '.'<li><i class="icon-general_edit"></i> <a href="'.$webRootPath.'MyAccount.php">Mon compte</a></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_smilies"></i> <a href="'.$webRootPath.'MyEmoticons.php">Mes émoticônes</a></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-general_pin"></i> <a href="'.$webRootPath.'MyPins.php">Mes messages favoris</a></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_articles"></i> <a href="'.$webRootPath.'MyArticles.php">Mes articles</a></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_lists"></i> <a href="'.$webRootPath.'MyLists.php">Mes listes</a><sup>Beta</sup></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_games"></i> <a href="'.$webRootPath.'Games.php">Jeux</a><sup>Beta</sup></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_tropes"></i> <a href="'.$webRootPath.'Tropes.php">Codes ludiques</a><sup>Beta</sup></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_didyouknow"></i> <a href="'.$webRootPath.'RandomTrivia.php">Le saviez-vous ?</a><sup>Beta</sup></li>'."\n";
   echo $padding.'   '.'<li><i class="icon-menu_invite"></i> <a href="'.$webRootPath.'Sponsorship.php">Inviter un ami</a></li>'."\n";
   if($adminTools)
   {
      echo $padding.'   '.'<li><i class="icon-menu_users"></i> <a href="'.$webRootPath.'Users.php">Utilisateurs</a></li>'."\n";
      echo $padding.'   '.'<li><i class="icon-general_alert"></i> <a href="'.$webRootPath.'Alerts.php">Alertes</a></li>'."\n";
   }
   // Log out link
   if(WebpageHandler::$redirections['log_out'])
   {
      $r = str_replace('&', 'amp;', "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
      echo $padding.'   '.'<li><i class="icon-menu_logout"></i> <a href="'.$webRootPath.'LogOut.php?redirection='.$r.'">Déconnexion</a></li>'."\n";
   }
   else
   {
      echo $padding.'   '.'<li><i class="icon-menu_logout"></i> <a href="'.$webRootPath.'LogOut.php">Déconnexion</a></li>'."\n";
   }
   echo $padding.'</ul></li>'."\n";
   echo '         </ul>'."\n";
   
   // Private messages
   echo '         <ul id="showPings">'."\n";
   if(LoggedUser::$data['new_pings'] > 0)
   {
      echo $padding.'<li><i class="icon-general_messages" style="color: #4bd568;" ></i>'."\n";
      echo $padding.'<ul id="pings">'."\n";
      for($i = 0; $i < LoggedUser::$data['new_pings'] && $i < 5; $i++)
      {
         echo $padding.'<li>';
         switch(LoggedUser::$messages[$i]['ping_type'])
         {
            case 'notification':
               echo '<i class="icon-general_alert" style="color: #e04f5f;"></i> ';
               echo LoggedUser::$messages[$i]['title'];
               break;
            
            case 'ping pong':
               $otherParty = LoggedUser::$messages[$i]['emitter'];
               if($otherParty === LoggedUser::$data['pseudo'])
                  $otherParty = LoggedUser::$messages[$i]['receiver'];
               echo '<i class="icon-general_messages" style="color: #25b6d2;"></i> ';
               echo '<a href="'.$webRootPath.'PrivateDiscussion.php?id_ping='.LoggedUser::$messages[$i]['id_ping'].'"><strong>'.$otherParty.' -</strong> '.LoggedUser::$messages[$i]['title'].'</a>';
               break;
            
            // Unknown
            default:
               echo '<i class="icon-general_alert" style="color: #f2b851;"></i> ';
               echo LoggedUser::$messages[$i]['title'];
               break;
         }
         echo '</li>'."\n";
      }
      if(LoggedUser::$data['new_pings'] > 5)
         echo $padding.'<li><i class="icon-menu_lists" style="color: #25b6d2;"></i> <a href="'.$webRootPath.'Pings.php">Liste de mes pings ('.LoggedUser::$data['new_pings'].' nouveaux)</a></li>'."\n";
      else
         echo $padding.'<li><i class="icon-menu_lists" style="color: #25b6d2;"></i> <a href="'.$webRootPath.'Pings.php">Liste de mes pings</a></li>'."\n";
      echo $padding.'</ul></li>'."\n";
   }
   else if(LoggedUser::$data['new_pings'] == 0)
   {
      echo $padding.'<li><i class="icon-general_messages" style="color: #25b6d2;"></i>'."\n";
      echo $padding.'<ul id="pings">'."\n";
      echo $padding.'<li><i class="icon-menu_lists" style="color: #25b6d2;"></i> <a href="'.$webRootPath.'Pings.php">Liste de mes pings</a></li>'."\n";
      echo $padding.'</ul></li>'."\n";
   }
   else
   {
      echo $padding.'<li><i class="icon-general_messages" style="color: #f2b851;"></i>'."\n";
      echo $padding.'<ul id="pings">'."\n";
      echo $padding.'<li>Une erreur est survenue...</li>'."\n";
      echo $padding.'<li><i class="icon-menu_lists" style="color: #25b6d2;"></i> <a href="'.$webRootPath.'Pings.php">Liste de mes pings</a></li>'."\n";
      echo $padding.'</ul></li>'."\n";
   }
   echo '         </ul>'."\n";
}
else
{
?>
         <ul>
            <li><img src="<?php echo $webRootPath; ?>defaultavatar-small.jpg" class="avatarMini" alt="Avatar mini"/> <a class="connectionLink">Se connecter</a></li>
         </ul>
<?php
}
?>
         <div class="mirroredTitle"></div>
      </div>
      <div id="main">
<?php 
echo WebpageHandler::$container['start'];
?>
